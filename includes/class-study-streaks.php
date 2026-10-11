<?php
/**
 * Study Streaks and Progress Goals service: tracks verified learning activity,
 * calculates consecutive daily streaks, manages weekly practice goals, and generates activity history.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IAQ_Study_Streaks {

	/**
	 * Record a verified learning event for a student.
	 *
	 * Only completed quizzes, mock tests, or custom drills are recorded.
	 * Page visits alone are strictly excluded.
	 *
	 * @param int    $user_id WordPress User ID.
	 * @param string $activity_type Event identifier (e.g. 'quiz_completed').
	 * @param int    $reference_id Attempt ID or Quiz ID.
	 * @param int    $questions_solved Number of questions answered.
	 * @param int    $time_spent_seconds Duration in seconds.
	 * @return int|bool Row ID on success or false.
	 */
	public static function record_activity( $user_id, $activity_type = 'quiz_completed', $reference_id = 0, $questions_solved = 0, $time_spent_seconds = 0 ) {
		global $wpdb;

		$user_id      = absint( $user_id );
		$reference_id = absint( $reference_id );

		if ( $user_id <= 0 ) {
			return false;
		}

		// Use site's configured local date
		$today_date = date_i18n( 'Y-m-d' );
		$table_name = IAQ_Database::get_table_name( 'study_activity' );

		// Check if exact same reference event was already recorded for this user today
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE user_id = %d AND activity_date = %s AND reference_id = %d",
				$user_id,
				$today_date,
				$reference_id
			)
		);

		if ( $existing ) {
			// Already recorded, update metrics if needed
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table_name} 
					 SET questions_solved = GREATEST(questions_solved, %d),
					     time_spent_seconds = GREATEST(time_spent_seconds, %d)
					 WHERE id = %d",
					absint( $questions_solved ),
					absint( $time_spent_seconds ),
					(int) $existing
				)
			);
			return (int) $existing;
		}

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'user_id'            => $user_id,
				'activity_date'      => $today_date,
				'activity_type'      => sanitize_text_field( $activity_type ),
				'reference_id'       => $reference_id,
				'questions_solved'   => absint( $questions_solved ),
				'time_spent_seconds' => absint( $time_spent_seconds ),
				'created_at'         => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%d', '%d', '%s' )
		);

		if ( ! $inserted ) {
			return false;
		}

		// Update best streak user meta cache
		self::update_best_streak_cache( $user_id );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Calculate current consecutive study days streak for a user.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return int Consecutive active days.
	 */
	public static function get_current_streak( $user_id ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return 0;
		}

		$table_name = IAQ_Database::get_table_name( 'study_activity' );
		$dates = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT activity_date FROM {$table_name} WHERE user_id = %d ORDER BY activity_date DESC",
				$user_id
			)
		);

		if ( empty( $dates ) ) {
			return 0;
		}

		$today     = date_i18n( 'Y-m-d' );
		$yesterday = date_i18n( 'Y-m-d', strtotime( '-1 day', current_time( 'timestamp' ) ) );

		$latest_date = $dates[0];

		// If user hasn't studied today or yesterday, streak is reset to 0
		if ( $latest_date !== $today && $latest_date !== $yesterday ) {
			return 0;
		}

		$date_set = array_flip( $dates );
		$streak   = 0;

		// Start counting backwards from latest active date (or today if active today)
		$start_ts = ( $latest_date === $today ) ? current_time( 'timestamp' ) : strtotime( '-1 day', current_time( 'timestamp' ) );

		for ( $i = 0; $i < 365; $i++ ) {
			$check_date = date_i18n( 'Y-m-d', strtotime( "-{$i} days", $start_ts ) );
			if ( isset( $date_set[ $check_date ] ) ) {
				$streak++;
			} else {
				break;
			}
		}

		return $streak;
	}

	/**
	 * Retrieve all-time best study streak for a user.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return int
	 */
	public static function get_best_streak( $user_id ) {
		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return 0;
		}

		$cached = (int) get_user_meta( $user_id, 'iaq_best_study_streak', true );
		$current = self::get_current_streak( $user_id );

		if ( $current > $cached ) {
			update_user_meta( $user_id, 'iaq_best_study_streak', $current );
			return $current;
		}

		return max( $cached, $current );
	}

	/**
	 * Recalculate and update the best streak cache for a student.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return int
	 */
	public static function update_best_streak_cache( $user_id ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return 0;
		}

		$table_name = IAQ_Database::get_table_name( 'study_activity' );
		$dates = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT activity_date FROM {$table_name} WHERE user_id = %d ORDER BY activity_date ASC",
				$user_id
			)
		);

		if ( empty( $dates ) ) {
			return 0;
		}

		$max_streak     = 1;
		$current_streak = 1;

		for ( $i = 1; $i < count( $dates ); $i++ ) {
			$prev = strtotime( $dates[ $i - 1 ] );
			$curr = strtotime( $dates[ $i ] );
			$diff = round( ( $curr - $prev ) / 86400 );

			if ( 1 === (int) $diff ) {
				$current_streak++;
				if ( $current_streak > $max_streak ) {
					$max_streak = $current_streak;
				}
			} elseif ( $diff > 1 ) {
				$current_streak = 1;
			}
		}

		$existing_best = (int) get_user_meta( $user_id, 'iaq_best_study_streak', true );
		$new_best      = max( $existing_best, $max_streak );
		update_user_meta( $user_id, 'iaq_best_study_streak', $new_best );

		return $new_best;
	}

	/**
	 * Retrieve activity dates history for visual activity calendar (e.g. past 14 or 30 days).
	 *
	 * @param int $user_id WordPress User ID.
	 * @param int $days Number of past days to inspect.
	 * @return array Array of days with status and questions solved.
	 */
	public static function get_activity_calendar( $user_id, $days = 14 ) {
		global $wpdb;

		$user_id = absint( $user_id );
		$days    = max( 7, min( 60, absint( $days ) ) );

		$table_name = IAQ_Database::get_table_name( 'study_activity' );
		$start_date = date_i18n( 'Y-m-d', strtotime( "-{$days} days", current_time( 'timestamp' ) ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT activity_date, SUM(questions_solved) as total_qs, COUNT(id) as total_events
				 FROM {$table_name}
				 WHERE user_id = %d AND activity_date >= %s
				 GROUP BY activity_date",
				$user_id,
				$start_date
			)
		);

		$active_map = array();
		if ( $rows ) {
			foreach ( $rows as $r ) {
				$active_map[ $r->activity_date ] = array(
					'questions' => (int) $r->total_qs,
					'events'    => (int) $r->total_events,
				);
			}
		}

		$calendar = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$d = date_i18n( 'Y-m-d', strtotime( "-{$i} days", current_time( 'timestamp' ) ) );
			$day_name = date_i18n( 'D', strtotime( $d ) );
			$is_active = isset( $active_map[ $d ] );

			$calendar[] = array(
				'date'             => $d,
				'day'              => $day_name,
				'is_active'        => $is_active,
				'questions_solved' => $is_active ? $active_map[ $d ]['questions'] : 0,
				'events_count'     => $is_active ? $active_map[ $d ]['events'] : 0,
			);
		}

		return $calendar;
	}

	/**
	 * Retrieve a student's personal study goals and current progress.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return array
	 */
	public static function get_user_goals( $user_id ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return array();
		}

		$target_weekly_tests = (int) get_user_meta( $user_id, 'iaq_goal_weekly_tests', true );
		if ( $target_weekly_tests <= 0 ) {
			$target_weekly_tests = (int) get_option( 'iaq_default_goal_weekly_tests', 5 );
		}

		$target_weekly_qs = (int) get_user_meta( $user_id, 'iaq_goal_weekly_questions', true );
		if ( $target_weekly_qs <= 0 ) {
			$target_weekly_qs = (int) get_option( 'iaq_default_goal_weekly_questions', 50 );
		}

		// Calculate current week activity (from Monday of current week)
		$now            = current_time( 'timestamp' );
		$week_start_str = date_i18n( 'Y-m-d', strtotime( 'monday this week', $now ) );

		$table_name = IAQ_Database::get_table_name( 'study_activity' );
		$week_stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT reference_id) as tests_completed,
				        SUM(questions_solved) as questions_completed
				 FROM {$table_name}
				 WHERE user_id = %d AND activity_date >= %s",
				$user_id,
				$week_start_str
			)
		);

		$current_tests = $week_stats ? (int) $week_stats->tests_completed : 0;
		$current_qs    = $week_stats ? (int) $week_stats->questions_completed : 0;

		$tests_progress_pct = min( 100, round( ( $current_tests / max( 1, $target_weekly_tests ) ) * 100 ) );
		$qs_progress_pct    = min( 100, round( ( $current_qs / max( 1, $target_weekly_qs ) ) * 100 ) );

		return array(
			'target_weekly_tests'     => $target_weekly_tests,
			'current_weekly_tests'    => $current_tests,
			'tests_progress_percent'  => $tests_progress_pct,
			'target_weekly_questions' => $target_weekly_qs,
			'current_weekly_questions'=> $current_qs,
			'qs_progress_percent'     => $qs_progress_pct,
		);
	}

	/**
	 * Update or reset personal study goals.
	 *
	 * @param int $user_id WordPress User ID.
	 * @param int $weekly_tests Weekly test target.
	 * @param int $weekly_questions Weekly question target.
	 * @return bool
	 */
	public static function update_user_goals( $user_id, $weekly_tests, $weekly_questions ) {
		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return false;
		}

		$weekly_tests     = max( 1, min( 50, absint( $weekly_tests ) ) );
		$weekly_questions = max( 10, min( 1000, absint( $weekly_questions ) ) );

		update_user_meta( $user_id, 'iaq_goal_weekly_tests', $weekly_tests );
		update_user_meta( $user_id, 'iaq_goal_weekly_questions', $weekly_questions );

		return true;
	}
}
