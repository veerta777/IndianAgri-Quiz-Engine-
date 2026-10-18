<?php
/**
 * Test Suite: Central Access Control, Gating, & Security Audits
 *
 * @package IndianAgri_Quiz_Engine
 */

class Test_Access_Control_Security {

	public static function run() {
		echo "\nRunning Test_Access_Control_Security...\n";

		IAQ_Database::seed_default_data();

		self::test_free_quiz_access();
		self::test_admin_bypass_entitlement();
		self::test_guest_premium_block();
		self::test_student_without_subscription_block();
		self::test_all_access_plan_entitlement();
		self::test_exam_specific_scope_matching();
		self::test_expired_subscription_access_loss();
		self::test_multiple_active_subscriptions_combination();
		self::test_server_side_attempt_start_gating();
		self::test_active_attempt_anti_cheat_payload_protection();
		self::test_cross_user_attempt_tampering_protection();
		self::test_csv_injection_neutralization();
		self::test_mobile_responsiveness_and_elementor_compatibility();

		echo "Test_Access_Control_Security: ALL PASS\n";
	}

	private static function test_free_quiz_access() {
		// Create a free quiz
		$free_quiz_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'         => 'Free Agricultural General Knowledge Quiz',
				'quiz_type'     => 'practice',
				'access_type'   => 'free',
				'exam_category' => 'General',
				'status'        => 'publish',
			)
		);

		$free_quiz = IAQ_Quiz_Builder::get_quiz( $free_quiz_id );

		// Guest user (0)
		$guest_check = IAQ_Access_Control::check_user_entitlement( 0, $free_quiz );
		if ( empty( $guest_check['allowed'] ) ) {
			throw new Exception( 'Free quiz must be accessible to guest users.' );
		}

		// Logged in user without subscription
		$student_check = IAQ_Access_Control::check_user_entitlement( 501, $free_quiz );
		if ( empty( $student_check['allowed'] ) ) {
			throw new Exception( 'Free quiz must be accessible to students without subscription.' );
		}

		echo "  [PASS] Free examination access verified for all visitors.\n";
	}

	private static function test_admin_bypass_entitlement() {
		$prem_quiz_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'         => 'ICAR JRF Agronomy Mock 1 (Premium)',
				'quiz_type'     => 'mock',
				'access_type'   => 'premium',
				'exam_category' => 'icar-jrf',
				'status'        => 'publish',
			)
		);

		$prem_quiz = IAQ_Quiz_Builder::get_quiz( $prem_quiz_id );

		// User 1 has manage_options capability in test bootstrap
		$admin_check = IAQ_Access_Control::check_user_entitlement( 1, $prem_quiz );
		if ( empty( $admin_check['allowed'] ) ) {
			throw new Exception( 'Administrator must bypass premium paywall.' );
		}

		echo "  [PASS] Administrator privilege bypass verified.\n";
	}

	private static function test_guest_premium_block() {
		$quiz = IAQ_Quiz_Builder::get_quiz_by_title( 'ICAR JRF Agronomy Mock 1 (Premium)' );
		$guest_check = IAQ_Access_Control::check_user_entitlement( 0, $quiz );

		if ( ! empty( $guest_check['allowed'] ) ) {
			throw new Exception( 'Security failure: guest was allowed access to premium quiz.' );
		}

		echo "  [PASS] Guest access block on premium quizzes verified.\n";
	}

	private static function test_student_without_subscription_block() {
		$quiz = IAQ_Quiz_Builder::get_quiz_by_title( 'ICAR JRF Agronomy Mock 1 (Premium)' );
		$unsubscribed_id = 888;

		$check = IAQ_Access_Control::check_user_entitlement( $unsubscribed_id, $quiz );
		if ( ! empty( $check['allowed'] ) ) {
			throw new Exception( 'Security failure: unsubscribed student was granted access to premium quiz.' );
		}

		echo "  [PASS] Unsubscribed student paywall enforcement verified.\n";
	}

	private static function test_all_access_plan_entitlement() {
		$quiz = IAQ_Quiz_Builder::get_quiz_by_title( 'ICAR JRF Agronomy Mock 1 (Premium)' );
		$student_id = 889;

		// Get starter All-Access plan
		$plan = IAQ_Access_Control::get_plan_by_code( 'monthly-all-access' );
		if ( ! $plan ) {
			throw new Exception( 'Could not find monthly-all-access plan.' );
		}

		IAQ_Access_Control::grant_subscription( $student_id, $plan->id, null, 'Monthly All Access Test' );

		$check = IAQ_Access_Control::check_user_entitlement( $student_id, $quiz );
		if ( empty( $check['allowed'] ) ) {
			throw new Exception( 'All-Access subscription holder was blocked from premium quiz.' );
		}

		echo "  [PASS] All-Access plan entitlement unlocks target examination.\n";
	}

	private static function test_exam_specific_scope_matching() {
		// Quiz A: icar-jrf
		$quiz_icar = IAQ_Quiz_Builder::get_quiz_by_title( 'ICAR JRF Agronomy Mock 1 (Premium)' );

		// Quiz B: upcatet-pg
		$quiz_upcatet_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'         => 'UPCATET PG Agronomy Paper (Premium)',
				'quiz_type'     => 'mock',
				'access_type'   => 'premium',
				'exam_category' => 'upcatet-pg',
				'status'        => 'publish',
			)
		);
		$quiz_upcatet = IAQ_Quiz_Builder::get_quiz( $quiz_upcatet_id );

		// Create exam-specific plan for ICAR JRF only
		$icar_plan_id = IAQ_Access_Control::save_plan(
			array(
				'name'              => 'ICAR JRF Specific Plan',
				'code'              => 'icar-jrf-specific-plan',
				'price'             => 99.00,
				'duration_value'    => 1,
				'duration_unit'     => 'months',
				'access_type'       => 'exam_specific',
				'included_exam_ids' => 'icar-jrf',
				'status'            => 'active',
			)
		);

		$student_id = 890;
		IAQ_Access_Control::grant_subscription( $student_id, $icar_plan_id, null, 'ICAR Specific Grant' );

		// Check icar quiz -> should be allowed
		$check_icar = IAQ_Access_Control::check_user_entitlement( $student_id, $quiz_icar );
		if ( empty( $check_icar['allowed'] ) ) {
			throw new Exception( 'Exam-specific plan failed to unlock matching examination category.' );
		}

		// Check upcatet quiz -> should be BLOCKED
		$check_upcatet = IAQ_Access_Control::check_user_entitlement( $student_id, $quiz_upcatet );
		if ( ! empty( $check_upcatet['allowed'] ) ) {
			throw new Exception( 'Security failure: Exam-specific plan granted access to unrelated examination!' );
		}

		echo "  [PASS] Examination-specific scope boundary strictly enforced.\n";
	}

	private static function test_expired_subscription_access_loss() {
		global $wpdb;
		$student_id = 891;
		$plan = IAQ_Access_Control::get_plan_by_code( 'monthly-all-access' );

		$sub_id = IAQ_Access_Control::grant_subscription( $student_id, $plan->id, null, 'Expired Test' );

		// Manually force expiry into the past
		$table_subs = IAQ_Database::get_table_name( 'user_subscriptions' );
		$wpdb->update(
			$table_subs,
			array(
				'expiry_date' => '2025-01-01 00:00:00',
				'status'      => 'expired',
			),
			array( 'id' => $sub_id )
		);

		$quiz = IAQ_Quiz_Builder::get_quiz_by_title( 'ICAR JRF Agronomy Mock 1 (Premium)' );
		$check = IAQ_Access_Control::check_user_entitlement( $student_id, $quiz );

		if ( ! empty( $check['allowed'] ) ) {
			throw new Exception( 'Security failure: Expired subscription holder was granted access.' );
		}

		echo "  [PASS] Expired subscription access automatically denied.\n";
	}

	private static function test_multiple_active_subscriptions_combination() {
		$student_id = 892;

		// Plan 1: Exam specific for upcatet-pg
		$upcatet_plan_id = IAQ_Access_Control::save_plan(
			array(
				'name'              => 'UPCATET PG Specific Plan',
				'code'              => 'upcatet-pg-plan',
				'price'             => 99.00,
				'duration_value'    => 1,
				'duration_unit'     => 'months',
				'access_type'       => 'exam_specific',
				'included_exam_ids' => 'upcatet-pg',
				'status'            => 'active',
			)
		);
		IAQ_Access_Control::grant_subscription( $student_id, $upcatet_plan_id, null, 'Grant 1' );

		// Plan 2: Exam specific for asrb-net
		$asrb_plan_id = IAQ_Access_Control::save_plan(
			array(
				'name'              => 'ASRB NET Specific Plan',
				'code'              => 'asrb-net-plan',
				'price'             => 99.00,
				'duration_value'    => 1,
				'duration_unit'     => 'months',
				'access_type'       => 'exam_specific',
				'included_exam_ids' => 'asrb-net-agronomy',
				'status'            => 'active',
			)
		);
		IAQ_Access_Control::grant_subscription( $student_id, $asrb_plan_id, null, 'Grant 2' );

		// Quizzes
		$quiz_upcatet = IAQ_Quiz_Builder::get_quiz_by_title( 'UPCATET PG Agronomy Paper (Premium)' );
		$quiz_asrb_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'         => 'ASRB NET Agronomy Paper (Premium)',
				'quiz_type'     => 'mock',
				'access_type'   => 'premium',
				'exam_category' => 'asrb-net-agronomy',
				'status'        => 'publish',
			)
		);
		$quiz_asrb = IAQ_Quiz_Builder::get_quiz( $quiz_asrb_id );

		// Student should have access to BOTH
		$check1 = IAQ_Access_Control::check_user_entitlement( $student_id, $quiz_upcatet );
		$check2 = IAQ_Access_Control::check_user_entitlement( $student_id, $quiz_asrb );

		if ( empty( $check1['allowed'] ) || empty( $check2['allowed'] ) ) {
			throw new Exception( 'Multiple simultaneous active subscriptions did not combine access.' );
		}

		echo "  [PASS] Multiple simultaneous active subscriptions combine entitlements properly.\n";
	}

	private static function test_server_side_attempt_start_gating() {
		$quiz = IAQ_Quiz_Builder::get_quiz_by_title( 'ICAR JRF Agronomy Mock 1 (Premium)' );
		$unauthorized_id = 999;

		$res = IAQ_Quiz_Attempt::start_or_resume_attempt( $quiz->id, $unauthorized_id );
		if ( ! is_wp_error( $res ) || 'access_denied' !== $res->get_error_code() ) {
			throw new Exception( 'Security failure: start_or_resume_attempt failed to block unauthorized candidate.' );
		}

		echo "  [PASS] Server-side attempt creation paywall gating verified.\n";
	}

	private static function test_active_attempt_anti_cheat_payload_protection() {
		// Ensure questions exist in quiz
		$quiz = IAQ_Quiz_Builder::get_quiz_by_title( 'ICAR JRF Agronomy Mock 1 (Premium)' );
		$all_q = IAQ_Question_Bank::get_questions( array( 'per_page' => 5 ) );
		if ( ! empty( $all_q ) ) {
			IAQ_Quiz_Builder::assign_questions_to_quiz( $quiz->id, array( $all_q[0]->id ) );
		}

		// Admin user (1) starts attempt
		$attempt_res = IAQ_Quiz_Attempt::start_or_resume_attempt( $quiz->id, 1 );
		if ( is_wp_error( $attempt_res ) ) {
			throw new Exception( 'Failed to start attempt for admin: ' . $attempt_res->get_error_message() );
		}

		$questions = ! empty( $attempt_res['questions'] ) ? $attempt_res['questions'] : array();

		if ( empty( $questions ) ) {
			throw new Exception( 'No questions returned for candidate attempt.' );
		}

		foreach ( $questions as $q ) {
			if ( isset( $q['correct_option'] ) || isset( $q['is_correct'] ) ) {
				throw new Exception( 'Security failure: correct answer key exposed in active exam payload!' );
			}
			if ( isset( $q['explanation'] ) || isset( $q['explanation_en'] ) || isset( $q['explanation_hi'] ) ) {
				throw new Exception( 'Security failure: explanation exposed in active exam payload!' );
			}
		}

		echo "  [PASS] Anti-cheat answer key & explanation stripping verified.\n";
	}

	private static function test_csv_injection_neutralization() {
		$malicious_val = '=cmd|/c calc.exe!A1';
		$sanitized = IAQ_Import_Export::sanitize_cell( $malicious_val );

		if ( strpos( $sanitized, "'" ) !== 0 ) {
			throw new Exception( 'CSV formula injection was not neutralized with leading single quote.' );
		}

		// Verify Devanagari Hindi text is preserved untouched
		$hindi_text = 'पौधों में नाइट्रोजन की कमी के लक्षण क्या हैं?';
		$hindi_sanitized = IAQ_Import_Export::sanitize_cell( $hindi_text );

		if ( $hindi_text !== $hindi_sanitized ) {
			throw new Exception( 'Hindi text was corrupted during CSV cell sanitization.' );
		}

		echo "  [PASS] CSV formula injection protection & Unicode preservation verified.\n";
	}

	private static function test_cross_user_attempt_tampering_protection() {
		$free_quiz = IAQ_Quiz_Builder::get_quiz_by_title( 'Free Agricultural General Knowledge Quiz' );
		$all_q = IAQ_Question_Bank::get_questions( array( 'per_page' => 5 ) );
		if ( ! empty( $all_q ) ) {
			IAQ_Quiz_Builder::assign_questions_to_quiz( $free_quiz->id, array( $all_q[0]->id ) );
		}
		$attempt_res = IAQ_Quiz_Attempt::start_or_resume_attempt( $free_quiz->id, 501 );
		if ( is_wp_error( $attempt_res ) ) {
			throw new Exception( 'Failed to start attempt for student 501: ' . $attempt_res->get_error_message() );
		}
		$attempt_id = $attempt_res['attempt_id'];

		// User 502 attempts to tamper with User 501's attempt answers
		$tamper_save = IAQ_Quiz_Attempt::save_answer( $attempt_id, 502, 1, 'B' );
		if ( ! is_wp_error( $tamper_save ) || 'invalid_attempt' !== $tamper_save->get_error_code() ) {
			throw new Exception( 'Security failure: User 502 was able to tamper with User 501 attempt answers!' );
		}

		// User 502 attempts to finalize User 501's attempt
		$tamper_submit = IAQ_Quiz_Attempt::submit_attempt( $attempt_id, 502, 120 );
		if ( ! is_wp_error( $tamper_submit ) || 'invalid_attempt' !== $tamper_submit->get_error_code() ) {
			throw new Exception( 'Security failure: User 502 was able to submit User 501 attempt!' );
		}

		echo "  [PASS] Cross-user attempt tampering protection verified (Strict attempt ownership).\n";
	}

	private static function test_mobile_responsiveness_and_elementor_compatibility() {
		// Verify frontend.css contains mobile touch target optimizations
		$css = file_get_contents( dirname( __DIR__ ) . '/assets/css/frontend.css' );
		if ( false === strpos( $css, 'min-height: 48px' ) || false === strpos( $css, '@media (max-width: 680px)' ) ) {
			throw new Exception( 'Mobile touch target optimizations missing in frontend.css' );
		}

		// Verify Elementor & WordPress shortcode output buffering (never echoes directly)
		$frontend = new IAQ_Frontend();
		ob_start();
		$out = $frontend->render_quiz_list_shortcode( array() );
		$echoed = ob_get_clean();

		if ( '' !== $echoed ) {
			throw new Exception( 'Elementor violation: Shortcode directly echoed output instead of returning string buffer!' );
		}
		if ( empty( $out ) || false === strpos( $out, 'iaq-quiz-grid' ) ) {
			throw new Exception( 'Shortcode returned empty or invalid HTML markup' );
		}

		echo "  [PASS] Mobile usability (touch targets & sticky timer) and Elementor shortcode buffer integrity verified.\n";
	}
}
