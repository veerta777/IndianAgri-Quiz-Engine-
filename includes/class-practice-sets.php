<?php
/**
 * Practice Sets Management & Multi-Hierarchy Browsing Service.
 * Implements Practice by Book, Practice by Subjects, and Practice by Exams.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IAQ_Practice_Sets
 */
class IAQ_Practice_Sets {

	/* =========================================================================
	 * 1. BOOKS MANAGEMENT (CRUD & QUIZ ATTACHMENT)
	 * ========================================================================= */

	/**
	 * Retrieve list of books with optional filters.
	 *
	 * @param array $args Filter and pagination arguments.
	 * @return array
	 */
	public static function get_books( $args = array() ) {
		global $wpdb;

		$table_books        = IAQ_Database::get_table_name( 'books' );
		$table_book_quizzes = IAQ_Database::get_table_name( 'book_quizzes' );
		$table_quizzes      = IAQ_Database::get_table_name( 'quizzes' );
		$table_subjects     = IAQ_Database::get_table_name( 'subjects' );
		$table_exams        = IAQ_Database::get_table_name( 'examinations' );

		$defaults = array(
			'status'         => '',
			'search'         => '',
			'access_type'    => '',
			'subject_id'     => 0,
			'examination_id' => 0,
			'orderby'        => 'display_order',
			'order'          => 'ASC',
			'limit'          => 100,
			'offset'         => 0,
		);

		$r = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $r['status'] ) ) {
			$where[]  = 'b.status = %s';
			$values[] = $r['status'];
		} else {
			$where[] = "b.status != 'archived'";
		}

		if ( ! empty( $r['access_type'] ) ) {
			$where[]  = 'b.access_type = %s';
			$values[] = $r['access_type'];
		}

		if ( ! empty( $r['subject_id'] ) ) {
			$where[]  = 'b.subject_id = %d';
			$values[] = absint( $r['subject_id'] );
		}

		if ( ! empty( $r['examination_id'] ) ) {
			$where[]  = 'b.examination_id = %d';
			$values[] = absint( $r['examination_id'] );
		}

		if ( ! empty( $r['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $r['search'] ) . '%';
			$where[]  = '(b.title LIKE %s OR b.author LIKE %s OR b.description LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		$where_clause = implode( ' AND ', $where );
		$allowed_order = array( 'id', 'title', 'display_order', 'created_at', 'author' );
		$orderby       = in_array( $r['orderby'], $allowed_order, true ) ? 'b.' . $r['orderby'] : 'b.display_order';
		$order         = 'DESC' === strtoupper( $r['order'] ) ? 'DESC' : 'ASC';
		$limit_clause  = sprintf( 'LIMIT %d OFFSET %d', absint( $r['limit'] ), absint( $r['offset'] ) );

		$query = "
			SELECT b.*,
				s.name AS subject_name,
				e.name AS examination_name,
				(
					SELECT COUNT(bq.id)
					FROM {$table_book_quizzes} bq
					INNER JOIN {$table_quizzes} q ON bq.quiz_id = q.id
					WHERE bq.book_id = b.id AND q.status = 'published'
				) AS practice_sets_count
			FROM {$table_books} b
			LEFT JOIN {$table_subjects} s ON b.subject_id = s.id
			LEFT JOIN {$table_exams} e ON b.examination_id = e.id
			WHERE {$where_clause}
			ORDER BY {$orderby} {$order}, b.id ASC
			{$limit_clause}
		";

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $query, $values );
		} else {
			$sql = $query;
		}

		$results = $wpdb->get_results( $sql );
		return is_array( $results ) ? $results : array();
	}

	/**
	 * Retrieve a single book by ID or slug.
	 *
	 * @param int|string $id_or_slug Book ID or slug.
	 * @return object|null
	 */
	public static function get_book( $id_or_slug ) {
		global $wpdb;

		$table_books        = IAQ_Database::get_table_name( 'books' );
		$table_book_quizzes = IAQ_Database::get_table_name( 'book_quizzes' );
		$table_quizzes      = IAQ_Database::get_table_name( 'quizzes' );
		$table_subjects     = IAQ_Database::get_table_name( 'subjects' );
		$table_exams        = IAQ_Database::get_table_name( 'examinations' );

		if ( is_numeric( $id_or_slug ) ) {
			$where = $wpdb->prepare( 'b.id = %d', absint( $id_or_slug ) );
		} else {
			$where = $wpdb->prepare( 'b.slug = %s', sanitize_title( $id_or_slug ) );
		}

		$query = "
			SELECT b.*,
				s.name AS subject_name,
				e.name AS examination_name,
				(
					SELECT COUNT(bq.id)
					FROM {$table_book_quizzes} bq
					INNER JOIN {$table_quizzes} q ON bq.quiz_id = q.id
					WHERE bq.book_id = b.id AND q.status = 'published'
				) AS practice_sets_count
			FROM {$table_books} b
			LEFT JOIN {$table_subjects} s ON b.subject_id = s.id
			LEFT JOIN {$table_exams} e ON b.examination_id = e.id
			WHERE {$where}
			LIMIT 1
		";

		return $wpdb->get_row( $query );
	}

	/**
	 * Save or update a book.
	 *
	 * @param array $data Book attributes.
	 * @return int|WP_Error Book ID on success.
	 */
	public static function save_book( $data ) {
		global $wpdb;

		$table_books = IAQ_Database::get_table_name( 'books' );

		$title = isset( $data['title'] ) ? sanitize_text_field( trim( $data['title'] ) ) : '';
		if ( empty( $title ) ) {
			return new WP_Error( 'missing_title', __( 'Book title is required.', 'indianagri-quiz-engine' ) );
		}

		$slug = isset( $data['slug'] ) && ! empty( $data['slug'] )
			? sanitize_title( $data['slug'] )
			: sanitize_title( $title );

		$id = isset( $data['id'] ) ? absint( $data['id'] ) : 0;

		// Ensure slug uniqueness.
		if ( $id > 0 ) {
			$slug_exists = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$table_books} WHERE slug = %s AND id != %d", $slug, $id )
			);
		} else {
			$slug_exists = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$table_books} WHERE slug = %s", $slug )
			);
		}

		if ( $slug_exists ) {
			$slug .= '-' . wp_rand( 100, 999 );
		}

		$row = array(
			'title'           => $title,
			'slug'            => $slug,
			'author'          => isset( $data['author'] ) ? sanitize_text_field( $data['author'] ) : '',
			'description'     => isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '',
			'cover_image_url' => isset( $data['cover_image_url'] ) ? esc_url_raw( $data['cover_image_url'] ) : '',
			'subject_id'      => isset( $data['subject_id'] ) ? absint( $data['subject_id'] ) : 0,
			'examination_id'  => isset( $data['examination_id'] ) ? absint( $data['examination_id'] ) : 0,
			'access_type'     => isset( $data['access_type'] ) && 'paid' === $data['access_type'] ? 'paid' : 'free',
			'display_order'   => isset( $data['display_order'] ) ? intval( $data['display_order'] ) : 0,
			'status'          => isset( $data['status'] ) && in_array( $data['status'], array( 'draft', 'published', 'archived' ), true ) ? $data['status'] : 'published',
		);

		if ( $id > 0 ) {
			$row['updated_at'] = current_time( 'mysql' );
			$wpdb->update( $table_books, $row, array( 'id' => $id ) );
			return $id;
		}

		$row['created_at'] = current_time( 'mysql' );
		$wpdb->insert( $table_books, $row );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Delete or archive a book.
	 *
	 * @param int  $book_id Book ID.
	 * @param bool $force   If true, deletes record. Otherwise sets status to 'archived'.
	 * @return bool
	 */
	public static function delete_book( $book_id, $force = false ) {
		global $wpdb;

		$table_books        = IAQ_Database::get_table_name( 'books' );
		$table_book_quizzes = IAQ_Database::get_table_name( 'book_quizzes' );
		$book_id            = absint( $book_id );

		if ( ! $book_id ) {
			return false;
		}

		if ( $force ) {
			// Detach quizzes without deleting the actual quiz records or attempts.
			$wpdb->delete( $table_book_quizzes, array( 'book_id' => $book_id ), array( '%d' ) );
			$wpdb->delete( $table_books, array( 'id' => $book_id ), array( '%d' ) );
			return true;
		}

		// Soft archive to protect historical assignments.
		$wpdb->update(
			$table_books,
			array( 'status' => 'archived', 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $book_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		return true;
	}

	/**
	 * Retrieve practice sets assigned to a specific book.
	 * Numbering dynamically starts at 1 based on administrator's sort order.
	 *
	 * @param int    $book_id Book ID.
	 * @param string $status  Quiz status filter ('published', 'draft', or '' for all).
	 * @param int    $user_id Optional student user ID for attempt status enrichment.
	 * @return array
	 */
	public static function get_book_quizzes( $book_id, $status = 'published', $user_id = 0 ) {
		global $wpdb;

		$table_book_quizzes = IAQ_Database::get_table_name( 'book_quizzes' );
		$table_quizzes      = IAQ_Database::get_table_name( 'quizzes' );
		$table_subjects     = IAQ_Database::get_table_name( 'subjects' );
		$table_attempts     = IAQ_Database::get_table_name( 'quiz_attempts' );

		$book_id = absint( $book_id );
		if ( ! $book_id ) {
			return array();
		}

		$where = array( 'bq.book_id = %d' );
		$values = array( $book_id );

		if ( ! empty( $status ) ) {
			$where[]  = 'q.status = %s';
			$values[] = $status;
		} else {
			$where[] = "q.status != 'archived'";
		}

		$where_clause = implode( ' AND ', $where );

		$query = "
			SELECT q.*,
				bq.sort_order AS book_sort_order,
				bq.id AS book_quiz_rel_id,
				s.name AS subject_name
			FROM {$table_book_quizzes} bq
			INNER JOIN {$table_quizzes} q ON bq.quiz_id = q.id
			LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
			WHERE {$where_clause}
			ORDER BY bq.sort_order ASC, q.id ASC
		";

		$rows = $wpdb->get_results( $wpdb->prepare( $query, $values ) );
		if ( empty( $rows ) ) {
			return array();
		}

		// Enrich each practice set with dynamic context numbering starting at 1.
		$user_id = $user_id > 0 ? $user_id : ( is_user_logged_in() ? get_current_user_id() : 0 );
		$counter = 1;

		foreach ( $rows as &$row ) {
			$row->practice_set_number = $counter++;
			$row->attempt_status      = 'not_attempted';
			$row->latest_percentage   = null;
			$row->latest_passed       = null;

			if ( $user_id > 0 ) {
				$latest_attempt = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT status, score, percentage, passed FROM {$table_attempts}
						 WHERE quiz_id = %d AND user_id = %d
						 ORDER BY id DESC LIMIT 1",
						$row->id,
						$user_id
					)
				);

				if ( $latest_attempt ) {
					$row->attempt_status    = $latest_attempt->status; // 'in_progress' or 'completed'
					$row->latest_percentage = $latest_attempt->percentage;
					$row->latest_passed     = (bool) $latest_attempt->passed;
				}
			}
		}

		return $rows;
	}

	/**
	 * Assign an existing practice set quiz to a book.
	 *
	 * @param int $book_id    Book ID.
	 * @param int $quiz_id    Quiz ID.
	 * @param int $sort_order Display order within book.
	 * @return bool
	 */
	public static function assign_quiz_to_book( $book_id, $quiz_id, $sort_order = 0 ) {
		global $wpdb;

		$table_book_quizzes = IAQ_Database::get_table_name( 'book_quizzes' );
		$book_id = absint( $book_id );
		$quiz_id = absint( $quiz_id );

		if ( ! $book_id || ! $quiz_id ) {
			return false;
		}

		// Check if relation already exists.
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_book_quizzes} WHERE book_id = %d AND quiz_id = %d",
				$book_id,
				$quiz_id
			)
		);

		if ( $existing_id ) {
			$wpdb->update(
				$table_book_quizzes,
				array( 'sort_order' => intval( $sort_order ) ),
				array( 'id' => $existing_id ),
				array( '%d' ),
				array( '%d' )
			);
			return true;
		}

		// If sort_order is 0, auto-assign next sequence number.
		if ( 0 === $sort_order ) {
			$max_order = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(sort_order) FROM {$table_book_quizzes} WHERE book_id = %d",
					$book_id
				)
			);
			$sort_order = $max_order + 1;
		}

		$wpdb->insert(
			$table_book_quizzes,
			array(
				'book_id'    => $book_id,
				'quiz_id'    => $quiz_id,
				'sort_order' => intval( $sort_order ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s' )
		);

		return true;
	}

	/**
	 * Remove a practice set from a book.
	 *
	 * @param int $book_id Book ID.
	 * @param int $quiz_id Quiz ID.
	 * @return bool
	 */
	public static function remove_quiz_from_book( $book_id, $quiz_id ) {
		global $wpdb;
		$table_book_quizzes = IAQ_Database::get_table_name( 'book_quizzes' );

		$wpdb->delete(
			$table_book_quizzes,
			array(
				'book_id' => absint( $book_id ),
				'quiz_id' => absint( $quiz_id ),
			),
			array( '%d', '%d' )
		);

		return true;
	}

	/**
	 * Reorder practice sets within a book.
	 *
	 * @param int   $book_id        Book ID.
	 * @param array $quiz_order_ids Array of quiz IDs in sequential order.
	 * @return bool
	 */
	public static function reorder_book_quizzes( $book_id, array $quiz_order_ids ) {
		global $wpdb;
		$table_book_quizzes = IAQ_Database::get_table_name( 'book_quizzes' );
		$book_id            = absint( $book_id );

		if ( ! $book_id || empty( $quiz_order_ids ) ) {
			return false;
		}

		$order = 1;
		foreach ( $quiz_order_ids as $qid ) {
			$wpdb->update(
				$table_book_quizzes,
				array( 'sort_order' => $order++ ),
				array(
					'book_id' => $book_id,
					'quiz_id' => absint( $qid ),
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
		}

		return true;
	}


	/* =========================================================================
	 * 2. PRACTICE BY SUBJECTS (SUBJECT CATALOG & ORDERED SETS)
	 * ========================================================================= */

	/**
	 * Retrieve subjects catalog with published practice sets count.
	 *
	 * @param array $args Filter parameters.
	 * @return array
	 */
	public static function get_subjects_catalog( $args = array() ) {
		global $wpdb;

		$table_subjects = IAQ_Database::get_table_name( 'subjects' );
		$table_quizzes  = IAQ_Database::get_table_name( 'quizzes' );
		$table_topics   = IAQ_Database::get_table_name( 'topics' );

		$search = ! empty( $args['search'] ) ? trim( $args['search'] ) : '';

		$where = '1=1';
		$params = array();

		if ( ! empty( $search ) ) {
			$where    .= ' AND (s.name LIKE %s OR s.description LIKE %s)';
			$params[]  = '%' . $wpdb->esc_like( $search ) . '%';
			$params[]  = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$query = "
			SELECT s.*,
				(
					SELECT COUNT(q.id)
					FROM {$table_quizzes} q
					WHERE q.subject_id = s.id
					  AND q.quiz_type = 'practice'
					  AND q.status = 'published'
				) AS practice_sets_count,
				(
					SELECT COUNT(t.id)
					FROM {$table_topics} t
					WHERE t.subject_id = s.id
				) AS topics_count
			FROM {$table_subjects} s
			WHERE {$where}
			ORDER BY s.name ASC
		";

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $query, $params );
		} else {
			$sql = $query;
		}

		$rows = $wpdb->get_results( $sql );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Retrieve practice sets for a specific subject.
	 * Numbering dynamically starts at 1 according to display_order.
	 *
	 * @param int    $subject_id Subject ID.
	 * @param string $status     Quiz status ('published' or all).
	 * @param int    $user_id    Optional user ID.
	 * @return array
	 */
	public static function get_subject_quizzes( $subject_id, $status = 'published', $user_id = 0 ) {
		global $wpdb;

		$table_quizzes  = IAQ_Database::get_table_name( 'quizzes' );
		$table_subjects = IAQ_Database::get_table_name( 'subjects' );
		$table_topics   = IAQ_Database::get_table_name( 'topics' );
		$table_attempts = IAQ_Database::get_table_name( 'quiz_attempts' );

		$subject_id = absint( $subject_id );
		if ( ! $subject_id ) {
			return array();
		}

		$where  = array( 'q.subject_id = %d', "q.quiz_type = 'practice'" );
		$values = array( $subject_id );

		if ( ! empty( $status ) ) {
			$where[]  = 'q.status = %s';
			$values[] = $status;
		} else {
			$where[] = "q.status != 'archived'";
		}

		$where_clause = implode( ' AND ', $where );

		$query = "
			SELECT q.*,
				s.name AS subject_name,
				t.name AS topic_name
			FROM {$table_quizzes} q
			LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
			LEFT JOIN {$table_topics} t ON q.topic_id = t.id
			WHERE {$where_clause}
			ORDER BY q.display_order ASC, q.id ASC
		";

		$rows = $wpdb->get_results( $wpdb->prepare( $query, $values ) );
		if ( empty( $rows ) ) {
			return array();
		}

		$user_id = $user_id > 0 ? $user_id : ( is_user_logged_in() ? get_current_user_id() : 0 );
		$counter = 1;

		foreach ( $rows as &$row ) {
			$row->practice_set_number = $counter++;
			$row->attempt_status      = 'not_attempted';
			$row->latest_percentage   = null;
			$row->latest_passed       = null;

			if ( $user_id > 0 ) {
				$latest_attempt = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT status, score, percentage, passed FROM {$table_attempts}
						 WHERE quiz_id = %d AND user_id = %d
						 ORDER BY id DESC LIMIT 1",
						$row->id,
						$user_id
					)
				);

				if ( $latest_attempt ) {
					$row->attempt_status    = $latest_attempt->status;
					$row->latest_percentage = $latest_attempt->percentage;
					$row->latest_passed     = (bool) $latest_attempt->passed;
				}
			}
		}

		return $rows;
	}

	/**
	 * Reorder practice sets within a subject.
	 *
	 * @param int   $subject_id     Subject ID.
	 * @param array $quiz_order_ids Sequential quiz IDs.
	 * @return bool
	 */
	public static function reorder_subject_quizzes( $subject_id, array $quiz_order_ids ) {
		global $wpdb;
		$table_quizzes = IAQ_Database::get_table_name( 'quizzes' );
		$subject_id    = absint( $subject_id );

		if ( ! $subject_id || empty( $quiz_order_ids ) ) {
			return false;
		}

		$order = 1;
		foreach ( $quiz_order_ids as $qid ) {
			$wpdb->update(
				$table_quizzes,
				array( 'display_order' => $order++ ),
				array(
					'id'         => absint( $qid ),
					'subject_id' => $subject_id,
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
		}

		return true;
	}


	/* =========================================================================
	 * 3. PRACTICE BY EXAMS (EXAMS CATALOG, SUBJECTS PER EXAM & SETS)
	 * ========================================================================= */

	/**
	 * Retrieve examinations catalog with subject count and available practice sets count.
	 *
	 * @param array $args Filter parameters.
	 * @return array
	 */
	public static function get_exams_catalog( $args = array() ) {
		global $wpdb;

		$table_exams         = IAQ_Database::get_table_name( 'examinations' );
		$table_exam_subjects = IAQ_Database::get_table_name( 'exam_subjects' );
		$table_quizzes       = IAQ_Database::get_table_name( 'quizzes' );
		$table_esq           = IAQ_Database::get_table_name( 'exam_subject_quizzes' );

		$search = ! empty( $args['search'] ) ? trim( $args['search'] ) : '';

		$where  = array( "e.status = 'active'" );
		$params = array();

		if ( ! empty( $search ) ) {
			$where[]  = '(e.name LIKE %s OR e.code LIKE %s OR e.description LIKE %s)';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$where_clause = implode( ' AND ', $where );

		$query = "
			SELECT e.*,
				(
					SELECT COUNT(es.id)
					FROM {$table_exam_subjects} es
					WHERE es.exam_id = e.id
				) AS assigned_subjects_count,
				(
					SELECT COUNT(DISTINCT q.id)
					FROM {$table_quizzes} q
					WHERE (
						q.id IN (SELECT esq.quiz_id FROM {$table_esq} esq WHERE esq.exam_id = e.id)
						OR (q.exam_category = e.code OR q.exam_category = e.name)
					)
					AND q.quiz_type = 'practice'
					AND q.status = 'published'
				) AS practice_sets_count
			FROM {$table_exams} e
			WHERE {$where_clause}
			ORDER BY e.display_order ASC, e.id ASC
		";

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $query, $params );
		} else {
			$sql = $query;
		}

		$rows = $wpdb->get_results( $sql );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Retrieve subjects configured for a specific examination.
	 * Displays only subjects actually assigned to this examination.
	 *
	 * @param int $exam_id Examination ID.
	 * @return array
	 */
	public static function get_exam_subjects( $exam_id ) {
		global $wpdb;

		$table_exam_subjects = IAQ_Database::get_table_name( 'exam_subjects' );
		$table_subjects      = IAQ_Database::get_table_name( 'subjects' );
		$table_exams         = IAQ_Database::get_table_name( 'examinations' );
		$table_quizzes       = IAQ_Database::get_table_name( 'quizzes' );
		$table_esq           = IAQ_Database::get_table_name( 'exam_subject_quizzes' );

		$exam_id = absint( $exam_id );
		if ( ! $exam_id ) {
			return array();
		}

		$exam = IAQ_Examinations::get_examination( $exam_id );
		$exam_code = $exam ? $exam->code : '';
		$exam_name = $exam ? $exam->name : '';

		$query = $wpdb->prepare(
			"SELECT s.*,
				es.display_order AS exam_subject_order,
				es.id AS exam_subject_rel_id,
				(
					SELECT COUNT(DISTINCT q.id)
					FROM {$table_quizzes} q
					WHERE q.subject_id = s.id
					  AND q.quiz_type = 'practice'
					  AND q.status = 'published'
					  AND (
						q.id IN (SELECT esq.quiz_id FROM {$table_esq} esq WHERE esq.exam_id = %d AND esq.subject_id = s.id)
						OR (q.exam_category = %s OR q.exam_category = %s)
					  )
				) AS practice_sets_count
			FROM {$table_exam_subjects} es
			INNER JOIN {$table_subjects} s ON es.subject_id = s.id
			WHERE es.exam_id = %d
			ORDER BY es.display_order ASC, s.name ASC",
			$exam_id,
			$exam_code,
			$exam_name,
			$exam_id
		);

		$rows = $wpdb->get_results( $query );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Assign a subject to an examination.
	 *
	 * @param int $exam_id       Examination ID.
	 * @param int $subject_id    Subject ID.
	 * @param int $display_order Display order.
	 * @return bool
	 */
	public static function assign_subject_to_exam( $exam_id, $subject_id, $display_order = 0 ) {
		global $wpdb;

		$table_exam_subjects = IAQ_Database::get_table_name( 'exam_subjects' );
		$exam_id    = absint( $exam_id );
		$subject_id = absint( $subject_id );

		if ( ! $exam_id || ! $subject_id ) {
			return false;
		}

		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_exam_subjects} WHERE exam_id = %d AND subject_id = %d",
				$exam_id,
				$subject_id
			)
		);

		if ( $existing_id ) {
			$wpdb->update(
				$table_exam_subjects,
				array( 'display_order' => intval( $display_order ) ),
				array( 'id' => $existing_id ),
				array( '%d' ),
				array( '%d' )
			);
			return true;
		}

		if ( 0 === $display_order ) {
			$max_order = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(display_order) FROM {$table_exam_subjects} WHERE exam_id = %d",
					$exam_id
				)
			);
			$display_order = $max_order + 1;
		}

		$wpdb->insert(
			$table_exam_subjects,
			array(
				'exam_id'       => $exam_id,
				'subject_id'    => $subject_id,
				'display_order' => intval( $display_order ),
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s' )
		);

		return true;
	}

	/**
	 * Remove a subject assignment from an examination.
	 *
	 * @param int $exam_id    Examination ID.
	 * @param int $subject_id Subject ID.
	 * @return bool
	 */
	public static function remove_subject_from_exam( $exam_id, $subject_id ) {
		global $wpdb;

		$table_exam_subjects = IAQ_Database::get_table_name( 'exam_subjects' );
		$table_esq           = IAQ_Database::get_table_name( 'exam_subject_quizzes' );

		$wpdb->delete(
			$table_exam_subjects,
			array(
				'exam_id'    => absint( $exam_id ),
				'subject_id' => absint( $subject_id ),
			),
			array( '%d', '%d' )
		);

		$wpdb->delete(
			$table_esq,
			array(
				'exam_id'    => absint( $exam_id ),
				'subject_id' => absint( $subject_id ),
			),
			array( '%d', '%d' )
		);

		return true;
	}

	/**
	 * Reorder subjects assigned within an examination.
	 *
	 * @param int   $exam_id            Examination ID.
	 * @param array $subject_order_ids  Array of subject IDs in display sequence.
	 * @return bool
	 */
	public static function reorder_exam_subjects( $exam_id, array $subject_order_ids ) {
		global $wpdb;
		$table_exam_subjects = IAQ_Database::get_table_name( 'exam_subjects' );
		$exam_id             = absint( $exam_id );

		if ( ! $exam_id || empty( $subject_order_ids ) ) {
			return false;
		}

		$order = 1;
		foreach ( $subject_order_ids as $sid ) {
			$wpdb->update(
				$table_exam_subjects,
				array( 'display_order' => $order++ ),
				array(
					'exam_id'    => $exam_id,
					'subject_id' => absint( $sid ),
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
		}

		return true;
	}

	/**
	 * Retrieve practice sets assigned to an Examination and Subject combination.
	 * Numbering dynamically starts at 1 based on administrator's sort order.
	 *
	 * @param int    $exam_id    Examination ID.
	 * @param int    $subject_id Subject ID.
	 * @param string $status     Status filter ('published' or all).
	 * @param int    $user_id    Optional user ID.
	 * @return array
	 */
	public static function get_exam_subject_quizzes( $exam_id, $subject_id, $status = 'published', $user_id = 0 ) {
		global $wpdb;

		$table_esq      = IAQ_Database::get_table_name( 'exam_subject_quizzes' );
		$table_quizzes  = IAQ_Database::get_table_name( 'quizzes' );
		$table_subjects = IAQ_Database::get_table_name( 'subjects' );
		$table_attempts = IAQ_Database::get_table_name( 'quiz_attempts' );

		$exam_id    = absint( $exam_id );
		$subject_id = absint( $subject_id );

		if ( ! $exam_id || ! $subject_id ) {
			return array();
		}

		$exam = IAQ_Examinations::get_examination( $exam_id );
		$exam_code = $exam ? $exam->code : '';
		$exam_name = $exam ? $exam->name : '';

		$status_clause = ! empty( $status ) ? $wpdb->prepare( 'AND q.status = %s', $status ) : "AND q.status != 'archived'";

		// First query explicitly mapped practice sets in table_esq.
		$query = $wpdb->prepare(
			"SELECT q.*,
				esq.sort_order AS exam_sort_order,
				esq.id AS exam_quiz_rel_id,
				s.name AS subject_name
			FROM {$table_esq} esq
			INNER JOIN {$table_quizzes} q ON esq.quiz_id = q.id
			LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
			WHERE esq.exam_id = %d AND esq.subject_id = %d {$status_clause}
			ORDER BY esq.sort_order ASC, q.id ASC",
			$exam_id,
			$subject_id
		);

		$rows = $wpdb->get_results( $query );

		// If no explicit relation rows, fallback to matching by exam_category & subject_id.
		if ( empty( $rows ) ) {
			$fallback_query = $wpdb->prepare(
				"SELECT q.*,
					q.display_order AS exam_sort_order,
					0 AS exam_quiz_rel_id,
					s.name AS subject_name
				FROM {$table_quizzes} q
				LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
				WHERE q.subject_id = %d
				  AND q.quiz_type = 'practice'
				  AND (q.exam_category = %s OR q.exam_category = %s)
				  {$status_clause}
				ORDER BY q.display_order ASC, q.id ASC",
				$subject_id,
				$exam_code,
				$exam_name
			);

			$rows = $wpdb->get_results( $fallback_query );
		}

		if ( empty( $rows ) ) {
			return array();
		}

		$user_id = $user_id > 0 ? $user_id : ( is_user_logged_in() ? get_current_user_id() : 0 );
		$counter = 1;

		foreach ( $rows as &$row ) {
			$row->practice_set_number = $counter++;
			$row->attempt_status      = 'not_attempted';
			$row->latest_percentage   = null;
			$row->latest_passed       = null;

			if ( $user_id > 0 ) {
				$latest_attempt = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT status, score, percentage, passed FROM {$table_attempts}
						 WHERE quiz_id = %d AND user_id = %d
						 ORDER BY id DESC LIMIT 1",
						$row->id,
						$user_id
					)
				);

				if ( $latest_attempt ) {
					$row->attempt_status    = $latest_attempt->status;
					$row->latest_percentage = $latest_attempt->percentage;
					$row->latest_passed     = (bool) $latest_attempt->passed;
				}
			}
		}

		return $rows;
	}

	/**
	 * Assign a practice set to an examination-subject combination.
	 *
	 * @param int $exam_id    Examination ID.
	 * @param int $subject_id Subject ID.
	 * @param int $quiz_id    Quiz ID.
	 * @param int $sort_order Display order.
	 * @return bool
	 */
	public static function assign_quiz_to_exam_subject( $exam_id, $subject_id, $quiz_id, $sort_order = 0 ) {
		global $wpdb;

		$table_esq  = IAQ_Database::get_table_name( 'exam_subject_quizzes' );
		$exam_id    = absint( $exam_id );
		$subject_id = absint( $subject_id );
		$quiz_id    = absint( $quiz_id );

		if ( ! $exam_id || ! $subject_id || ! $quiz_id ) {
			return false;
		}

		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_esq} WHERE exam_id = %d AND subject_id = %d AND quiz_id = %d",
				$exam_id,
				$subject_id,
				$quiz_id
			)
		);

		if ( $existing_id ) {
			$wpdb->update(
				$table_esq,
				array( 'sort_order' => intval( $sort_order ) ),
				array( 'id' => $existing_id ),
				array( '%d' ),
				array( '%d' )
			);
			return true;
		}

		if ( 0 === $sort_order ) {
			$max_order = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(sort_order) FROM {$table_esq} WHERE exam_id = %d AND subject_id = %d",
					$exam_id,
					$subject_id
				)
			);
			$sort_order = $max_order + 1;
		}

		$wpdb->insert(
			$table_esq,
			array(
				'exam_id'    => $exam_id,
				'subject_id' => $subject_id,
				'quiz_id'    => $quiz_id,
				'sort_order' => intval( $sort_order ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%d', '%s' )
		);

		return true;
	}

	/**
	 * Remove a practice set from an examination-subject combination.
	 *
	 * @param int $exam_id    Examination ID.
	 * @param int $subject_id Subject ID.
	 * @param int $quiz_id    Quiz ID.
	 * @return bool
	 */
	public static function remove_quiz_from_exam_subject( $exam_id, $subject_id, $quiz_id ) {
		global $wpdb;
		$table_esq = IAQ_Database::get_table_name( 'exam_subject_quizzes' );

		$wpdb->delete(
			$table_esq,
			array(
				'exam_id'    => absint( $exam_id ),
				'subject_id' => absint( $subject_id ),
				'quiz_id'    => absint( $quiz_id ),
			),
			array( '%d', '%d', '%d' )
		);

		return true;
	}

	/**
	 * Reorder practice sets within an examination-subject combination.
	 *
	 * @param int   $exam_id        Examination ID.
	 * @param int   $subject_id     Subject ID.
	 * @param array $quiz_order_ids Sequential quiz IDs.
	 * @return bool
	 */
	public static function reorder_exam_subject_quizzes( $exam_id, $subject_id, array $quiz_order_ids ) {
		global $wpdb;
		$table_esq  = IAQ_Database::get_table_name( 'exam_subject_quizzes' );
		$exam_id    = absint( $exam_id );
		$subject_id = absint( $subject_id );

		if ( ! $exam_id || ! $subject_id || empty( $quiz_order_ids ) ) {
			return false;
		}

		$order = 1;
		foreach ( $quiz_order_ids as $qid ) {
			$wpdb->update(
				$table_esq,
				array( 'sort_order' => $order++ ),
				array(
					'exam_id'    => $exam_id,
					'subject_id' => $subject_id,
					'quiz_id'    => absint( $qid ),
				),
				array( '%d' ),
				array( '%d', '%d', '%d' )
			);
		}

		return true;
	}


	/* =========================================================================
	 * 4. NAVIGATION & URL ROUTING HELPERS
	 * ========================================================================= */

	/**
	 * Generate Practice Sets landing or subview URL.
	 *
	 * @param string $view Optional subview ('books', 'book', 'subjects', 'subject', 'exams', 'exam', 'exam_subject').
	 * @param array  $params Optional query parameters.
	 * @return string
	 */
	public static function get_url( $view = '', $params = array() ) {
		$base_url = function_exists( 'get_permalink' ) ? get_permalink() : '/';
		$args     = array();

		// Preserve sandbox preview page parameter if active.
		if ( isset( $_GET['page'] ) && strpos( sanitize_key( $_GET['page'] ), 'iaq-preview' ) !== false ) {
			$args['page'] = sanitize_key( $_GET['page'] );
		}

		if ( ! empty( $view ) ) {
			$args['iaq_ps_view'] = sanitize_key( $view );
		}

		foreach ( $params as $k => $v ) {
			$args[ $k ] = sanitize_text_field( $v );
		}

		return add_query_arg( $args, $base_url );
	}
}
