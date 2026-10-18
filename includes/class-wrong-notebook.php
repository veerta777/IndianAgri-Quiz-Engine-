<?php
/**
 * Wrong Answer Notebook management service: automatically logs incorrect attempts,
 * allows revision status updates, filters, and generates retry practice drills.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IAQ_Wrong_Notebook {

	/**
	 * Record or increment an incorrect question answer for a user.
	 *
	 * @param int $user_id WordPress User ID.
	 * @param int $question_id Question ID.
	 * @param int $attempt_id Attempt ID where the error occurred.
	 * @return int|WP_Error Notebook row ID on success.
	 */
	public static function record_incorrect_answer( $user_id, $question_id, $attempt_id = 0 ) {
		global $wpdb;

		$user_id     = absint( $user_id );
		$question_id = absint( $question_id );
		$attempt_id  = absint( $attempt_id );

		if ( $user_id <= 0 || $question_id <= 0 ) {
			return new WP_Error( 'invalid_params', __( 'Invalid student or question identifier.', 'indianagri-quiz-engine' ) );
		}

		$table_notebook = IAQ_Database::get_table_name( 'wrong_notebook' );

		// Check if record already exists for this user and question.
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, times_incorrect, last_attempt_id FROM {$table_notebook} WHERE user_id = %d AND question_id = %d",
				$user_id,
				$question_id
			)
		);

		if ( $existing ) {
			$increment = ( $attempt_id > 0 && isset( $existing->last_attempt_id ) && (int) $existing->last_attempt_id === $attempt_id ) ? 0 : 1;
			$wpdb->update(
				$table_notebook,
				array(
					'last_attempt_id' => $attempt_id,
					'times_incorrect' => (int) $existing->times_incorrect + $increment,
					'status'          => 'active', // Reactivate on repeat mistake
					'updated_at'      => current_time( 'mysql' ),
				),
				array( 'id' => (int) $existing->id ),
				array( '%d', '%d', '%s', '%s' ),
				array( '%d' )
			);
			return (int) $existing->id;
		}

		$inserted = $wpdb->insert(
			$table_notebook,
			array(
				'user_id'         => $user_id,
				'question_id'     => $question_id,
				'last_attempt_id' => $attempt_id,
				'times_incorrect' => 1,
				'status'          => 'active',
				'notes'           => '',
				'created_at'      => current_time( 'mysql' ),
				'updated_at'      => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return new WP_Error( 'db_error', __( 'Failed to record incorrect question.', 'indianagri-quiz-engine' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Ingest all incorrect answers from a completed quiz attempt.
	 *
	 * @param int $attempt_id Attempt ID.
	 * @return int Number of incorrect questions recorded.
	 */
	public static function ingest_attempt_wrong_answers( $attempt_id ) {
		global $wpdb;

		$attempt_id = absint( $attempt_id );
		if ( $attempt_id <= 0 ) {
			return 0;
		}

		$table_attempts = IAQ_Database::get_table_name( 'quiz_attempts' );
		$attempt        = $wpdb->get_row( $wpdb->prepare( "SELECT user_id, status FROM {$table_attempts} WHERE id = %d", $attempt_id ) );

		if ( ! $attempt || 'completed' !== $attempt->status ) {
			return 0;
		}

		$user_id = (int) $attempt->user_id;
		if ( $user_id <= 0 ) {
			return 0;
		}

		$table_answers = IAQ_Database::get_table_name( 'attempt_answers' );

		// Query answered questions marked incorrect (is_correct = 0 and selected_option IS NOT NULL).
		// Exclude unattempted questions from wrong answer notebook unless explicitly marked.
		$wrong_answers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT question_id FROM {$table_answers}
				 WHERE attempt_id = %d AND is_correct = 0 AND selected_option IS NOT NULL AND selected_option != ''",
				$attempt_id
			)
		);

		if ( empty( $wrong_answers ) ) {
			return 0;
		}

		$recorded = 0;
		foreach ( $wrong_answers as $ans ) {
			$res = self::record_incorrect_answer( $user_id, (int) $ans->question_id, $attempt_id );
			if ( ! is_wp_error( $res ) ) {
				$recorded++;
			}
		}

		return $recorded;
	}

	/**
	 * Update notebook item status (active, resolved, dismissed).
	 *
	 * @param int    $user_id WordPress User ID (ownership check).
	 * @param int    $question_id Question ID.
	 * @param string $status New status ('active', 'resolved', 'dismissed').
	 * @return bool
	 */
	public static function update_status( $user_id, $question_id, $status = 'resolved' ) {
		global $wpdb;

		$user_id     = absint( $user_id );
		$question_id = absint( $question_id );
		$status      = in_array( $status, array( 'active', 'resolved', 'dismissed' ), true ) ? $status : 'resolved';

		if ( $user_id <= 0 || $question_id <= 0 ) {
			return false;
		}

		$table_notebook = IAQ_Database::get_table_name( 'wrong_notebook' );
		$updated = $wpdb->update(
			$table_notebook,
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql' ),
			),
			array(
				'user_id'     => $user_id,
				'question_id' => $question_id,
			),
			array( '%s', '%s' ),
			array( '%d', '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Remove question from user's notebook without affecting historical attempt records.
	 *
	 * @param int $user_id WordPress User ID.
	 * @param int $question_id Question ID.
	 * @return bool
	 */
	public static function remove_entry( $user_id, $question_id ) {
		global $wpdb;

		$user_id     = absint( $user_id );
		$question_id = absint( $question_id );

		if ( $user_id <= 0 || $question_id <= 0 ) {
			return false;
		}

		$table_notebook = IAQ_Database::get_table_name( 'wrong_notebook' );
		$deleted = $wpdb->delete(
			$table_notebook,
			array(
				'user_id'     => $user_id,
				'question_id' => $question_id,
			),
			array( '%d', '%d' )
		);

		return (bool) $deleted;
	}

	/**
	 * Get notebook entries for a student with filters.
	 *
	 * @param int   $user_id WordPress User ID.
	 * @param array $args Query filters.
	 * @return array
	 */
	public static function get_user_notebook( $user_id, $args = array() ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return array();
		}

		$defaults = array(
			'status'        => 'active', // 'active', 'resolved', 'all'
			'subject_id'    => 0,
			'topic_id'      => 0,
			'exam_category' => '',
			'limit'         => 20,
			'offset'        => 0,
		);
		$r = wp_parse_args( $args, $defaults );

		$table_notebook = IAQ_Database::get_table_name( 'wrong_notebook' );
		$table_questions = IAQ_Database::get_table_name( 'questions' );
		$table_subjects  = IAQ_Database::get_table_name( 'subjects' );
		$table_topics    = IAQ_Database::get_table_name( 'topics' );
		$table_attempts  = IAQ_Database::get_table_name( 'quiz_attempts' );
		$table_answers   = IAQ_Database::get_table_name( 'attempt_answers' );

		$where  = array( 'n.user_id = %d' );
		$params = array( $user_id );

		if ( ! empty( $r['status'] ) && 'all' !== $r['status'] ) {
			$where[]  = 'n.status = %s';
			$params[] = sanitize_text_field( $r['status'] );
		}

		if ( ! empty( $r['subject_id'] ) ) {
			$where[]  = 'q.subject_id = %d';
			$params[] = absint( $r['subject_id'] );
		}

		if ( ! empty( $r['topic_id'] ) ) {
			$where[]  = 'q.topic_id = %d';
			$params[] = absint( $r['topic_id'] );
		}

		if ( ! empty( $r['exam_category'] ) ) {
			$where[]  = 'q.exam_category = %s';
			$params[] = sanitize_text_field( $r['exam_category'] );
		}

		$where_clause = implode( ' AND ', $where );
		$limit_clause = sprintf( 'LIMIT %d OFFSET %d', absint( $r['limit'] ), absint( $r['offset'] ) );

		$sql = "SELECT n.id as notebook_id, n.user_id, n.question_id, n.last_attempt_id,
		               n.times_incorrect, n.status as notebook_status, n.notes as student_notes,
		               n.updated_at as last_failed_at,
		               q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option,
		               q.explanation, q.marks, q.negative_marks, q.difficulty, q.exam_category,
		               q.subject_id, q.topic_id,
		               s.name as subject_name,
		               t.name as topic_name,
		               ans.selected_option as last_selected_option
		        FROM {$table_notebook} n
		        JOIN {$table_questions} q ON n.question_id = q.id
		        LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
		        LEFT JOIN {$table_topics} t ON q.topic_id = t.id
		        LEFT JOIN {$table_answers} ans ON (n.last_attempt_id = ans.attempt_id AND n.question_id = ans.question_id)
		        WHERE {$where_clause}
		        ORDER BY n.updated_at DESC
		        {$limit_clause}";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		return $results ? $results : array();
	}

	/**
	 * Get total count of notebook items matching filters.
	 *
	 * @param int   $user_id WordPress User ID.
	 * @param array $args Query filters.
	 * @return int
	 */
	public static function get_notebook_count( $user_id, $args = array() ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return 0;
		}

		$table_notebook  = IAQ_Database::get_table_name( 'wrong_notebook' );
		$table_questions = IAQ_Database::get_table_name( 'questions' );

		$where  = array( 'n.user_id = %d' );
		$params = array( $user_id );

		if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
			$where[]  = 'n.status = %s';
			$params[] = sanitize_text_field( $args['status'] );
		}

		if ( ! empty( $args['subject_id'] ) ) {
			$where[]  = 'q.subject_id = %d';
			$params[] = absint( $args['subject_id'] );
		}

		if ( ! empty( $args['topic_id'] ) ) {
			$where[]  = 'q.topic_id = %d';
			$params[] = absint( $args['topic_id'] );
		}

		if ( ! empty( $args['exam_category'] ) ) {
			$where[]  = 'q.exam_category = %s';
			$params[] = sanitize_text_field( $args['exam_category'] );
		}

		$where_clause = implode( ' AND ', $where );
		$sql = "SELECT COUNT(n.id)
		        FROM {$table_notebook} n
		        JOIN {$table_questions} q ON n.question_id = q.id
		        WHERE {$where_clause}";

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Create an on-demand retry practice quiz from questions in the student's Wrong Answer Notebook.
	 *
	 * @param int   $user_id WordPress User ID.
	 * @param array $args Filter args or question_ids.
	 * @return int|WP_Error Quiz ID on success.
	 */
	public static function create_retry_quiz( $user_id, $args = array() ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return new WP_Error( 'unauthorized', __( 'Authentication required.', 'indianagri-quiz-engine' ) );
		}

		$table_notebook  = IAQ_Database::get_table_name( 'wrong_notebook' );
		$table_questions = IAQ_Database::get_table_name( 'questions' );

		// Query active incorrect question IDs belonging to this user.
		if ( ! empty( $args['subject_id'] ) ) {
			$sql = "SELECT n.question_id FROM {$table_notebook} n
			        JOIN {$table_questions} q ON n.question_id = q.id
			        WHERE n.user_id = %d AND n.status = 'active' AND q.subject_id = %d
			        ORDER BY n.times_incorrect DESC, n.updated_at DESC";
			$question_ids = $wpdb->get_col( $wpdb->prepare( $sql, $user_id, absint( $args['subject_id'] ) ) );
		} else {
			$sql = "SELECT n.question_id FROM {$table_notebook} n
			        JOIN {$table_questions} q ON n.question_id = q.id
			        WHERE n.user_id = %d AND n.status = 'active'
			        ORDER BY n.times_incorrect DESC, n.updated_at DESC";
			$question_ids = $wpdb->get_col( $wpdb->prepare( $sql, $user_id ) );
		}

		$question_ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $question_ids ) ) ) );

		if ( empty( $question_ids ) ) {
			// Fallback: If no 'active' questions, check if any non-dismissed entries exist for retry
			if ( ! empty( $args['subject_id'] ) ) {
				$sql = "SELECT n.question_id FROM {$table_notebook} n
				        JOIN {$table_questions} q ON n.question_id = q.id
				        WHERE n.user_id = %d AND n.status != 'dismissed' AND q.subject_id = %d
				        ORDER BY n.times_incorrect DESC, n.updated_at DESC";
				$question_ids = $wpdb->get_col( $wpdb->prepare( $sql, $user_id, absint( $args['subject_id'] ) ) );
			} else {
				$sql = "SELECT n.question_id FROM {$table_notebook} n
				        JOIN {$table_questions} q ON n.question_id = q.id
				        WHERE n.user_id = %d AND n.status != 'dismissed'
				        ORDER BY n.times_incorrect DESC, n.updated_at DESC";
				$question_ids = $wpdb->get_col( $wpdb->prepare( $sql, $user_id ) );
			}
			$question_ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $question_ids ) ) ) );
		}

		if ( empty( $question_ids ) ) {
			return new WP_Error( 'no_questions', __( 'No active incorrect questions found in your notebook for retry practice.', 'indianagri-quiz-engine' ) );
		}

		// Cap max retry questions to admin limit or reasonable 30.
		$max_limit = (int) get_option( 'iaq_retry_max_questions', 30 );
		if ( $max_limit > 0 && count( $question_ids ) > $max_limit ) {
			$question_ids = array_slice( $question_ids, 0, $max_limit );
		}

		$count = count( $question_ids );
		$title = sprintf( __( 'Wrong Answer Retry Drill (%s)', 'indianagri-quiz-engine' ), date_i18n( 'M j, Y' ) );
		$slug  = 'wrong-retry-' . $user_id . '-' . time();

		$table_quizzes = IAQ_Database::get_table_name( 'quizzes' );
		$quiz_data = array(
			'title'             => $title,
			'slug'              => $slug,
			'description'       => __( 'Targeted corrective practice generated from your personal Wrong Answer Notebook.', 'indianagri-quiz-engine' ),
			'quiz_type'         => 'practice',
			'exam_category'     => 'Revision',
			'total_questions'   => $count,
			'total_marks'       => (float) $count,
			'duration_minutes'  => 0, // untimed retry practice
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
			return new WP_Error( 'db_error', __( 'Failed to generate retry practice test.', 'indianagri-quiz-engine' ) );
		}

		// Attach questions.
		$table_qq = IAQ_Database::get_table_name( 'quiz_questions' );
		$order    = 1;
		foreach ( $question_ids as $qid ) {
			$wpdb->insert(
				$table_qq,
				array(
					'quiz_id'        => $quiz_id,
					'question_id'    => (int) $qid,
					'question_order' => $order++,
				),
				array( '%d', '%d', '%d' )
			);
		}

		return $quiz_id;
	}

	/**
	 * Alias for get_notebook_count.
	 *
	 * @param int          $user_id WordPress User ID.
	 * @param string|array $status Status filter string or args array.
	 * @return int
	 */
	public static function get_user_notebook_count( $user_id, $status = 'all' ) {
		$args = is_array( $status ) ? $status : array( 'status' => $status );
		return self::get_notebook_count( $user_id, $args );
	}

	/**
	 * Alias for update_status.
	 *
	 * @param int    $user_id WordPress User ID.
	 * @param int    $question_id Question ID.
	 * @param string $status Status string.
	 * @return bool
	 */
	public static function update_entry_status( $user_id, $question_id, $status = 'resolved' ) {
		return self::update_status( $user_id, $question_id, $status );
	}

	/**
	 * Alias for create_retry_quiz.
	 *
	 * @param int   $user_id WordPress User ID.
	 * @param array $args Filter args.
	 * @return int|WP_Error
	 */
	public static function generate_retry_quiz( $user_id, $args = array() ) {
		return self::create_retry_quiz( $user_id, $args );
	}
}
