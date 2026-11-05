<?php
/**
 * Previous Year Question Papers (PYQ) Domain Service.
 * Implements dedicated PYQ content type (quiz_type = 'previous_year')
 * while seamlessly reusing the existing Mock Test hierarchy for navigation & classification.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IAQ_PYQ {

	/**
	 * Retrieve all categories by reusing the existing Mock Test hierarchy.
	 *
	 * @param array $args Query filters.
	 * @return array
	 */
	public static function get_categories( $args = array() ) {
		return IAQ_Mock_Tests::get_categories( $args );
	}

	/**
	 * Retrieve a single category by ID or slug.
	 *
	 * @param int|string $id_or_slug ID or slug.
	 * @return object|null
	 */
	public static function get_category( $id_or_slug ) {
		return IAQ_Mock_Tests::get_category( $id_or_slug );
	}

	/**
	 * Retrieve hierarchy configuration for a category.
	 *
	 * @param string $category_slug Category slug.
	 * @return array
	 */
	public static function get_category_hierarchy_config( $category_slug ) {
		return IAQ_Mock_Tests::get_category_hierarchy_config( $category_slug );
	}

	/**
	 * Retrieve PYQ papers for any specific context in the existing hierarchy.
	 * Strictly enforces primary content type PREVIOUS_YEAR (quiz_type = 'previous_year').
	 *
	 * @param array $context Context parameters (category_id, exam_id, subject_id, vacancy_id, state_id, series_id, pyq_year, etc.)
	 * @param int   $user_id User ID for attempt status enrichment.
	 * @return array
	 */
	public static function get_context_pyq_papers( $context = array(), $user_id = 0 ) {
		global $wpdb;

		$table_quizzes   = IAQ_Database::get_table_name( 'quizzes' );
		$table_relations = IAQ_Database::get_table_name( 'mock_test_relations' );
		$table_attempts  = IAQ_Database::get_table_name( 'quiz_attempts' );

		$cat_id    = ! empty( $context['category_id'] ) ? absint( $context['category_id'] ) : 0;
		$exam_id   = ! empty( $context['exam_id'] ) ? absint( $context['exam_id'] ) : 0;
		$sub_id    = ! empty( $context['subject_id'] ) ? absint( $context['subject_id'] ) : 0;
		$vac_id    = ! empty( $context['vacancy_id'] ) ? absint( $context['vacancy_id'] ) : 0;
		$state_id  = ! empty( $context['state_id'] ) ? absint( $context['state_id'] ) : 0;
		$univ_id   = ! empty( $context['university_id'] ) ? absint( $context['university_id'] ) : 0;
		$series_id = ! empty( $context['series_id'] ) ? absint( $context['series_id'] ) : 0;
		$year      = ! empty( $context['year'] ) ? absint( $context['year'] ) : ( ! empty( $context['pyq_year'] ) ? absint( $context['pyq_year'] ) : 0 );
		$user_id   = $user_id ? absint( $user_id ) : get_current_user_id();

		// Fetch relational matches from bridge table
		$rel_where = array( '1=1' );
		$rel_vals  = array();

		if ( $cat_id > 0 ) {
			$rel_where[] = 'r.category_id = %d';
			$rel_vals[]  = $cat_id;
		}
		if ( $exam_id > 0 ) {
			$rel_where[] = '(r.exam_id = %d OR r.exam_id IS NULL)';
			$rel_vals[]  = $exam_id;
		}
		if ( $sub_id > 0 ) {
			$rel_where[] = '(r.subject_id = %d OR r.subject_id IS NULL)';
			$rel_vals[]  = $sub_id;
		}
		if ( $vac_id > 0 ) {
			$rel_where[] = '(r.vacancy_id = %d OR r.vacancy_id IS NULL)';
			$rel_vals[]  = $vac_id;
		}
		if ( $state_id > 0 ) {
			$rel_where[] = '(r.state_id = %d OR r.state_id IS NULL)';
			$rel_vals[]  = $state_id;
		}
		if ( $univ_id > 0 ) {
			$rel_where[] = '(r.university_id = %d OR r.university_id IS NULL)';
			$rel_vals[]  = $univ_id;
		}
		if ( $series_id > 0 ) {
			$rel_where[] = 'r.series_id = %d';
			$rel_vals[]  = $series_id;
		}
		if ( $year > 0 ) {
			$rel_where[] = 'q.pyq_year = %d';
			$rel_vals[]  = $year;
		}

		$rel_where_clause = implode( ' AND ', $rel_where );

		$status_filter = ! empty( $context['status'] ) ? sanitize_key( $context['status'] ) : 'published';
		$status_clause = 'all' === $status_filter ? " AND q.status != 'archived'" : " AND q.status = '{$status_filter}'";

		// Query joined PYQ papers (strictly previous_year)
		$query = "
			SELECT q.*, r.id AS pyq_rel_id, r.sort_order AS pyq_sort_order, r.series_id AS pyq_series_id,
			       r.category_id AS hierarchy_category_id, r.exam_id AS hierarchy_exam_id, r.subject_id AS hierarchy_subject_id
			FROM {$table_quizzes} q
			INNER JOIN {$table_relations} r ON q.id = r.quiz_id
			WHERE q.quiz_type = 'previous_year'
			  {$status_clause}
			  AND {$rel_where_clause}
			ORDER BY q.pyq_year DESC, r.sort_order ASC, q.display_order ASC, q.id DESC
		";

		if ( ! empty( $rel_vals ) ) {
			$sql = $wpdb->prepare( $query, $rel_vals );
		} else {
			$sql = $query;
		}

		$quizzes = $wpdb->get_results( $sql );

		// Fallback: If no relation records exist in bridge table yet, match directly from wp_iaq_quizzes
		if ( empty( $quizzes ) ) {
			$direct_where = array( "q.quiz_type = 'previous_year'" );
			if ( 'all' !== $status_filter ) {
				$direct_where[] = "q.status = '{$status_filter}'";
			} else {
				$direct_where[] = "q.status != 'archived'";
			}
			$direct_vals = array();

			if ( $year > 0 ) {
				$direct_where[] = 'q.pyq_year = %d';
				$direct_vals[]  = $year;
			}

			if ( $sub_id > 0 ) {
				$direct_where[] = 'q.subject_id = %d';
				$direct_vals[]  = $sub_id;
			}

			if ( $exam_id > 0 ) {
				$exam_obj = IAQ_Examinations::get_examination( $exam_id );
				if ( $exam_obj ) {
					$direct_where[] = '(q.exam_category = %s OR q.exam_category = %s)';
					$direct_vals[]  = $exam_obj->code;
					$direct_vals[]  = $exam_obj->name;
				}
			}

			$dir_where_clause = implode( ' AND ', $direct_where );
			$dir_query        = "SELECT q.*, 0 AS pyq_rel_id, q.display_order AS pyq_sort_order, 0 AS pyq_series_id FROM {$table_quizzes} q WHERE {$dir_where_clause} ORDER BY q.pyq_year DESC, q.display_order ASC, q.id DESC";

			if ( ! empty( $direct_vals ) ) {
				$quizzes = $wpdb->get_results( $wpdb->prepare( $dir_query, $direct_vals ) );
			} else {
				$quizzes = $wpdb->get_results( $dir_query );
			}
		}

		if ( ! is_array( $quizzes ) ) {
			return array();
		}

		// Ensure uniqueness by quiz ID
		$unique = array();
		$seen   = array();
		foreach ( $quizzes as $qz ) {
			if ( empty( $qz ) ) {
				continue;
			}
			$qid = (int) $qz->id;
			if ( isset( $seen[ $qid ] ) ) {
				continue;
			}
			$seen[ $qid ] = true;
			$unique[]     = $qz;
		}

		// Enrich each PYQ with attempt status, score, and access rules
		foreach ( $unique as &$pq ) {
			$pq->attempt_status    = 'not_attempted';
			$pq->latest_percentage = null;
			$pq->latest_score      = null;
			$pq->latest_passed     = null;

			if ( $user_id > 0 ) {
				$latest_attempt = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT status, score, percentage, passed, total_marks 
						 FROM {$table_attempts} 
						 WHERE quiz_id = %d AND user_id = %d 
						 ORDER BY id DESC LIMIT 1",
						$pq->id,
						$user_id
					)
				);

				if ( $latest_attempt ) {
					$pq->attempt_status    = $latest_attempt->status;
					$pq->latest_percentage = $latest_attempt->percentage ? round( floatval( $latest_attempt->percentage ), 1 ) : 0;
					$pq->latest_score      = $latest_attempt->score ? round( floatval( $latest_attempt->score ), 2 ) : 0;
					$pq->latest_passed     = (bool) $latest_attempt->passed;
				}
			}

			// Access control check
			$can_solve_result = class_exists( 'IAQ_Access_Control' )
				? IAQ_Access_Control::can_attempt_quiz( $user_id, $pq->id )
				: array( 'allowed' => true );

			$pq->can_solve      = ! empty( $can_solve_result['allowed'] );
			$pq->lock_reason    = ! empty( $can_solve_result['reason'] ) ? $can_solve_result['reason'] : '';
			$pq->is_locked      = ! $pq->can_solve;
			$pq->is_exam_mode   = ( 'after_submission' === $pq->show_answers_mode );
		}

		return $unique;
	}

	/**
	 * Save or update a PYQ Paper.
	 *
	 * @param array $data Paper attributes.
	 * @return int|WP_Error Quiz ID on success.
	 */
	public static function save_paper( $data ) {
		global $wpdb;

		if ( empty( $data['title'] ) ) {
			return new WP_Error( 'missing_title', __( 'PYQ Paper Title is required.', 'indianagri-quiz-engine' ) );
		}

		// Strictly enforce previous_year quiz type
		$data['quiz_type'] = 'previous_year';

		// Preserve original sequence by default (no shuffling unless explicitly requested)
		if ( ! isset( $data['shuffle_questions'] ) ) {
			$data['shuffle_questions'] = 0;
		}

		$data['pyq_year']    = ! empty( $data['pyq_year'] ) ? absint( $data['pyq_year'] ) : ( ! empty( $data['year'] ) ? absint( $data['year'] ) : date( 'Y' ) );
		$data['pyq_session'] = ! empty( $data['pyq_session'] ) ? sanitize_text_field( $data['pyq_session'] ) : '';

		$quiz_id = IAQ_Quiz_Builder::save_quiz( $data );
		if ( is_wp_error( $quiz_id ) || ! $quiz_id ) {
			return $quiz_id;
		}

		// Connect to the existing Mock Test hierarchy
		$category_id = ! empty( $data['category_id'] ) ? absint( $data['category_id'] ) : 0;
		if ( $category_id > 0 ) {
			$table_rel = IAQ_Database::get_table_name( 'mock_test_relations' );
			$wpdb->delete( $table_rel, array( 'quiz_id' => $quiz_id ), array( '%d' ) );

			$context = array(
				'category_id'   => $category_id,
				'exam_id'       => ! empty( $data['exam_id'] ) ? absint( $data['exam_id'] ) : null,
				'sub_exam_id'   => ! empty( $data['sub_exam_id'] ) ? absint( $data['sub_exam_id'] ) : null,
				'subject_id'    => ! empty( $data['subject_id'] ) ? absint( $data['subject_id'] ) : null,
				'vacancy_id'    => ! empty( $data['vacancy_id'] ) ? absint( $data['vacancy_id'] ) : null,
				'state_id'      => ! empty( $data['state_id'] ) ? absint( $data['state_id'] ) : null,
				'university_id' => ! empty( $data['university_id'] ) ? absint( $data['university_id'] ) : null,
				'series_id'     => ! empty( $data['series_id'] ) ? absint( $data['series_id'] ) : null,
				'sort_order'    => isset( $data['display_order'] ) ? intval( $data['display_order'] ) : 0,
			);

			IAQ_Mock_Tests::attach_quiz_to_mock_context( $quiz_id, $context );
		}

		return $quiz_id;
	}

	/**
	 * Delete or archive a PYQ paper.
	 *
	 * @param int  $quiz_id Quiz ID.
	 * @param bool $force_delete Whether to permanently delete.
	 * @return bool
	 */
	public static function delete_paper( $quiz_id, $force_delete = true ) {
		global $wpdb;
		$quiz_id = absint( $quiz_id );
		if ( ! $quiz_id ) {
			return false;
		}

		// Detach from hierarchy bridge table
		$table_rel = IAQ_Database::get_table_name( 'mock_test_relations' );
		$wpdb->delete( $table_rel, array( 'quiz_id' => $quiz_id ), array( '%d' ) );

		if ( $force_delete ) {
			return IAQ_Quiz_Builder::delete_quiz( $quiz_id );
		} else {
			return IAQ_Quiz_Builder::archive_quiz( $quiz_id );
		}
	}

	/**
	 * Retrieve all available distinct PYQ examination years.
	 *
	 * @return array List of integer years in descending order.
	 */
	public static function get_available_years() {
		global $wpdb;
		$table_quizzes = IAQ_Database::get_table_name( 'quizzes' );
		$years = $wpdb->get_col(
			"SELECT DISTINCT pyq_year 
			 FROM {$table_quizzes} 
			 WHERE quiz_type = 'previous_year' 
			   AND pyq_year IS NOT NULL 
			   AND pyq_year > 1990 
			   AND status != 'archived'
			 ORDER BY pyq_year DESC"
		);
		return is_array( $years ) ? array_map( 'intval', $years ) : array();
	}

	/**
	 * Retrieve admin paginated PYQ papers list.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function get_admin_papers( $args = array() ) {
		global $wpdb;

		$table_quizzes   = IAQ_Database::get_table_name( 'quizzes' );
		$table_relations = IAQ_Database::get_table_name( 'mock_test_relations' );
		$table_cats      = IAQ_Database::get_table_name( 'mock_categories' );
		$table_exams     = IAQ_Database::get_table_name( 'examinations' );
		$table_subjects  = IAQ_Database::get_table_name( 'subjects' );

		$defaults = array(
			'search'      => '',
			'year'        => 0,
			'category_id' => 0,
			'status'      => 'all',
			'orderby'     => 'pyq_year',
			'order'       => 'DESC',
			'limit'       => 20,
			'offset'      => 0,
		);
		$r = wp_parse_args( $args, $defaults );

		$where  = array( "q.quiz_type = 'previous_year'" );
		$values = array();

		if ( ! empty( $r['status'] ) && 'all' !== $r['status'] ) {
			$where[]  = 'q.status = %s';
			$values[] = $r['status'];
		} else {
			$where[] = "q.status != 'archived'";
		}

		if ( ! empty( $r['year'] ) ) {
			$where[]  = 'q.pyq_year = %d';
			$values[] = absint( $r['year'] );
		}

		if ( ! empty( $r['category_id'] ) ) {
			$where[]  = 'r.category_id = %d';
			$values[] = absint( $r['category_id'] );
		}

		if ( ! empty( $r['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $r['search'] ) . '%';
			$where[]  = '(q.title LIKE %s OR q.description LIKE %s)';
			$values[] = $like;
			$values[] = $like;
		}

		$where_clause = implode( ' AND ', $where );
		$limit_clause = sprintf( 'LIMIT %d OFFSET %d', absint( $r['limit'] ), absint( $r['offset'] ) );

		$query = "
			SELECT q.*, 
			       r.category_id AS hierarchy_category_id,
			       r.exam_id AS hierarchy_exam_id,
			       r.subject_id AS hierarchy_subject_id,
			       c.name AS category_name,
			       e.name AS exam_name,
			       s.name AS subject_name
			FROM {$table_quizzes} q
			LEFT JOIN {$table_relations} r ON q.id = r.quiz_id
			LEFT JOIN {$table_cats} c ON r.category_id = c.id
			LEFT JOIN {$table_exams} e ON r.exam_id = e.id
			LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
			WHERE {$where_clause}
			GROUP BY q.id
			ORDER BY q.pyq_year DESC, q.id DESC
			{$limit_clause}
		";

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $query, $values );
		} else {
			$sql = $query;
		}

		$papers = $wpdb->get_results( $sql );
		return is_array( $papers ) ? $papers : array();
	}

	/**
	 * Retrieve total count of PYQ papers matching filters.
	 *
	 * @param array $args Query arguments.
	 * @return int
	 */
	public static function get_admin_papers_count( $args = array() ) {
		global $wpdb;

		$table_quizzes   = IAQ_Database::get_table_name( 'quizzes' );
		$table_relations = IAQ_Database::get_table_name( 'mock_test_relations' );

		$where  = array( "q.quiz_type = 'previous_year'" );
		$values = array();

		if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
			$where[]  = 'q.status = %s';
			$values[] = $args['status'];
		} else {
			$where[] = "q.status != 'archived'";
		}

		if ( ! empty( $args['year'] ) ) {
			$where[]  = 'q.pyq_year = %d';
			$values[] = absint( $args['year'] );
		}

		if ( ! empty( $args['category_id'] ) ) {
			$where[]  = 'r.category_id = %d';
			$values[] = absint( $args['category_id'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(q.title LIKE %s OR q.description LIKE %s)';
			$values[] = $like;
			$values[] = $like;
		}

		$where_clause = implode( ' AND ', $where );
		$query        = "SELECT COUNT(DISTINCT q.id) FROM {$table_quizzes} q LEFT JOIN {$table_relations} r ON q.id = r.quiz_id WHERE {$where_clause}";

		if ( ! empty( $values ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $query, $values ) );
		}
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Construct frontend URLs preserving navigation query parameters.
	 *
	 * @param string $subview View identifier.
	 * @param array  $params Additional parameters.
	 * @return string
	 */
	public static function get_url( $subview = '', $params = array() ) {
		$base_url = get_permalink();
		if ( ! $base_url ) {
			$base_url = home_url( '/previous-year-papers/' );
		}

		$clean_params = array();
		if ( ! empty( $subview ) && 'landing' !== $subview ) {
			$clean_params['iaq_pyq_view'] = sanitize_key( $subview );
		}

		foreach ( $params as $k => $v ) {
			if ( null !== $v && '' !== $v ) {
				$clean_params[ $k ] = sanitize_text_field( $v );
			}
		}

		return ! empty( $clean_params ) ? add_query_arg( $clean_params, $base_url ) : $base_url;
	}
}
