<?php
/**
 * Subscription monetization and access control engine.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IAQ_Access_Control {

	/**
	 * Initialize hooks for WooCommerce order processing.
	 */
	public static function init() {
		// WooCommerce payment hooks.
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'handle_wc_order_activated' ) );
		add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'handle_wc_order_activated' ) );
		add_action( 'woocommerce_order_status_refunded', array( __CLASS__, 'handle_wc_order_refunded' ) );
		add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'handle_wc_order_cancelled' ) );

		// Scheduled cron for automatic subscription expiration and student deactivation.
		add_action( 'iaq_hourly_subscription_sweep', array( __CLASS__, 'sync_expired_subscriptions' ) );
		if ( function_exists( 'wp_next_scheduled' ) && ! wp_next_scheduled( 'iaq_hourly_subscription_sweep' ) ) {
			wp_schedule_event( time(), 'hourly', 'iaq_hourly_subscription_sweep' );
		}

		// WooCommerce Product Edit page direct plan linking.
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'add_wc_product_subscription_field' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_wc_product_subscription_field' ) );

		// Real-time synchronization upon student login.
		add_action( 'wp_login', array( __CLASS__, 'on_user_login' ), 10, 2 );

		// WooCommerce Order Thank You page subscription confirmation banner.
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'render_order_thankyou_subscription_banner' ), 5 );
	}

	/**
	 * Synchronize student subscription status upon WordPress user login.
	 *
	 * @param string  $user_login User login username.
	 * @param WP_User $user       WP_User object.
	 */
	public static function on_user_login( $user_login, $user ) {
		if ( $user && ! empty( $user->ID ) ) {
			self::sync_expired_subscriptions( (int) $user->ID );
		}
	}

	/**
	 * Retrieve subscription plans.
	 *
	 * @param array $args Query filters.
	 * @return array
	 */
	public static function get_plans( $args = array() ) {
		global $wpdb;

		$table_name = IAQ_Database::get_table_name( 'subscription_plans' );

		$defaults = array(
			'status'  => 'active',
			'orderby' => 'display_order',
			'order'   => 'ASC',
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

		$where_clause = implode( ' AND ', $where );
		$allowed_orderby = array( 'id', 'name', 'code', 'price', 'display_order', 'created_at', 'status' );
		$orderby         = in_array( $r['orderby'], $allowed_orderby, true ) ? $r['orderby'] : 'display_order';
		$order           = 'DESC' === strtoupper( $r['order'] ) ? 'DESC' : 'ASC';
		$order_by        = "{$orderby} {$order}";

		$query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY {$order_by}";

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $query, $values );
		} else {
			$sql = $query;
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Retrieve subscription plans (alias for get_plans).
	 *
	 * @param array $args Query filters.
	 * @return array
	 */
	public static function get_subscription_plans( $args = array() ) {
		return self::get_plans( $args );
	}

	/**
	 * Retrieve a single subscription plan by ID.
	 *
	 * @param int $plan_id Plan ID.
	 * @return object|null
	 */
	public static function get_plan( $plan_id ) {
		global $wpdb;
		$table_name = IAQ_Database::get_table_name( 'subscription_plans' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $plan_id ) );
	}

	/**
	 * Retrieve a single subscription plan by unique code.
	 *
	 * @param string $code Plan unique code.
	 * @return object|null
	 */
	public static function get_plan_by_code( $code ) {
		global $wpdb;
		$table_name = IAQ_Database::get_table_name( 'subscription_plans' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE slug = %s", $code ) );
	}

	/**
	 * Save or update a subscription plan.
	 *
	 * @param array $data Plan parameters.
	 * @param int   $plan_id Optional Plan ID for update.
	 * @return int|WP_Error
	 */
	public static function save_plan( $data, $plan_id = 0 ) {
		global $wpdb;

		$table_name = IAQ_Database::get_table_name( 'subscription_plans' );

		if ( empty( $data['name'] ) ) {
			return new WP_Error( 'missing_name', __( 'Plan title is required.', 'indianagri-quiz-engine' ) );
		}

		$plan_id       = $plan_id ? absint( $plan_id ) : ( ! empty( $data['id'] ) ? absint( $data['id'] ) : 0 );
		$name          = sanitize_text_field( $data['name'] );
		$slug          = ! empty( $data['code'] ) ? sanitize_title( $data['code'] ) : ( ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name ) );
		$desc          = ! empty( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$price         = isset( $data['price'] ) ? floatval( $data['price'] ) : 0.00;
		$currency      = ! empty( $data['currency'] ) ? sanitize_text_field( $data['currency'] ) : 'INR';
		$duration_val  = ! empty( $data['duration_value'] ) ? absint( $data['duration_value'] ) : 1;
		$duration_unit = ! empty( $data['duration_unit'] ) && in_array( $data['duration_unit'], array( 'days', 'months', 'years' ), true ) ? $data['duration_unit'] : 'months';
		
		// Support both access_type and access_scope (from admin template)
		$raw_access = ! empty( $data['access_type'] ) ? $data['access_type'] : ( ! empty( $data['access_scope'] ) ? $data['access_scope'] : 'all_access' );
		if ( 'specific_exams' === $raw_access ) {
			$raw_access = 'exam_specific';
		}
		$access_type   = in_array( $raw_access, array( 'all_access', 'exam_specific' ), true ) ? $raw_access : 'all_access';
		$wc_product_id = ! empty( $data['wc_product_id'] ) ? absint( $data['wc_product_id'] ) : null;
		$is_featured   = ! empty( $data['is_featured'] ) ? 1 : 0;
		$display_order = isset( $data['display_order'] ) ? intval( $data['display_order'] ) : 0;
		$features      = ! empty( $data['features'] ) ? sanitize_textarea_field( $data['features'] ) : '';
		$status        = ! empty( $data['status'] ) && in_array( $data['status'], array( 'active', 'inactive', 'archived' ), true ) ? $data['status'] : 'active';

		// Handle included examination categories/IDs (support included_exam_ids and included_categories alias)
		$raw_included = ! empty( $data['included_exam_ids'] ) ? $data['included_exam_ids'] : ( ! empty( $data['included_categories'] ) ? $data['included_categories'] : '' );
		$included_exam_ids = '';
		if ( 'exam_specific' === $access_type && ! empty( $raw_included ) ) {
			if ( is_array( $raw_included ) ) {
				$included_exam_ids = implode( ',', array_map( 'sanitize_text_field', $raw_included ) );
			} else {
				$included_exam_ids = sanitize_text_field( $raw_included );
			}
		}

		$payload = array(
			'name'              => $name,
			'slug'              => $slug,
			'description'       => $desc,
			'price'             => $price,
			'currency'          => $currency,
			'duration_value'    => $duration_val,
			'duration_unit'     => $duration_unit,
			'access_type'       => $access_type,
			'included_exam_ids' => $included_exam_ids,
			'wc_product_id'     => $wc_product_id,
			'is_featured'       => $is_featured,
			'display_order'     => $display_order,
			'features'          => $features,
			'status'            => $status,
		);

		if ( $plan_id > 0 ) {
			$payload['updated_at'] = current_time( 'mysql' );
			$wpdb->update( $table_name, $payload, array( 'id' => $plan_id ), array( '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s' ), array( '%d' ) );
			return $plan_id;
		}

		$payload['created_at'] = current_time( 'mysql' );
		$wpdb->insert( $table_name, $payload, array( '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s' ) );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Calculate calendar-aware subscription expiry date.
	 *
	 * Properly handles month-end boundaries (e.g. Jan 31 + 1 month = Feb 28/29)
	 * and leap years in the WordPress site's configured timezone.
	 *
	 * @param string $start_date_str Start date (Y-m-d H:i:s).
	 * @param int    $duration_value Numeric quantity.
	 * @param string $duration_unit  'days', 'months', or 'years'.
	 * @return string Expiry datetime in 'Y-m-d H:i:s'.
	 */
	public static function calculate_calendar_expiry( $start_date_str, $duration_value, $duration_unit ) {
		$tz_string = get_option( 'timezone_string' );
		if ( ! $tz_string ) {
			$tz_string = 'UTC';
		}

		try {
			$tz    = new DateTimeZone( $tz_string );
			$start = new DateTime( $start_date_str, $tz );
		} catch ( Exception $e ) {
			$tz    = new DateTimeZone( 'UTC' );
			$start = new DateTime( 'now', $tz );
		}

		$duration_value = absint( $duration_value );

		if ( 'days' === $duration_unit ) {
			$start->modify( "+{$duration_value} days" );
			return $start->format( 'Y-m-d H:i:s' );
		}

		if ( 'months' === $duration_unit ) {
			$orig_day = (int) $start->format( 'j' );
			$start->modify( 'first day of this month' );
			$start->modify( "+{$duration_value} months" );
			$days_in_target_month = (int) $start->format( 't' );
			$target_day = min( $orig_day, $days_in_target_month );
			$start->setDate( (int) $start->format( 'Y' ), (int) $start->format( 'n' ), $target_day );
			return $start->format( 'Y-m-d H:i:s' );
		}

		if ( 'years' === $duration_unit ) {
			$orig_month = (int) $start->format( 'n' );
			$orig_day   = (int) $start->format( 'j' );
			$target_year = (int) $start->format( 'Y' ) + $duration_value;

			// Handle Feb 29 leap year transition to non-leap year
			if ( 2 === $orig_month && 29 === $orig_day ) {
				$is_leap = ( ( $target_year % 4 === 0 && $target_year % 100 !== 0 ) || $target_year % 400 === 0 );
				$target_day = $is_leap ? 29 : 28;
			} else {
				$target_day = $orig_day;
			}

			$start->setDate( $target_year, $orig_month, $target_day );
			return $start->format( 'Y-m-d H:i:s' );
		}

		$start->modify( "+{$duration_value} months" );
		return $start->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Grant a student subscription entitlement.
	 *
	 * Extends existing active subscription if user already holds one for this plan.
	 *
	 * @param int      $user_id WordPress User ID.
	 * @param int      $plan_id Subscription Plan ID.
	 * @param int|null $wc_order_id Optional WooCommerce order reference.
	 * @param string   $notes Audit notes.
	 * @return int|WP_Error Subscription ID.
	 */
	public static function grant_subscription( $user_id, $plan_id, $wc_order_id = null, $notes = '' ) {
		global $wpdb;

		$table_subs = IAQ_Database::get_table_name( 'user_subscriptions' );
		$plan       = self::get_plan( $plan_id );

		if ( ! $plan ) {
			return new WP_Error( 'invalid_plan', __( 'Subscription plan does not exist.', 'indianagri-quiz-engine' ) );
		}

		$now = current_time( 'mysql' );

		// Check if user already holds an active subscription for this plan (stack/extend duration)
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_subs} WHERE user_id = %d AND plan_id = %d AND status = 'active' AND expiry_date > %s ORDER BY expiry_date DESC LIMIT 1",
				$user_id,
				$plan_id,
				$now
			)
		);

		$base_start = $now;
		if ( $existing && ! empty( $existing->expiry_date ) ) {
			$base_start = $existing->expiry_date;
		}

		$expiry_date = self::calculate_calendar_expiry( $base_start, $plan->duration_value, $plan->duration_unit );

		$payload = array(
			'user_id'                    => absint( $user_id ),
			'plan_id'                    => absint( $plan_id ),
			'plan_name_snapshot'         => $plan->name,
			'price_snapshot'             => $plan->price,
			'currency_snapshot'          => $plan->currency,
			'access_type_snapshot'       => $plan->access_type,
			'included_exam_ids_snapshot' => ! empty( $plan->included_exam_ids ) ? $plan->included_exam_ids : '',
			'start_date'                 => $base_start,
			'expiry_date'                => $expiry_date,
			'wc_order_id'                => $wc_order_id ? absint( $wc_order_id ) : null,
			'payment_status'             => 'completed',
			'status'                     => 'active',
			'notes'                      => $notes,
			'created_at'                 => $now,
		);

		$inserted = $wpdb->insert( $table_subs, $payload );
		if ( false === $inserted ) {
			return new WP_Error( 'db_insert_error', __( 'Could not insert subscription record.', 'indianagri-quiz-engine' ) );
		}

		$inserted_id = (int) $wpdb->insert_id;
		if ( $inserted_id > 0 ) {
			self::clear_subscription_cache( (int) $user_id );
			self::sync_student_deactivation_status( (int) $user_id );
		}

		return $inserted_id;
	}

	/**
	 * Revoke or cancel an active student subscription.
	 *
	 * @param int    $subscription_id Subscription record ID.
	 * @param string $notes_or_status Notes string or status ('cancelled', 'refunded').
	 * @param string $notes Optional additional notes.
	 * @return bool
	 */
	public static function revoke_subscription( $subscription_id, $notes_or_status = '', $notes = '' ) {
		global $wpdb;
		$table_subs = IAQ_Database::get_table_name( 'user_subscriptions' );

		$sub = $wpdb->get_row( $wpdb->prepare( "SELECT user_id FROM {$table_subs} WHERE id = %d", $subscription_id ) );

		$status      = 'cancelled';
		$final_notes = '';

		if ( in_array( $notes_or_status, array( 'cancelled', 'refunded' ), true ) ) {
			$status      = $notes_or_status;
			$final_notes = $notes;
		} else {
			$final_notes = $notes_or_status;
			if ( in_array( $notes, array( 'cancelled', 'refunded' ), true ) ) {
				$status = $notes;
			}
		}

		$update = array(
			'status' => $status,
		);
		if ( ! empty( $final_notes ) ) {
			$update['notes'] = $final_notes;
		}

		$res = (bool) $wpdb->update(
			$table_subs,
			$update,
			array( 'id' => absint( $subscription_id ) )
		);

		if ( $sub && ! empty( $sub->user_id ) ) {
			self::clear_subscription_cache( (int) $sub->user_id );
			self::sync_student_deactivation_status( (int) $sub->user_id );
		}

		return $res;
	}

	/**
	 * Retrieve subscriptions for a student.
	 *
	 * @param int    $user_id WordPress User ID.
	 * @param string $status Filter by status ('all', 'active', 'expired', 'revoked').
	 * @return array
	 */
	public static function get_user_subscriptions( $user_id, $status = 'all' ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return array();
		}

		$table_subs = IAQ_Database::get_table_name( 'user_subscriptions' );
		$now        = current_time( 'mysql' );

		if ( 'active' === $status ) {
			return self::get_user_active_subscriptions( $user_id );
		}

		if ( 'expired' === $status ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table_subs} WHERE user_id = %d AND (status = 'expired' OR expiry_date < %s) ORDER BY expiry_date DESC",
					$user_id,
					$now
				)
			);
			return $rows ? $rows : array();
		}

		if ( 'all' !== $status ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table_subs} WHERE user_id = %d AND status = %s ORDER BY id DESC",
					$user_id,
					$status
				)
			);
			return $rows ? $rows : array();
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_subs} WHERE user_id = %d ORDER BY id DESC",
				$user_id
			)
		);

		return $rows ? $rows : array();
	}

	/**
	 * Invalidate active subscriptions cache (safe no-op for backward compatibility).
	 *
	 * @param int $user_id Optional user ID.
	 */
	public static function clear_subscription_cache( $user_id = 0 ) {
		// Clean real-time query interface - no stale cache maintained.
	}

	/**
	 * Retrieve all active subscriptions for a student.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return array
	 */
	public static function get_user_active_subscriptions( $user_id ) {
		$user_id = (int) $user_id;
		if ( empty( $user_id ) ) {
			return array();
		}

		global $wpdb;

		$table_subs = IAQ_Database::get_table_name( 'user_subscriptions' );
		$now        = current_time( 'mysql' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_subs} 
				 WHERE user_id = %d AND status = 'active' AND expiry_date >= %s 
				 ORDER BY expiry_date DESC",
				$user_id,
				$now
			)
		);

		return $rows ? $rows : array();
	}

	/**
	 * Check whether a student can attempt a quiz (boolean helper).
	 *
	 * @param int        $user_id WordPress User ID.
	 * @param int|object $quiz Quiz ID or quiz object.
	 * @return bool
	 */
	public static function can_attempt_quiz( $user_id, $quiz ) {
		$check = self::check_user_entitlement( $user_id, $quiz );
		return ! empty( $check['allowed'] );
	}

	/**
	 * Central server-side entitlement check: Determine if user can access a quiz.
	 *
	 * @param int        $user_id WordPress User ID.
	 * @param int|object $quiz Quiz ID or quiz object.
	 * @return array Array with 'allowed' (bool) and 'reason' (string).
	 */
	public static function check_user_entitlement( $user_id, $quiz ) {
		if ( is_numeric( $quiz ) ) {
			$quiz = IAQ_Quiz_Builder::get_quiz( $quiz );
		}

		if ( ! $quiz ) {
			return array(
				'allowed' => false,
				'reason'  => __( 'Examination could not be found.', 'indianagri-quiz-engine' ),
			);
		}

		// 1. Free quizzes are accessible to all
		if ( 'free' === $quiz->access_type ) {
			return array(
				'allowed' => true,
				'reason'  => __( 'Free examination.', 'indianagri-quiz-engine' ),
			);
		}

		// 2. Administrators always have access
		if ( user_can( $user_id, 'manage_options' ) ) {
			return array(
				'allowed' => true,
				'reason'  => __( 'Administrator bypass.', 'indianagri-quiz-engine' ),
			);
		}

		// 3. Guests cannot access premium quizzes
		if ( empty( $user_id ) ) {
			return array(
				'allowed' => false,
				'reason'  => __( 'Please log in to your account to verify your subscription access.', 'indianagri-quiz-engine' ),
			);
		}

		// 4. Run lazy expiration sweep for student to guarantee real-time deactivation consistency.
		self::sync_expired_subscriptions( $user_id );

		// 5. Inspect student's active subscriptions
		$active_subs = self::get_user_active_subscriptions( $user_id );
		if ( empty( $active_subs ) ) {
			$all_subs = self::get_user_subscriptions( $user_id, 'all' );
			if ( ! empty( $all_subs ) ) {
				return array(
					'allowed' => false,
					'reason'  => __( 'Your subscription is complete and your student access has been automatically deactivated. Please renew your plan to continue taking examinations.', 'indianagri-quiz-engine' ),
					'code'    => 'subscription_expired',
				);
			}

			return array(
				'allowed' => false,
				'reason'  => __( 'This examination is premium. You do not have an active subscription covering this paper. Please subscribe to unlock.', 'indianagri-quiz-engine' ),
				'code'    => 'no_subscription',
			);
		}

		foreach ( $active_subs as $sub ) {
			// All-Access plan grants immediate entry
			if ( 'all_access' === $sub->access_type_snapshot ) {
				return array(
					'allowed' => true,
					'reason'  => sprintf( __( 'Unlocked via %s (Active until %s)', 'indianagri-quiz-engine' ), $sub->plan_name_snapshot, $sub->expiry_date ),
				);
			}

			// Examination-specific subscription
			if ( 'exam_specific' === $sub->access_type_snapshot && ! empty( $sub->included_exam_ids_snapshot ) ) {
				$allowed_exams = array_map( 'trim', explode( ',', $sub->included_exam_ids_snapshot ) );

				// Collect direct exam identifiers
				$quiz_exam_identifiers = array();
				if ( ! empty( $quiz->exam_category ) ) {
					$quiz_exam_identifiers[] = (string) $quiz->exam_category;
				}
				if ( ! empty( $quiz->examination_id ) ) {
					$quiz_exam_identifiers[] = (string) $quiz->examination_id;
				}

				// If it's a practice set, check exam associations in exam_subject_quizzes and books
				if ( isset( $quiz->quiz_type ) && 'practice' === $quiz->quiz_type ) {
					global $wpdb;
					$table_esq   = IAQ_Database::get_table_name( 'exam_subject_quizzes' );
					$table_exams = IAQ_Database::get_table_name( 'examinations' );
					$table_bq    = IAQ_Database::get_table_name( 'book_quizzes' );
					$table_books = IAQ_Database::get_table_name( 'books' );

					// Check exam_subject_quizzes
					$esq_exams = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT e.id, e.name, e.code, e.slug 
							 FROM {$table_esq} esq
							 INNER JOIN {$table_exams} e ON esq.exam_id = e.id
							 WHERE esq.quiz_id = %d",
							$quiz->id
						)
					);
					if ( ! empty( $esq_exams ) ) {
						foreach ( $esq_exams as $ex ) {
							$quiz_exam_identifiers[] = (string) $ex->id;
							$quiz_exam_identifiers[] = (string) $ex->code;
							$quiz_exam_identifiers[] = (string) $ex->slug;
							$quiz_exam_identifiers[] = (string) $ex->name;
						}
					}

					// Check parent book's examination_id
					$book_exams = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT b.examination_id, e.id, e.name, e.code, e.slug
							 FROM {$table_bq} bq
							 INNER JOIN {$table_books} b ON bq.book_id = b.id
							 LEFT JOIN {$table_exams} e ON b.examination_id = e.id
							 WHERE bq.quiz_id = %d",
							$quiz->id
						)
					);
					if ( ! empty( $book_exams ) ) {
						foreach ( $book_exams as $bex ) {
							if ( ! empty( $bex->examination_id ) ) {
								$quiz_exam_identifiers[] = (string) $bex->examination_id;
							}
							if ( ! empty( $bex->code ) ) {
								$quiz_exam_identifiers[] = (string) $bex->code;
								$quiz_exam_identifiers[] = (string) $bex->slug;
								$quiz_exam_identifiers[] = (string) $bex->name;
							}
						}
					}
				}

				if ( ! empty( array_intersect( $allowed_exams, $quiz_exam_identifiers ) ) ) {
					return array(
						'allowed' => true,
						'reason'  => sprintf( __( 'Unlocked via %s (Active until %s)', 'indianagri-quiz-engine' ), $sub->plan_name_snapshot, $sub->expiry_date ),
					);
				}
			}
		}

		return array(
			'allowed' => false,
			'reason'  => __( 'Your current active subscription does not cover this examination category. Please upgrade or choose an All-Access plan.', 'indianagri-quiz-engine' ),
		);
	}

	/**
	 * WooCommerce Order Activated Hook (Processing or Completed).
	 *
	 * Idempotently provisions subscription for user upon verified payment.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 */
	public static function handle_wc_order_activated( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$user_id = $order->get_user_id();
		if ( ! $user_id ) {
			// Resolve or auto-create student account via order billing email
			$billing_email = sanitize_email( $order->get_billing_email() );
			if ( ! empty( $billing_email ) ) {
				$existing_user = get_user_by( 'email', $billing_email );
				if ( $existing_user ) {
					$user_id = (int) $existing_user->ID;
					$order->set_customer_id( $user_id );
					$order->save();
				} else {
					$username = sanitize_user( current( explode( '@', $billing_email ) ) );
					if ( empty( $username ) || username_exists( $username ) ) {
						$username .= '_' . wp_rand( 100, 999 );
					}
					$password = wp_generate_password( 12, false );
					$new_uid  = wp_create_user( $username, $password, $billing_email );
					if ( ! is_wp_error( $new_uid ) ) {
						$user_id = (int) $new_uid;
						$order->set_customer_id( $user_id );
						$order->save();
						if ( function_exists( 'wp_new_user_notification' ) ) {
							wp_new_user_notification( $user_id, null, 'both' );
						}
					}
				}
			}
		}

		if ( ! $user_id ) {
			return;
		}

		// Prevent duplicate processing
		if ( 'yes' === $order->get_meta( '_iaq_subscription_provisioned' ) ) {
			return;
		}

		global $wpdb;
		$table_plans = IAQ_Database::get_table_name( 'subscription_plans' );

		$provisioned = 0;
		foreach ( $order->get_items() as $item ) {
			$product_id   = is_callable( array( $item, 'get_product_id' ) ) ? $item->get_product_id() : 0;
			$variation_id = is_callable( array( $item, 'get_variation_id' ) ) ? $item->get_variation_id() : 0;

			// Match plan mapped to this WC product ID or variation ID
			$plan = null;
			if ( ! empty( $variation_id ) ) {
				$plan = $wpdb->get_row(
					$wpdb->prepare( "SELECT * FROM {$table_plans} WHERE wc_product_id = %d AND status = 'active'", $variation_id )
				);
			}
			if ( ! $plan && ! empty( $product_id ) ) {
				$plan = $wpdb->get_row(
					$wpdb->prepare( "SELECT * FROM {$table_plans} WHERE wc_product_id = %d AND status = 'active'", $product_id )
				);
			}

			// Fallback: check WooCommerce product meta _iaq_linked_plan_id
			if ( ! $plan ) {
				$meta_plan_id = 0;
				if ( ! empty( $variation_id ) ) {
					$meta_plan_id = absint( get_post_meta( $variation_id, '_iaq_linked_plan_id', true ) );
				}
				if ( ! $meta_plan_id && ! empty( $product_id ) ) {
					$meta_plan_id = absint( get_post_meta( $product_id, '_iaq_linked_plan_id', true ) );
				}
				if ( $meta_plan_id > 0 ) {
					$plan = self::get_plan( $meta_plan_id );
				}
			}

			if ( $plan ) {
				self::grant_subscription(
					$user_id,
					$plan->id,
					$order_id,
					sprintf( 'Auto-provisioned via WooCommerce Order #%d', $order_id )
				);
				$provisioned++;
			}
		}

		if ( $provisioned > 0 ) {
			$order->update_meta_data( '_iaq_subscription_provisioned', 'yes' );
			$order->add_order_note( sprintf( __( 'IndianAgri Quiz Engine: Granted %d active subscription plan(s) to student.', 'indianagri-quiz-engine' ), $provisioned ) );
			$order->save();
		}
	}

	/**
	 * Render Quiz Engine subscription plan selector inside WooCommerce Product edit screen.
	 */
	public static function add_wc_product_subscription_field() {
		global $post;
		if ( ! $post || empty( $post->ID ) ) {
			return;
		}

		$plans = self::get_plans( array( 'status' => 'active' ) );
		$options = array(
			'' => __( '— No Quiz Subscription Linked —', 'indianagri-quiz-engine' ),
		);
		$current_plan_id = '';

		foreach ( $plans as $p ) {
			$options[ $p->id ] = sprintf( '%s (₹%s, %d %s)', $p->name, number_format( (float) $p->price, 0 ), $p->duration_value, ucfirst( $p->duration_unit ) );
			if ( (int) $p->wc_product_id === (int) $post->ID ) {
				$current_plan_id = (string) $p->id;
			}
		}

		if ( empty( $current_plan_id ) ) {
			$saved_meta = get_post_meta( $post->ID, '_iaq_linked_plan_id', true );
			if ( ! empty( $saved_meta ) ) {
				$current_plan_id = (string) $saved_meta;
			}
		}

		echo '<div class="options_group show_if_simple show_if_variable">';
		woocommerce_wp_select(
			array(
				'id'          => '_iaq_linked_plan_id',
				'label'       => __( 'IndianAgri Quiz Plan', 'indianagri-quiz-engine' ),
				'description' => __( 'Select the Quiz Engine subscription plan granted to students when purchasing this product.', 'indianagri-quiz-engine' ),
				'desc_tip'    => true,
				'value'       => $current_plan_id,
				'options'     => $options,
			)
		);
		echo '</div>';
	}

	/**
	 * Save Quiz Engine subscription plan setting on WooCommerce Product edit screen.
	 *
	 * @param int $post_id Product post ID.
	 */
	public static function save_wc_product_subscription_field( $post_id ) {
		if ( ! isset( $_POST['_iaq_linked_plan_id'] ) ) {
			return;
		}

		global $wpdb;
		$table_plans = IAQ_Database::get_table_name( 'subscription_plans' );
		$selected_plan_id = absint( $_POST['_iaq_linked_plan_id'] );

		update_post_meta( $post_id, '_iaq_linked_plan_id', $selected_plan_id );

		// Clear old plan that pointed to this product if changed
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table_plans} SET wc_product_id = NULL WHERE wc_product_id = %d AND id != %d",
				$post_id,
				$selected_plan_id
			)
		);

		// Link new plan
		if ( $selected_plan_id > 0 ) {
			$wpdb->update(
				$table_plans,
				array( 'wc_product_id' => $post_id ),
				array( 'id' => $selected_plan_id ),
				array( '%d' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Display a subscription activation banner on WooCommerce order received / thank you page.
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public static function render_order_thankyou_subscription_banner( $order_id ) {
		if ( ! $order_id || ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Ensure order activation is evaluated in real-time
		self::handle_wc_order_activated( $order_id );

		$has_quiz_plan = false;
		global $wpdb;
		$table_plans = IAQ_Database::get_table_name( 'subscription_plans' );

		foreach ( $order->get_items() as $item ) {
			$product_id   = $item->get_product_id();
			$variation_id = $item->get_variation_id();
			$plan = null;
			if ( $variation_id ) {
				$plan = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table_plans} WHERE wc_product_id = %d AND status = 'active'", $variation_id ) );
			}
			if ( ! $plan && $product_id ) {
				$plan = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table_plans} WHERE wc_product_id = %d AND status = 'active'", $product_id ) );
			}
			if ( ! $plan && $product_id ) {
				$meta_pid = absint( get_post_meta( $product_id, '_iaq_linked_plan_id', true ) );
				if ( $meta_pid > 0 ) {
					$plan = true;
				}
			}
			if ( $plan ) {
				$has_quiz_plan = true;
				break;
			}
		}

		if ( ! $has_quiz_plan ) {
			return;
		}

		$is_logged_in  = is_user_logged_in();
		$practice_url  = home_url( '/practice-quiz/' );
		$dashboard_url = self::get_student_dashboard_url();
		?>
		<div style="background:#ecfdf5;border:2px solid #10b981;border-radius:12px;padding:24px;margin:20px 0 30px 0;text-align:center;">
			<div style="font-size:36px;margin-bottom:8px;">🎓 ✅</div>
			<h2 style="color:#065f46;margin:0 0 8px 0;font-size:22px;font-weight:700;">
				<?php esc_html_e( 'Your Quiz Subscription is Activated!', 'indianagri-quiz-engine' ); ?>
			</h2>
			<p style="color:#047857;font-size:15px;margin:0 0 18px 0;max-width:600px;margin-left:auto;margin-right:auto;">
				<?php esc_html_e( 'Your payment was successful and your full access to all agricultural mock tests, practice sets, and solutions is unlocked.', 'indianagri-quiz-engine' ); ?>
			</p>
			<?php if ( ! $is_logged_in ) : ?>
				<p style="color:#b45309;background:#fef3c7;padding:10px 16px;border-radius:8px;font-size:14px;display:inline-block;margin:0 0 16px 0;">
					⚠️ <?php printf( esc_html__( 'Please log in with your email (%s) to start taking your examinations.', 'indianagri-quiz-engine' ), esc_html( $order->get_billing_email() ) ); ?>
				</p>
				<div>
					<a href="<?php echo esc_url( wp_login_url( $practice_url ) ); ?>" style="display:inline-block;background:#117E3A;color:#fff;padding:12px 28px;border-radius:8px;font-weight:700;text-decoration:none;font-size:15px;box-shadow:0 4px 6px -1px rgba(17,126,58,0.25);">
						<?php esc_html_e( 'Log In & Start Practicing', 'indianagri-quiz-engine' ); ?> &rarr;
					</a>
				</div>
			<?php else : ?>
				<div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;align-items:center;">
					<a href="<?php echo esc_url( $practice_url ); ?>" style="display:inline-block;background:#117E3A;color:#fff;padding:13px 28px;border-radius:8px;font-weight:700;text-decoration:none;font-size:15px;box-shadow:0 4px 6px -1px rgba(17,126,58,0.25);">
						<?php esc_html_e( 'Start Practicing Quizzes Now', 'indianagri-quiz-engine' ); ?> &rarr;
					</a>
					<a href="<?php echo esc_url( $dashboard_url ); ?>" style="display:inline-block;background:#0f172a;color:#fff;padding:13px 24px;border-radius:8px;font-weight:700;text-decoration:none;font-size:15px;">
						<?php esc_html_e( 'Student Dashboard', 'indianagri-quiz-engine' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Retrieve or automatically create the public Student Dashboard page URL.
	 *
	 * Guaranteed never to return a 404!
	 *
	 * @return string Public URL to student dashboard.
	 */
	public static function get_student_dashboard_url() {
		// 1. Check if a custom dashboard URL is explicitly configured
		$configured_url = get_option( 'iaq_student_dashboard_url', '' );
		if ( ! empty( $configured_url ) ) {
			return esc_url( $configured_url );
		}

		// 2. Search for any published page containing the shortcode [iaq_student_dashboard]
		global $wpdb;
		$page_id = $wpdb->get_var(
			"SELECT ID FROM {$wpdb->posts} 
			 WHERE post_type = 'page' 
			   AND post_status = 'publish' 
			   AND post_content LIKE '%[iaq_student_dashboard%' 
			 ORDER BY ID ASC LIMIT 1"
		);

		if ( $page_id ) {
			return get_permalink( (int) $page_id );
		}

		// 3. Search for existing page by common slugs ('student-dashboard', 'dashboard', 'my-dashboard')
		foreach ( array( 'student-dashboard', 'dashboard', 'my-dashboard' ) as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page && 'publish' === $page->post_status ) {
				// Make sure our shortcode is inside this page
				if ( false === strpos( $page->post_content, '[iaq_student_dashboard]' ) ) {
					wp_update_post(
						array(
							'ID'           => $page->ID,
							'post_content' => $page->post_content . "\n\n" . '[iaq_student_dashboard]',
						)
					);
				}
				return get_permalink( $page->ID );
			}
		}

		// 4. Automatically create the "Student Dashboard" page if none of the above exist
		return self::ensure_student_dashboard_page();
	}

	/**
	 * Automatically create the Student Dashboard page with shortcode if missing.
	 *
	 * @return string URL of created page.
	 */
	public static function ensure_student_dashboard_page() {
		global $wpdb;
		$existing_id = $wpdb->get_var(
			"SELECT ID FROM {$wpdb->posts} 
			 WHERE post_type = 'page' 
			   AND post_status = 'publish' 
			   AND (post_name = 'student-dashboard' OR post_content LIKE '%[iaq_student_dashboard%')
			 LIMIT 1"
		);

		if ( $existing_id ) {
			return get_permalink( (int) $existing_id );
		}

		$new_page_id = wp_insert_post(
			array(
				'post_title'     => 'Student Dashboard',
				'post_name'      => 'student-dashboard',
				'post_content'   => '<!-- wp:shortcode -->[iaq_student_dashboard]<!-- /wp:shortcode -->',
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'comment_status' => 'closed',
			)
		);

		if ( $new_page_id && ! is_wp_error( $new_page_id ) ) {
			if ( function_exists( 'flush_rewrite_rules' ) ) {
				flush_rewrite_rules( false );
			}
			return get_permalink( (int) $new_page_id );
		}

		// Fallback: WooCommerce My Account page or site root
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$myaccount_url = wc_get_page_permalink( 'myaccount' );
			if ( ! empty( $myaccount_url ) ) {
				return $myaccount_url;
			}
		}

		return home_url( '/' );
	}

	/**
	 * Automatically sweep and transition completed/expired subscriptions to 'expired' status,
	 * and update student active state accordingly.
	 *
	 * @param int|null $user_id Optional specific user ID to evaluate. If null, sweeps globally.
	 * @return int Number of subscriptions transitioned to expired.
	 */
	public static function sync_expired_subscriptions( $user_id = null ) {
		global $wpdb;
		$table_subs = IAQ_Database::get_table_name( 'user_subscriptions' );
		$now        = current_time( 'mysql' );

		if ( $user_id ) {
			$expired_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, user_id, expiry_date FROM {$table_subs} WHERE user_id = %d AND status = 'active' AND expiry_date < %s",
					$user_id,
					$now
				)
			);
		} else {
			$expired_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, user_id, expiry_date FROM {$table_subs} WHERE status = 'active' AND expiry_date < %s",
					$now
				)
			);
		}

		if ( empty( $expired_rows ) ) {
			if ( $user_id ) {
				self::sync_student_deactivation_status( (int) $user_id );
			}
			return 0;
		}

		$affected_user_ids = array();
		$transitioned_count = 0;
		foreach ( $expired_rows as $row ) {
			if ( ! empty( $row->expiry_date ) && strtotime( $row->expiry_date ) >= strtotime( $now ) ) {
				continue;
			}

			$wpdb->update(
				$table_subs,
				array(
					'status'     => 'expired',
					'updated_at' => $now,
				),
				array( 'id' => (int) $row->id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
			$affected_user_ids[ (int) $row->user_id ] = true;
			$transitioned_count++;
		}

		// Evaluate student account active state for all affected students.
		foreach ( array_keys( $affected_user_ids ) as $uid ) {
			self::clear_subscription_cache( (int) $uid );
			self::sync_student_deactivation_status( (int) $uid );
		}

		return $transitioned_count;
	}

	/**
	 * Evaluate and update student account status based on active subscriptions.
	 *
	 * When a student's subscription is complete and no active subscriptions remain:
	 * Deactivates the student status, sets user meta, and triggers hook.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return bool True if student is active, false if deactivated.
	 */
	public static function sync_student_deactivation_status( $user_id ) {
		if ( empty( $user_id ) ) {
			return false;
		}

		// Administrators and instructors are exempt from student deactivation.
		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'manage_iaq_quizzes' ) ) {
			update_user_meta( $user_id, '_iaq_student_active', 1 );
			update_user_meta( $user_id, '_iaq_subscription_status', 'active' );
			return true;
		}

		$active_subs = self::get_user_active_subscriptions( $user_id );
		$is_active   = ! empty( $active_subs );

		if ( $is_active ) {
			update_user_meta( $user_id, '_iaq_student_active', 1 );
			update_user_meta( $user_id, '_iaq_subscription_status', 'active' );
			delete_user_meta( $user_id, '_iaq_deactivated_at' );
		} else {
			// Check if the user had at least one subscription that has completed/expired.
			$all_subs = self::get_user_subscriptions( $user_id, 'all' );
			if ( ! empty( $all_subs ) ) {
				update_user_meta( $user_id, '_iaq_student_active', 0 );
				update_user_meta( $user_id, '_iaq_subscription_status', 'expired' );
				if ( ! get_user_meta( $user_id, '_iaq_deactivated_at', true ) ) {
					update_user_meta( $user_id, '_iaq_deactivated_at', current_time( 'mysql' ) );
				}

				/**
				 * Fires when a student account is automatically deactivated after subscription completes.
				 *
				 * @param int   $user_id  Student WordPress User ID.
				 * @param array $all_subs All past subscription records.
				 */
				do_action( 'iaq_student_subscription_completed_deactivated', $user_id, $all_subs );
			}
		}

		return $is_active;
	}

	/**
	 * Check if a student user is currently active with valid subscription.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return bool
	 */
	public static function is_student_active( $user_id ) {
		if ( empty( $user_id ) ) {
			return false;
		}
		if ( function_exists( 'user_can' ) && ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'manage_iaq_quizzes' ) ) ) {
			return true;
		}
		self::sync_expired_subscriptions( $user_id );
		$active_meta = get_user_meta( $user_id, '_iaq_student_active', true );
		if ( '' !== $active_meta ) {
			return (bool) $active_meta;
		}
		return self::sync_student_deactivation_status( $user_id );
	}

	/**
	 * WooCommerce Order Refunded Hook.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 */
	public static function handle_wc_order_refunded( $order_id ) {
		global $wpdb;
		$table_subs = IAQ_Database::get_table_name( 'user_subscriptions' );

		$affected_uids = $wpdb->get_col(
			$wpdb->prepare( "SELECT DISTINCT user_id FROM {$table_subs} WHERE wc_order_id = %d", $order_id )
		);

		$wpdb->update(
			$table_subs,
			array(
				'status'     => 'refunded',
				'notes'      => sprintf( 'Revoked due to WooCommerce Order #%d refund', $order_id ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'wc_order_id' => $order_id, 'status' => 'active' ),
			array( '%s', '%s', '%s' ),
			array( '%d', '%s' )
		);

		if ( ! empty( $affected_uids ) ) {
			foreach ( $affected_uids as $uid ) {
				self::sync_student_deactivation_status( (int) $uid );
			}
		}
	}

	/**
	 * WooCommerce Order Cancelled Hook.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 */
	public static function handle_wc_order_cancelled( $order_id ) {
		global $wpdb;
		$table_subs = IAQ_Database::get_table_name( 'user_subscriptions' );

		$affected_uids = $wpdb->get_col(
			$wpdb->prepare( "SELECT DISTINCT user_id FROM {$table_subs} WHERE wc_order_id = %d", $order_id )
		);

		$wpdb->update(
			$table_subs,
			array(
				'status'     => 'cancelled',
				'notes'      => sprintf( 'Cancelled due to WooCommerce Order #%d cancellation', $order_id ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'wc_order_id' => $order_id, 'status' => 'active' ),
			array( '%s', '%s', '%s' ),
			array( '%d', '%s' )
		);

		if ( ! empty( $affected_uids ) ) {
			foreach ( $affected_uids as $uid ) {
				self::sync_student_deactivation_status( (int) $uid );
			}
		}
	}
}
