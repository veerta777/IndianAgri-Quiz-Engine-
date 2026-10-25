<?php
/**
 * REST API Controller for IndianAgri Quiz Engine.
 * Exposes secure, authenticated REST endpoints under namespace 'iaq/v1' for mobile applications.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IAQ_Rest_Api
 */
class IAQ_Rest_Api {

	/**
	 * Namespace for the REST API.
	 *
	 * @var string
	 */
	const NAMESPACE = 'iaq/v1';

	/**
	 * Meta key used to store active mobile auth tokens.
	 *
	 * @var string
	 */
	const TOKEN_META_KEY = '_iaq_mobile_auth_tokens';

	/**
	 * Initialize and register REST API hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_filter( 'determine_current_user', array( __CLASS__, 'determine_current_user_from_bearer_token' ), 20 );
	}

	/**
	 * Register all REST API routes.
	 */
	public static function register_routes() {
		// --- 1. Authentication Routes ---
		register_rest_route(
			self::NAMESPACE,
			'/auth/login',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'handle_login' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/auth/register',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'handle_register' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/auth/forgot-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'handle_forgot_password' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/auth/me',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_current_student_profile' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/auth/logout',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'handle_logout' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		// --- 2. Dashboard Route ---
		register_rest_route(
			self::NAMESPACE,
			'/dashboard/summary',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_dashboard_summary' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		// --- 3. Examinations Routes ---
		register_rest_route(
			self::NAMESPACE,
			'/examinations',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_examinations' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/examinations/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_examination_details' ),
				'permission_callback' => '__return_true',
			)
		);

		// --- 4. Practice Sets Routes ---
		register_rest_route(
			self::NAMESPACE,
			'/practice-sets/books',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_practice_books' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/practice-sets/books/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_book_practice_sets' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/practice-sets/subjects',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_practice_subjects' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/practice-sets/subjects/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_subject_practice_sets' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/practice-sets/exams/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_exam_practice_sets' ),
				'permission_callback' => '__return_true',
			)
		);

		// --- 5. Mock Tests Routes ---
		register_rest_route(
			self::NAMESPACE,
			'/mock-tests/categories',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_mock_categories' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/mock-tests/hierarchy',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_mock_hierarchy' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/mock-tests/series/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_mock_series_quizzes' ),
				'permission_callback' => '__return_true',
			)
		);

		// --- 6. Quiz CBT Attempt Engine Routes ---
		register_rest_route(
			self::NAMESPACE,
			'/quiz/(?P<id>\d+)/instructions',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_quiz_instructions' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/quiz/(?P<id>\d+)/start',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'start_or_resume_quiz_attempt' ),
				'permission_callback' => array( __CLASS__, 'check_quiz_start_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/quiz/attempt/(?P<id>\d+)/save-answer',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'save_quiz_answer' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/quiz/attempt/(?P<id>\d+)/submit',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'submit_quiz_attempt' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/quiz/attempt/(?P<id>\d+)/scorecard',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_attempt_scorecard' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/quiz/attempt/(?P<id>\d+)/solutions',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_attempt_solutions' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		// --- 7. Student Learning Tools Routes ---
		register_rest_route(
			self::NAMESPACE,
			'/learning/bookmarks',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_bookmarks' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/learning/bookmarks/toggle',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'toggle_bookmark' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/learning/bookmarks/create-quiz',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_bookmark_revision_quiz' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/learning/wrong-notebook',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_wrong_notebook' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/learning/wrong-notebook/status',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'update_wrong_notebook_status' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/learning/wrong-notebook/remove',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'remove_wrong_notebook_entry' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/learning/wrong-notebook/create-quiz',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_wrong_retry_quiz' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/learning/custom-practice/options',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_custom_practice_options' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/learning/custom-practice/create',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_custom_practice_quiz' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/learning/study-streaks',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_study_streaks' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/learning/goals',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'update_study_goals' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/learning/leaderboard',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_leaderboard' ),
				'permission_callback' => '__return_true',
			)
		);

		// --- 8. Subscriptions & Monetization Routes ---
		register_rest_route(
			self::NAMESPACE,
			'/subscriptions/plans',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_subscription_plans' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/subscriptions/my-subscriptions',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_my_subscriptions' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/subscriptions/checkout-url',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'get_checkout_url' ),
				'permission_callback' => array( __CLASS__, 'require_authenticated_student' ),
			)
		);

		// --- 9. Mobile App Version & Update Information ---
		register_rest_route(
			self::NAMESPACE,
			'/app/version',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_app_version_info' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/* =========================================================================
	 * AUTHENTICATION & TOKEN MANAGEMENT
	 * ========================================================================= */

	/**
	 * Extract Bearer token from the Authorization header.
	 *
	 * @return string
	 */
	public static function get_bearer_token() {
		$headers = function_exists( 'getallheaders' ) ? getallheaders() : array();
		$auth_header = '';

		if ( isset( $headers['Authorization'] ) ) {
			$auth_header = $headers['Authorization'];
		} elseif ( isset( $headers['authorization'] ) ) {
			$auth_header = $headers['authorization'];
		} elseif ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$auth_header = $_SERVER['HTTP_AUTHORIZATION'];
		}

		if ( ! empty( $auth_header ) && preg_match( '/Bearer\s+(\S+)/i', $auth_header, $matches ) ) {
			return trim( $matches[1] );
		}

		return '';
	}

	/**
	 * Determine the current WordPress user ID from a Bearer token.
	 *
	 * @param int|false $user_id Current user ID.
	 * @return int|false
	 */
	public static function determine_current_user_from_bearer_token( $user_id ) {
		if ( ! empty( $user_id ) ) {
			return $user_id;
		}

		$token = self::get_bearer_token();
		if ( empty( $token ) ) {
			return $user_id;
		}

		$parts = explode( '.', $token );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		$uid        = absint( $parts[0] );
		$token_hash = hash( 'sha256', $token );

		if ( $uid <= 0 ) {
			return false;
		}

		$tokens = get_user_meta( $uid, self::TOKEN_META_KEY, true );
		if ( ! is_array( $tokens ) ) {
			return false;
		}

		if ( isset( $tokens[ $token_hash ] ) && (int) $tokens[ $token_hash ] > time() ) {
			return $uid;
		}

		return false;
	}

	/**
	 * Generate and store a secure mobile Bearer token for a student.
	 *
	 * @param int $user_id User ID.
	 * @param int $duration Seconds until expiration (default 30 days).
	 * @return string The raw Bearer token.
	 */
	public static function generate_auth_token( $user_id, $duration = 2592000 ) {
		$random_str = wp_generate_password( 48, false, false );
		$token      = sprintf( '%d.%s', $user_id, $random_str );
		$token_hash = hash( 'sha256', $token );
		$expires_at = time() + $duration;

		$tokens = get_user_meta( $user_id, self::TOKEN_META_KEY, true );
		if ( ! is_array( $tokens ) ) {
			$tokens = array();
		}

		// Prune expired tokens.
		$now = time();
		foreach ( $tokens as $hash => $exp ) {
			if ( (int) $exp < $now ) {
				unset( $tokens[ $hash ] );
			}
		}

		$tokens[ $token_hash ] = $expires_at;
		update_user_meta( $user_id, self::TOKEN_META_KEY, $tokens );

		return $token;
	}

	/**
	 * Revoke a specific Bearer token.
	 *
	 * @param int    $user_id User ID.
	 * @param string $token Raw token.
	 * @return bool
	 */
	public static function revoke_token( $user_id, $token ) {
		$tokens = get_user_meta( $user_id, self::TOKEN_META_KEY, true );
		if ( ! is_array( $tokens ) ) {
			return true;
		}

		$token_hash = hash( 'sha256', $token );
		if ( isset( $tokens[ $token_hash ] ) ) {
			unset( $tokens[ $token_hash ] );
			update_user_meta( $user_id, self::TOKEN_META_KEY, $tokens );
		}

		return true;
	}

	/**
	 * Permission callback: Require authenticated student.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public static function require_authenticated_student( $request ) {
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			return true;
		}

		return new WP_Error(
			'rest_not_logged_in',
			__( 'Authentication required. Please provide a valid Bearer token.', 'indianagri-quiz-engine' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * Permission callback for starting or resuming a quiz attempt.
	 * Allows authenticated candidates, free quizzes, or sample demo drills.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public static function check_quiz_start_permission( $request ) {
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			return true;
		}

		$is_demo = ! empty( $request->get_param( 'demo_mode' ) ) || ! empty( $request->get_param( 'is_demo' ) );
		if ( $is_demo ) {
			return true;
		}

		$quiz_id = absint( $request->get_param( 'id' ) );
		if ( $quiz_id > 0 ) {
			$quiz = IAQ_Quiz_Builder::get_quiz( $quiz_id );
			if ( $quiz && 'free' === $quiz->access_type ) {
				return true;
			}
		}

		return new WP_Error(
			'rest_not_logged_in',
			__( 'Authentication required. Please log in to your account or subscribe to unlock this examination.', 'indianagri-quiz-engine' ),
			array( 'status' => 401 )
		);
	}

	/* =========================================================================
	 * ROUTE HANDLERS: AUTH
	 * ========================================================================= */

	/**
	 * Handle student login.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_login( $request ) {
		$username = sanitize_text_field( $request->get_param( 'username' ) );
		$password = (string) $request->get_param( 'password' );

		if ( empty( $username ) || empty( $password ) ) {
			return new WP_Error(
				'missing_credentials',
				__( 'Please provide both username/email and password.', 'indianagri-quiz-engine' ),
				array( 'status' => 400 )
			);
		}

		$user = wp_authenticate( $username, $password );
		if ( is_wp_error( $user ) ) {
			return new WP_Error(
				'invalid_credentials',
				__( 'Invalid username or password.', 'indianagri-quiz-engine' ),
				array( 'status' => 401 )
			);
		}

		$token         = self::generate_auth_token( $user->ID );
		$subscriptions = IAQ_Access_Control::get_user_active_subscriptions( $user->ID );

		$response_data = array(
			'token'         => $token,
			'user'          => array(
				'id'           => $user->ID,
				'username'     => $user->user_login,
				'email'        => $user->user_email,
				'display_name' => $user->display_name,
				'first_name'   => $user->first_name,
				'last_name'    => $user->last_name,
				'avatar'       => get_avatar_url( $user->ID, array( 'size' => 128 ) ),
			),
			'subscriptions' => $subscriptions,
		);

		return rest_ensure_response( $response_data );
	}

	/**
	 * Handle student registration.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_register( $request ) {
		if ( ! get_option( 'users_can_register' ) ) {
			return new WP_Error(
				'registration_disabled',
				__( 'Student registration is currently disabled by administrator.', 'indianagri-quiz-engine' ),
				array( 'status' => 403 )
			);
		}

		$username = sanitize_user( $request->get_param( 'username' ) );
		$email    = sanitize_email( $request->get_param( 'email' ) );
		$password = (string) $request->get_param( 'password' );
		$name     = sanitize_text_field( $request->get_param( 'display_name' ) );

		if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
			return new WP_Error(
				'missing_fields',
				__( 'Please provide username, email, and password.', 'indianagri-quiz-engine' ),
				array( 'status' => 400 )
			);
		}

		if ( username_exists( $username ) ) {
			return new WP_Error(
				'username_exists',
				__( 'This username is already registered.', 'indianagri-quiz-engine' ),
				array( 'status' => 409 )
			);
		}

		if ( email_exists( $email ) ) {
			return new WP_Error(
				'email_exists',
				__( 'This email address is already registered.', 'indianagri-quiz-engine' ),
				array( 'status' => 409 )
			);
		}

		$user_id = wp_create_user( $username, $password, $email );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		if ( ! empty( $name ) ) {
			wp_update_user(
				array(
					'ID'           => $user_id,
					'display_name' => $name,
				)
			);
		}

		$token = self::generate_auth_token( $user_id );
		$user  = get_userdata( $user_id );

		return rest_ensure_response(
			array(
				'token' => $token,
				'user'  => array(
					'id'           => $user->ID,
					'username'     => $user->user_login,
					'email'        => $user->user_email,
					'display_name' => $user->display_name,
					'avatar'       => get_avatar_url( $user->ID, array( 'size' => 128 ) ),
				),
			)
		);
	}

	/**
	 * Handle forgot password.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_forgot_password( $request ) {
		$user_input = sanitize_text_field( $request->get_param( 'user_login' ) );
		if ( empty( $user_input ) ) {
			return new WP_Error(
				'missing_login',
				__( 'Please provide your registered username or email.', 'indianagri-quiz-engine' ),
				array( 'status' => 400 )
			);
		}

		$user_data = is_email( $user_input ) ? get_user_by( 'email', $user_input ) : get_user_by( 'login', $user_input );
		if ( ! $user_data ) {
			// Return success to prevent username enumeration.
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => __( 'If an account exists with this email/username, password reset instructions have been sent.', 'indianagri-quiz-engine' ),
				)
			);
		}

		// Use core retrieve_password if available.
		if ( function_exists( 'retrieve_password' ) ) {
			retrieve_password( $user_data->user_login );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Password reset instructions have been sent to your registered email.', 'indianagri-quiz-engine' ),
			)
		);
	}

	/**
	 * Get current student profile.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_current_student_profile() {
		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );

		$subscriptions = IAQ_Access_Control::get_user_active_subscriptions( $user_id );
		$stats         = IAQ_Quiz_Attempt::get_user_dashboard_stats( $user_id );

		return rest_ensure_response(
			array(
				'user'          => array(
					'id'           => $user->ID,
					'username'     => $user->user_login,
					'email'        => $user->user_email,
					'display_name' => $user->display_name,
					'first_name'   => $user->first_name,
					'last_name'    => $user->last_name,
					'avatar'       => get_avatar_url( $user->ID, array( 'size' => 128 ) ),
				),
				'subscriptions' => $subscriptions,
				'stats'         => $stats,
			)
		);
	}

	/**
	 * Handle logout.
	 *
	 * @return WP_REST_Response
	 */
	public static function handle_logout() {
		$user_id = get_current_user_id();
		$token   = self::get_bearer_token();

		if ( $user_id && $token ) {
			self::revoke_token( $user_id, $token );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Successfully logged out.', 'indianagri-quiz-engine' ),
			)
		);
	}

	/* =========================================================================
	 * ROUTE HANDLERS: DASHBOARD & EXAMS
	 * ========================================================================= */

	/**
	 * Get student dashboard summary.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_dashboard_summary() {
		$user_id = get_current_user_id();

		$stats             = IAQ_Quiz_Attempt::get_user_dashboard_stats( $user_id );
		$current_streak    = IAQ_Study_Streaks::get_current_streak( $user_id );
		$best_streak       = IAQ_Study_Streaks::get_best_streak( $user_id );
		$user_goals        = IAQ_Study_Streaks::get_user_goals( $user_id );
		$active_attempt    = IAQ_Quiz_Attempt::get_user_active_attempt( $user_id );
		$continue_quizzes  = IAQ_Quiz_Attempt::get_user_continue_practicing_quizzes( $user_id, 4 );
		$recent_history    = IAQ_Quiz_Attempt::get_user_attempt_history( $user_id, 5 );
		$subject_mastery   = IAQ_Quiz_Attempt::get_user_overall_subject_mastery( $user_id );
		$performance_trend = IAQ_Quiz_Attempt::get_user_performance_trend( $user_id, 6 );

		return rest_ensure_response(
			array(
				'stats'             => $stats,
				'streaks'           => array(
					'current' => $current_streak,
					'best'    => $best_streak,
					'goals'   => $user_goals,
				),
				'active_attempt'    => $active_attempt,
				'continue_quizzes'  => $continue_quizzes,
				'recent_history'    => $recent_history,
				'subject_mastery'   => $subject_mastery,
				'performance_trend' => $performance_trend,
			)
		);
	}

	/**
	 * Get examinations catalog.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_examinations() {
		$exams = IAQ_Examinations::get_examinations( array( 'status' => 'active' ) );
		return rest_ensure_response( $exams );
	}

	/**
	 * Get single examination details.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_examination_details( $request ) {
		$id   = absint( $request->get_param( 'id' ) );
		$exam = IAQ_Examinations::get_examination( $id );

		if ( ! $exam ) {
			return new WP_Error( 'not_found', __( 'Examination not found.', 'indianagri-quiz-engine' ), array( 'status' => 404 ) );
		}

		$subjects = IAQ_Examinations::get_examination_subjects( $id );

		return rest_ensure_response(
			array(
				'examination' => $exam,
				'subjects'    => $subjects,
			)
		);
	}

	/* =========================================================================
	 * ROUTE HANDLERS: PRACTICE SETS
	 * ========================================================================= */

	/**
	 * Get books catalog for Practice Sets.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_practice_books() {
		$books = IAQ_Practice_Sets::get_books( array( 'status' => 'active' ) );
		return rest_ensure_response( $books );
	}

	/**
	 * Get practice sets inside a book.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_book_practice_sets( $request ) {
		$id      = absint( $request->get_param( 'id' ) );
		$user_id = get_current_user_id();
		$book    = IAQ_Practice_Sets::get_book( $id );

		if ( ! $book ) {
			return new WP_Error( 'not_found', __( 'Book not found.', 'indianagri-quiz-engine' ), array( 'status' => 404 ) );
		}

		$sets = IAQ_Practice_Sets::get_book_practice_sets( $id, $user_id );

		return rest_ensure_response(
			array(
				'book' => $book,
				'sets' => $sets,
			)
		);
	}

	/**
	 * Get subjects catalog for Practice Sets.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_practice_subjects() {
		$subjects = IAQ_Practice_Sets::get_subjects_catalog();
		return rest_ensure_response( $subjects );
	}

	/**
	 * Get practice sets inside a subject.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_subject_practice_sets( $request ) {
		$id      = absint( $request->get_param( 'id' ) );
		$user_id = get_current_user_id();
		$subject = IAQ_Question_Bank::get_subject( $id );

		if ( ! $subject ) {
			return new WP_Error( 'not_found', __( 'Subject not found.', 'indianagri-quiz-engine' ), array( 'status' => 404 ) );
		}

		$sets = IAQ_Practice_Sets::get_subject_practice_sets( $id, $user_id );

		return rest_ensure_response(
			array(
				'subject' => $subject,
				'sets'    => $sets,
			)
		);
	}

	/**
	 * Get practice sets hierarchy for an examination.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_exam_practice_sets( $request ) {
		$id      = absint( $request->get_param( 'id' ) );
		$user_id = get_current_user_id();
		$exam    = IAQ_Examinations::get_examination( $id );

		if ( ! $exam ) {
			return new WP_Error( 'not_found', __( 'Examination not found.', 'indianagri-quiz-engine' ), array( 'status' => 404 ) );
		}

		$hierarchy = IAQ_Practice_Sets::get_exam_practice_hierarchy( $id, $user_id );

		return rest_ensure_response(
			array(
				'examination' => $exam,
				'hierarchy'   => $hierarchy,
			)
		);
	}

	/* =========================================================================
	 * ROUTE HANDLERS: MOCK TESTS
	 * ========================================================================= */

	/**
	 * Get 10 Main Mock Test Categories.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_mock_categories() {
		$categories = IAQ_Mock_Tests::get_categories();
		return rest_ensure_response( $categories );
	}

	/**
	 * Get mock test hierarchy drilldown.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function get_mock_hierarchy( $request ) {
		$user_id = get_current_user_id();
		$cat_id  = absint( $request->get_param( 'category_id' ) );

		$filters = array(
			'exam_id'     => absint( $request->get_param( 'exam_id' ) ),
			'state_id'    => absint( $request->get_param( 'state_id' ) ),
			'univ_id'     => absint( $request->get_param( 'univ_id' ) ),
			'vacancy_id'  => absint( $request->get_param( 'vacancy_id' ) ),
			'search'      => sanitize_text_field( $request->get_param( 'search' ) ),
		);

		if ( $cat_id > 0 ) {
			$exams = IAQ_Mock_Tests::get_category_examinations( $cat_id );
			$mocks = IAQ_Mock_Tests::get_context_mock_tests(
				array_merge( array( 'category_id' => $cat_id ), $filters ),
				$user_id
			);

			return rest_ensure_response(
				array(
					'category_id'  => $cat_id,
					'examinations' => $exams,
					'mock_tests'   => $mocks,
				)
			);
		}

		$categories = IAQ_Mock_Tests::get_categories( array( 'status' => 'active' ) );
		return rest_ensure_response( $categories );
	}

	/**
	 * Get quizzes inside a Mock Series.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_mock_series_quizzes( $request ) {
		$id      = absint( $request->get_param( 'id' ) );
		$user_id = get_current_user_id();
		$quizzes = IAQ_Mock_Tests::get_context_mock_tests( array( 'series_id' => $id ), $user_id );

		return rest_ensure_response( $quizzes );
	}

	/* =========================================================================
	 * ROUTE HANDLERS: QUIZ CBT ATTEMPT ENGINE
	 * ========================================================================= */

	/**
	 * Get quiz instructions and metadata.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_quiz_instructions( $request ) {
		$quiz_id = absint( $request->get_param( 'id' ) );
		$quiz    = IAQ_Quiz_Builder::get_quiz( $quiz_id );

		if ( ! $quiz || 'published' !== $quiz->status ) {
			return new WP_Error( 'not_found', __( 'Quiz not found or unpublished.', 'indianagri-quiz-engine' ), array( 'status' => 404 ) );
		}

		$user_id     = get_current_user_id();
		$entitlement = IAQ_Access_Control::check_user_entitlement( $user_id, $quiz );

		// Sample questions preview (first 5 questions) for syllabus transparency
		$raw_questions   = IAQ_Quiz_Builder::get_quiz_questions( $quiz_id );
		$sample_preview = array();
		if ( ! empty( $raw_questions ) ) {
			$s_idx = 1;
			foreach ( array_slice( $raw_questions, 0, 5 ) as $sq ) {
				$sample_preview[] = array(
					'index'            => $s_idx++,
					'id'               => (int) $sq->id,
					'question_text'    => $sq->question_text,
					'question_text_hi' => ! empty( $sq->question_text_hi ) ? $sq->question_text_hi : '',
					'subject_name'     => isset( $sq->subject_name ) ? $sq->subject_name : '',
				);
			}
		}

		return rest_ensure_response(
			array(
				'quiz'             => array(
					'id'                 => (int) $quiz->id,
					'title'              => $quiz->title,
					'description'        => $quiz->description,
					'quiz_type'          => $quiz->quiz_type,
					'total_questions'    => (int) $quiz->total_questions,
					'total_marks'        => (float) $quiz->total_marks,
					'duration_minutes'   => (int) $quiz->duration_minutes,
					'correct_marks'      => (float) $quiz->correct_marks,
					'negative_marks'     => (float) $quiz->negative_marks,
					'pass_percentage'    => (float) $quiz->pass_percentage,
					'instructions'       => $quiz->instructions,
					'show_answers_mode'  => $quiz->show_answers_mode,
					'access_type'        => $quiz->access_type,
				),
				'entitlement'      => $entitlement,
				'sample_questions' => $sample_preview,
			)
		);
	}

	/**
	 * Start or resume a quiz attempt.
	 * Supports full student attempts, free public attempts, and instant demo practice sessions.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function start_or_resume_quiz_attempt( $request ) {
		$quiz_id   = absint( $request->get_param( 'id' ) );
		$user_id   = get_current_user_id();
		$demo_mode = ! empty( $request->get_param( 'demo_mode' ) ) || ! empty( $request->get_param( 'is_demo' ) );

		// Demo practice mode or unauthenticated candidate preview
		if ( $user_id <= 0 && $demo_mode ) {
			$quiz = IAQ_Quiz_Builder::get_quiz( $quiz_id );
			if ( ! $quiz ) {
				return new WP_Error( 'not_found', __( 'Quiz not found.', 'indianagri-quiz-engine' ), array( 'status' => 404 ) );
			}
			$raw_questions  = IAQ_Quiz_Builder::get_quiz_questions( $quiz_id );
			$demo_questions = array_slice( $raw_questions, 0, 10 );
			$safe_questions = array();
			$index = 1;

			foreach ( $demo_questions as $q ) {
				$opts = array(
					array( 'key' => 'A', 'text' => $q->option_a, 'text_hi' => ! empty( $q->option_a_hi ) ? $q->option_a_hi : '' ),
					array( 'key' => 'B', 'text' => $q->option_b, 'text_hi' => ! empty( $q->option_b_hi ) ? $q->option_b_hi : '' ),
					array( 'key' => 'C', 'text' => $q->option_c, 'text_hi' => ! empty( $q->option_c_hi ) ? $q->option_c_hi : '' ),
					array( 'key' => 'D', 'text' => $q->option_d, 'text_hi' => ! empty( $q->option_d_hi ) ? $q->option_d_hi : '' ),
				);

				$safe_questions[] = array(
					'index'            => $index++,
					'id'               => (int) $q->id,
					'question_text'    => $q->question_text,
					'question_text_hi' => ! empty( $q->question_text_hi ) ? $q->question_text_hi : '',
					'subject_name'     => isset( $q->subject_name ) ? $q->subject_name : '',
					'topic_name'       => isset( $q->topic_name ) ? $q->topic_name : '',
					'marks'            => (float) $q->marks,
					'negative_marks'   => (float) $q->negative_marks,
					'options'          => $opts,
					'selected'         => null,
					'is_marked'        => 0,
					'immediate_feedback' => array(
						'is_immediate'     => true,
						'is_locked'        => false,
						'correct_option'   => strtoupper( trim( $q->correct_option ) ),
						'explanation'      => IAQ_Quiz_Attempt::clean_explanation( $q->explanation ),
						'feedback_message' => __( 'Review explanation to master this concept.', 'indianagri-quiz-engine' ),
					),
				);
			}

			return rest_ensure_response(
				array(
					'attempt_id'        => 9999000 + $quiz_id,
					'quiz_id'           => (int) $quiz->id,
					'quiz_title'        => $quiz->title . ' (Sample Practice Drill)',
					'quiz_type'         => $quiz->quiz_type,
					'show_answers_mode' => 'immediate',
					'is_immediate'      => true,
					'is_demo'           => true,
					'duration_minutes'  => 0,
					'remaining_seconds' => null,
					'total_questions'   => count( $safe_questions ),
					'total_marks'       => (float) count( $safe_questions ),
					'pass_percentage'   => 50.0,
					'questions'         => $safe_questions,
				)
			);
		}

		$result = IAQ_Quiz_Attempt::start_or_resume_attempt( $quiz_id, $user_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Save answer for a question in an attempt.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function save_quiz_answer( $request ) {
		$attempt_id           = absint( $request->get_param( 'id' ) );
		$question_id          = absint( $request->get_param( 'question_id' ) );
		$selected_option      = sanitize_text_field( $request->get_param( 'selected_option' ) );
		$is_marked_for_review = ! empty( $request->get_param( 'is_marked_for_review' ) ) ? 1 : 0;
		$time_spent           = absint( $request->get_param( 'time_spent_seconds' ) );
		$user_id              = get_current_user_id();

		$result = IAQ_Quiz_Attempt::save_answer(
			$attempt_id,
			$user_id,
			$question_id,
			$selected_option,
			$is_marked_for_review,
			$time_spent
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Submit a quiz attempt.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function submit_quiz_attempt( $request ) {
		$attempt_id = absint( $request->get_param( 'id' ) );
		$time_spent = absint( $request->get_param( 'time_spent_seconds' ) );
		$user_id    = get_current_user_id();

		$result = IAQ_Quiz_Attempt::submit_attempt( $attempt_id, $user_id, $time_spent );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Get scorecard for a completed attempt.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_attempt_scorecard( $request ) {
		$attempt_id = absint( $request->get_param( 'id' ) );
		$user_id    = get_current_user_id();

		$scorecard = IAQ_Quiz_Attempt::get_attempt_scorecard( $attempt_id, $user_id );
		if ( is_wp_error( $scorecard ) ) {
			return $scorecard;
		}

		$breakdown = IAQ_Quiz_Attempt::get_attempt_subject_breakdown( $attempt_id, $user_id );

		return rest_ensure_response(
			array(
				'scorecard' => $scorecard,
				'breakdown' => $breakdown,
			)
		);
	}

	/**
	 * Get solution review questions for a completed attempt.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_attempt_solutions( $request ) {
		$attempt_id = absint( $request->get_param( 'id' ) );
		$user_id    = get_current_user_id();

		$questions = IAQ_Quiz_Attempt::get_attempt_review_questions( $attempt_id, $user_id );
		if ( is_wp_error( $questions ) ) {
			return $questions;
		}

		return rest_ensure_response( $questions );
	}

	/* =========================================================================
	 * ROUTE HANDLERS: STUDENT LEARNING FEATURES
	 * ========================================================================= */

	/**
	 * Get user bookmarks.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function get_bookmarks( $request ) {
		$user_id = get_current_user_id();
		$limit   = absint( $request->get_param( 'limit' ) );
		$offset  = absint( $request->get_param( 'offset' ) );

		$args = array(
			'limit'  => $limit > 0 ? $limit : 50,
			'offset' => $offset,
		);

		$bookmarks = IAQ_Bookmarks::get_user_bookmarks( $user_id, $args );
		$count     = IAQ_Bookmarks::get_bookmark_count( $user_id );

		return rest_ensure_response(
			array(
				'total'     => $count,
				'bookmarks' => $bookmarks,
			)
		);
	}

	/**
	 * Toggle bookmark on a question.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function toggle_bookmark( $request ) {
		$user_id     = get_current_user_id();
		$question_id = absint( $request->get_param( 'question_id' ) );
		$notes       = sanitize_textarea_field( $request->get_param( 'notes' ) );

		$res = IAQ_Bookmarks::toggle_bookmark( $user_id, $question_id, $notes );
		return rest_ensure_response( $res );
	}

	/**
	 * Create revision quiz from bookmarks.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_bookmark_revision_quiz() {
		$user_id = get_current_user_id();
		$quiz_id = IAQ_Bookmarks::create_revision_quiz( $user_id );

		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}

		return rest_ensure_response( array( 'quiz_id' => $quiz_id ) );
	}

	/**
	 * Get wrong answer notebook entries.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function get_wrong_notebook( $request ) {
		$user_id = get_current_user_id();
		$status  = sanitize_text_field( $request->get_param( 'status' ) );
		$status  = ! empty( $status ) ? $status : 'active';
		$limit   = absint( $request->get_param( 'limit' ) );
		$offset  = absint( $request->get_param( 'offset' ) );

		$args = array(
			'status' => $status,
			'limit'  => $limit > 0 ? $limit : 50,
			'offset' => $offset,
		);

		$notebook = IAQ_Wrong_Notebook::get_user_notebook( $user_id, $args );
		$total    = IAQ_Wrong_Notebook::get_notebook_count( $user_id, array( 'status' => $status ) );

		return rest_ensure_response(
			array(
				'total'    => $total,
				'entries'  => $notebook,
			)
		);
	}

	/**
	 * Update wrong answer notebook status (active <-> resolved).
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function update_wrong_notebook_status( $request ) {
		$user_id     = get_current_user_id();
		$question_id = absint( $request->get_param( 'question_id' ) );
		$status      = sanitize_text_field( $request->get_param( 'status' ) );

		$updated = IAQ_Wrong_Notebook::update_status( $user_id, $question_id, $status );
		return rest_ensure_response( array( 'updated' => $updated ) );
	}

	/**
	 * Remove question from wrong answer notebook.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function remove_wrong_notebook_entry( $request ) {
		$user_id     = get_current_user_id();
		$question_id = absint( $request->get_param( 'question_id' ) );

		$removed = IAQ_Wrong_Notebook::remove_entry( $user_id, $question_id );
		return rest_ensure_response( array( 'removed' => $removed ) );
	}

	/**
	 * Create retry quiz from wrong answer notebook.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_wrong_retry_quiz( $request ) {
		$user_id    = get_current_user_id();
		$subject_id = absint( $request->get_param( 'subject_id' ) );

		$args = array();
		if ( $subject_id > 0 ) {
			$args['subject_id'] = $subject_id;
		}

		$quiz_id = IAQ_Wrong_Notebook::create_retry_quiz( $user_id, $args );
		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}

		return rest_ensure_response( array( 'quiz_id' => $quiz_id ) );
	}

	/**
	 * Get options for custom practice test.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_custom_practice_options() {
		$subjects = IAQ_Question_Bank::get_subjects( array( 'status' => 'active' ) );
		$exams    = IAQ_Examinations::get_examinations( array( 'status' => 'active' ) );

		return rest_ensure_response(
			array(
				'subjects'     => $subjects,
				'examinations' => $exams,
				'difficulties' => array( 'easy', 'medium', 'hard' ),
			)
		);
	}

	/**
	 * Generate custom practice test.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_custom_practice_quiz( $request ) {
		$user_id = get_current_user_id();
		$params  = $request->get_json_params();

		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$quiz_id = IAQ_Custom_Practice::generate_custom_test( $user_id, $params );
		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}

		return rest_ensure_response( array( 'quiz_id' => $quiz_id ) );
	}

	/**
	 * Get study streak and 14-day activity calendar.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_study_streaks() {
		$user_id  = get_current_user_id();
		$current  = IAQ_Study_Streaks::get_current_streak( $user_id );
		$best     = IAQ_Study_Streaks::get_best_streak( $user_id );
		$calendar = IAQ_Study_Streaks::get_activity_calendar( $user_id, 14 );
		$goals    = IAQ_Study_Streaks::get_user_goals( $user_id );

		return rest_ensure_response(
			array(
				'current_streak' => $current,
				'best_streak'    => $best,
				'calendar'       => $calendar,
				'goals'          => $goals,
			)
		);
	}

	/**
	 * Update weekly study goals.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function update_study_goals( $request ) {
		$user_id      = get_current_user_id();
		$weekly_tests = absint( $request->get_param( 'weekly_tests' ) );
		$weekly_qs    = absint( $request->get_param( 'weekly_questions' ) );

		$success = IAQ_Study_Streaks::update_user_goals( $user_id, $weekly_tests, $weekly_qs );
		return rest_ensure_response( array( 'success' => $success ) );
	}

	/**
	 * Get leaderboard rankings.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function get_leaderboard( $request ) {
		$scope         = sanitize_key( $request->get_param( 'scope' ) );
		$quiz_id       = absint( $request->get_param( 'quiz_id' ) );
		$exam_category = sanitize_text_field( $request->get_param( 'exam_category' ) );
		$period        = sanitize_key( $request->get_param( 'period' ) );
		$page          = absint( $request->get_param( 'page' ) );

		$data = IAQ_Leaderboard::get_leaderboard(
			array(
				'scope'         => ! empty( $scope ) ? $scope : 'overall',
				'quiz_id'       => $quiz_id,
				'exam_category' => $exam_category,
				'period'        => ! empty( $period ) ? $period : 'all_time',
				'page'          => $page > 0 ? $page : 1,
				'limit'         => 20,
				'current_user'  => get_current_user_id(),
			)
		);

		return rest_ensure_response( $data );
	}

	/* =========================================================================
	 * ROUTE HANDLERS: SUBSCRIPTIONS & MONETIZATION
	 * ========================================================================= */

	/**
	 * Get subscription plans pricing catalog.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_subscription_plans() {
		$plans = IAQ_Access_Control::get_plans( array( 'status' => 'active' ) );
		return rest_ensure_response( $plans );
	}

	/**
	 * Get student subscriptions.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_my_subscriptions() {
		$user_id = get_current_user_id();
		$active  = IAQ_Access_Control::get_user_active_subscriptions( $user_id );
		$all     = IAQ_Access_Control::get_user_subscriptions( $user_id, 'all' );

		return rest_ensure_response(
			array(
				'active' => $active,
				'all'    => $all,
			)
		);
	}

	/**
	 * Get server-verified WooCommerce checkout URL for a subscription plan.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_checkout_url( $request ) {
		$plan_id = absint( $request->get_param( 'plan_id' ) );
		$plan    = IAQ_Access_Control::get_plan( $plan_id );

		if ( ! $plan || 'active' !== $plan->status ) {
			return new WP_Error( 'not_found', __( 'Subscription plan not found or inactive.', 'indianagri-quiz-engine' ), array( 'status' => 404 ) );
		}

		$checkout_url = home_url( '/checkout/' );
		if ( function_exists( 'wc_get_checkout_url' ) ) {
			$checkout_url = wc_get_checkout_url();
		}

		if ( ! empty( $plan->wc_product_id ) ) {
			$checkout_url = add_query_arg(
				array(
					'add-to-cart' => $plan->wc_product_id,
					'iaq_plan_id' => $plan->id,
				),
				$checkout_url
			);
		} else {
			$checkout_url = add_query_arg( 'iaq_plan_id', $plan->id, $checkout_url );
		}

		return rest_ensure_response(
			array(
				'plan_id'      => (int) $plan->id,
				'plan_name'    => $plan->name,
				'price'        => (float) $plan->price,
				'currency'     => $plan->currency,
				'checkout_url' => $checkout_url,
			)
		);
	}

	/**
	 * Get application update and version information.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public static function get_app_version_info( $request ) {
		$client_version = sanitize_text_field( $request->get_param( 'current_version' ) );
		if ( empty( $client_version ) ) {
			$client_version = '1.0.0';
		}

		$latest_version = get_option( 'iaq_app_latest_version', '1.1.0' );
		$min_version    = get_option( 'iaq_app_min_version', '1.0.0' );
		$force_update   = (bool) get_option( 'iaq_app_force_update', 0 );
		$update_title   = get_option( 'iaq_app_update_title', __( 'Exciting New Update Available!', 'indianagri-quiz-engine' ) );
		$update_message = get_option( 'iaq_app_update_message', __( 'We have updated the IndianAgri mobile app with new Subject-Wise Practice Sets (Set 1, 2, 3), revamped Examination pathways, and enhanced exam speed.', 'indianagri-quiz-engine' ) );
		$release_notes  = get_option( 'iaq_app_release_notes', "• New Subject-wise Practice Sets (Set 1, 2, 3)\n• Revamped Practice by Books & Examinations\n• Ultra-fast CBT quiz timer and instant review\n• Enhanced login stability and offline crash prevention" );
		$default_update_url = 'https://github.com/veerta777/IndianAgri-Quiz-Engine-/releases/latest/download/IndianAgriExam.apk';
		$update_url         = get_option( 'iaq_app_update_url', $default_update_url );
		if ( empty( $update_url ) || false !== strpos( $update_url, 'indianagriexam.com/mobile-app' ) ) {
			$update_url = $default_update_url;
		}
		$published_at   = get_option( 'iaq_app_update_date', date( 'Y-m-d' ) );

		// Parse release notes into list of strings if string
		$notes_array = array();
		if ( is_string( $release_notes ) ) {
			$lines = explode( "\n", $release_notes );
			foreach ( $lines as $line ) {
				$line = trim( $line, " \t\n\r\0\x0B•-*" );
				if ( ! empty( $line ) ) {
					$notes_array[] = $line;
				}
			}
		} elseif ( is_array( $release_notes ) ) {
			$notes_array = $release_notes;
		}

		// Check if update is available
		$is_update_available = version_compare( $client_version, $latest_version, '<' );
		$is_force_update     = $force_update || version_compare( $client_version, $min_version, '<' );

		return rest_ensure_response(
			array(
				'success'               => true,
				'current_version'       => $client_version,
				'latest_version'        => $latest_version,
				'min_supported_version' => $min_version,
				'is_update_available'   => (bool) $is_update_available,
				'is_force_update'       => (bool) $is_force_update,
				'title'                 => $update_title,
				'message'               => $update_message,
				'release_notes'         => $notes_array,
				'update_url'            => $update_url,
				'published_at'          => $published_at,
			)
		);
	}
}
