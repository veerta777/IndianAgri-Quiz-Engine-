<?php
/**
 * Test Suite: Subscription Plans, Calendar Expiry, & WooCommerce Monetization
 *
 * @package IndianAgri_Quiz_Engine
 */

class Test_Subscriptions_Monetization {

	public static function run() {
		echo "\nRunning Test_Subscriptions_Monetization...\n";

		self::test_plan_creation_and_attributes();
		self::test_calendar_aware_expiry_calculations();
		self::test_student_subscription_provisioning_and_stacking();
		self::test_admin_manual_revocation();
		self::test_woocommerce_order_processing_and_idempotency();
		self::test_woocommerce_refund_revocation();

		echo "Test_Subscriptions_Monetization: ALL PASS\n";
	}

	private static function test_plan_creation_and_attributes() {
		$plan_id = IAQ_Access_Control::save_plan(
			array(
				'name'              => 'ASRB NET Agronomy 6-Month Pass',
				'code'              => 'asrb-agronomy-6m',
				'description'       => 'Full test series for ASRB NET Agronomy aspirants.',
				'price'             => 199.00,
				'duration_value'    => 6,
				'duration_unit'     => 'months',
				'access_type'       => 'exam_specific',
				'included_exam_ids' => 'asrb-net-agronomy',
				'wc_product_id'     => 1001,
				'is_featured'       => 1,
				'status'            => 'active',
			)
		);

		if ( is_wp_error( $plan_id ) || ! $plan_id ) {
			throw new Exception( 'Failed to create subscription plan.' );
		}

		$plan = IAQ_Access_Control::get_plan( $plan_id );
		if ( ! $plan || 'ASRB NET Agronomy 6-Month Pass' !== $plan->name ) {
			throw new Exception( 'Retrieved subscription plan does not match saved parameters.' );
		}
		if ( 'exam_specific' !== $plan->access_type || 'asrb-net-agronomy' !== $plan->included_exam_ids ) {
			throw new Exception( 'Exam specific scope was not properly saved.' );
		}

		echo "  [PASS] Subscription plan creation and scoped attributes verified.\n";
	}

	private static function test_calendar_aware_expiry_calculations() {
		// 1. Days calculation
		$start_days = '2026-03-01 10:00:00';
		$exp_days   = IAQ_Access_Control::calculate_calendar_expiry( $start_days, 15, 'days' );
		if ( '2026-03-16 10:00:00' !== $exp_days ) {
			throw new Exception( "Calendar days calculation failed: expected 2026-03-16 10:00:00, got {$exp_days}" );
		}

		// 2. Month-end non-leap year (Jan 31 + 1 month = Feb 28)
		$start_jan = '2026-01-31 12:00:00';
		$exp_feb   = IAQ_Access_Control::calculate_calendar_expiry( $start_jan, 1, 'months' );
		if ( '2026-02-28 12:00:00' !== $exp_feb ) {
			throw new Exception( "Month-end calculation (non-leap) failed: expected 2026-02-28 12:00:00, got {$exp_feb}" );
		}

		// 3. Month-end leap year (Jan 31, 2024 + 1 month = Feb 29, 2024)
		$start_leap_jan = '2024-01-31 12:00:00';
		$exp_leap_feb   = IAQ_Access_Control::calculate_calendar_expiry( $start_leap_jan, 1, 'months' );
		if ( '2024-02-29 12:00:00' !== $exp_leap_feb ) {
			throw new Exception( "Month-end calculation (leap year) failed: expected 2024-02-29 12:00:00, got {$exp_leap_feb}" );
		}

		// 4. Leap year Feb 29 transition to non-leap year (Feb 29, 2024 + 1 year = Feb 28, 2025)
		$start_leap_feb = '2024-02-29 15:30:00';
		$exp_year_next  = IAQ_Access_Control::calculate_calendar_expiry( $start_leap_feb, 1, 'years' );
		if ( '2025-02-28 15:30:00' !== $exp_year_next ) {
			throw new Exception( "Leap year to non-leap year transition failed: expected 2025-02-28 15:30:00, got {$exp_year_next}" );
		}

		// 5. Quarter end (Mar 31 + 3 months = Jun 30)
		$start_mar = '2026-03-31 08:00:00';
		$exp_jun   = IAQ_Access_Control::calculate_calendar_expiry( $start_mar, 3, 'months' );
		if ( '2026-06-30 08:00:00' !== $exp_jun ) {
			throw new Exception( "Quarter-end month adjustment failed: expected 2026-06-30 08:00:00, got {$exp_jun}" );
		}

		echo "  [PASS] Calendar-aware expiry calculation (month-ends & leap years) verified.\n";
	}

	private static function test_student_subscription_provisioning_and_stacking() {
		$user_id = 42;
		$plan    = IAQ_Access_Control::get_plan_by_code( 'asrb-agronomy-6m' );
		if ( ! $plan ) {
			throw new Exception( 'Could not find test subscription plan.' );
		}

		// Initial grant
		$sub_id_1 = IAQ_Access_Control::grant_subscription( $user_id, $plan->id, null, 'Test Initial Grant' );
		if ( is_wp_error( $sub_id_1 ) || ! $sub_id_1 ) {
			throw new Exception( 'Failed to grant initial student subscription.' );
		}

		$sub_1 = IAQ_Access_Control::get_user_subscriptions( $user_id, 'active' );
		if ( empty( $sub_1 ) || 1 !== count( $sub_1 ) ) {
			throw new Exception( 'Student active subscription list did not reflect initial grant.' );
		}

		$first_expiry = $sub_1[0]->expiry_date;

		// Stacking renewal: grant same plan again before expiry
		$sub_id_2 = IAQ_Access_Control::grant_subscription( $user_id, $plan->id, null, 'Test Stacking Grant' );
		if ( is_wp_error( $sub_id_2 ) || ! $sub_id_2 ) {
			throw new Exception( 'Failed to grant stacked subscription renewal.' );
		}

		global $wpdb;
		$table_subs = IAQ_Database::get_table_name( 'user_subscriptions' );
		$sub_record_2 = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_subs} WHERE id = %d", $sub_id_2 ) );

		// The second subscription start date must chain from the first expiry date
		if ( $sub_record_2->start_date !== $first_expiry ) {
			throw new Exception( "Stacked subscription start date ({$sub_record_2->start_date}) did not chain from previous expiry ({$first_expiry})." );
		}

		echo "  [PASS] Student subscription entitlement provisioning and stacking verified.\n";
	}

	private static function test_admin_manual_revocation() {
		$user_id = 42;
		$subs    = IAQ_Access_Control::get_user_subscriptions( $user_id, 'active' );
		if ( empty( $subs ) ) {
			throw new Exception( 'No active subscriptions found for revocation test.' );
		}

		$target_sub_id = $subs[0]->id;
		$res = IAQ_Access_Control::revoke_subscription( $target_sub_id, 'Admin manually revoked for fraud violation' );

		if ( ! $res ) {
			throw new Exception( 'Failed to revoke student subscription.' );
		}

		global $wpdb;
		$table_subs = IAQ_Database::get_table_name( 'user_subscriptions' );
		$revoked_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_subs} WHERE id = %d", $target_sub_id ) );

		if ( 'cancelled' !== $revoked_row->status ) {
			throw new Exception( 'Revoked subscription status was not updated to cancelled.' );
		}
		if ( strpos( $revoked_row->notes, 'Admin manually revoked' ) === false ) {
			throw new Exception( 'Audit note not appended to revoked subscription.' );
		}

		echo "  [PASS] Admin manual entitlement revocation and audit notes verified.\n";
	}

	private static function test_woocommerce_order_processing_and_idempotency() {
		global $mock_wc_orders;

		$student_id = 99;
		$order_id   = 7788;

		// Create mock order
		$order = new Mock_WC_Order( $order_id, $student_id );
		$order->items[] = new Mock_WC_Order_Item( 1001 ); // Maps to asrb-agronomy-6m
		$mock_wc_orders[ $order_id ] = $order;

		// Trigger order activation hook
		IAQ_Access_Control::handle_wc_order_activated( $order_id );

		// Verify subscription was created for student 99
		$student_subs = IAQ_Access_Control::get_user_subscriptions( $student_id, 'active' );
		if ( empty( $student_subs ) ) {
			throw new Exception( 'WooCommerce order activation failed to provision subscription for customer.' );
		}

		// Verify idempotency flag
		if ( 'yes' !== $order->get_meta( '_iaq_subscription_provisioned' ) ) {
			throw new Exception( 'WooCommerce order did not receive _iaq_subscription_provisioned metadata.' );
		}

		$count_before = count( $student_subs );

		// Simulate duplicate webhook / order status change
		IAQ_Access_Control::handle_wc_order_activated( $order_id );

		$student_subs_after = IAQ_Access_Control::get_user_subscriptions( $student_id, 'active' );
		if ( count( $student_subs_after ) !== $count_before ) {
			throw new Exception( 'Idempotency failure: duplicate order event created duplicate subscription!' );
		}

		echo "  [PASS] WooCommerce payment verification & idempotent provisioning verified.\n";
	}

	private static function test_woocommerce_refund_revocation() {
		$student_id = 99;
		$order_id   = 7788;

		// Trigger refund hook
		IAQ_Access_Control::handle_wc_order_refunded( $order_id );

		global $wpdb;
		$table_subs = IAQ_Database::get_table_name( 'user_subscriptions' );
		$sub = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_subs} WHERE wc_order_id = %d", $order_id ) );

		if ( ! $sub || 'refunded' !== $sub->status ) {
			throw new Exception( 'WooCommerce refund hook failed to revoke student entitlement.' );
		}

		echo "  [PASS] WooCommerce refund event revocation policy verified.\n";
	}
}
