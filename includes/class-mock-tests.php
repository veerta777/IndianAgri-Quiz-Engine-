<?php
/**
 * Mock Tests Management & Multi-Level Hierarchy Domain Service.
 * Implements the 10 Main Mock Test Categories, Examinations, Subjects,
 * Vacancies, States, Universities, and Mock Series.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IAQ_Mock_Tests {

	/* =========================================================================
	 * 1. CATEGORY MANAGEMENT (10 Main Categories & Custom)
	 * ========================================================================= */

	/**
	 * Retrieve all mock categories.
	 *
	 * @param array $args Query filters.
	 * @return array
	 */
	public static function get_categories( $args = array() ) {
		global $wpdb;
		$table_cats = IAQ_Database::get_table_name( 'mock_categories' );

		$defaults = array(
			'status'  => 'active',
			'search'  => '',
			'orderby' => 'display_order',
			'order'   => 'ASC',
		);
		$r = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $r['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $r['status'];
		}

		if ( ! empty( $r['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $r['search'] ) . '%';
			$where[]  = '(name LIKE %s OR code LIKE %s OR description LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		$where_clause = implode( ' AND ', $where );
		$orderby      = in_array( $r['orderby'], array( 'id', 'name', 'display_order', 'created_at' ), true ) ? $r['orderby'] : 'display_order';
		$order        = 'DESC' === strtoupper( $r['order'] ) ? 'DESC' : 'ASC';

		$query = "SELECT * FROM {$table_cats} WHERE {$where_clause} ORDER BY {$orderby} {$order}";

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $query, $values );
		} else {
			$sql = $query;
		}

		$rows = $wpdb->get_results( $sql );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Retrieve a single category by ID or slug.
	 *
	 * @param int|string $id_or_slug ID or slug.
	 * @return object|null
	 */
	public static function get_category( $id_or_slug ) {
		global $wpdb;
		$table_cats = IAQ_Database::get_table_name( 'mock_categories' );

		$row = is_numeric( $id_or_slug )
			? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_cats} WHERE id = %d", absint( $id_or_slug ) ) )
			: $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_cats} WHERE slug = %s", sanitize_title( $id_or_slug ) ) );
		if ( $row ) {
			$row->description   = isset( $row->description ) ? $row->description : '';
			$row->code          = isset( $row->code ) ? $row->code : '';
			$row->badge_text    = isset( $row->badge_text ) ? $row->badge_text : '';
			$row->icon          = isset( $row->icon ) ? $row->icon : 'dashicons-awards';
			$row->display_order = isset( $row->display_order ) ? (int) $row->display_order : 1;
			$row->status        = isset( $row->status ) ? $row->status : 'active';
		}
		return $row ?: null;
	}

	/**
	 * Save or update a mock category.
	 *
	 * @param array $data Category attributes.
	 * @return int|WP_Error
	 */
	public static function save_category( $data ) {
		global $wpdb;
		$table_cats = IAQ_Database::get_table_name( 'mock_categories' );

		if ( empty( $data['name'] ) ) {
			return new WP_Error( 'missing_name', __( 'Category name is required.', 'indianagri-quiz-engine' ) );
		}

		$name  = sanitize_text_field( $data['name'] );
		$slug  = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$code  = ! empty( $data['code'] ) ? sanitize_text_field( $data['code'] ) : '';
		$desc  = isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '';
		$icon  = ! empty( $data['icon'] ) ? sanitize_text_field( $data['icon'] ) : 'dashicons-awards';
		$badge = ! empty( $data['badge_text'] ) ? sanitize_text_field( $data['badge_text'] ) : '';
		$order = isset( $data['display_order'] ) ? intval( $data['display_order'] ) : 0;
		$stat  = ! empty( $data['status'] ) && in_array( $data['status'], array( 'active', 'inactive' ), true ) ? $data['status'] : 'active';

		$cat_id = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;

		if ( $cat_id > 0 ) {
			$wpdb->update(
				$table_cats,
				array(
					'name'          => $name,
					'slug'          => $slug,
					'code'          => $code,
					'description'   => $desc,
					'icon'          => $icon,
					'badge_text'    => $badge,
					'display_order' => $order,
					'status'        => $stat,
					'updated_at'    => current_time( 'mysql' ),
				),
				array( 'id' => $cat_id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ),
				array( '%d' )
			);
			return $cat_id;
		}

		$wpdb->insert(
			$table_cats,
			array(
				'name'          => $name,
				'slug'          => $slug,
				'code'          => $code,
				'description'   => $desc,
				'icon'          => $icon,
				'badge_text'    => $badge,
				'display_order' => $order,
				'status'        => $stat,
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Delete a mock category.
	 *
	 * @param int $category_id Category ID.
	 * @return bool
	 */
	public static function delete_category( $category_id ) {
		global $wpdb;
		$table_cats = IAQ_Database::get_table_name( 'mock_categories' );
		$category_id = absint( $category_id );

		if ( ! $category_id ) {
			return false;
		}

		$wpdb->delete( $table_cats, array( 'id' => $category_id ), array( '%d' ) );
		return true;
	}


	/* =========================================================================
	 * 2. EXAMINATIONS UNDER CATEGORY & SUB-EXAMS
	 * ========================================================================= */

	/**
	 * Retrieve examinations under a specific category.
	 *
	 * @param int $category_id Category ID.
	 * @param int $parent_id   Parent exam ID (0 for top-level exams).
	 * @return array
	 */
	public static function get_category_exams( $category_id, $parent_id = 0 ) {
		global $wpdb;
		$table_exams = IAQ_Database::get_table_name( 'examinations' );
		$category_id = absint( $category_id );
		$parent_id   = absint( $parent_id );

		// Query by category_id or fallback matching category code/slug
		$cat = self::get_category( $category_id );
		$cat_code = $cat ? $cat->code : '';
		$cat_slug = $cat ? $cat->slug : '';

		$where  = array( "status = 'active'" );
		$values = array();

		if ( $category_id > 0 ) {
			$where[] = '(category_id = %d OR category_id IS NULL)';
			$values[] = $category_id;
		}

		if ( $parent_id > 0 ) {
			$where[] = 'parent_id = %d';
			$values[] = $parent_id;
		} else {
			$where[] = '(parent_id = 0 OR parent_id IS NULL)';
		}

		$where_clause = implode( ' AND ', $where );
		$sql = $wpdb->prepare( "SELECT * FROM {$table_exams} WHERE {$where_clause} ORDER BY display_order ASC, name ASC", $values );
		$rows = $wpdb->get_results( $sql );

		// Filter for contextual relevance
		$filtered = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $ex ) {
				// If explicitly mapped to this category
				if ( ! empty( $ex->category_id ) && (int) $ex->category_id === $category_id ) {
					$filtered[] = $ex;
					continue;
				}
				// If not explicitly mapped, check category slug / code
				if ( empty( $ex->category_id ) ) {
					if ( 'icar-exams' === $cat_slug && false !== stripos( $ex->name . ' ' . $ex->code, 'ICAR' ) ) {
						$filtered[] = $ex;
					} elseif ( 'cuet' === $cat_slug && false !== stripos( $ex->name . ' ' . $ex->code, 'CUET' ) ) {
						$filtered[] = $ex;
					} elseif ( 'up-agriculture-exams' === $cat_slug && ( false !== stripos( $ex->name, 'UP' ) || false !== stripos( $ex->code, 'UP' ) ) ) {
						$filtered[] = $ex;
					} elseif ( 'banking-afo' === $cat_slug && ( false !== stripos( $ex->name, 'IBPS' ) || false !== stripos( $ex->name, 'Banking' ) ) ) {
						$filtered[] = $ex;
					} elseif ( 'general-agriculture' === $cat_slug && false !== stripos( $ex->name, 'General' ) ) {
						$filtered[] = $ex;
					} elseif ( empty( $cat_slug ) || 'other-govt-exams' === $cat_slug ) {
						$filtered[] = $ex;
					}
				}
			}
		}

		// Fallback: if no category-specific exams found, return top examinations
		if ( empty( $filtered ) && $category_id > 0 ) {
			$all_active = IAQ_Examinations::get_examinations( array( 'status' => 'active' ) );
			return is_array( $all_active ) ? $all_active : array();
		}

		return $filtered;
	}

	/**
	 * Retrieve sub-examinations (e.g., ARS & NET under ASRB).
	 *
	 * @param int $parent_exam_id Parent examination ID.
	 * @return array
	 */
	public static function get_sub_exams( $parent_exam_id ) {
		global $wpdb;
		$table_exams = IAQ_Database::get_table_name( 'examinations' );
		$parent_exam_id = absint( $parent_exam_id );

		if ( ! $parent_exam_id ) {
			return array();
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_exams} WHERE parent_id = %d AND status = 'active' ORDER BY display_order ASC, name ASC",
				$parent_exam_id
			)
		);
		return is_array( $rows ) ? $rows : array();
	}


	/* =========================================================================
	 * 3. STATES & UNIVERSITIES MANAGEMENT
	 * ========================================================================= */

	/**
	 * Retrieve states list.
	 *
	 * @param array $args Query filters.
	 * @return array
	 */
	public static function get_states( $args = array() ) {
		global $wpdb;
		$table_su = IAQ_Database::get_table_name( 'mock_states_universities' );

		$defaults = array(
			'status'  => 'active',
			'orderby' => 'display_order',
			'order'   => 'ASC',
		);
		$r = wp_parse_args( $args, $defaults );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_su} WHERE entity_type = 'state' AND status = %s ORDER BY display_order ASC, name ASC",
				$r['status']
			)
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Retrieve universities list.
	 *
	 * @param array $args Query filters.
	 * @return array
	 */
	public static function get_universities( $args = array() ) {
		global $wpdb;
		$table_su = IAQ_Database::get_table_name( 'mock_states_universities' );

		$defaults = array(
			'status'  => 'active',
			'orderby' => 'display_order',
			'order'   => 'ASC',
		);
		$r = wp_parse_args( $args, $defaults );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_su} WHERE entity_type = 'university' AND status = %s ORDER BY display_order ASC, name ASC",
				$r['status']
			)
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Retrieve single state or university entity by ID.
	 *
	 * @param int $id Entity ID.
	 * @return object|null
	 */
	public static function get_state_university( $id ) {
		global $wpdb;
		$table_su = IAQ_Database::get_table_name( 'mock_states_universities' );
		$id       = absint( $id );
		if ( ! $id ) {
			return null;
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_su} WHERE id = %d", $id ) );
		if ( $row ) {
			$type             = ! empty( $row->entity_type ) ? $row->entity_type : ( ! empty( $row->type ) ? $row->type : 'state' );
			$row->type        = $type;
			$row->entity_type = $type;
			$row->code        = isset( $row->code ) ? $row->code : '';
			$row->description = isset( $row->description ) ? $row->description : '';
		}
		return $row ?: null;
	}

	/**
	 * Save a state or university entity.
	 *
	 * @param array $data Entity attributes.
	 * @return int|WP_Error
	 */
	public static function save_state_university( $data ) {
		global $wpdb;
		$table_su = IAQ_Database::get_table_name( 'mock_states_universities' );

		if ( empty( $data['name'] ) ) {
			return new WP_Error( 'missing_name', __( 'Name is required.', 'indianagri-quiz-engine' ) );
		}

		$raw_type = ! empty( $data['entity_type'] ) ? $data['entity_type'] : ( ! empty( $data['type'] ) ? $data['type'] : 'state' );
		$type     = in_array( $raw_type, array( 'state', 'university' ), true ) ? $raw_type : 'state';
		$name     = sanitize_text_field( $data['name'] );
		$slug     = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$code     = ! empty( $data['code'] ) ? sanitize_text_field( $data['code'] ) : '';
		$desc     = isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '';
		$order    = isset( $data['display_order'] ) ? intval( $data['display_order'] ) : 0;
		$stat     = ! empty( $data['status'] ) && in_array( $data['status'], array( 'active', 'inactive' ), true ) ? $data['status'] : 'active';
		$id       = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;

		if ( $id > 0 ) {
			$wpdb->update(
				$table_su,
				array(
					'entity_type'   => $type,
					'name'          => $name,
					'slug'          => $slug,
					'code'          => $code,
					'description'   => $desc,
					'display_order' => $order,
					'status'        => $stat,
				),
				array( 'id' => $id ),
				array( '%s', '%s', '%s', '%s', '%s', '%d', '%s' ),
				array( '%d' )
			);
			return $id;
		}

		$wpdb->insert(
			$table_su,
			array(
				'entity_type'   => $type,
				'name'          => $name,
				'slug'          => $slug,
				'code'          => $code,
				'description'   => $desc,
				'display_order' => $order,
				'status'        => $stat,
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Delete a state or university entity.
	 *
	 * @param int $id Entity ID.
	 * @return bool
	 */
	public static function delete_state_university( $id ) {
		global $wpdb;
		$table_su = IAQ_Database::get_table_name( 'mock_states_universities' );
		$wpdb->delete( $table_su, array( 'id' => absint( $id ) ), array( '%d' ) );
		return true;
	}


	/* =========================================================================
	 * 4. VACANCIES & POSTS MANAGEMENT
	 * ========================================================================= */

	/**
	 * Retrieve vacancies for an examination (e.g. UPSSSC -> Technical Assistant).
	 *
	 * @param int $exam_id Examination ID.
	 * @return array
	 */
	public static function get_vacancies( $exam_id = 0 ) {
		global $wpdb;
		$table_vac = IAQ_Database::get_table_name( 'mock_vacancies' );
		$exam_id   = absint( $exam_id );

		$where = "status = 'active'";
		if ( $exam_id > 0 ) {
			$where .= $wpdb->prepare( ' AND exam_id = %d', $exam_id );
		}

		$rows = $wpdb->get_results( "SELECT * FROM {$table_vac} WHERE {$where} ORDER BY display_order ASC, title ASC" );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Retrieve single vacancy by ID.
	 *
	 * @param int $id Vacancy ID.
	 * @return object|null
	 */
	public static function get_vacancy( $id ) {
		global $wpdb;
		$table_vac = IAQ_Database::get_table_name( 'mock_vacancies' );
		$id        = absint( $id );
		if ( ! $id ) {
			return null;
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_vac} WHERE id = %d", $id ) );
		if ( $row ) {
			$eid                   = ! empty( $row->exam_id ) ? (int) $row->exam_id : ( ! empty( $row->examination_id ) ? (int) $row->examination_id : 0 );
			$row->exam_id          = $eid;
			$row->examination_id   = $eid;
			$row->posts_count      = isset( $row->posts_count ) ? (int) $row->posts_count : 0;
			$row->advertisement_no = isset( $row->advertisement_no ) ? $row->advertisement_no : '';
			$row->description      = isset( $row->description ) ? $row->description : '';
		}
		return $row ?: null;
	}

	/**
	 * Save or update a vacancy.
	 *
	 * @param array $data Vacancy attributes.
	 * @return int|WP_Error
	 */
	public static function save_vacancy( $data ) {
		global $wpdb;
		$table_vac = IAQ_Database::get_table_name( 'mock_vacancies' );

		if ( empty( $data['title'] ) ) {
			return new WP_Error( 'missing_title', __( 'Vacancy title is required.', 'indianagri-quiz-engine' ) );
		}

		$title   = sanitize_text_field( $data['title'] );
		$exam_id = ! empty( $data['exam_id'] ) ? absint( $data['exam_id'] ) : ( ! empty( $data['examination_id'] ) ? absint( $data['examination_id'] ) : 0 );
		$slug    = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $title );
		$desc    = isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '';
		$posts   = isset( $data['posts_count'] ) ? intval( $data['posts_count'] ) : 0;
		$advt    = isset( $data['advertisement_no'] ) ? sanitize_text_field( $data['advertisement_no'] ) : '';
		$order   = isset( $data['display_order'] ) ? intval( $data['display_order'] ) : 0;
		$stat    = ! empty( $data['status'] ) && in_array( $data['status'], array( 'active', 'inactive' ), true ) ? $data['status'] : 'active';
		$id      = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;

		if ( $id > 0 ) {
			$wpdb->update(
				$table_vac,
				array(
					'exam_id'          => $exam_id,
					'title'            => $title,
					'slug'             => $slug,
					'description'      => $desc,
					'posts_count'      => $posts,
					'advertisement_no' => $advt,
					'display_order'    => $order,
					'status'           => $stat,
				),
				array( 'id' => $id ),
				array( '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s' ),
				array( '%d' )
			);
			return $id;
		}

		$wpdb->insert(
			$table_vac,
			array(
				'exam_id'          => $exam_id,
				'title'            => $title,
				'slug'             => $slug,
				'description'      => $desc,
				'posts_count'      => $posts,
				'advertisement_no' => $advt,
				'display_order'    => $order,
				'status'           => $stat,
				'created_at'       => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Delete a vacancy.
	 *
	 * @param int $id Vacancy ID.
	 * @return bool
	 */
	public static function delete_vacancy( $id ) {
		global $wpdb;
		$table_vac = IAQ_Database::get_table_name( 'mock_vacancies' );
		$wpdb->delete( $table_vac, array( 'id' => absint( $id ) ), array( '%d' ) );
		return true;
	}


	/* =========================================================================
	 * 5. MOCK TEST SERIES MANAGEMENT
	 * ========================================================================= */

	/**
	 * Retrieve mock test series list.
	 *
	 * @param array $args Query filters.
	 * @return array
	 */
	public static function get_series_list( $args = array() ) {
		global $wpdb;
		$table_ser = IAQ_Database::get_table_name( 'mock_series' );

		$defaults = array(
			'category_id' => 0,
			'exam_id'     => 0,
			'subject_id'  => 0,
			'status'      => 'published',
			'orderby'     => 'display_order',
			'order'       => 'ASC',
		);
		$r = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $r['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $r['status'];
		}

		if ( ! empty( $r['category_id'] ) ) {
			$where[]  = 'category_id = %d';
			$values[] = absint( $r['category_id'] );
		}

		if ( ! empty( $r['exam_id'] ) ) {
			$where[]  = 'exam_id = %d';
			$values[] = absint( $r['exam_id'] );
		}

		if ( ! empty( $r['subject_id'] ) ) {
			$where[]  = 'subject_id = %d';
			$values[] = absint( $r['subject_id'] );
		}

		$where_clause = implode( ' AND ', $where );
		$orderby      = in_array( $r['orderby'], array( 'id', 'title', 'display_order', 'created_at' ), true ) ? $r['orderby'] : 'display_order';
		$order        = 'DESC' === strtoupper( $r['order'] ) ? 'DESC' : 'ASC';

		$query = "SELECT * FROM {$table_ser} WHERE {$where_clause} ORDER BY {$orderby} {$order}";

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $query, $values );
		} else {
			$sql = $query;
		}

		$rows = $wpdb->get_results( $sql );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Retrieve a single mock test series.
	 *
	 * @param int $series_id Series ID.
	 * @return object|null
	 */
	public static function get_series( $series_id ) {
		global $wpdb;
		$table_ser = IAQ_Database::get_table_name( 'mock_series' );
		$row       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_ser} WHERE id = %d", absint( $series_id ) ) );
		if ( $row ) {
			$eid                 = ! empty( $row->exam_id ) ? (int) $row->exam_id : ( ! empty( $row->examination_id ) ? (int) $row->examination_id : 0 );
			$row->exam_id        = $eid;
			$row->examination_id = $eid;
			$row->description    = isset( $row->description ) ? $row->description : '';
			$row->total_tests    = isset( $row->total_tests ) ? (int) $row->total_tests : 10;
		}
		return $row ?: null;
	}

	/**
	 * Save or update a mock test series.
	 *
	 * @param array $data Series data.
	 * @return int|WP_Error
	 */
	public static function save_series( $data ) {
		global $wpdb;
		$table_ser = IAQ_Database::get_table_name( 'mock_series' );

		if ( empty( $data['title'] ) ) {
			return new WP_Error( 'missing_title', __( 'Series title is required.', 'indianagri-quiz-engine' ) );
		}

		$title   = sanitize_text_field( $data['title'] );
		$cat_id  = isset( $data['category_id'] ) ? absint( $data['category_id'] ) : 1;
		$exam_id = ! empty( $data['exam_id'] ) ? absint( $data['exam_id'] ) : ( ! empty( $data['examination_id'] ) ? absint( $data['examination_id'] ) : null );
		$sub_id  = isset( $data['subject_id'] ) && absint( $data['subject_id'] ) > 0 ? absint( $data['subject_id'] ) : null;
		$slug    = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $title );
		$desc    = isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '';
		$total   = isset( $data['total_tests'] ) ? intval( $data['total_tests'] ) : 0;
		$access  = ! empty( $data['access_type'] ) && in_array( $data['access_type'], array( 'free', 'paid', 'premium' ), true ) ? $data['access_type'] : 'free';
		$order   = isset( $data['display_order'] ) ? intval( $data['display_order'] ) : 0;
		$stat    = ! empty( $data['status'] ) && in_array( $data['status'], array( 'published', 'draft', 'archived' ), true ) ? $data['status'] : 'published';
		$id      = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;

		if ( $id > 0 ) {
			$wpdb->update(
				$table_ser,
				array(
					'category_id'   => $cat_id,
					'exam_id'       => $exam_id,
					'subject_id'    => $sub_id,
					'title'         => $title,
					'slug'          => $slug,
					'description'   => $desc,
					'total_tests'   => $total,
					'access_type'   => $access,
					'display_order' => $order,
					'status'        => $stat,
					'updated_at'    => current_time( 'mysql' ),
				),
				array( 'id' => $id ),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' ),
				array( '%d' )
			);
			return $id;
		}

		$wpdb->insert(
			$table_ser,
			array(
				'category_id'   => $cat_id,
				'exam_id'       => $exam_id,
				'subject_id'    => $sub_id,
				'title'         => $title,
				'slug'          => $slug,
				'description'   => $desc,
				'total_tests'   => $total,
				'access_type'   => $access,
				'display_order' => $order,
				'status'        => $stat,
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Delete a mock test series.
	 *
	 * @param int $id Series ID.
	 * @return bool
	 */
	public static function delete_series( $id ) {
		global $wpdb;
		$table_ser = IAQ_Database::get_table_name( 'mock_series' );
		$wpdb->delete( $table_ser, array( 'id' => absint( $id ) ), array( '%d' ) );
		return true;
	}


	/* =========================================================================
	 * 6. MOCK TESTS IN CONTEXT & DYNAMIC NUMBERING (Mock Test 1..N)
	 * ========================================================================= */

	/**
	 * Retrieve mock tests for any specific context in the 10-category hierarchy.
	 * Strictly enforces primary content type MOCK_TEST (quiz_type = 'mock').
	 * Dynamically numbers tests as Mock Test 1, Mock Test 2... restarting at 1.
	 *
	 * @param array $context Context parameters (category_id, exam_id, subject_id, vacancy_id, state_id, series_id, etc.)
	 * @param int   $user_id User ID for attempt status enrichment.
	 * @return array
	 */
	public static function get_context_mock_tests( $context = array(), $user_id = 0 ) {
		global $wpdb;

		$table_quizzes   = IAQ_Database::get_table_name( 'quizzes' );
		$table_relations = IAQ_Database::get_table_name( 'mock_test_relations' );
		$table_attempts  = IAQ_Database::get_table_name( 'quiz_attempts' );

		$cat_id   = ! empty( $context['category_id'] ) ? absint( $context['category_id'] ) : 0;
		$exam_id  = ! empty( $context['exam_id'] ) ? absint( $context['exam_id'] ) : 0;
		$sub_id   = ! empty( $context['subject_id'] ) ? absint( $context['subject_id'] ) : 0;
		$vac_id   = ! empty( $context['vacancy_id'] ) ? absint( $context['vacancy_id'] ) : 0;
		$state_id = ! empty( $context['state_id'] ) ? absint( $context['state_id'] ) : 0;
		$series_id= ! empty( $context['series_id'] ) ? absint( $context['series_id'] ) : 0;
		$user_id  = $user_id ? absint( $user_id ) : get_current_user_id();

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
		if ( $series_id > 0 ) {
			$rel_where[] = 'r.series_id = %d';
			$rel_vals[]  = $series_id;
		}

		$rel_where_clause = implode( ' AND ', $rel_where );

		$status_filter = ! empty( $context['status'] ) ? sanitize_key( $context['status'] ) : 'published';
		$status_clause = 'all' === $status_filter ? '' : " AND q.status = '{$status_filter}'";

		// Query joined mock tests
		$query = "
			SELECT q.*, r.id AS mock_rel_id, r.sort_order AS mock_sort_order, r.series_id AS mock_series_id
			FROM {$table_quizzes} q
			INNER JOIN {$table_relations} r ON q.id = r.quiz_id
			WHERE q.quiz_type = 'mock'
			  {$status_clause}
			  AND {$rel_where_clause}
			ORDER BY r.sort_order ASC, q.display_order ASC, q.id ASC
		";

		if ( ! empty( $rel_vals ) ) {
			$sql = $wpdb->prepare( $query, $rel_vals );
		} else {
			$sql = $query;
		}

		$quizzes = $wpdb->get_results( $sql );

		// Fallback: If no relation records exist yet, match directly from canonical wp_iaq_quizzes
		if ( empty( $quizzes ) ) {
			$direct_where = array( "q.quiz_type = 'mock'" );
			if ( 'all' !== $status_filter ) {
				$direct_where[] = "q.status = '{$status_filter}'";
			}
			$direct_vals  = array();

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
			$dir_query        = "SELECT q.*, 0 AS mock_rel_id, q.display_order AS mock_sort_order, 0 AS mock_series_id FROM {$table_quizzes} q WHERE {$dir_where_clause} ORDER BY q.display_order ASC, q.id ASC";

			if ( ! empty( $direct_vals ) ) {
				$quizzes = $wpdb->get_results( $wpdb->prepare( $dir_query, $direct_vals ) );
			} else {
				$quizzes = $wpdb->get_results( $dir_query );
			}
		}

		// Fallback for general categories: if still empty and top category is requested, return mock quizzes
		if ( empty( $quizzes ) && ( $cat_id > 0 || empty( $context ) ) ) {
			$fb_status = 'all' === $status_filter ? '' : " AND q.status = 'published'";
			$quizzes = $wpdb->get_results(
				"SELECT q.*, 0 AS mock_rel_id, q.display_order AS mock_sort_order, 0 AS mock_series_id FROM {$table_quizzes} q WHERE q.quiz_type = 'mock' {$fb_status} ORDER BY q.display_order ASC, q.id ASC"
			);
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
			$qid = is_object( $qz ) ? ( isset( $qz->id ) ? (int) $qz->id : 0 ) : ( isset( $qz['id'] ) ? (int) $qz['id'] : 0 );
			if ( ! $qid ) {
				continue;
			}
			if ( ! in_array( $qid, $seen, true ) ) {
				$seen[]   = $qid;
				$unique[] = is_object( $qz ) ? $qz : (object) $qz;
			}
		}

		// Apply Dynamic Numeric Ordering: Mock Test 1, Mock Test 2, Mock Test 3...
		$counter = 1;
		foreach ( $unique as &$quiz ) {
			$num = $counter++;
			$quiz->mock_test_number = $num;
			$quiz->formatted_title  = sprintf( __( 'Mock Test %d: %s', 'indianagri-quiz-engine' ), $num, $quiz->title );

			// Check paywall access
			$quiz->is_accessible = IAQ_Access_Control::can_attempt_quiz( $user_id, $quiz );

			// Check attempt status
			$quiz->attempt_status   = 'not_attempted';
			$quiz->latest_score     = null;
			$quiz->latest_percentage= null;
			$quiz->latest_passed    = null;

			if ( $user_id > 0 ) {
				$attempt = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM {$table_attempts} WHERE quiz_id = %d AND user_id = %d ORDER BY id DESC LIMIT 1",
						$quiz->id,
						$user_id
					)
				);
				if ( $attempt ) {
					$quiz->attempt_status    = $attempt->status;
					$quiz->latest_score      = $attempt->score;
					$quiz->latest_percentage = $attempt->percentage;
					$quiz->latest_passed     = $attempt->passed;
				}
			}
		}
		unset( $quiz );

		return $unique;
	}

	/**
	 * Attach a mock quiz to a specific context in the hierarchy.
	 *
	 * @param int   $quiz_id Quiz ID.
	 * @param array $context Context parameters.
	 * @return bool
	 */
	public static function attach_quiz_to_mock_context( $quiz_id, $context ) {
		global $wpdb;
		$table_rel = IAQ_Database::get_table_name( 'mock_test_relations' );
		$quiz_id   = absint( $quiz_id );

		if ( ! $quiz_id || empty( $context['category_id'] ) ) {
			return false;
		}

		$cat_id   = absint( $context['category_id'] );
		$exam_id  = ! empty( $context['exam_id'] ) ? absint( $context['exam_id'] ) : null;
		$sub_exam = ! empty( $context['sub_exam_id'] ) ? absint( $context['sub_exam_id'] ) : null;
		$subj_id  = ! empty( $context['subject_id'] ) ? absint( $context['subject_id'] ) : null;
		$vac_id   = ! empty( $context['vacancy_id'] ) ? absint( $context['vacancy_id'] ) : null;
		$state_id = ! empty( $context['state_id'] ) ? absint( $context['state_id'] ) : null;
		$univ_id  = ! empty( $context['university_id'] ) ? absint( $context['university_id'] ) : null;
		$series_id= ! empty( $context['series_id'] ) ? absint( $context['series_id'] ) : null;
		$sort_order = isset( $context['sort_order'] ) ? intval( $context['sort_order'] ) : 0;

		$wpdb->insert(
			$table_rel,
			array(
				'quiz_id'       => $quiz_id,
				'category_id'   => $cat_id,
				'exam_id'       => $exam_id,
				'sub_exam_id'   => $sub_exam,
				'subject_id'    => $subj_id,
				'vacancy_id'    => $vac_id,
				'state_id'      => $state_id,
				'university_id' => $univ_id,
				'series_id'     => $series_id,
				'sort_order'    => $sort_order,
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s' )
		);

		return true;
	}

	/**
	 * Detach a mock quiz from a relation.
	 *
	 * @param int $relation_id Relation ID.
	 * @return bool
	 */
	public static function detach_quiz_from_mock_context( $relation_id ) {
		global $wpdb;
		$table_rel = IAQ_Database::get_table_name( 'mock_test_relations' );
		$wpdb->delete( $table_rel, array( 'id' => absint( $relation_id ) ), array( '%d' ) );
		return true;
	}

	/**
	 * Reorder mock tests within a context.
	 *
	 * @param array $relation_ids Array of relation IDs in sequential order.
	 * @return bool
	 */
	public static function reorder_mock_quizzes( array $relation_ids ) {
		global $wpdb;
		$table_rel = IAQ_Database::get_table_name( 'mock_test_relations' );

		if ( empty( $relation_ids ) ) {
			return false;
		}

		$order = 1;
		foreach ( $relation_ids as $rel_id ) {
			$wpdb->update(
				$table_rel,
				array( 'sort_order' => $order++ ),
				array( 'id' => absint( $rel_id ) ),
				array( '%d' ),
				array( '%d' )
			);
		}

		return true;
	}


	/* =========================================================================
	 * 7. ADAPTIVE NAVIGATION & URL ROUTING
	 * ========================================================================= */

	/**
	 * Determine the structural navigation type and levels for any category.
	 * Allows the engine to skip irrelevant levels seamlessly.
	 *
	 * @param string $category_slug Category slug.
	 * @return array
	 */
	public static function get_category_hierarchy_config( $category_slug ) {
		switch ( $category_slug ) {
			case 'icar-exams':
				return array(
					'has_exams'     => true,
					'has_sub_exams' => true, // ASRB -> ARS / NET
					'has_subjects'  => true, // Agronomy, Soil Science, etc.
					'has_vacancies' => false,
					'has_states'    => false,
					'has_series'    => true,
					'drill_label'   => __( 'Select Examination', 'indianagri-quiz-engine' ),
				);

			case 'cuet':
				return array(
					'has_exams'     => true, // CUET UG, CUET PG
					'has_sub_exams' => false,
					'has_subjects'  => true,
					'has_vacancies' => false,
					'has_states'    => false,
					'has_series'    => true,
					'drill_label'   => __( 'Select CUET Level (UG / PG)', 'indianagri-quiz-engine' ),
				);

			case 'up-agriculture-exams':
			case 'up-agri-exams':
				return array(
					'has_exams'     => true, // UPPCS, UPSSSC, UPCATET
					'has_sub_exams' => false,
					'has_subjects'  => true,
					'has_vacancies' => true, // Technical Assistant, Sugarcane Officer
					'has_states'    => false,
					'has_series'    => true,
					'drill_label'   => __( 'Select UP Examination / Board', 'indianagri-quiz-engine' ),
				);

			case 'teaching-exams':
				return array(
					'has_exams'     => true, // TGT/PGT, Asst Professor
					'has_sub_exams' => false,
					'has_subjects'  => true,
					'has_vacancies' => false,
					'has_states'    => false,
					'has_series'    => true,
					'drill_label'   => __( 'Select Teaching Post', 'indianagri-quiz-engine' ),
				);

			case 'banking-afo':
				return array(
					'has_exams'     => true, // IBPS AFO, NABARD
					'skip_to_tests' => true,
					'has_sub_exams' => false,
					'has_subjects'  => false, // Skip subjects directly to Mock Tests!
					'has_vacancies' => false,
					'has_states'    => false,
					'has_series'    => true,
					'drill_label'   => __( 'Select Banking Exam', 'indianagri-quiz-engine' ),
				);

			case 'state-agriculture-exams':
			case 'state-agri-exams':
				return array(
					'has_exams'     => true,
					'has_sub_exams' => false,
					'has_subjects'  => false,
					'has_vacancies' => false,
					'has_states'    => true, // Rajasthan, MP, Bihar, UP, Maharashtra
					'has_series'    => true,
					'drill_label'   => __( 'Select State', 'indianagri-quiz-engine' ),
				);

			case 'university-entrance':
				return array(
					'has_exams'     => true,
					'has_sub_exams' => false,
					'has_subjects'  => false,
					'has_vacancies' => false,
					'has_states'    => false,
					'has_universities' => true, // SAUs, PG, Ph.D.
					'has_series'    => true,
					'drill_label'   => __( 'Select University / Entrance Type', 'indianagri-quiz-engine' ),
				);

			case 'subject-wise-mocks':
				return array(
					'has_exams'     => false, // Skip exams directly to Subjects!
					'has_sub_exams' => false,
					'has_subjects'  => true,
					'has_vacancies' => false,
					'has_states'    => false,
					'has_series'    => true,
					'drill_label'   => __( 'Select Agricultural Discipline', 'indianagri-quiz-engine' ),
				);

			case 'general-agriculture':
			case 'general-agri':
				return array(
					'has_exams'     => false, // Skip directly to tests!
					'flat_category' => true,
					'has_sub_exams' => false,
					'has_subjects'  => false,
					'has_vacancies' => false,
					'has_states'    => false,
					'has_series'    => true,
					'drill_label'   => __( 'Explore General Mock Tests', 'indianagri-quiz-engine' ),
				);

			default:
				return array(
					'has_exams'     => true,
					'has_sub_exams' => false,
					'has_subjects'  => true,
					'has_vacancies' => false,
					'has_states'    => false,
					'has_series'    => true,
					'drill_label'   => __( 'Explore Mock Tests', 'indianagri-quiz-engine' ),
				);
		}
	}

	/**
	 * Generate Mock Tests subview URL.
	 *
	 * @param string $view Subview name.
	 * @param array  $params Query parameters.
	 * @return string
	 */
	public static function get_url( $view = '', $params = array() ) {
		$base_url = function_exists( 'get_permalink' ) ? get_permalink() : '/';
		$args     = array();

		// Preserve sandbox preview page parameter if active
		if ( isset( $_GET['page'] ) && strpos( sanitize_key( $_GET['page'] ), 'iaq-preview' ) !== false ) {
			$args['page'] = sanitize_key( $_GET['page'] );
		}

		if ( ! empty( $view ) ) {
			$args['iaq_mt_view'] = sanitize_key( $view );
		}

		foreach ( $params as $k => $v ) {
			$args[ $k ] = sanitize_text_field( $v );
		}

		return add_query_arg( $args, $base_url );
	}

	/**
	 * Ensure default exams and subjects are linked to categories if not yet configured.
	 *
	 * @param int $category_id Category ID.
	 */
	public static function ensure_default_category_hierarchy( $category_id ) {
		global $wpdb;
		$category_id = absint( $category_id );
		if ( ! $category_id ) {
			return;
		}

		$cat = self::get_category( $category_id );
		if ( ! $cat ) {
			return;
		}

		$table_exams         = IAQ_Database::get_table_name( 'examinations' );
		$table_exam_subjects = IAQ_Database::get_table_name( 'exam_subjects' );
		$table_subjects      = IAQ_Database::get_table_name( 'subjects' );

		$cat_slug = $cat->slug;
		if ( 'icar-exams' === $cat_slug || false !== strpos( strtolower( $cat->name ), 'icar' ) ) {
			$has_icar = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table_exams} WHERE category_id = %d AND (code = 'ICAR-JRF' OR name LIKE '%%JRF%%')", $category_id )
			);
			if ( 0 === $has_icar ) {
				$default_icar = array(
					array(
						'name'        => 'ICAR JRF (Junior Research Fellowship)',
						'code'        => 'ICAR-JRF',
						'slug'        => 'icar-jrf',
						'description' => 'ICAR All India Entrance Examination for PG Admission & Junior Research Fellowship in Agriculture.',
						'order'       => 1,
					),
					array(
						'name'        => 'ICAR SRF (Senior Research Fellowship)',
						'code'        => 'ICAR-SRF',
						'slug'        => 'icar-srf',
						'description' => 'ICAR All India Competitive Examination for Ph.D. Admission & Senior Research Fellowship.',
						'order'       => 2,
					),
					array(
						'name'        => 'ICAR NET / ASRB NET',
						'code'        => 'ICAR-NET',
						'slug'        => 'icar-net',
						'description' => 'Agricultural Scientists Recruitment Board National Eligibility Test for Agricultural Lectureship.',
						'order'       => 3,
					),
				);

				foreach ( $default_icar as $item ) {
					// Check if exam already exists by code
					$exam_row = $wpdb->get_row(
						$wpdb->prepare( "SELECT id FROM {$table_exams} WHERE code = %s", $item['code'] )
					);
					if ( $exam_row ) {
						$wpdb->update(
							$table_exams,
							array( 'category_id' => $category_id ),
							array( 'id' => $exam_row->id )
						);
						$e_id = (int) $exam_row->id;
					} else {
						$wpdb->insert(
							$table_exams,
							array(
								'category_id'   => $category_id,
								'name'          => $item['name'],
								'code'          => $item['code'],
								'slug'          => $item['slug'],
								'description'   => $item['description'],
								'instructions'  => 'Attempt all questions in accordance with examination guidelines. Negative marking: -1.00 for incorrect answers.',
								'display_order' => $item['order'],
								'status'        => 'active',
								'created_at'    => current_time( 'mysql' ),
							)
						);
						$e_id = (int) $wpdb->insert_id;
					}

					// Assign top subjects to ICAR JRF / SRF / NET
					if ( $e_id ) {
						$assigned_sub_count = (int) $wpdb->get_var(
							$wpdb->prepare( "SELECT COUNT(*) FROM {$table_exam_subjects} WHERE exam_id = %d", $e_id )
						);
						if ( 0 === $assigned_sub_count ) {
							$all_subs = $wpdb->get_results( "SELECT id FROM {$table_subjects} ORDER BY id ASC" );
							if ( ! empty( $all_subs ) ) {
								$sub_order = 1;
								foreach ( $all_subs as $s_row ) {
									$wpdb->insert(
										$table_exam_subjects,
										array(
											'exam_id'       => $e_id,
											'subject_id'    => $s_row->id,
											'display_order' => $sub_order++,
											'created_at'    => current_time( 'mysql' ),
										)
									);
								}
							}
						}
					}
				}
			} elseif ( 'cuet' === $cat_slug || false !== strpos( strtolower( $cat->name ), 'cuet' ) ) {
				$cuet_row = $wpdb->get_row( "SELECT id FROM {$table_exams} WHERE code LIKE 'CUET%' LIMIT 1" );
				if ( $cuet_row ) {
					$wpdb->update( $table_exams, array( 'category_id' => $category_id ), array( 'id' => $cuet_row->id ) );
				}
			} elseif ( 'up-agriculture-exams' === $cat_slug || false !== strpos( strtolower( $cat->name ), 'up' ) ) {
				$up_row = $wpdb->get_row( "SELECT id FROM {$table_exams} WHERE code LIKE 'UP%' LIMIT 1" );
				if ( $up_row ) {
					$wpdb->update( $table_exams, array( 'category_id' => $category_id ), array( 'id' => $up_row->id ) );
				}
			} elseif ( 'banking-afo' === $cat_slug || false !== strpos( strtolower( $cat->name ), 'banking' ) ) {
				$bank_row = $wpdb->get_row( "SELECT id FROM {$table_exams} WHERE code LIKE 'IBPS%' LIMIT 1" );
				if ( $bank_row ) {
					$wpdb->update( $table_exams, array( 'category_id' => $category_id ), array( 'id' => $bank_row->id ) );
				}
			}
		}
	}

	/**
	 * Retrieve examinations for a specific mock category.
	 *
	 * @param int $category_id Category ID.
	 * @return array
	 */
	public static function get_category_examinations( $category_id ) {
		self::ensure_default_category_hierarchy( $category_id );
		return IAQ_Examinations::get_examinations( array( 'category_id' => absint( $category_id ), 'limit' => 200 ) );
	}
}
