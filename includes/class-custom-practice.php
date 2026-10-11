<?php
/**
 * Custom Practice Test Builder service: validates eligibility, enforces subscription checks,
 * queries available question pools, and generates personalized practice quizzes.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IAQ_Custom_Practice {

	/**
	 * Verify if a student can build a custom test for an examination.
	 *
	 * @param int    $user_id WordPress User ID.
	 * @param string $exam_category Examination code or identifier.
	 * @return array Array with 'allowed' (bool) and 'reason' (string).
	 */
	public static function check_custom_test_access( $user_id, $exam_category ) {
		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return array(
				'allowed' => false,
				'reason'  => __( 'You must be logged in to create a custom practice test.', 'indianagri-quiz-engine' ),
			);
		}

		if ( user_can( $user_id, 'manage_options' ) ) {
			return array(
				'allowed' => true,
				'reason'  => __( 'Administrator bypass.', 'indianagri-quiz-engine' ),
			);
		}

		// Check if examination itself is free.
		$exam = IAQ_Examinations::get_examination_by_code( $exam_category );
		if ( $exam && ! empty( $exam->is_free ) ) {
			return array(
				'allowed' => true,
				'reason'  => __( 'Free examination.', 'indianagri-quiz-engine' ),
			);
		}

		// Check if mock quiz representation is free or check user subscriptions.
		$active_subs = IAQ_Access_Control::get_user_active_subscriptions( $user_id );
		if ( empty( $active_subs ) ) {
			return array(
				'allowed' => false,
				'reason'  => __( 'This examination category is premium. An active subscription is required to build custom tests.', 'indianagri-quiz-engine' ),
			);
		}

		foreach ( $active_subs as $sub ) {
			if ( 'all_access' === $sub->access_type_snapshot ) {
				return array(
					'allowed' => true,
					'reason'  => sprintf( __( 'Covered by %s', 'indianagri-quiz-engine' ), $sub->plan_name_snapshot ),
				);
			}

			if ( 'exam_specific' === $sub->access_type_snapshot && ! empty( $sub->included_exam_ids_snapshot ) ) {
				$allowed_exams = array_map( 'trim', explode( ',', $sub->included_exam_ids_snapshot ) );
				if ( in_array( $exam_category, $allowed_exams, true ) ) {
					return array(
						'allowed' => true,
						'reason'  => sprintf( __( 'Covered by %s', 'indianagri-quiz-engine' ), $sub->plan_name_snapshot ),
					);
				}
			}
		}

		return array(
			'allowed' => false,
			'reason'  => __( 'Your active subscription does not cover this examination category.', 'indianagri-quiz-engine' ),
		);
	}

	/**
	 * Count available eligible published questions matching criteria.
	 *
	 * @param array $params Query criteria.
	 * @return int
	 */
	public static function get_available_pool_count( $params = array() ) {
		global $wpdb;

		$table_questions = IAQ_Database::get_table_name( 'questions' );
		$where  = array( "status = 'published'" );
		$values = array();

		if ( ! empty( $params['exam_category'] ) ) {
			$where[]  = 'exam_category = %s';
			$values[] = sanitize_text_field( $params['exam_category'] );
		}

		if ( ! empty( $params['subject_id'] ) ) {
			$where[]  = 'subject_id = %d';
			$values[] = absint( $params['subject_id'] );
		}

		if ( ! empty( $params['topic_id'] ) ) {
			$where[]  = 'topic_id = %d';
			$values[] = absint( $params['topic_id'] );
		}

		if ( ! empty( $params['difficulty'] ) && 'all' !== $params['difficulty'] ) {
			$where[]  = 'difficulty = %s';
			$values[] = sanitize_text_field( $params['difficulty'] );
		}

		$where_clause = implode( ' AND ', $where );
		$sql = "SELECT COUNT(id) FROM {$table_questions} WHERE {$where_clause}";

		if ( ! empty( $values ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $values ) );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Generate a custom practice quiz.
	 *
	 * @param int   $user_id WordPress User ID.
	 * @param array $args Custom test configuration parameters.
	 * @return int|WP_Error Generated Quiz ID on success.
	 */
	public static function generate_custom_test( $user_id, $args = array() ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return new WP_Error( 'unauthorized', __( 'You must be logged in to create a custom practice test.', 'indianagri-quiz-engine' ) );
		}

		$defaults = array(
			'exam_category' => '',
			'subject_id'    => 0,
			'topic_id'      => 0,
			'num_questions' => 20,
			'difficulty'    => 'all', // 'all', 'easy', 'medium', 'hard'
			'time_limit'    => 0,     // minutes, 0 = untimed
			'mode'          => 'random', // 'random', 'latest'
		);
		$r = wp_parse_args( $args, $defaults );

		$exam_category = sanitize_text_field( $r['exam_category'] );
		$subject_id    = absint( $r['subject_id'] );
		$topic_id      = absint( $r['topic_id'] );
		$requested_qty = max( 1, min( 100, absint( $r['num_questions'] ) ) );
		$difficulty    = in_array( $r['difficulty'], array( 'all', 'easy', 'medium', 'hard' ), true ) ? $r['difficulty'] : 'all';
		$time_limit    = max( 0, min( 180, absint( $r['time_limit'] ) ) );
		$mode          = in_array( $r['mode'], array( 'random', 'latest' ), true ) ? $r['mode'] : 'random';

		// 1. Enforce Subscription / Entitlement Access Check
		if ( ! empty( $exam_category ) ) {
			$access = self::check_custom_test_access( $user_id, $exam_category );
			if ( ! $access['allowed'] ) {
				return new WP_Error( 'access_denied', $access['reason'] );
			}
		}

		// 2. Validate Question Pool
		$table_questions = IAQ_Database::get_table_name( 'questions' );
		$where  = array( "status = 'published'" );
		$values = array();

		if ( ! empty( $exam_category ) ) {
			$where[]  = 'exam_category = %s';
			$values[] = $exam_category;
		}

		if ( ! empty( $subject_id ) ) {
			$where[]  = 'subject_id = %d';
			$values[] = $subject_id;
		}

		if ( ! empty( $topic_id ) ) {
			$where[]  = 'topic_id = %d';
			$values[] = $topic_id;
		}

		if ( 'all' !== $difficulty ) {
			$where[]  = 'difficulty = %s';
			$values[] = $difficulty;
		}

		$where_clause = implode( ' AND ', $where );
		$order_clause = ( 'latest' === $mode ) ? 'ORDER BY id DESC' : 'ORDER BY RAND()';

		$sql = "SELECT id, marks, negative_marks FROM {$table_questions} WHERE {$where_clause} {$order_clause} LIMIT %d";
		$values[] = $requested_qty;

		$pool = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );

		if ( empty( $pool ) ) {
			return new WP_Error(
				'no_questions',
				__( 'No eligible published questions found matching your selected criteria. Try adjusting the subject, topic, or difficulty.', 'indianagri-quiz-engine' )
			);
		}

		if ( count( $pool ) < $requested_qty ) {
			return new WP_Error(
				'insufficient_questions',
				sprintf(
					__( 'Insufficient questions available. Requested %d but only %d found in the pool.', 'indianagri-quiz-engine' ),
					$requested_qty,
					count( $pool )
				)
			);
		}

		$actual_count = count( $pool );

		// 3. Construct Title and Details
		$exam_label = ! empty( $exam_category ) ? $exam_category : __( 'General Agriculture', 'indianagri-quiz-engine' );
		$subj_name  = '';
		if ( $subject_id > 0 ) {
			$table_subj = IAQ_Database::get_table_name( 'subjects' );
			$subj_row   = $wpdb->get_row( $wpdb->prepare( "SELECT name FROM {$table_subj} WHERE id = %d", $subject_id ) );
			if ( $subj_row ) {
				$subj_name = $subj_row->name;
			}
		}

		$title_parts = array( __( 'Custom Practice', 'indianagri-quiz-engine' ) );
		if ( $subj_name ) {
			$title_parts[] = $subj_name;
		} else {
			$title_parts[] = $exam_label;
		}
		$title_parts[] = sprintf( '(%d Qs)', $actual_count );

		$title = implode( ': ', array_slice( $title_parts, 0, 2 ) );
		if ( isset( $title_parts[2] ) ) {
			$title .= ' ' . $title_parts[2];
		}

		$slug = 'custom-practice-' . $user_id . '-' . time();

		// Calculate total marks based on pooled questions.
		$total_marks = 0.00;
		foreach ( $pool as $q ) {
			$total_marks += (float) ( $q->marks > 0 ? $q->marks : 1.00 );
		}

		$table_quizzes = IAQ_Database::get_table_name( 'quizzes' );
		$quiz_data = array(
			'title'             => $title,
			'slug'              => $slug,
			'description'       => sprintf(
				__( 'Custom practice test tailored to %s. Questions: %d, Mode: %s, Difficulty: %s.', 'indianagri-quiz-engine' ),
				$subj_name ? $subj_name : $exam_label,
				$actual_count,
				ucfirst( $mode ),
				ucfirst( $difficulty )
			),
			'quiz_type'         => 'practice',
			'exam_category'     => ! empty( $exam_category ) ? $exam_category : 'Custom Practice',
			'total_questions'   => $actual_count,
			'total_marks'       => $total_marks,
			'duration_minutes'  => $time_limit,
			'correct_marks'     => 1.00,
			'negative_marks'    => 0.25,
			'pass_percentage'   => 40.00,
			'shuffle_questions' => 1,
			'shuffle_options'   => 1,
			'access_type'       => 'free',
			'show_answers_mode' => 'after_submission',
			'is_custom'         => 1,
			'status'            => 'published',
			'created_by'        => $user_id,
			'created_at'        => current_time( 'mysql' ),
		);

		$wpdb->insert( $table_quizzes, $quiz_data );
		$quiz_id = (int) $wpdb->insert_id;

		if ( ! $quiz_id ) {
			return new WP_Error( 'db_error', __( 'Failed to create custom practice quiz record.', 'indianagri-quiz-engine' ) );
		}

		// 4. Attach Question IDs
		$table_qq = IAQ_Database::get_table_name( 'quiz_questions' );
		$order    = 1;
		foreach ( $pool as $q ) {
			$wpdb->insert(
				$table_qq,
				array(
					'quiz_id'        => $quiz_id,
					'question_id'    => (int) $q->id,
					'question_order' => $order++,
				),
				array( '%d', '%d', '%d' )
			);
		}

		return $quiz_id;
	}

	/**
	 * Alias for generate_custom_test for API consistency.
	 *
	 * @param int   $user_id WordPress User ID.
	 * @param array $args Custom test configuration parameters.
	 * @return int|WP_Error Generated Quiz ID on success.
	 */
	public static function generate_custom_quiz( $user_id, $args = array() ) {
		return self::generate_custom_test( $user_id, $args );
	}
}
