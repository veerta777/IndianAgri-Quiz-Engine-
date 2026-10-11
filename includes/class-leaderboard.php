<?php
/**
 * Leaderboard service: calculates rankings from verified completed quiz attempts,
 * enforces privacy and pseudonyms, handles ties, scopes, and pagination.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IAQ_Leaderboard {

	/**
	 * Check if leaderboard feature is globally enabled by admin.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) get_option( 'iaq_leaderboard_enabled', 1 );
	}

	/**
	 * Retrieve ranked leaderboard entries with deterministic tie-breaking and privacy masking.
	 *
	 * @param array $args Query scope and pagination arguments.
	 * @return array Array containing 'entries', 'total', 'page', 'pages', 'current_user_rank'.
	 */
	public static function get_leaderboard( $args = array() ) {
		global $wpdb;

		if ( ! self::is_enabled() ) {
			return array(
				'enabled'           => false,
				'entries'           => array(),
				'total'             => 0,
				'current_user_rank' => null,
			);
		}

		$defaults = array(
			'scope'         => 'quiz',       // 'quiz', 'exam', 'overall'
			'quiz_id'       => 0,
			'exam_category' => '',
			'period'        => 'all_time',   // 'all_time', 'monthly', 'weekly'
			'limit'         => 10,
			'page'          => 1,
			'current_user'  => get_current_user_id(),
		);
		$r = wp_parse_args( $args, $defaults );

		$limit  = max( 1, min( 50, absint( $r['limit'] ) ) );
		$page   = max( 1, absint( $r['page'] ) );
		$offset = ( $page - 1 ) * $limit;

		$table_attempts = IAQ_Database::get_table_name( 'quiz_attempts' );
		$table_quizzes  = IAQ_Database::get_table_name( 'quizzes' );

		$where  = array( "a.status = 'completed'" );
		$params = array();

		// Scope filtering
		if ( 'quiz' === $r['scope'] && ! empty( $r['quiz_id'] ) ) {
			$where[]  = 'a.quiz_id = %d';
			$params[] = absint( $r['quiz_id'] );
		} elseif ( 'exam' === $r['scope'] && ! empty( $r['exam_category'] ) ) {
			$where[]  = 'q.exam_category = %s';
			$params[] = sanitize_text_field( $r['exam_category'] );
		}

		// Period filtering
		$now = current_time( 'timestamp' );
		if ( 'monthly' === $r['period'] ) {
			$month_start = date( 'Y-m-01 00:00:00', $now );
			$where[]     = 'a.end_time >= %s';
			$params[]    = $month_start;
		} elseif ( 'weekly' === $r['period'] ) {
			$week_start = date( 'Y-m-d 00:00:00', strtotime( '-7 days', $now ) );
			$where[]    = 'a.end_time >= %s';
			$params[]   = $week_start;
		}

		$where_clause = implode( ' AND ', $where );

		// We group by user_id to select the best attempt per user.
		// Ranking priority: highest score DESC, highest percentage DESC, shortest time_spent_seconds ASC, earliest attempt ASC.
		$sql = "SELECT a.user_id,
		               MAX(a.score) as max_score,
		               MAX(a.percentage) as max_percentage,
		               MIN(a.time_spent_seconds) as min_time,
		               MAX(a.end_time) as latest_attempt_time,
		               COUNT(a.id) as total_attempts,
		               q.title as quiz_title,
		               q.exam_category
		        FROM {$table_attempts} a
		        JOIN {$table_quizzes} q ON a.quiz_id = q.id
		        WHERE {$where_clause}
		        GROUP BY a.user_id
		        ORDER BY max_score DESC, max_percentage DESC, min_time ASC, latest_attempt_time ASC";

		if ( ! empty( $params ) ) {
			$all_ranked = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		} else {
			$all_ranked = $wpdb->get_results( $sql );
		}

		if ( empty( $all_ranked ) ) {
			return array(
				'enabled'           => true,
				'entries'           => array(),
				'total'             => 0,
				'page'              => $page,
				'pages'             => 1,
				'current_user_rank' => null,
			);
		}

		// Filter out students who opted out of public leaderboard
		$eligible_ranked = array();
		$rank_counter    = 1;
		$current_user_rank = null;

		foreach ( $all_ranked as $row ) {
			$uid = (int) $row->user_id;

			// Check privacy opt-out meta
			$is_opted_out = (bool) get_user_meta( $uid, 'iaq_exclude_from_leaderboard', true );
			if ( $is_opted_out && $uid !== (int) $r['current_user'] ) {
				continue;
			}

			$entry = array(
				'rank'               => $rank_counter,
				'user_id'            => $uid,
				'display_name'       => self::get_privacy_display_name( $uid ),
				'score'              => (float) $row->max_score,
				'percentage'         => (float) $row->max_percentage,
				'time_spent_seconds' => (int) $row->min_time,
				'total_attempts'     => (int) $row->total_attempts,
				'is_current_user'    => ( $uid === (int) $r['current_user'] ),
			);

			if ( $uid === (int) $r['current_user'] ) {
				$current_user_rank = $entry;
			}

			$eligible_ranked[] = $entry;
			$rank_counter++;
		}

		$total = count( $eligible_ranked );
		$pages = ceil( $total / $limit );
		$paged_entries = array_slice( $eligible_ranked, $offset, $limit );

		return array(
			'enabled'           => true,
			'entries'           => $paged_entries,
			'total'             => $total,
			'page'              => $page,
			'pages'             => $pages,
			'current_user_rank' => $current_user_rank,
		);
	}

	/**
	 * Get privacy-safe pseudonym or display name for a user.
	 *
	 * Never exposes email, phone, or billing details.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return string
	 */
	public static function get_privacy_display_name( $user_id ) {
		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return __( 'Anonymous Candidate', 'indianagri-quiz-engine' );
		}

		$name_type = get_option( 'iaq_leaderboard_name_type', 'pseudonym' ); // 'display_name', 'first_name', 'pseudonym'

		$user = function_exists( 'get_userdata' ) ? get_userdata( $user_id ) : null;
		if ( ! $user ) {
			return sprintf( __( 'Candidate #%d', 'indianagri-quiz-engine' ), $user_id );
		}

		if ( 'display_name' === $name_type ) {
			// Strip emails if user display name was accidentally set to email
			$display = $user->display_name;
			if ( is_email( $display ) ) {
				$parts   = explode( '@', $display );
				return sanitize_text_field( $parts[0] );
			}
			return sanitize_text_field( $display );
		}

		if ( 'first_name' === $name_type ) {
			$first = $user->first_name;
			$last  = $user->last_name ? ' ' . mb_substr( $user->last_name, 0, 1 ) . '.' : '';
			if ( ! empty( $first ) ) {
				return sanitize_text_field( $first . $last );
			}
		}

		// Default: Elegant agricultural scholar pseudonym
		return sprintf( __( 'AgriScholar #%04d', 'indianagri-quiz-engine' ), $user_id );
	}
}
