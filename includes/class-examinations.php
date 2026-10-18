<?php
/**
 * Examinations management class.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IAQ_Examinations {

	/**
	 * Retrieve all examinations.
	 *
	 * @param array $args Query filters.
	 * @return array
	 */
	public static function get_examinations( $args = array() ) {
		global $wpdb;

		$table_name = IAQ_Database::get_table_name( 'examinations' );

		$defaults = array(
			'status'  => '',
			'search'  => '',
			'orderby' => 'display_order',
			'order'   => 'ASC',
			'limit'   => 100,
			'offset'  => 0,
		);

		$r = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$values = array();

		if ( ! empty( $r['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $r['status'];
		} else {
			$where[] = "status != 'archived'";
		}

		if ( ! empty( $r['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $r['search'] ) . '%';
			$where[]  = '(name LIKE %s OR code LIKE %s OR description LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		if ( ! empty( $r['category_id'] ) ) {
			$where[]  = 'category_id = %d';
			$values[] = absint( $r['category_id'] );
		}

		if ( isset( $r['parent_id'] ) && '' !== $r['parent_id'] ) {
			$where[]  = 'parent_id = %d';
			$values[] = absint( $r['parent_id'] );
		}

		$where_clause = implode( ' AND ', $where );
		$allowed_orderby = array( 'id', 'name', 'code', 'display_order', 'created_at', 'status' );
		$orderby         = in_array( $r['orderby'], $allowed_orderby, true ) ? $r['orderby'] : 'display_order';
		$order           = 'DESC' === strtoupper( $r['order'] ) ? 'DESC' : 'ASC';
		$order_by        = "{$orderby} {$order}";
		$limit_clause    = sprintf( 'LIMIT %d OFFSET %d', absint( $r['limit'] ), absint( $r['offset'] ) );

		$query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY {$order_by} {$limit_clause}";

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $query, $values );
		} else {
			$sql = $query;
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Retrieve a single examination by ID.
	 *
	 * @param int $exam_id Examination ID.
	 * @return object|null
	 */
	public static function get_examination( $exam_id ) {
		global $wpdb;
		$table_name = IAQ_Database::get_table_name( 'examinations' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $exam_id ) );
	}

	/**
	 * Retrieve a single examination by code.
	 *
	 * @param string $code Examination code.
	 * @return object|null
	 */
	public static function get_examination_by_code( $code ) {
		global $wpdb;
		$table_name = IAQ_Database::get_table_name( 'examinations' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE code = %s", $code ) );
	}

	/**
	 * Save or update an examination.
	 *
	 * @param array $data Examination data.
	 * @return int|WP_Error
	 */
	public static function save_examination( $data, $exam_id = 0 ) {
		global $wpdb;

		$table_name = IAQ_Database::get_table_name( 'examinations' );

		if ( empty( $data['name'] ) ) {
			return new WP_Error( 'missing_name', __( 'Examination name is required.', 'indianagri-quiz-engine' ) );
		}

		$exam_id = $exam_id ? absint( $exam_id ) : ( ! empty( $data['id'] ) ? absint( $data['id'] ) : 0 );
		$name    = sanitize_text_field( $data['name'] );
		$slug    = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$code    = ! empty( $data['code'] ) ? sanitize_text_field( $data['code'] ) : '';
		$desc    = ! empty( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$inst    = ! empty( $data['instructions'] ) ? wp_kses_post( $data['instructions'] ) : '';
		$order   = isset( $data['sort_order'] ) ? intval( $data['sort_order'] ) : ( isset( $data['display_order'] ) ? intval( $data['display_order'] ) : 0 );
		$status  = ! empty( $data['status'] ) && in_array( $data['status'], array( 'active', 'inactive', 'archived' ), true ) ? $data['status'] : 'active';

		if ( empty( $code ) ) {
			return new WP_Error( 'missing_code', __( 'Examination code is required.', 'indianagri-quiz-engine' ) );
		}

		// Ensure unique code
		$existing_code = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table_name} WHERE code = %s AND id != %d", $code, $exam_id )
		);
		if ( $existing_code ) {
			return new WP_Error( 'duplicate_code', __( 'An examination with this code already exists.', 'indianagri-quiz-engine' ) );
		}

		// Ensure unique slug
		$existing_slug = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table_name} WHERE slug = %s AND id != %d", $slug, $exam_id )
		);
		if ( $existing_slug ) {
			$slug .= '-' . time();
		}

		$payload = array(
			'name'          => $name,
			'slug'          => $slug,
			'code'          => $code,
			'description'   => $desc,
			'instructions'  => $inst,
			'display_order' => $order,
			'status'        => $status,
		);

		if ( isset( $data['category_id'] ) ) {
			$payload['category_id'] = ! empty( $data['category_id'] ) ? absint( $data['category_id'] ) : null;
		}
		if ( isset( $data['parent_id'] ) ) {
			$payload['parent_id'] = ! empty( $data['parent_id'] ) ? absint( $data['parent_id'] ) : null;
		}

		if ( $exam_id > 0 ) {
			$payload['updated_at'] = current_time( 'mysql' );
			$wpdb->update( $table_name, $payload, array( 'id' => $exam_id ) );
			return $exam_id;
		}

		$payload['created_at'] = current_time( 'mysql' );
		$wpdb->insert( $table_name, $payload );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Duplicate an examination.
	 *
	 * @param int $exam_id Examination ID.
	 * @return int|WP_Error
	 */
	public static function duplicate_examination( $exam_id ) {
		$exam = self::get_examination( $exam_id );
		if ( ! $exam ) {
			return new WP_Error( 'not_found', __( 'Examination not found.', 'indianagri-quiz-engine' ) );
		}

		$data = array(
			'name'          => $exam->name . ' (Copy)',
			'slug'          => $exam->slug . '-copy-' . time(),
			'code'          => $exam->code ? $exam->code . '-COPY' : '',
			'description'   => $exam->description,
			'instructions'  => $exam->instructions,
			'display_order' => $exam->display_order + 1,
			'status'        => 'inactive',
		);

		return self::save_examination( $data );
	}

	/**
	 * Archive or delete an examination.
	 *
	 * @param int  $exam_id Examination ID.
	 * @param bool $permanent Delete row permanently.
	 * @return bool
	 */
	public static function delete_examination( $exam_id, $permanent = false ) {
		global $wpdb;
		$table_name = IAQ_Database::get_table_name( 'examinations' );

		if ( $permanent ) {
			return (bool) $wpdb->delete( $table_name, array( 'id' => $exam_id ), array( '%d' ) );
		}

		return (bool) $wpdb->update(
			$table_name,
			array( 'status' => 'archived', 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $exam_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Toggle status of an examination (active <-> inactive).
	 *
	 * @param int $exam_id Examination ID.
	 * @return string New status.
	 */
	public static function toggle_status( $exam_id ) {
		global $wpdb;
		$exam = self::get_examination( $exam_id );
		if ( ! $exam ) {
			return false;
		}

		$new_status = 'active' === $exam->status ? 'inactive' : 'active';
		$table_name = IAQ_Database::get_table_name( 'examinations' );

		$wpdb->update(
			$table_name,
			array(
				'status'     => $new_status,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $exam_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return $new_status;
	}
}
