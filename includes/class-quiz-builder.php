<?php
/**
 * Quiz Builder engine: Quiz CRUD, question assignments, exam configuration rules, and query handlers.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IAQ_Quiz_Builder
 */
class IAQ_Quiz_Builder {

	/**
	 * Supported quiz types.
	 *
	 * @return array
	 */
	public static function get_quiz_types() {
		return array(
			'practice'      => array(
				'label'       => __( 'Practice Set', 'indianagri-quiz-engine' ),
				'description' => __( 'Self-paced practice with optional instant feedback and flexible timing.', 'indianagri-quiz-engine' ),
				'badge_color' => '#0284c7', // Sky blue
			),
			'mock'          => array(
				'label'       => __( 'Mock Test', 'indianagri-quiz-engine' ),
				'description' => __( 'Full-length simulated examination with strict countdown timer and negative marking.', 'indianagri-quiz-engine' ),
				'badge_color' => '#117E3A', // Brand green
			),
		);
	}

	/**
	 * Result and answer review modes.
	 *
	 * @return array
	 */
	public static function get_review_modes() {
		return array(
			'after_submission' => __( 'Show Correct Answers & Explanations After Submission', 'indianagri-quiz-engine' ),
			'immediate'        => __( 'Show Correct Answer Immediately After Answering Each Question (Practice Only)', 'indianagri-quiz-engine' ),
			'never'            => __( 'Hide Answer Key & Explanations (Show Scorecard Only)', 'indianagri-quiz-engine' ),
		);
	}

	/**
	 * Retrieve single quiz by ID.
	 *
	 * @param int $id Quiz ID.
	 * @return object|null Quiz object.
	 */
	public static function get_quiz( $id ) {
		global $wpdb;

		$table_quizzes  = IAQ_Database::get_table_name( 'quizzes' );
		$table_subjects = IAQ_Database::get_table_name( 'subjects' );
		$table_topics   = IAQ_Database::get_table_name( 'topics' );

		$query = $wpdb->prepare(
			"SELECT q.*, s.name AS subject_name, t.name AS topic_name
			 FROM {$table_quizzes} q
			 LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
			 LEFT JOIN {$table_topics} t ON q.topic_id = t.id
			 WHERE q.id = %d",
			absint( $id )
		);

		return $wpdb->get_row( $query );
	}

	/**
	 * Retrieve single quiz by title.
	 *
	 * @param string $title Quiz title.
	 * @return object|null Quiz object.
	 */
	public static function get_quiz_by_title( $title ) {
		global $wpdb;

		$table_quizzes  = IAQ_Database::get_table_name( 'quizzes' );
		$table_subjects = IAQ_Database::get_table_name( 'subjects' );
		$table_topics   = IAQ_Database::get_table_name( 'topics' );

		$query = $wpdb->prepare(
			"SELECT q.*, s.name AS subject_name, t.name AS topic_name
			 FROM {$table_quizzes} q
			 LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
			 LEFT JOIN {$table_topics} t ON q.topic_id = t.id
			 WHERE q.title = %s LIMIT 1",
			$title
		);

		return $wpdb->get_row( $query );
	}

	/**
	 * Build SQL WHERE clause for quiz queries.
	 *
	 * @param array $args
	 * @return array
	 */
	private static function build_where_clause( $args ) {
		$where_clauses = array( '1=1' );
		$params        = array();

		// Keyword search.
		if ( ! empty( $args['search'] ) ) {
			$search = '%' . trim( $args['search'] ) . '%';
			$where_clauses[] = '(q.title LIKE %s OR q.description LIKE %s)';
			$params[] = $search;
			$params[] = $search;
		}

		// Quiz Type filter.
		if ( ! empty( $args['quiz_type'] ) && in_array( $args['quiz_type'], array( 'practice', 'mock' ), true ) ) {
			$where_clauses[] = 'q.quiz_type = %s';
			$params[]        = $args['quiz_type'];
		}

		// Exam Category filter.
		if ( ! empty( $args['exam_category'] ) ) {
			$where_clauses[] = 'q.exam_category = %s';
			$params[]        = sanitize_text_field( $args['exam_category'] );
		}

		// Subject filter.
		if ( ! empty( $args['subject_id'] ) ) {
			$where_clauses[] = 'q.subject_id = %d';
			$params[]        = absint( $args['subject_id'] );
		}

		// Access Type (free / paid).
		if ( ! empty( $args['access_type'] ) && in_array( $args['access_type'], array( 'free', 'paid' ), true ) ) {
			$where_clauses[] = 'q.access_type = %s';
			$params[]        = $args['access_type'];
		}

		// Status.
		if ( ! empty( $args['status'] ) && in_array( $args['status'], array( 'draft', 'published', 'archived' ), true ) ) {
			$where_clauses[] = 'q.status = %s';
			$params[]        = $args['status'];
		}

		return array(
			'where'  => implode( ' AND ', $where_clauses ),
			'params' => $params,
		);
	}

	/**
	 * Retrieve quizzes with pagination, filtering, and subject joins.
	 *
	 * @param array $args
	 * @return array
	 */
	public static function get_quizzes( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'search'        => '',
			'quiz_type'     => '',
			'exam_category' => '',
			'subject_id'    => 0,
			'access_type'   => '',
			'status'        => '',
			'page'          => 1,
			'per_page'      => 20,
			'orderby'       => 'id',
			'order'         => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$table_quizzes  = IAQ_Database::get_table_name( 'quizzes' );
		$table_subjects = IAQ_Database::get_table_name( 'subjects' );
		$table_topics   = IAQ_Database::get_table_name( 'topics' );
		$table_quiz_q   = IAQ_Database::get_table_name( 'quiz_questions' );

		$clause = self::build_where_clause( $args );
		$where  = $clause['where'];
		$params = $clause['params'];

		$allowed_orderby = array( 'id', 'title', 'quiz_type', 'total_questions', 'total_marks', 'duration_minutes', 'created_at', 'status' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'id';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$per_page = max( 1, absint( $args['per_page'] ) );
		$page     = max( 1, absint( $args['page'] ) );
		$offset   = ( $page - 1 ) * $per_page;

		$sql = "
			SELECT q.*, s.name AS subject_name, t.name AS topic_name,
				(SELECT COUNT(*) FROM {$table_quiz_q} qq WHERE qq.quiz_id = q.id) AS assigned_questions_count
			FROM {$table_quizzes} q
			LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
			LEFT JOIN {$table_topics} t ON q.topic_id = t.id
			WHERE {$where}
			ORDER BY q.{$orderby} {$order}
			LIMIT %d OFFSET %d
		";

		$params[] = $per_page;
		$params[] = $offset;

		$prepared = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $prepared );
	}

	/**
	 * Count total quizzes matching filters.
	 *
	 * @param array $args
	 * @return int
	 */
	public static function get_quiz_count( $args = array() ) {
		global $wpdb;

		$table_quizzes = IAQ_Database::get_table_name( 'quizzes' );
		$clause        = self::build_where_clause( $args );
		$where         = $clause['where'];
		$params        = $clause['params'];

		$sql = "SELECT COUNT(*) FROM {$table_quizzes} q WHERE {$where}";

		if ( ! empty( $params ) ) {
			$prepared = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return (int) $wpdb->get_var( $prepared );
		}

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Save Quiz (Insert or Update).
	 *
	 * @param array $data
	 * @return int|WP_Error
	 */
	public static function save_quiz( $data ) {
		global $wpdb;

		$table = IAQ_Database::get_table_name( 'quizzes' );
		$id    = isset( $data['id'] ) ? absint( $data['id'] ) : 0;

		$title = isset( $data['title'] ) ? sanitize_text_field( wp_unslash( $data['title'] ) ) : '';
		if ( empty( $title ) ) {
			return new WP_Error( 'empty_title', __( 'Quiz title is required.', 'indianagri-quiz-engine' ) );
		}

		$slug = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $title );
		if ( empty( $slug ) ) {
			$slug = 'quiz-' . time();
		}

		// Ensure unique slug.
		$existing_slug = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s AND id != %d", $slug, $id )
		);
		if ( $existing_slug ) {
			$slug .= '-' . time();
		}

		$quiz_type = isset( $data['quiz_type'] ) && in_array( $data['quiz_type'], array( 'practice', 'mock' ), true ) ? $data['quiz_type'] : 'practice';
		$subject_id = ! empty( $data['subject_id'] ) ? absint( $data['subject_id'] ) : null;
		$topic_id   = ! empty( $data['topic_id'] ) ? absint( $data['topic_id'] ) : null;
		$exam_cat   = ! empty( $data['exam_category'] ) ? sanitize_text_field( wp_unslash( $data['exam_category'] ) ) : 'General Agriculture';

		$description  = isset( $data['description'] ) ? wp_kses_post( wp_unslash( $data['description'] ) ) : '';
		$instructions = isset( $data['instructions'] ) ? wp_kses_post( wp_unslash( $data['instructions'] ) ) : '';

		// Scoring & Exam parameters.
		$duration_minutes = isset( $data['duration_minutes'] ) ? max( 0, absint( $data['duration_minutes'] ) ) : 60;
		$correct_marks    = isset( $data['correct_marks'] ) ? max( 0.01, floatval( $data['correct_marks'] ) ) : 1.00;
		$negative_marks   = isset( $data['negative_marks'] ) ? max( 0.00, floatval( $data['negative_marks'] ) ) : 0.25;
		$pass_percentage  = isset( $data['pass_percentage'] ) ? min( 100.00, max( 0.00, floatval( $data['pass_percentage'] ) ) ) : 40.00;

		$shuffle_questions = ! empty( $data['shuffle_questions'] ) ? 1 : 0;
		$shuffle_options   = ! empty( $data['shuffle_options'] ) ? 1 : 0;
		$max_attempts      = isset( $data['max_attempts'] ) ? max( 0, absint( $data['max_attempts'] ) ) : 0;

		// Access & review mode.
		$access_type       = ( isset( $data['access_type'] ) && in_array( $data['access_type'], array( 'paid', 'premium' ), true ) ) ? 'paid' : 'free';
		$wc_product_id     = ! empty( $data['wc_product_id'] ) ? absint( $data['wc_product_id'] ) : null;
		$show_answers_mode = isset( $data['show_answers_mode'] ) && in_array( $data['show_answers_mode'], array( 'immediate', 'after_submission', 'never' ), true ) ? $data['show_answers_mode'] : 'after_submission';

		// PYQ specifics.
		$pyq_year           = ! empty( $data['pyq_year'] ) ? absint( $data['pyq_year'] ) : null;
		$pyq_session        = ! empty( $data['pyq_session'] ) ? sanitize_text_field( wp_unslash( $data['pyq_session'] ) ) : null;
		$pdf_attachment_url = ! empty( $data['pdf_attachment_url'] ) ? esc_url_raw( wp_unslash( $data['pdf_attachment_url'] ) ) : null;

		// Scheduling.
		$start_date = ! empty( $data['start_date'] ) ? sanitize_text_field( $data['start_date'] ) : null;
		$end_date   = ! empty( $data['end_date'] ) ? sanitize_text_field( $data['end_date'] ) : null;

		$status = isset( $data['status'] ) && in_array( $data['status'], array( 'draft', 'published', 'archived' ), true ) ? $data['status'] : 'draft';

		$display_order = isset( $data['display_order'] ) ? intval( $data['display_order'] ) : 0;

		$payload = array(
			'title'              => $title,
			'slug'               => $slug,
			'description'        => $description,
			'quiz_type'          => $quiz_type,
			'subject_id'         => $subject_id,
			'topic_id'           => $topic_id,
			'exam_category'      => $exam_cat,
			'duration_minutes'   => $duration_minutes,
			'correct_marks'      => $correct_marks,
			'negative_marks'     => $negative_marks,
			'pass_percentage'    => $pass_percentage,
			'shuffle_questions'  => $shuffle_questions,
			'shuffle_options'    => $shuffle_options,
			'max_attempts'       => $max_attempts,
			'access_type'        => $access_type,
			'wc_product_id'      => $wc_product_id,
			'instructions'       => $instructions,
			'show_answers_mode'  => $show_answers_mode,
			'pyq_year'           => $pyq_year,
			'pyq_session'        => $pyq_session,
			'pdf_attachment_url' => $pdf_attachment_url,
			'start_date'         => $start_date,
			'end_date'           => $end_date,
			'display_order'      => $display_order,
			'status'             => $status,
		);

		$formats = array(
			'%s', // title
			'%s', // slug
			'%s', // description
			'%s', // quiz_type
			'%d', // subject_id
			'%d', // topic_id
			'%s', // exam_category
			'%d', // duration_minutes
			'%f', // correct_marks
			'%f', // negative_marks
			'%f', // pass_percentage
			'%d', // shuffle_questions
			'%d', // shuffle_options
			'%d', // max_attempts
			'%s', // access_type
			'%d', // wc_product_id
			'%s', // instructions
			'%s', // show_answers_mode
			'%d', // pyq_year
			'%s', // pyq_session
			'%s', // pdf_attachment_url
			'%s', // start_date
			'%s', // end_date
			'%d', // display_order
			'%s', // status
		);

		$target_id = 0;
		if ( $id > 0 ) {
			$payload['updated_at'] = current_time( 'mysql' );
			$formats[]             = '%s';

			$updated = $wpdb->update( $table, $payload, array( 'id' => $id ), $formats, array( '%d' ) );
			if ( false !== $updated ) {
				self::recalculate_quiz_totals( $id );
				$target_id = $id;
			} else {
				return new WP_Error( 'db_error', __( 'Failed to update quiz.', 'indianagri-quiz-engine' ) );
			}
		} else {
			$payload['created_by'] = get_current_user_id();
			$payload['created_at'] = current_time( 'mysql' );
			$formats[]             = '%d';
			$formats[]             = '%s';

			$inserted = $wpdb->insert( $table, $payload, $formats );
			if ( false !== $inserted ) {
				$target_id = (int) $wpdb->insert_id;
				self::recalculate_quiz_totals( $target_id );
			} else {
				return new WP_Error( 'db_error', __( 'Failed to create quiz.', 'indianagri-quiz-engine' ) );
			}
		}

		// If a book assignment was requested, link to book.
		if ( $target_id > 0 && ! empty( $data['book_id'] ) && class_exists( 'IAQ_Practice_Sets' ) ) {
			IAQ_Practice_Sets::assign_quiz_to_book( absint( $data['book_id'] ), $target_id );
		}

		return $target_id;
	}

	/**
	 * Duplicate an existing quiz, including all its assigned questions.
	 *
	 * @param int $quiz_id Original quiz ID.
	 * @return int|WP_Error New quiz ID or WP_Error.
	 */
	public static function duplicate_quiz( $quiz_id ) {
		global $wpdb;

		$quiz_id = absint( $quiz_id );
		$table_quizzes = IAQ_Database::get_table_name( 'quizzes' );
		$table_quiz_q  = IAQ_Database::get_table_name( 'quiz_questions' );

		$orig = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_quizzes} WHERE id = %d", $quiz_id )
		);

		if ( ! $orig ) {
			return new WP_Error( 'not_found', __( 'Original quiz not found.', 'indianagri-quiz-engine' ) );
		}

		$copy_data = array(
			'title'              => $orig->title . ' ' . __( '(Copy)', 'indianagri-quiz-engine' ),
			'slug'               => $orig->slug . '-copy-' . time(),
			'description'        => $orig->description,
			'quiz_type'          => $orig->quiz_type,
			'subject_id'         => $orig->subject_id,
			'topic_id'           => $orig->topic_id,
			'exam_category'      => $orig->exam_category,
			'duration_minutes'   => $orig->duration_minutes,
			'correct_marks'      => $orig->correct_marks,
			'negative_marks'     => $orig->negative_marks,
			'pass_percentage'    => $orig->pass_percentage,
			'shuffle_questions'  => $orig->shuffle_questions,
			'shuffle_options'    => $orig->shuffle_options,
			'max_attempts'       => $orig->max_attempts,
			'access_type'        => $orig->access_type,
			'wc_product_id'      => $orig->wc_product_id,
			'instructions'       => $orig->instructions,
			'show_answers_mode'  => $orig->show_answers_mode,
			'pyq_year'           => isset( $orig->pyq_year ) ? $orig->pyq_year : null,
			'pyq_session'        => isset( $orig->pyq_session ) ? $orig->pyq_session : null,
			'pdf_attachment_url' => isset( $orig->pdf_attachment_url ) ? $orig->pdf_attachment_url : null,
			'status'             => 'draft',
			'created_by'         => get_current_user_id(),
			'created_at'         => current_time( 'mysql' ),
		);

		$inserted = $wpdb->insert( $table_quizzes, $copy_data );
		if ( false === $inserted ) {
			return new WP_Error( 'insert_failed', __( 'Could not duplicate quiz.', 'indianagri-quiz-engine' ) );
		}

		$new_quiz_id = (int) $wpdb->insert_id;

		// Copy assigned questions.
		$assigned = $wpdb->get_results(
			$wpdb->prepare( "SELECT question_id, question_order, marks, negative_marks FROM {$table_quiz_q} WHERE quiz_id = %d ORDER BY question_order ASC", $quiz_id )
		);

		if ( ! empty( $assigned ) ) {
			foreach ( $assigned as $item ) {
				$wpdb->insert(
					$table_quiz_q,
					array(
						'quiz_id'        => $new_quiz_id,
						'question_id'    => $item->question_id,
						'question_order' => $item->question_order,
						'marks'          => $item->marks,
						'negative_marks' => $item->negative_marks,
					)
				);
			}
		}

		self::recalculate_quiz_totals( $new_quiz_id );

		return $new_quiz_id;
	}

	/**
	 * Delete quiz and associated question links.
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function delete_quiz( $id ) {
		global $wpdb;

		$id             = absint( $id );
		$table_quizzes  = IAQ_Database::get_table_name( 'quizzes' );
		$table_quiz_q   = IAQ_Database::get_table_name( 'quiz_questions' );
		$table_attempts = IAQ_Database::get_table_name( 'quiz_attempts' );

		// Remove linked questions.
		$wpdb->delete( $table_quiz_q, array( 'quiz_id' => $id ), array( '%d' ) );

		return (bool) $wpdb->delete( $table_quizzes, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Bulk delete quizzes.
	 *
	 * @param array $ids
	 * @return int
	 */
	public static function bulk_delete_quizzes( $ids ) {
		global $wpdb;

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return 0;
		}

		$ids             = array_map( 'absint', $ids );
		$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$table_quizzes   = IAQ_Database::get_table_name( 'quizzes' );
		$table_quiz_q    = IAQ_Database::get_table_name( 'quiz_questions' );

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table_quiz_q} WHERE quiz_id IN ({$ids_placeholder})", $ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_quizzes} WHERE id IN ({$ids_placeholder})", $ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $deleted;
	}

	/**
	 * Bulk update quiz status.
	 *
	 * @param array  $ids
	 * @param string $status
	 * @return int
	 */
	public static function bulk_update_quiz_status( $ids, $status ) {
		global $wpdb;

		if ( empty( $ids ) || ! in_array( $status, array( 'published', 'draft', 'archived' ), true ) ) {
			return 0;
		}

		$ids             = array_map( 'absint', $ids );
		$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$table           = IAQ_Database::get_table_name( 'quizzes' );

		$params   = array_merge( array( $status ), $ids );
		$prepared = $wpdb->prepare( "UPDATE {$table} SET status = %s WHERE id IN ({$ids_placeholder})", $params ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $wpdb->query( $prepared ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/* =========================================================================
	 * QUIZ QUESTIONS LINKING & ORDERING
	 * ========================================================================= */

	/**
	 * Retrieve assigned questions for a quiz, in sequential order.
	 *
	 * @param int $quiz_id
	 * @return array
	 */
	public static function get_quiz_questions( $quiz_id ) {
		global $wpdb;

		$table_quiz_q    = IAQ_Database::get_table_name( 'quiz_questions' );
		$table_questions = IAQ_Database::get_table_name( 'questions' );
		$table_subjects  = IAQ_Database::get_table_name( 'subjects' );
		$table_topics    = IAQ_Database::get_table_name( 'topics' );

		$query = $wpdb->prepare(
			"SELECT qq.id AS link_id, qq.question_order, qq.custom_marks, qq.custom_negative_marks,
			        q.*, s.name AS subject_name, t.name AS topic_name
			 FROM {$table_quiz_q} qq
			 INNER JOIN {$table_questions} q ON qq.question_id = q.id
			 LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
			 LEFT JOIN {$table_topics} t ON q.topic_id = t.id
			 WHERE qq.quiz_id = %d
			 ORDER BY qq.question_order ASC, qq.id ASC",
			absint( $quiz_id )
		);

		return $wpdb->get_results( $query );
	}

	/**
	 * Retrieve array of assigned question IDs for a quiz.
	 *
	 * @param int $quiz_id
	 * @return array Array of integer question IDs.
	 */
	public static function get_quiz_question_ids( $quiz_id ) {
		$questions = self::get_quiz_questions( $quiz_id );
		return wp_list_pluck( $questions, 'id' );
	}

	/**
	 * Assign questions to a quiz.
	 *
	 * @param int   $quiz_id
	 * @param array $question_ids
	 * @return int Number of questions newly added.
	 */
	public static function assign_questions_to_quiz( $quiz_id, $question_ids ) {
		global $wpdb;

		$quiz_id = absint( $quiz_id );
		if ( empty( $quiz_id ) || empty( $question_ids ) ) {
			return 0;
		}

		$table_quiz_q = IAQ_Database::get_table_name( 'quiz_questions' );

		// Get current max order.
		$max_order = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT MAX(question_order) FROM {$table_quiz_q} WHERE quiz_id = %d", $quiz_id )
		);

		$added = 0;
		foreach ( $question_ids as $qid ) {
			$qid = absint( $qid );
			if ( ! $qid ) {
				continue;
			}

			// Check if already assigned.
			$exists = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$table_quiz_q} WHERE quiz_id = %d AND question_id = %d", $quiz_id, $qid )
			);

			if ( ! $exists ) {
				$max_order++;
				$wpdb->insert(
					$table_quiz_q,
					array(
						'quiz_id'        => $quiz_id,
						'question_id'    => $qid,
						'question_order' => $max_order,
					),
					array( '%d', '%d', '%d' )
				);
				$added++;
			}
		}

		if ( $added > 0 ) {
			self::recalculate_quiz_totals( $quiz_id );
		}

		return $added;
	}

	/**
	 * Remove a question from a quiz.
	 *
	 * @param int $quiz_id
	 * @param int $question_id
	 * @return bool
	 */
	public static function remove_question_from_quiz( $quiz_id, $question_id ) {
		global $wpdb;

		$table_quiz_q = IAQ_Database::get_table_name( 'quiz_questions' );
		$deleted      = $wpdb->delete(
			$table_quiz_q,
			array(
				'quiz_id'     => absint( $quiz_id ),
				'question_id' => absint( $question_id ),
			),
			array( '%d', '%d' )
		);

		if ( $deleted ) {
			self::recalculate_quiz_totals( $quiz_id );
		}

		return (bool) $deleted;
	}

	/**
	 * Reorder assigned questions.
	 *
	 * @param int   $quiz_id
	 * @param array $ordered_question_ids Array of question IDs in sequential order.
	 * @return bool
	 */
	public static function reorder_quiz_questions( $quiz_id, $ordered_question_ids ) {
		global $wpdb;

		$quiz_id      = absint( $quiz_id );
		$table_quiz_q = IAQ_Database::get_table_name( 'quiz_questions' );

		foreach ( $ordered_question_ids as $order => $qid ) {
			$wpdb->update(
				$table_quiz_q,
				array( 'question_order' => absint( $order + 1 ) ),
				array(
					'quiz_id'     => $quiz_id,
					'question_id' => absint( $qid ),
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
		}

		return true;
	}

	/**
	 * Auto-select questions from Question Bank by criteria (Subject, Topic, Difficulty, Exam, Count).
	 *
	 * @param int   $quiz_id
	 * @param array $criteria
	 * @return int Count of assigned questions.
	 */
	public static function auto_assign_questions( $quiz_id, $criteria = array() ) {
		global $wpdb;

		$table_questions = IAQ_Database::get_table_name( 'questions' );
		$table_quiz_q    = IAQ_Database::get_table_name( 'quiz_questions' );

		$count = isset( $criteria['count'] ) ? max( 1, absint( $criteria['count'] ) ) : 20;

		$where  = array( "status = 'published'" );
		$params = array();

		// Exclude already assigned.
		$where[] = "id NOT IN (SELECT question_id FROM {$table_quiz_q} WHERE quiz_id = %d)";
		$params[] = absint( $quiz_id );

		if ( ! empty( $criteria['subject_id'] ) ) {
			$where[]  = 'subject_id = %d';
			$params[] = absint( $criteria['subject_id'] );
		}

		if ( ! empty( $criteria['topic_id'] ) ) {
			$where[]  = 'topic_id = %d';
			$params[] = absint( $criteria['topic_id'] );
		}

		if ( ! empty( $criteria['exam_category'] ) ) {
			$where[]  = 'exam_category = %s';
			$params[] = sanitize_text_field( $criteria['exam_category'] );
		}

		if ( ! empty( $criteria['difficulty'] ) && in_array( $criteria['difficulty'], array( 'easy', 'medium', 'hard' ), true ) ) {
			$where[]  = 'difficulty = %s';
			$params[] = $criteria['difficulty'];
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT id FROM {$table_questions} WHERE {$where_sql} ORDER BY RAND() LIMIT %d";
		$params[]  = $count;

		$prepared = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$q_ids    = $wpdb->get_col( $prepared );

		if ( empty( $q_ids ) ) {
			return 0;
		}

		return self::assign_questions_to_quiz( $quiz_id, $q_ids );
	}

	/**
	 * Recalculate and update total_questions and total_marks for a quiz.
	 *
	 * @param int $quiz_id
	 */
	public static function recalculate_quiz_totals( $quiz_id ) {
		global $wpdb;

		$quiz_id         = absint( $quiz_id );
		$table_quizzes   = IAQ_Database::get_table_name( 'quizzes' );
		$table_quiz_q    = IAQ_Database::get_table_name( 'quiz_questions' );
		$table_questions = IAQ_Database::get_table_name( 'questions' );

		$quiz = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_quizzes} WHERE id = %d", $quiz_id ) );
		if ( ! $quiz ) {
			return;
		}

		$questions = self::get_quiz_questions( $quiz_id );
		$total_q   = count( $questions );
		$total_m   = 0.00;

		foreach ( $questions as $q ) {
			// If link has custom_marks, use it; otherwise use quiz correct_marks or question marks.
			if ( null !== $q->custom_marks && floatval( $q->custom_marks ) > 0 ) {
				$total_m += floatval( $q->custom_marks );
			} elseif ( floatval( $quiz->correct_marks ) > 0 ) {
				$total_m += floatval( $quiz->correct_marks );
			} else {
				$total_m += floatval( $q->marks );
			}
		}

		$wpdb->update(
			$table_quizzes,
			array(
				'total_questions' => $total_q,
				'total_marks'     => $total_m,
			),
			array( 'id' => $quiz_id ),
			array( '%d', '%f' ),
			array( '%d' )
		);
	}

	/**
	 * Retrieve published quiz counts categorized by type.
	 *
	 * @return array
	 */
	public static function get_published_quiz_counts() {
		global $wpdb;
		$table_quizzes = IAQ_Database::get_table_name( 'quizzes' );

		$rows = $wpdb->get_results(
			"SELECT quiz_type, COUNT(id) as count_val 
			 FROM {$table_quizzes} 
			 WHERE status = 'published' 
			 GROUP BY quiz_type"
		);

		$counts = array(
			'practice' => 0,
			'mock'     => 0,
			'total'    => 0,
		);

		if ( $rows ) {
			foreach ( $rows as $r ) {
				$t = $r->quiz_type;
				$c = (int) $r->count_val;
				if ( isset( $counts[ $t ] ) ) {
					$counts[ $t ] = $c;
				}
				$counts['total'] += $c;
			}
		}

		return $counts;
	}
}
