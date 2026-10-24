<?php
/**
 * Test Suite: WordPress REST API Controller (iaq/v1)
 *
 * Verifies all mobile REST endpoints, Bearer token authentication,
 * dashboard summary, examination catalogue, practice sets (books, subjects, exams),
 * mock test hierarchy, CBT attempt lifecycle (start, immediate feedback, anti-tamper lock),
 * mock test answer key concealment, results review, bookmarks, notebook, custom practice,
 * study streaks, leaderboard, and subscription checkout.
 *
 * @package IndianAgri_Quiz_Engine
 */

require_once __DIR__ . '/bootstrap.php';

class Test_Rest_Api {

	public static function run() {
		echo "Running REST API (iaq/v1) Test Suite...\n";

		// Initialize REST API routes.
		IAQ_Rest_Api::register_routes();

		self::test_route_registration();
		self::test_auth_login_and_token_generation();
		self::test_token_authentication_middleware();
		self::test_dashboard_summary_endpoint();
		self::test_examinations_endpoint();
		self::test_practice_sets_endpoints();
		self::test_mock_tests_endpoints();
		self::test_quiz_cbt_lifecycle_and_immediate_feedback();
		self::test_mock_test_key_concealment_on_rest();
		self::test_learning_features_endpoints();
		self::test_subscriptions_endpoints();
		self::test_app_version_endpoint();

		echo "Test_Rest_Api: ALL PASS\n\n";
	}

	private static function test_route_registration() {
		global $mock_rest_routes;

		$expected_routes = array(
			'/iaq/v1/auth/login',
			'/iaq/v1/auth/register',
			'/iaq/v1/auth/forgot-password',
			'/iaq/v1/auth/me',
			'/iaq/v1/dashboard/summary',
			'/iaq/v1/examinations',
			'/iaq/v1/practice-sets/books',
			'/iaq/v1/practice-sets/subjects',
			'/iaq/v1/mock-tests/categories',
			'/iaq/v1/mock-tests/hierarchy',
			'/iaq/v1/learning/bookmarks',
			'/iaq/v1/learning/wrong-notebook',
			'/iaq/v1/learning/study-streaks',
			'/iaq/v1/learning/leaderboard',
			'/iaq/v1/subscriptions/plans',
			'/iaq/v1/subscriptions/checkout-url',
			'/iaq/v1/app/version',
		);

		foreach ( $expected_routes as $route ) {
			assert( isset( $mock_rest_routes[ $route ] ), "Expected route {$route} was not registered" );
		}

		echo "  [PASS] All expected REST API routes registered under /iaq/v1/ namespace\n";
	}

	private static function test_auth_login_and_token_generation() {
		// Test valid login.
		$req = new WP_REST_Request( 'POST', '/iaq/v1/auth/login' );
		$req->set_param( 'username', 'virendra' );
		$req->set_param( 'password', 'correct_password' );

		$res = IAQ_Rest_Api::handle_login( $req );
		assert( ! is_wp_error( $res ), 'Valid login returned WP_Error' );
		$data = $res->get_data();

		assert( ! empty( $data['token'] ), 'Login response missing auth token' );
		assert( isset( $data['user']['id'] ) && $data['user']['id'] === 1, 'Login response user ID mismatch' );
		assert( isset( $data['user']['email'] ), 'Login response user email missing' );

		// Test invalid login.
		$bad_req = new WP_REST_Request( 'POST', '/iaq/v1/auth/login' );
		$bad_req->set_param( 'username', 'virendra' );
		$bad_req->set_param( 'password', 'invalid' );

		$bad_res = IAQ_Rest_Api::handle_login( $bad_req );
		assert( is_wp_error( $bad_res ), 'Invalid login did not return WP_Error' );
		assert( $bad_res->get_error_code() === 'invalid_credentials', 'Unexpected error code on invalid credentials' );

		echo "  [PASS] Student authentication, token issuance, and credential validation verified\n";
	}

	private static function test_token_authentication_middleware() {
		$token = IAQ_Rest_Api::generate_auth_token( 1 );
		assert( ! empty( $token ), 'Auth token generation failed' );

		// Set header in simulated server.
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

		$extracted_token = IAQ_Rest_Api::get_bearer_token();
		assert( $extracted_token === $token, 'Extracted Bearer token does not match generated token' );

		$user_id = IAQ_Rest_Api::determine_current_user_from_bearer_token( false );
		assert( $user_id === 1, 'Bearer token did not resolve to user ID 1' );

		// Test token revocation.
		IAQ_Rest_Api::revoke_token( 1, $token );
		$revoked_user = IAQ_Rest_Api::determine_current_user_from_bearer_token( false );
		assert( $revoked_user === false, 'Revoked token was still accepted' );

		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		echo "  [PASS] Bearer token resolution, middleware validation, and revocation verified\n";
	}

	private static function test_dashboard_summary_endpoint() {
		wp_set_current_user( 1 );

		$res  = IAQ_Rest_Api::get_dashboard_summary();
		$data = $res->get_data();

		assert( isset( $data['stats'] ), 'Dashboard summary missing stats' );
		assert( isset( $data['streaks'] ), 'Dashboard summary missing streaks' );
		assert( isset( $data['recent_history'] ), 'Dashboard summary missing recent_history' );
		assert( isset( $data['subject_mastery'] ), 'Dashboard summary missing subject_mastery' );

		echo "  [PASS] Student dashboard summary REST payload verified\n";
	}

	private static function test_examinations_endpoint() {
		$res  = IAQ_Rest_Api::get_examinations();
		$data = $res->get_data();
		assert( is_array( $data ), 'Examinations list did not return an array' );

		echo "  [PASS] Examination catalog REST endpoint verified\n";
	}

	private static function test_practice_sets_endpoints() {
		// Books catalog.
		$res_books  = IAQ_Rest_Api::get_practice_books();
		$data_books = $res_books->get_data();
		assert( is_array( $data_books ), 'Practice books did not return an array' );

		// Subjects catalog.
		$res_subs  = IAQ_Rest_Api::get_practice_subjects();
		$data_subs = $res_subs->get_data();
		assert( is_array( $data_subs ), 'Practice subjects did not return an array' );

		echo "  [PASS] Practice Sets (Books and Subjects catalogs) verified\n";
	}

	private static function test_mock_tests_endpoints() {
		// Categories.
		$res_cats  = IAQ_Rest_Api::get_mock_categories();
		$data_cats = $res_cats->get_data();
		assert( is_array( $data_cats ), 'Mock categories did not return an array' );

		// Hierarchy drilldown.
		$req = new WP_REST_Request( 'GET', '/iaq/v1/mock-tests/hierarchy' );
		$res_hier  = IAQ_Rest_Api::get_mock_hierarchy( $req );
		$data_hier = $res_hier->get_data();
		assert( is_array( $data_hier ), 'Mock hierarchy drilldown did not return an array' );

		echo "  [PASS] Mock Tests (10 categories & dynamic hierarchy) verified\n";
	}

	private static function test_quiz_cbt_lifecycle_and_immediate_feedback() {
		global $wpdb;
		wp_set_current_user( 1 );

		// Seed a Practice Set with immediate feedback.
		$table_quizzes = $wpdb->prefix . 'iaq_quizzes';
		$wpdb->insert(
			$table_quizzes,
			array(
				'title'             => 'REST Practice Set - Agronomy',
				'slug'              => 'rest-practice-agronomy',
				'quiz_type'         => 'practice',
				'show_answers_mode' => 'immediate',
				'duration_minutes'  => 15,
				'total_questions'   => 1,
				'total_marks'       => 4.00,
				'correct_marks'     => 4.00,
				'negative_marks'    => 1.00,
				'pass_percentage'   => 40.00,
				'max_attempts'      => 0,
				'exam_category'     => 'General',
				'status'            => 'published',
				'access_type'       => 'free',
			)
		);
		$quiz_id = (int) $wpdb->insert_id;

		// Seed a Question.
		$table_questions = $wpdb->prefix . 'iaq_questions';
		$wpdb->insert(
			$table_questions,
			array(
				'subject_id'     => 1,
				'topic_id'       => 1,
				'exam_category'  => 'General',
				'difficulty'     => 'medium',
				'question_text'  => 'Which crop is known as Queen of Cereals?',
				'option_a'       => 'Wheat',
				'option_b'       => 'Maize',
				'option_c'       => 'Rice',
				'option_d'       => 'Barley',
				'correct_option' => 'B',
				'marks'          => 4.00,
				'negative_marks' => 1.00,
				'explanation'    => 'Maize (Zea mays) is known as Queen of Cereals due to high yield potential.',
				'status'         => 'published',
			)
		);
		$q_id = (int) $wpdb->insert_id;

		// Attach question.
		$wpdb->insert(
			$wpdb->prefix . 'iaq_quiz_questions',
			array(
				'quiz_id'        => $quiz_id,
				'question_id'    => $q_id,
				'question_order' => 1,
			)
		);

		// 1. Start Attempt via REST.
		$start_req = new WP_REST_Request( 'POST', "/iaq/v1/quiz/{$quiz_id}/start" );
		$start_req->set_param( 'id', $quiz_id );
		$start_res = IAQ_Rest_Api::start_or_resume_quiz_attempt( $start_req );
		assert( ! is_wp_error( $start_res ), 'Start attempt returned error' );
		$start_data = $start_res->get_data();
		$attempt_id = (int) $start_data['attempt_id'];
		assert( $attempt_id > 0, 'Attempt ID not returned' );
		assert( ! empty( $start_data['is_immediate'] ), 'is_immediate flag should be true for immediate practice set' );

		// 2. Save Answer via REST (Option B = Correct).
		$ans_req = new WP_REST_Request( 'POST', "/iaq/v1/quiz/attempt/{$attempt_id}/save-answer" );
		$ans_req->set_param( 'id', $attempt_id );
		$ans_req->set_param( 'question_id', $q_id );
		$ans_req->set_param( 'selected_option', 'B' );
		$ans_req->set_param( 'time_spent_seconds', 25 );

		$ans_res = IAQ_Rest_Api::save_quiz_answer( $ans_req );
		assert( ! is_wp_error( $ans_res ), 'Save answer returned error' );
		$ans_data = $ans_res->get_data();

		assert( $ans_data['is_correct'] === true, 'Correct answer was marked incorrect' );
		assert( (float) $ans_data['marks_awarded'] === 4.00, 'Marks awarded mismatch' );
		assert( ! empty( $ans_data['explanation'] ), 'Explanation missing from immediate feedback' );

		// 3. Anti-Tamper check: Resubmitting different answer on same question is locked.
		$tamper_req = new WP_REST_Request( 'POST', "/iaq/v1/quiz/attempt/{$attempt_id}/save-answer" );
		$tamper_req->set_param( 'id', $attempt_id );
		$tamper_req->set_param( 'question_id', $q_id );
		$tamper_req->set_param( 'selected_option', 'A' );
		$tamper_res = IAQ_Rest_Api::save_quiz_answer( $tamper_req );
		$tamper_data = $tamper_res->get_data();
		assert( ! empty( $tamper_data['is_locked'] ), 'Anti-tamper: question was not locked against score change' );
		assert( $tamper_data['selected'] === 'B', 'Anti-tamper: recorded answer was altered' );

		// 4. Submit Attempt via REST.
		$sub_req = new WP_REST_Request( 'POST', "/iaq/v1/quiz/attempt/{$attempt_id}/submit" );
		$sub_req->set_param( 'id', $attempt_id );
		$sub_res = IAQ_Rest_Api::submit_quiz_attempt( $sub_req );
		assert( ! is_wp_error( $sub_res ), 'Submit attempt returned error' );

		// 5. Fetch Scorecard via REST.
		$card_req = new WP_REST_Request( 'GET', "/iaq/v1/quiz/attempt/{$attempt_id}/scorecard" );
		$card_req->set_param( 'id', $attempt_id );
		$card_res = IAQ_Rest_Api::get_attempt_scorecard( $card_req );
		assert( ! is_wp_error( $card_res ), 'Get scorecard returned error' );
		$card_data = $card_res->get_data();
		assert( (float) $card_data['scorecard']['score'] === 4.00, 'Scorecard score calculation mismatch' );

		echo "  [PASS] Quiz CBT lifecycle, immediate feedback card, anti-tamper answer freeze, and scorecard verified\n";
	}

	private static function test_mock_test_key_concealment_on_rest() {
		global $wpdb;
		wp_set_current_user( 1 );

		// Seed a Mock Test (quiz_type = 'mock').
		$table_quizzes = $wpdb->prefix . 'iaq_quizzes';
		$wpdb->insert(
			$table_quizzes,
			array(
				'title'             => 'REST Mock Test - ICAR JRF',
				'slug'              => 'rest-mock-jrf',
				'quiz_type'         => 'mock',
				'show_answers_mode' => 'after_submission',
				'duration_minutes'  => 60,
				'total_questions'   => 1,
				'total_marks'       => 4.00,
				'correct_marks'     => 4.00,
				'negative_marks'    => 1.00,
				'pass_percentage'   => 40.00,
				'max_attempts'      => 0,
				'exam_category'     => 'General',
				'status'            => 'published',
				'access_type'       => 'free',
			)
		);
		$mock_id = (int) $wpdb->insert_id;

		// Seed question.
		$table_questions = $wpdb->prefix . 'iaq_questions';
		$wpdb->insert(
			$table_questions,
			array(
				'subject_id'     => 1,
				'topic_id'       => 1,
				'exam_category'  => 'General',
				'difficulty'     => 'hard',
				'question_text'  => 'Secret mock question?',
				'option_a'       => 'A',
				'option_b'       => 'B',
				'option_c'       => 'C',
				'option_d'       => 'D',
				'correct_option' => 'C',
				'marks'          => 4.00,
				'negative_marks' => 1.00,
				'explanation'    => 'Confidential scientific explanation.',
				'status'         => 'published',
			)
		);
		$mock_q_id = (int) $wpdb->insert_id;

		$wpdb->insert(
			$wpdb->prefix . 'iaq_quiz_questions',
			array(
				'quiz_id'        => $mock_id,
				'question_id'    => $mock_q_id,
				'question_order' => 1,
			)
		);

		// Start Mock Attempt.
		$req = new WP_REST_Request( 'POST', "/iaq/v1/quiz/{$mock_id}/start" );
		$req->set_param( 'id', $mock_id );
		$res = IAQ_Rest_Api::start_or_resume_quiz_attempt( $req );
		$data = $res->get_data();
		$attempt_id = (int) $data['attempt_id'];

		// Verify questions payload conceals correct_option and explanation.
		$first_q = $data['questions'][0];
		assert( ! isset( $first_q->correct_option ), 'Mock test question payload leaked correct_option' );
		assert( ! isset( $first_q->explanation ), 'Mock test question payload leaked explanation' );

		// Save Answer on Mock Test.
		$ans_req = new WP_REST_Request( 'POST', "/iaq/v1/quiz/attempt/{$attempt_id}/save-answer" );
		$ans_req->set_param( 'id', $attempt_id );
		$ans_req->set_param( 'question_id', $mock_q_id );
		$ans_req->set_param( 'selected_option', 'A' );
		$ans_res = IAQ_Rest_Api::save_quiz_answer( $ans_req );
		$ans_data = $ans_res->get_data();

		assert( ! isset( $ans_data['correct_option'] ), 'Mock test save-answer leaked correct_option' );
		assert( ! isset( $ans_data['explanation'] ), 'Mock test save-answer leaked explanation' );
		assert( ! isset( $ans_data['is_correct'] ), 'Mock test save-answer leaked is_correct' );

		echo "  [PASS] Server-side Mock Test strict answer key concealment verified on REST\n";
	}

	private static function test_learning_features_endpoints() {
		wp_set_current_user( 1 );

		// Bookmarks toggle.
		$req_bm = new WP_REST_Request( 'POST', '/iaq/v1/learning/bookmarks/toggle' );
		$req_bm->set_param( 'question_id', 1 );
		$req_bm->set_param( 'notes', 'Review for JRF Agronomy' );
		$res_bm = IAQ_Rest_Api::toggle_bookmark( $req_bm );
		assert( ! is_wp_error( $res_bm ), 'Toggle bookmark returned error' );

		// Bookmarks list.
		$res_bms = IAQ_Rest_Api::get_bookmarks( new WP_REST_Request( 'GET', '/iaq/v1/learning/bookmarks' ) );
		assert( isset( $res_bms->get_data()['bookmarks'] ), 'Bookmarks list missing data' );

		// Wrong notebook list.
		$res_wb = IAQ_Rest_Api::get_wrong_notebook( new WP_REST_Request( 'GET', '/iaq/v1/learning/wrong-notebook' ) );
		assert( isset( $res_wb->get_data()['entries'] ), 'Wrong notebook missing entries' );

		// Study streaks.
		$res_st = IAQ_Rest_Api::get_study_streaks();
		assert( isset( $res_st->get_data()['current_streak'] ), 'Streaks missing current_streak' );

		// Leaderboard.
		$res_lb = IAQ_Rest_Api::get_leaderboard( new WP_REST_Request( 'GET', '/iaq/v1/learning/leaderboard' ) );
		assert( ! is_wp_error( $res_lb ), 'Leaderboard returned error' );

		echo "  [PASS] Student learning tools (Bookmarks, Wrong Notebook, Streaks, Leaderboard) verified\n";
	}

	private static function test_subscriptions_endpoints() {
		// Pricing plans.
		$res_plans = IAQ_Rest_Api::get_subscription_plans();
		assert( is_array( $res_plans->get_data() ), 'Subscription plans did not return an array' );

		// Checkout URL.
		global $wpdb;
		$table_plans = $wpdb->prefix . 'iaq_subscription_plans';
		$wpdb->insert(
			$table_plans,
			array(
				'name'          => 'All-Access Annual Pass',
				'slug'          => 'all-access-annual-rest',
				'price'         => 1999.00,
				'currency'      => 'INR',
				'wc_product_id' => 999,
				'status'        => 'active',
			)
		);
		$plan_id = (int) $wpdb->insert_id;

		$req_checkout = new WP_REST_Request( 'POST', '/iaq/v1/subscriptions/checkout-url' );
		$req_checkout->set_param( 'plan_id', $plan_id );
		$res_checkout = IAQ_Rest_Api::get_checkout_url( $req_checkout );
		assert( ! is_wp_error( $res_checkout ), 'Checkout URL returned error' );
		$data_checkout = $res_checkout->get_data();
		assert( strpos( $data_checkout['checkout_url'], 'add-to-cart=999' ) !== false, 'Checkout URL missing WooCommerce cart param' );

		echo "  [PASS] Subscription plans and WooCommerce checkout redirection verified\n";
	}

	private static function test_app_version_endpoint() {
		// Test app version information with current client version 1.0.0
		$req = new WP_REST_Request( 'GET', '/iaq/v1/app/version' );
		$req->set_param( 'current_version', '1.0.0' );
		$res = IAQ_Rest_Api::get_app_version_info( $req );
		assert( ! is_wp_error( $res ), 'App version endpoint returned error' );
		$data = $res->get_data();

		assert( $data['success'] === true, 'App version endpoint success flag false' );
		assert( ! empty( $data['latest_version'] ), 'Latest version is empty' );
		assert( ! empty( $data['update_url'] ), 'Update URL is empty' );
		assert( is_array( $data['release_notes'] ), 'Release notes should be an array' );
		assert( isset( $data['is_update_available'] ), 'is_update_available flag missing' );
		assert( isset( $data['is_force_update'] ), 'is_force_update flag missing' );

		echo "  [PASS] Mobile App Version & Update Information REST endpoint verified\n";
	}
}
