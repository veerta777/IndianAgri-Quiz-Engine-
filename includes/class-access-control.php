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

		// Real-time synchronization upon student login.
		add_action( 'wp_login', array( __CLASS__, 'on_user_login' ), 10, 2 );
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
		$access_type   = ! empty( $data['access_type'] ) && in_array( $data['access_type'], array( 'all_access', 'exam_specific' ), true ) ? $data['access_type'] : 'all_access';
		$wc_product_id = ! empty( $data['wc_product_id'] ) ? absint( $data['wc_product_id'] ) : null;
		$is_featured   = ! empty( $data['is_featured'] ) ? 1 : 0;
		$display_order = isset( $data['display_order'] ) ? intval( $data['display_order'] ) : 0;
		$features      = ! empty( $data['features'] ) ? sanitize_textarea_field( $data['features'] ) : '';
		$status        = ! empty( $data['status'] ) && in_array( $data['status'], array( 'active', 'inactive', 'archived' ), true ) ? $data['status'] : 'active';

		// Handle included examination categories/IDs
		$included_exam_ids = '';
		if ( 'exam_specific' === $access_type && ! empty( $data['included_exam_ids'] ) ) {
			if ( is_array( $data['included_exam_ids'] ) ) {
				$included_exam_ids = implode( ',', array_map( 'sanitize_text_field', $data['included_exam_ids'] ) );
			} else {
				$included_exam_ids = sanitize_text_field( $data['included_exam_ids'] );
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
	 * Retrieve all active subscriptions for a student.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return array
	 */
	public static function get_user_active_subscriptions( $user_id ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return array();
		}

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
				if ( in_array( $quiz->exam_category, $allowed_exams, true ) ) {
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
			$product_id = $item->get_product_id();

			// Match plan mapped to this WC product ID
			$plan = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$table_plans} WHERE wc_product_id = %d AND status = 'active'", $product_id )
			);

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
