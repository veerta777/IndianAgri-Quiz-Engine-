<?php
/**
 * Bookmarks management service: save, remove, filter, and practice bookmarked questions.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IAQ_Bookmarks {

	/**
	 * Add or toggle bookmark for a question.
	 *
	 * @param int    $user_id WordPress User ID.
	 * @param int    $question_id Question ID.
	 * @param string $notes Optional student revision notes.
	 * @return int|WP_Error Bookmark ID on success.
	 */
	public static function add_bookmark( $user_id, $question_id, $notes = '' ) {
		global $wpdb;

		$user_id     = absint( $user_id );
		$question_id = absint( $question_id );

		if ( $user_id <= 0 ) {
			return new WP_Error( 'unauthorized', __( 'You must be logged in to bookmark questions.', 'indianagri-quiz-engine' ) );
		}

		if ( $question_id <= 0 ) {
			return new WP_Error( 'invalid_question', __( 'Invalid question identifier.', 'indianagri-quiz-engine' ) );
		}

		// Verify question exists.
		$table_questions = IAQ_Database::get_table_name( 'questions' );
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_questions} WHERE id = %d", $question_id ) );
		if ( ! $exists ) {
			return new WP_Error( 'not_found', __( 'Question does not exist.', 'indianagri-quiz-engine' ) );
		}

		$table_bookmarks = IAQ_Database::get_table_name( 'bookmarks' );

		// Check if already bookmarked.
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_bookmarks} WHERE user_id = %d AND question_id = %d",
				$user_id,
				$question_id
			)
		);

		if ( $existing_id ) {
			if ( ! empty( $notes ) ) {
				$wpdb->update(
					$table_bookmarks,
					array( 'notes' => sanitize_textarea_field( $notes ) ),
					array( 'id' => (int) $existing_id )
				);
			}
			return (int) $existing_id;
		}

		$inserted = $wpdb->insert(
			$table_bookmarks,
			array(
				'user_id'     => $user_id,
				'question_id' => $question_id,
				'notes'       => sanitize_textarea_field( $notes ),
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return new WP_Error( 'db_error', __( 'Could not save bookmark.', 'indianagri-quiz-engine' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Remove a bookmark for a question.
	 *
	 * @param int $user_id WordPress User ID.
	 * @param int $question_id Question ID.
	 * @return bool
	 */
	public static function remove_bookmark( $user_id, $question_id ) {
		global $wpdb;

		$user_id     = absint( $user_id );
		$question_id = absint( $question_id );

		if ( $user_id <= 0 || $question_id <= 0 ) {
			return false;
		}

		$table_bookmarks = IAQ_Database::get_table_name( 'bookmarks' );
		$deleted = $wpdb->delete(
			$table_bookmarks,
			array(
				'user_id'     => $user_id,
				'question_id' => $question_id,
			),
			array( '%d', '%d' )
		);

		return (bool) $deleted;
	}

	/**
	 * Toggle bookmark status.
	 *
	 * @param int    $user_id WordPress User ID.
	 * @param int    $question_id Question ID.
	 * @param string $notes Optional student revision notes.
	 * @return array Status payload.
	 */
	public static function toggle_bookmark( $user_id, $question_id, $notes = '' ) {
		$is_bookmarked = self::is_bookmarked( $user_id, $question_id );
		if ( $is_bookmarked ) {
			$removed = self::remove_bookmark( $user_id, $question_id );
			return array(
				'bookmarked' => false,
				'success'    => $removed,
				'message'    => __( 'Question removed from your bookmarks.', 'indianagri-quiz-engine' ),
			);
		} else {
			$id = self::add_bookmark( $user_id, $question_id, $notes );
			return array(
				'bookmarked'  => ! is_wp_error( $id ),
				'bookmark_id' => is_wp_error( $id ) ? 0 : $id,
				'success'     => ! is_wp_error( $id ),
				'message'     => is_wp_error( $id ) ? $id->get_error_message() : __( 'Question saved to your bookmarks!', 'indianagri-quiz-engine' ),
			);
		}
	}

	/**
	 * Check if a question is bookmarked by a user.
	 *
	 * @param int $user_id WordPress User ID.
	 * @param int $question_id Question ID.
	 * @return bool
	 */
	public static function is_bookmarked( $user_id, $question_id ) {
		global $wpdb;

		$user_id     = absint( $user_id );
		$question_id = absint( $question_id );

		if ( $user_id <= 0 || $question_id <= 0 ) {
			return false;
		}

		$table_bookmarks = IAQ_Database::get_table_name( 'bookmarks' );
		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_bookmarks} WHERE user_id = %d AND question_id = %d LIMIT 1",
				$user_id,
				$question_id
			)
		);

		return ! empty( $found );
	}

	/**
	 * Get all bookmarked question IDs for a user.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return array
	 */
	public static function get_bookmarked_question_ids( $user_id ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return array();
		}

		$table_bookmarks = IAQ_Database::get_table_name( 'bookmarks' );
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT question_id FROM {$table_bookmarks} WHERE user_id = %d ORDER BY id DESC",
				$user_id
			)
		);

		return array_map( 'intval', (array) $rows );
	}

	/**
	 * Retrieve detailed list of bookmarked questions for a student.
	 *
	 * @param int   $user_id WordPress User ID.
	 * @param array $args Query filters.
	 * @return array
	 */
	public static function get_user_bookmarks( $user_id, $args = array() ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return array();
		}

		$defaults = array(
			'subject_id'    => 0,
			'topic_id'      => 0,
			'exam_category' => '',
			'limit'         => 20,
			'offset'        => 0,
			'orderby'       => 'b.id',
			'order'         => 'DESC',
		);
		$r = wp_parse_args( $args, $defaults );

		$table_bookmarks = IAQ_Database::get_table_name( 'bookmarks' );
		$table_questions = IAQ_Database::get_table_name( 'questions' );
		$table_subjects  = IAQ_Database::get_table_name( 'subjects' );
		$table_topics    = IAQ_Database::get_table_name( 'topics' );

		$where  = array( 'b.user_id = %d' );
		$params = array( $user_id );

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

		$sql = "SELECT b.id as bookmark_id, b.user_id, b.question_id, b.notes, b.created_at as bookmarked_at,
		               q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option,
		               q.explanation, q.marks, q.negative_marks, q.difficulty, q.exam_category,
		               q.subject_id, q.topic_id,
		               s.name as subject_name,
		               t.name as topic_name
		        FROM {$table_bookmarks} b
		        JOIN {$table_questions} q ON b.question_id = q.id
		        LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
		        LEFT JOIN {$table_topics} t ON q.topic_id = t.id
		        WHERE {$where_clause}
		        ORDER BY b.id DESC
		        {$limit_clause}";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		return $results ? $results : array();
	}

	/**
	 * Get total count of bookmarks for student matching filters.
	 *
	 * @param int   $user_id WordPress User ID.
	 * @param array $args Query filters.
	 * @return int
	 */
	public static function get_bookmark_count( $user_id, $args = array() ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return 0;
		}

		$table_bookmarks = IAQ_Database::get_table_name( 'bookmarks' );
		$table_questions = IAQ_Database::get_table_name( 'questions' );

		$where  = array( 'b.user_id = %d' );
		$params = array( $user_id );

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
		$sql = "SELECT COUNT(b.id)
		        FROM {$table_bookmarks} b
		        JOIN {$table_questions} q ON b.question_id = q.id
		        WHERE {$where_clause}";

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Create an on-demand practice quiz containing the student's bookmarked questions.
	 *
	 * @param int   $user_id WordPress User ID.
	 * @param array $question_ids Optional subset of question IDs.
	 * @return int|WP_Error Quiz ID on success.
	 */
	public static function create_revision_quiz( $user_id, $question_ids = array() ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return new WP_Error( 'unauthorized', __( 'Authentication required.', 'indianagri-quiz-engine' ) );
		}

		// If no specific IDs provided, get all student bookmarks.
		if ( empty( $question_ids ) ) {
			$question_ids = self::get_bookmarked_question_ids( $user_id );
		} else {
			// Verify all requested question IDs are genuinely bookmarked by this user.
			$owned_ids    = self::get_bookmarked_question_ids( $user_id );
			$question_ids = array_values( array_intersect( $question_ids, $owned_ids ) );
		}

		if ( empty( $question_ids ) ) {
			return new WP_Error( 'no_bookmarks', __( 'No bookmarked questions available for revision practice.', 'indianagri-quiz-engine' ) );
		}

		$table_quizzes = IAQ_Database::get_table_name( 'quizzes' );
		$title         = sprintf( __( 'Bookmark Revision Practice (%s)', 'indianagri-quiz-engine' ), date_i18n( 'M j, Y' ) );
		$slug          = 'bookmark-revision-' . $user_id . '-' . time();
		$count         = count( $question_ids );

		$quiz_data = array(
			'title'             => $title,
			'slug'              => $slug,
			'description'       => __( 'Personalized revision drill generated from your saved bookmarks.', 'indianagri-quiz-engine' ),
			'quiz_type'         => 'practice',
			'exam_category'     => 'Revision',
			'total_questions'   => $count,
			'total_marks'       => (float) $count,
			'duration_minutes'  => 0, // untimed revision practice
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
			return new WP_Error( 'db_error', __( 'Failed to generate revision practice set.', 'indianagri-quiz-engine' ) );
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
	 * Alias for get_bookmark_count.
	 *
	 * @param int   $user_id WordPress User ID.
	 * @param array $args Filter args.
	 * @return int
	 */
	public static function get_user_bookmark_count( $user_id, $args = array() ) {
		return self::get_bookmark_count( $user_id, $args );
	}

	/**
	 * Alias for create_revision_quiz.
	 *
	 * @param int   $user_id WordPress User ID.
	 * @param array $question_ids Optional question IDs.
	 * @return int|WP_Error
	 */
	public static function generate_revision_quiz( $user_id, $question_ids = array() ) {
		return self::create_revision_quiz( $user_id, $question_ids );
	}
}
