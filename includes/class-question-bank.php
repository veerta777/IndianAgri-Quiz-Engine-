<?php
/**
 * Question Bank management: CRUD operations, taxonomy relationships, filtering, and query handlers.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IAQ_Question_Bank
 */
class IAQ_Question_Bank {

	/**
	 * Default examination categories for Indian agriculture.
	 *
	 * @return array List of exam categories.
	 */
	public static function get_exam_categories() {
		return apply_filters(
			'iaq_exam_categories',
			array(
				'General Agriculture'     => 'General Agriculture',
				'ICAR AIEEA (UG / PG)'    => 'ICAR AIEEA (UG / PG)',
				'ICAR JRF / SRF / NET'    => 'ICAR JRF / SRF / NET',
				'IBPS AFO (Mains)'        => 'IBPS AFO (Agricultural Field Officer)',
				'NABARD Grade A & B'      => 'NABARD Grade A & B',
				'State Agriculture PSC'   => 'State PSC (ADA, AO, ADO, RAEO)',
				'UPSC Agriculture'        => 'UPSC Agriculture Optional',
				'FCI / CWC Technical'     => 'FCI / CWC (Technical / AGM)',
				'IFFCO / KRIBHCO AGT'     => 'IFFCO / KRIBHCO (AGT / Field Officer)',
				'BHU / CUET Agri Entrance'=> 'BHU / CUET Agriculture Entrance',
			)
		);
	}

	/**
	 * Get difficulty levels with display labels and badge colors.
	 *
	 * @return array Difficulties metadata.
	 */
	public static function get_difficulty_levels() {
		return array(
			'easy'   => array(
				'label' => __( 'Easy', 'indianagri-quiz-engine' ),
				'color' => '#16a34a', // Emerald green
			),
			'medium' => array(
				'label' => __( 'Medium', 'indianagri-quiz-engine' ),
				'color' => '#d97706', // Amber
			),
			'hard'   => array(
				'label' => __( 'Hard', 'indianagri-quiz-engine' ),
				'color' => '#dc2626', // Crimson red
			),
		);
	}

	/* =========================================================================
	 * SUBJECTS CRUD
	 * ========================================================================= */

	/**
	 * Retrieve all subjects with question count.
	 *
	 * @return array List of subject objects.
	 */
	public static function get_subjects() {
		global $wpdb;

		$table_subjects  = IAQ_Database::get_table_name( 'subjects' );
		$table_questions = IAQ_Database::get_table_name( 'questions' );
		$table_topics    = IAQ_Database::get_table_name( 'topics' );
		$table_quizzes   = IAQ_Database::get_table_name( 'quizzes' );

		$query = "
			SELECT s.*, 
				(SELECT COUNT(*) FROM {$table_questions} q WHERE q.subject_id = s.id) AS question_count,
				(SELECT COUNT(*) FROM {$table_topics} t WHERE t.subject_id = s.id) AS topic_count,
				(SELECT COUNT(*) FROM {$table_quizzes} qz WHERE qz.subject_id = s.id AND (qz.quiz_type = 'practice' OR qz.quiz_type = '' OR qz.quiz_type IS NULL) AND qz.status != 'archived') AS practice_sets_count
			FROM {$table_subjects} s
			ORDER BY s.name ASC
		";

		return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get single subject by ID.
	 *
	 * @param int $id Subject ID.
	 * @return object|null Subject object.
	 */
	public static function get_subject( $id ) {
		global $wpdb;

		$table = IAQ_Database::get_table_name( 'subjects' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ) );
	}

	/**
	 * Save subject (insert or update).
	 *
	 * @param array $data Subject data.
	 * @return int|WP_Error Inserted/updated ID or error.
	 */
	public static function save_subject( $data ) {
		global $wpdb;

		$table = IAQ_Database::get_table_name( 'subjects' );

		$name        = sanitize_text_field( isset( $data['name'] ) ? $data['name'] : '' );
		$slug        = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$description = sanitize_textarea_field( isset( $data['description'] ) ? $data['description'] : '' );
		$id          = isset( $data['id'] ) ? absint( $data['id'] ) : 0;

		if ( empty( $name ) ) {
			return new WP_Error( 'empty_name', __( 'Subject name cannot be empty.', 'indianagri-quiz-engine' ) );
		}

		// Ensure unique slug.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE slug = %s AND id != %d",
				$slug,
				$id
			)
		);
		if ( $existing ) {
			$slug = $slug . '-' . time();
		}

		$payload = array(
			'name'        => $name,
			'slug'        => $slug,
			'description' => $description,
		);

		if ( $id > 0 ) {
			$payload['updated_at'] = current_time( 'mysql' );
			$updated = $wpdb->update( $table, $payload, array( 'id' => $id ), array( '%s', '%s', '%s', '%s' ), array( '%d' ) );
			return ( false !== $updated ) ? $id : new WP_Error( 'db_error', __( 'Could not update subject.', 'indianagri-quiz-engine' ) );
		} else {
			$payload['created_at'] = current_time( 'mysql' );
			$inserted = $wpdb->insert( $table, $payload, array( '%s', '%s', '%s', '%s' ) );
			return ( false !== $inserted ) ? (int) $wpdb->insert_id : new WP_Error( 'db_error', __( 'Could not create subject.', 'indianagri-quiz-engine' ) );
		}
	}

	/**
	 * Delete subject by ID.
	 *
	 * @param int $id Subject ID.
	 * @return bool
	 */
	public static function delete_subject( $id ) {
		global $wpdb;

		$id              = absint( $id );
		$table_subjects  = IAQ_Database::get_table_name( 'subjects' );
		$table_topics    = IAQ_Database::get_table_name( 'topics' );
		$table_questions = IAQ_Database::get_table_name( 'questions' );

		// Delete topics under subject.
		$wpdb->delete( $table_topics, array( 'subject_id' => $id ), array( '%d' ) );

		// Reassign questions under subject to 0 or delete.
		$wpdb->update( $table_questions, array( 'subject_id' => 0, 'topic_id' => 0 ), array( 'subject_id' => $id ), array( '%d', '%d' ), array( '%d' ) );

		return (bool) $wpdb->delete( $table_subjects, array( 'id' => $id ), array( '%d' ) );
	}

	/* =========================================================================
	 * TOPICS CRUD
	 * ========================================================================= */

	/**
	 * Get topics, optionally filtered by subject ID.
	 *
	 * @param int $subject_id Optional subject ID filter.
	 * @return array List of topic objects.
	 */
	public static function get_topics( $subject_id = 0 ) {
		global $wpdb;

		$table_topics    = IAQ_Database::get_table_name( 'topics' );
		$table_questions = IAQ_Database::get_table_name( 'questions' );

		if ( $subject_id > 0 ) {
			$query = $wpdb->prepare(
				"SELECT t.*, (SELECT COUNT(*) FROM {$table_questions} q WHERE q.topic_id = t.id) AS question_count
				 FROM {$table_topics} t
				 WHERE t.subject_id = %d
				 ORDER BY t.name ASC",
				absint( $subject_id )
			);
		} else {
			$query = "
				SELECT t.*, (SELECT COUNT(*) FROM {$table_questions} q WHERE q.topic_id = t.id) AS question_count
				FROM {$table_topics} t
				ORDER BY t.name ASC
			";
		}

		return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get single topic by ID.
	 *
	 * @param int $id Topic ID.
	 * @return object|null Topic object.
	 */
	public static function get_topic( $id ) {
		global $wpdb;

		$table = IAQ_Database::get_table_name( 'topics' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ) );
	}

	/**
	 * Save topic.
	 *
	 * @param array $data Topic data.
	 * @return int|WP_Error Inserted/updated ID or error.
	 */
	public static function save_topic( $data ) {
		global $wpdb;

		$table = IAQ_Database::get_table_name( 'topics' );

		$subject_id  = absint( isset( $data['subject_id'] ) ? $data['subject_id'] : 0 );
		$name        = sanitize_text_field( isset( $data['name'] ) ? $data['name'] : '' );
		$slug        = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$description = sanitize_textarea_field( isset( $data['description'] ) ? $data['description'] : '' );
		$id          = isset( $data['id'] ) ? absint( $data['id'] ) : 0;

		if ( empty( $name ) ) {
			return new WP_Error( 'empty_name', __( 'Topic name cannot be empty.', 'indianagri-quiz-engine' ) );
		}

		if ( empty( $subject_id ) ) {
			return new WP_Error( 'empty_subject', __( 'Please select a parent subject for this topic.', 'indianagri-quiz-engine' ) );
		}

		$payload = array(
			'subject_id'  => $subject_id,
			'name'        => $name,
			'slug'        => $slug,
			'description' => $description,
		);

		if ( $id > 0 ) {
			$payload['updated_at'] = current_time( 'mysql' );
			$updated = $wpdb->update( $table, $payload, array( 'id' => $id ), array( '%d', '%s', '%s', '%s', '%s' ), array( '%d' ) );
			return ( false !== $updated ) ? $id : new WP_Error( 'db_error', __( 'Could not update topic.', 'indianagri-quiz-engine' ) );
		} else {
			$payload['created_at'] = current_time( 'mysql' );
			$inserted = $wpdb->insert( $table, $payload, array( '%d', '%s', '%s', '%s', '%s' ) );
			return ( false !== $inserted ) ? (int) $wpdb->insert_id : new WP_Error( 'db_error', __( 'Could not create topic.', 'indianagri-quiz-engine' ) );
		}
	}

	/**
	 * Delete topic by ID.
	 *
	 * @param int $id Topic ID.
	 * @return bool
	 */
	public static function delete_topic( $id ) {
		global $wpdb;

		$id              = absint( $id );
		$table_topics    = IAQ_Database::get_table_name( 'topics' );
		$table_questions = IAQ_Database::get_table_name( 'questions' );

		// Detach topic from questions.
		$wpdb->update( $table_questions, array( 'topic_id' => null ), array( 'topic_id' => $id ), array( '%s' ), array( '%d' ) );

		return (bool) $wpdb->delete( $table_topics, array( 'id' => $id ), array( '%d' ) );
	}

	/* =========================================================================
	 * QUESTIONS CRUD & FILTERING
	 * ========================================================================= */

	/**
	 * Retrieve single question by ID.
	 *
	 * @param int $id Question ID.
	 * @return object|null Question object with subject and topic names.
	 */
	public static function get_question( $id ) {
		global $wpdb;

		$table_questions = IAQ_Database::get_table_name( 'questions' );
		$table_subjects  = IAQ_Database::get_table_name( 'subjects' );
		$table_topics    = IAQ_Database::get_table_name( 'topics' );

		$query = $wpdb->prepare(
			"SELECT q.*, s.name AS subject_name, t.name AS topic_name
			 FROM {$table_questions} q
			 LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
			 LEFT JOIN {$table_topics} t ON q.topic_id = t.id
			 WHERE q.id = %d",
			absint( $id )
		);

		return $wpdb->get_row( $query );
	}

	/**
	 * Build SQL WHERE clause from filter args.
	 *
	 * @param array $args Filter parameters.
	 * @return array Array with 'where' SQL string and 'params' values for prepare.
	 */
	private static function build_where_clause( $args ) {
		$where_clauses = array( '1=1' );
		$params        = array();

		// Keyword search in question_text, explanation, options.
		if ( ! empty( $args['search'] ) ) {
			$search = '%' . trim( $args['search'] ) . '%';
			$where_clauses[] = '(q.question_text LIKE %s OR q.explanation LIKE %s OR q.option_a LIKE %s OR q.option_b LIKE %s OR q.option_c LIKE %s OR q.option_d LIKE %s)';
			$params[] = $search;
			$params[] = $search;
			$params[] = $search;
			$params[] = $search;
			$params[] = $search;
			$params[] = $search;
		}

		// Subject filter.
		if ( ! empty( $args['subject_id'] ) ) {
			$where_clauses[] = 'q.subject_id = %d';
			$params[]        = absint( $args['subject_id'] );
		}

		// Topic filter.
		if ( ! empty( $args['topic_id'] ) ) {
			$where_clauses[] = 'q.topic_id = %d';
			$params[]        = absint( $args['topic_id'] );
		}

		// Exam Category filter.
		if ( ! empty( $args['exam_category'] ) ) {
			$where_clauses[] = 'q.exam_category = %s';
			$params[]        = sanitize_text_field( $args['exam_category'] );
		}

		// Difficulty filter.
		if ( ! empty( $args['difficulty'] ) && in_array( $args['difficulty'], array( 'easy', 'medium', 'hard' ), true ) ) {
			$where_clauses[] = 'q.difficulty = %s';
			$params[]        = $args['difficulty'];
		}

		// Status filter.
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
	 * Retrieve questions with pagination, search, and multi-faceted filtering.
	 *
	 * @param array $args Query arguments.
	 * @return array List of question objects.
	 */
	public static function get_questions( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'search'        => '',
			'subject_id'    => 0,
			'topic_id'      => 0,
			'exam_category' => '',
			'difficulty'    => '',
			'status'        => '',
			'page'          => 1,
			'per_page'      => 20,
			'orderby'       => 'id',
			'order'         => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$table_questions = IAQ_Database::get_table_name( 'questions' );
		$table_subjects  = IAQ_Database::get_table_name( 'subjects' );
		$table_topics    = IAQ_Database::get_table_name( 'topics' );

		$clause = self::build_where_clause( $args );
		$where  = $clause['where'];
		$params = $clause['params'];

		// Ordering safeguards.
		$allowed_orderby = array( 'id', 'question_text', 'difficulty', 'marks', 'created_at', 'status' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'id';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$per_page = max( 1, absint( $args['per_page'] ) );
		$page     = max( 1, absint( $args['page'] ) );
		$offset   = ( $page - 1 ) * $per_page;

		$sql = "
			SELECT q.*, s.name AS subject_name, t.name AS topic_name
			FROM {$table_questions} q
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
	 * Count total questions matching filters.
	 *
	 * @param array $args Query arguments.
	 * @return int Total questions count.
	 */
	public static function get_question_count( $args = array() ) {
		global $wpdb;

		$table_questions = IAQ_Database::get_table_name( 'questions' );
		$clause          = self::build_where_clause( $args );
		$where           = $clause['where'];
		$params          = $clause['params'];

		$sql = "SELECT COUNT(*) FROM {$table_questions} q WHERE {$where}";

		if ( ! empty( $params ) ) {
			$prepared = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return (int) $wpdb->get_var( $prepared );
		}

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Save question (Insert or Update) with full bilingual and formatting validation.
	 *
	 * @param array $data Question data.
	 * @return int|WP_Error Question ID or WP_Error on validation failure.
	 */
	public static function save_question( $data ) {
		global $wpdb;

		$table = IAQ_Database::get_table_name( 'questions' );
		$id    = isset( $data['id'] ) ? absint( $data['id'] ) : 0;

		// Validation: Question text is mandatory.
		// Use wp_kses_post to allow sub/superscripts, math formatting, and rich Unicode/Hindi text.
		$question_text = isset( $data['question_text'] ) ? wp_kses_post( wp_unslash( $data['question_text'] ) ) : '';
		if ( empty( trim( strip_tags( $question_text ) ) ) && empty( trim( $question_text ) ) ) {
			return new WP_Error( 'empty_question', __( 'Question text is required.', 'indianagri-quiz-engine' ) );
		}

		// Options validation.
		$option_a = isset( $data['option_a'] ) ? wp_kses_post( wp_unslash( $data['option_a'] ) ) : '';
		$option_b = isset( $data['option_b'] ) ? wp_kses_post( wp_unslash( $data['option_b'] ) ) : '';
		$option_c = isset( $data['option_c'] ) ? wp_kses_post( wp_unslash( $data['option_c'] ) ) : '';
		$option_d = isset( $data['option_d'] ) ? wp_kses_post( wp_unslash( $data['option_d'] ) ) : '';

		if ( '' === trim( $option_a ) || '' === trim( $option_b ) || '' === trim( $option_c ) || '' === trim( $option_d ) ) {
			return new WP_Error( 'empty_options', __( 'All four answer options (A, B, C, D) are mandatory.', 'indianagri-quiz-engine' ) );
		}

		// Correct option validation.
		$correct_option = isset( $data['correct_option'] ) ? strtoupper( trim( sanitize_text_field( $data['correct_option'] ) ) ) : '';
		if ( ! in_array( $correct_option, array( 'A', 'B', 'C', 'D' ), true ) ) {
			return new WP_Error( 'invalid_correct_option', __( 'Correct option must be one of A, B, C, or D.', 'indianagri-quiz-engine' ) );
		}

		// Taxonomy and categorization.
		$subject_id    = absint( isset( $data['subject_id'] ) ? $data['subject_id'] : 0 );
		$topic_id      = ! empty( $data['topic_id'] ) ? absint( $data['topic_id'] ) : null;
		$exam_category = ! empty( $data['exam_category'] ) ? sanitize_text_field( wp_unslash( $data['exam_category'] ) ) : 'General Agriculture';

		$difficulty = isset( $data['difficulty'] ) ? sanitize_key( $data['difficulty'] ) : 'medium';
		if ( ! in_array( $difficulty, array( 'easy', 'medium', 'hard' ), true ) ) {
			$difficulty = 'medium';
		}

		$status = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'published';
		if ( ! in_array( $status, array( 'draft', 'published', 'archived' ), true ) ) {
			$status = 'published';
		}

		// Scoring fields.
		$marks          = isset( $data['marks'] ) ? max( 0.01, floatval( $data['marks'] ) ) : 1.00;
		$negative_marks = isset( $data['negative_marks'] ) ? max( 0.00, floatval( $data['negative_marks'] ) ) : 0.00;

		// Explanation.
		$explanation = isset( $data['explanation'] ) ? wp_kses_post( wp_unslash( $data['explanation'] ) ) : '';

		// Author.
		$created_by = isset( $data['created_by'] ) ? absint( $data['created_by'] ) : get_current_user_id();

		$payload = array(
			'subject_id'     => $subject_id,
			'topic_id'       => $topic_id,
			'exam_category'  => $exam_category,
			'difficulty'     => $difficulty,
			'question_text'  => $question_text,
			'option_a'       => $option_a,
			'option_b'       => $option_b,
			'option_c'       => $option_c,
			'option_d'       => $option_d,
			'correct_option' => $correct_option,
			'marks'          => $marks,
			'negative_marks' => $negative_marks,
			'explanation'    => $explanation,
			'status'         => $status,
		);

		$formats = array(
			'%d', // subject_id
			'%d', // topic_id
			'%s', // exam_category
			'%s', // difficulty
			'%s', // question_text
			'%s', // option_a
			'%s', // option_b
			'%s', // option_c
			'%s', // option_d
			'%s', // correct_option
			'%f', // marks
			'%f', // negative_marks
			'%s', // explanation
			'%s', // status
		);

		if ( $id > 0 ) {
			$payload['updated_at'] = current_time( 'mysql' );
			$formats[]             = '%s';

			$updated = $wpdb->update( $table, $payload, array( 'id' => $id ), $formats, array( '%d' ) );
			return ( false !== $updated ) ? $id : new WP_Error( 'db_error', __( 'Failed to update question.', 'indianagri-quiz-engine' ) );
		} else {
			$payload['created_by'] = $created_by;
			$payload['created_at'] = current_time( 'mysql' );
			$formats[]             = '%d';
			$formats[]             = '%s';

			$inserted = $wpdb->insert( $table, $payload, $formats );
			return ( false !== $inserted ) ? (int) $wpdb->insert_id : new WP_Error( 'db_error', __( 'Failed to create question.', 'indianagri-quiz-engine' ) );
		}
	}

	/**
	 * Delete a question by ID.
	 *
	 * @param int $id Question ID.
	 * @return bool
	 */
	public static function delete_question( $id ) {
		global $wpdb;

		$id              = absint( $id );
		$table_questions = IAQ_Database::get_table_name( 'questions' );
		$table_quiz_q    = IAQ_Database::get_table_name( 'quiz_questions' );

		// Remove association with quizzes.
		$wpdb->delete( $table_quiz_q, array( 'question_id' => $id ), array( '%d' ) );

		return (bool) $wpdb->delete( $table_questions, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Bulk delete questions by IDs.
	 *
	 * @param array $ids Question IDs.
	 * @return int Number of deleted rows.
	 */
	public static function bulk_delete_questions( $ids ) {
		global $wpdb;

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return 0;
		}

		$ids             = array_map( 'absint', $ids );
		$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$table_questions = IAQ_Database::get_table_name( 'questions' );
		$table_quiz_q    = IAQ_Database::get_table_name( 'quiz_questions' );

		// Detach from quizzes.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table_quiz_q} WHERE question_id IN ({$ids_placeholder})", $ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Delete from questions.
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_questions} WHERE id IN ({$ids_placeholder})", $ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $deleted;
	}

	/**
	 * Bulk update status of questions.
	 *
	 * @param array  $ids Question IDs.
	 * @param string $status New status ('published', 'draft', 'archived').
	 * @return int
	 */
	public static function bulk_update_status( $ids, $status ) {
		global $wpdb;

		if ( empty( $ids ) || ! in_array( $status, array( 'published', 'draft', 'archived' ), true ) ) {
			return 0;
		}

		$ids             = array_map( 'absint', $ids );
		$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$table           = IAQ_Database::get_table_name( 'questions' );

		$params   = array_merge( array( $status ), $ids );
		$prepared = $wpdb->prepare( "UPDATE {$table} SET status = %s WHERE id IN ({$ids_placeholder})", $params ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $wpdb->query( $prepared ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Find existing question by normalized question text and optional subject ID.
	 *
	 * @param string $question_text Question stem text.
	 * @param int    $subject_id    Optional subject ID to narrow lookup.
	 * @return int|null Existing question ID or null.
	 */
	public static function find_duplicate_question( $question_text, $subject_id = 0 ) {
		global $wpdb;

		$table      = IAQ_Database::get_table_name( 'questions' );
		$clean_text = trim( wp_strip_all_tags( (string) $question_text ) );

		if ( empty( $clean_text ) ) {
			return null;
		}

		if ( $subject_id > 0 ) {
			$sql = $wpdb->prepare(
				"SELECT id FROM {$table} WHERE subject_id = %d AND question_text = %s LIMIT 1",
				absint( $subject_id ),
				$clean_text
			);
			$found = $wpdb->get_var( $sql );
			if ( $found ) {
				return (int) $found;
			}
		}

		$sql = $wpdb->prepare(
			"SELECT id FROM {$table} WHERE question_text = %s LIMIT 1",
			$clean_text
		);
		$found = $wpdb->get_var( $sql );

		return $found ? (int) $found : null;
	}
}

