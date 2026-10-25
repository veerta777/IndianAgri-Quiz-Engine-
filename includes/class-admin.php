<?php
/**
 * Admin controller: Menus, views, asset registration, forms, and AJAX actions.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IAQ_Admin
 */
class IAQ_Admin {

	/**
	 * Notices to display.
	 *
	 * @var array
	 */
	private $notices = array();

	/**
	 * Initialize admin hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_form_submissions' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );

		// AJAX endpoints.
		add_action( 'wp_ajax_iaq_get_topics_by_subject', array( $this, 'ajax_get_topics_by_subject' ) );
	}

	/**
	 * Register WordPress admin menus and submenus.
	 */
	public function register_admin_menus() {
		$capability = 'manage_options';

		// Top-level menu: Dashboard & Analytics.
		add_menu_page(
			__( 'IndianAgri Quiz Engine', 'indianagri-quiz-engine' ),
			__( 'Agri Quiz Engine', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-dashboard',
			array( $this, 'render_dashboard_page' ),
			'dashicons-welcome-learn-more',
			56
		);

		// 1. Dashboard.
		add_submenu_page(
			'iaq-dashboard',
			__( 'Dashboard & Analytics', 'indianagri-quiz-engine' ),
			__( 'Dashboard', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-dashboard',
			array( $this, 'render_dashboard_page' )
		);

		// 2. Examinations.
		add_submenu_page(
			'iaq-dashboard',
			__( 'Examinations & Categories', 'indianagri-quiz-engine' ),
			__( 'Examinations', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-examinations',
			array( $this, 'render_examinations_page' )
		);

		// 3. Practice Books.
		add_submenu_page(
			'iaq-dashboard',
			__( 'Practice Books', 'indianagri-quiz-engine' ),
			__( 'Practice Books', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-books',
			array( $this, 'render_books_page' )
		);

		// 3. Subjects & Topics.
		add_submenu_page(
			'iaq-dashboard',
			__( 'Subjects & Topics', 'indianagri-quiz-engine' ),
			__( 'Subjects & Topics', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-subjects-topics',
			array( $this, 'render_subjects_topics_page' )
		);

		// 4. Question Bank.
		add_submenu_page(
			'iaq-dashboard',
			__( 'Question Bank', 'indianagri-quiz-engine' ),
			__( 'Question Bank', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-question-bank',
			array( $this, 'render_question_bank_page' )
		);

		// 5. Add New Question.
		add_submenu_page(
			'iaq-dashboard',
			__( 'Add New Question', 'indianagri-quiz-engine' ),
			__( 'Add Question', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-add-question',
			array( $this, 'render_add_question_page' )
		);

		// 6. Quizzes & Mock Tests.
		add_submenu_page(
			'iaq-dashboard',
			__( 'Quizzes & Mock Tests', 'indianagri-quiz-engine' ),
			__( 'Quizzes & Mocks', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-quizzes',
			array( $this, 'render_quizzes_page' )
		);

		// 7. Add New Quiz.
		add_submenu_page(
			'iaq-dashboard',
			__( 'Add New Quiz', 'indianagri-quiz-engine' ),
			__( 'Add New Quiz', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-add-quiz',
			array( $this, 'render_add_quiz_page' )
		);

		// 7b. Mock Test Hierarchy.
		add_submenu_page(
			'iaq-dashboard',
			__( 'Mock Test Hierarchy', 'indianagri-quiz-engine' ),
			__( 'Mock Test Hierarchy', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-mock-tests',
			array( $this, 'render_mock_tests_page' )
		);

		// 8. Subscription Plans.
		add_submenu_page(
			'iaq-dashboard',
			__( 'Subscription Plans', 'indianagri-quiz-engine' ),
			__( 'Subscription Plans', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-subscription-plans',
			array( $this, 'render_subscription_plans_page' )
		);

		// 9. Student Subscriptions.
		add_submenu_page(
			'iaq-dashboard',
			__( 'Student Subscriptions Audit', 'indianagri-quiz-engine' ),
			__( 'Student Subscriptions', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-user-subscriptions',
			array( $this, 'render_user_subscriptions_page' )
		);

		// 10. Evaluation & Attempts.
		add_submenu_page(
			'iaq-dashboard',
			__( 'Quiz Attempts & Results', 'indianagri-quiz-engine' ),
			__( 'Evaluation & Results', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-attempts',
			array( $this, 'render_attempts_page' )
		);

		// 11. Import & Export.
		add_submenu_page(
			'iaq-dashboard',
			__( 'Import / Export', 'indianagri-quiz-engine' ),
			__( 'Import / Export', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-import-export',
			array( $this, 'render_import_export_page' )
		);

		// 12. Settings.
		add_submenu_page(
			'iaq-dashboard',
			__( 'Settings', 'indianagri-quiz-engine' ),
			__( 'Settings', 'indianagri-quiz-engine' ),
			$capability,
			'iaq-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin scripts and stylesheets.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( $hook_suffix ) {
		// Only enqueue on our plugin pages.
		if ( strpos( $hook_suffix, 'iaq-' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'iaq-admin-styles',
			IAQ_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			IAQ_VERSION
		);

		wp_enqueue_script(
			'iaq-admin-scripts',
			IAQ_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			IAQ_VERSION,
			true
		);

		wp_localize_script(
			'iaq-admin-scripts',
			'iaqAdminData',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'iaq_admin_ajax_nonce' ),
				'i18n'      => array(
					'confirmDelete' => __( 'Are you sure you want to delete this item? This action cannot be undone.', 'indianagri-quiz-engine' ),
					'selectTopic'   => __( 'Select Topic', 'indianagri-quiz-engine' ),
					'allTopics'     => __( 'All Topics', 'indianagri-quiz-engine' ),
					'loading'       => __( 'Loading...', 'indianagri-quiz-engine' ),
				),
			)
		);
	}

	/**
	 * Handle admin form submissions (CRUD, CSV Import, CSV Export).
	 */
	public function handle_form_submissions() {
		if ( ! is_admin() || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_iaq_quizzes' ) ) ) {
			return;
		}

		// 1. Download Sample CSV.
		if ( isset( $_GET['iaq_action'] ) && 'download_sample_csv' === $_GET['iaq_action'] ) {
			check_admin_referer( 'iaq_download_sample_csv' );
			IAQ_Import_Export::generate_sample_csv();
		}

		// 1b. Download Sample JSON.
		if ( isset( $_GET['iaq_action'] ) && 'download_sample_json' === $_GET['iaq_action'] ) {
			check_admin_referer( 'iaq_download_sample_json' );
			IAQ_Import_Export::generate_sample_json();
		}

		// 2. Export CSV.
		if ( isset( $_GET['iaq_action'] ) && 'export_csv' === $_GET['iaq_action'] ) {
			check_admin_referer( 'iaq_export_csv' );
			$filter_args = array(
				'search'        => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
				'subject_id'    => isset( $_GET['subject_id'] ) ? absint( $_GET['subject_id'] ) : 0,
				'topic_id'      => isset( $_GET['topic_id'] ) ? absint( $_GET['topic_id'] ) : 0,
				'exam_category' => isset( $_GET['exam_category'] ) ? sanitize_text_field( wp_unslash( $_GET['exam_category'] ) ) : '',
				'difficulty'    => isset( $_GET['difficulty'] ) ? sanitize_key( $_GET['difficulty'] ) : '',
				'status'        => isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '',
			);
			IAQ_Import_Export::export_to_csv( $filter_args );
		}

		// 3. Save Question.
		if ( isset( $_POST['iaq_save_question'] ) ) {
			check_admin_referer( 'iaq_save_question_nonce' );

			$result = IAQ_Question_Bank::save_question( $_POST );
			if ( is_wp_error( $result ) ) {
				$this->add_notice( 'error', $result->get_error_message() );
			} else {
				$message = ! empty( $_POST['id'] ) ? 'question_updated' : 'question_created';
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-question-bank', 'message' => $message ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 4. Delete Single Question.
		if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['id'] ) && isset( $_GET['page'] ) && 'iaq-question-bank' === $_GET['page'] ) {
			check_admin_referer( 'iaq_delete_question_' . absint( $_GET['id'] ) );
			IAQ_Question_Bank::delete_question( absint( $_GET['id'] ) );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-question-bank', 'message' => 'question_deleted' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 5. Bulk Actions on Questions.
		if ( isset( $_POST['iaq_bulk_action_submit'] ) && ! empty( $_POST['bulk_action'] ) && ! empty( $_POST['question_ids'] ) ) {
			check_admin_referer( 'iaq_bulk_questions_nonce' );
			$action = sanitize_key( $_POST['bulk_action'] );
			$ids    = array_map( 'absint', (array) $_POST['question_ids'] );

			if ( 'delete' === $action ) {
				$count = IAQ_Question_Bank::bulk_delete_questions( $ids );
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-question-bank', 'message' => 'bulk_deleted', 'count' => $count ), admin_url( 'admin.php' ) ) );
				exit;
			} elseif ( in_array( $action, array( 'published', 'draft', 'archived' ), true ) ) {
				$count = IAQ_Question_Bank::bulk_update_status( $ids, $action );
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-question-bank', 'message' => 'bulk_status_updated', 'count' => $count ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 6. Save Subject.
		if ( isset( $_POST['iaq_save_subject'] ) ) {
			check_admin_referer( 'iaq_save_subject_nonce' );
			$result = IAQ_Question_Bank::save_subject( $_POST );
			if ( is_wp_error( $result ) ) {
				$this->add_notice( 'error', $result->get_error_message() );
			} else {
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-subjects-topics', 'message' => 'subject_saved' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 7. Delete Subject.
		if ( isset( $_GET['iaq_action'] ) && 'delete_subject' === $_GET['iaq_action'] && isset( $_GET['id'] ) ) {
			check_admin_referer( 'iaq_delete_subject_' . absint( $_GET['id'] ) );
			IAQ_Question_Bank::delete_subject( absint( $_GET['id'] ) );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-subjects-topics', 'message' => 'subject_deleted' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 8. Save Topic.
		if ( isset( $_POST['iaq_save_topic'] ) ) {
			check_admin_referer( 'iaq_save_topic_nonce' );
			$result = IAQ_Question_Bank::save_topic( $_POST );
			if ( is_wp_error( $result ) ) {
				$this->add_notice( 'error', $result->get_error_message() );
			} else {
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-subjects-topics', 'message' => 'topic_saved' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 9. Delete Topic.
		if ( isset( $_GET['iaq_action'] ) && 'delete_topic' === $_GET['iaq_action'] && isset( $_GET['id'] ) ) {
			check_admin_referer( 'iaq_delete_topic_' . absint( $_GET['id'] ) );
			IAQ_Question_Bank::delete_topic( absint( $_GET['id'] ) );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-subjects-topics', 'message' => 'topic_deleted' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 10. Handle File Upload & Import (CSV or JSON).
		if ( isset( $_POST['iaq_upload_csv'] ) ) {
			check_admin_referer( 'iaq_upload_csv_nonce' );

			if ( empty( $_FILES['csv_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['csv_file']['tmp_name'] ) ) {
				$this->add_notice( 'error', __( 'Please choose a valid JSON or CSV file to upload.', 'indianagri-quiz-engine' ) );
				return;
			}

			$file_name = sanitize_file_name( $_FILES['csv_file']['name'] );
			$ext       = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

			if ( ! in_array( $ext, array( 'csv', 'json' ), true ) ) {
				$this->add_notice( 'error', __( 'Invalid file extension. Only .json and .csv files are supported.', 'indianagri-quiz-engine' ) );
				return;
			}

			$target_quiz_id     = ! empty( $_POST['assign_quiz_id'] ) ? absint( $_POST['assign_quiz_id'] ) : 0;
			$duplicate_handling = ! empty( $_POST['duplicate_handling'] ) && in_array( $_POST['duplicate_handling'], array( 'skip', 'update', 'allow' ), true ) ? sanitize_key( $_POST['duplicate_handling'] ) : 'skip';
			$import_res         = IAQ_Import_Export::import_from_file( $_FILES['csv_file']['tmp_name'], $target_quiz_id, null, $file_name, $duplicate_handling );
			if ( ! $import_res['success'] ) {
				$this->add_notice( 'error', $import_res['message'] );
			} else {
				if ( $target_quiz_id > 0 ) {
					$msg = sprintf(
						/* translators: 1: imported count, 2: skipped count, 3: quiz id */
						__( 'Import complete: %1$d questions imported and assigned directly to Quiz/Set #%3$d. %2$d rows skipped.', 'indianagri-quiz-engine' ),
						$import_res['imported'],
						$import_res['skipped'],
						$target_quiz_id
					);
				} else {
					$msg = sprintf(
						/* translators: 1: imported count, 2: skipped count */
						__( 'Import complete: %1$d questions imported successfully. %2$d rows skipped.', 'indianagri-quiz-engine' ),
						$import_res['imported'],
						$import_res['skipped']
					);
				}

				if ( ! empty( $import_res['errors'] ) ) {
					$msg .= '<br><ul style="margin-top:6px;list-style:disc;padding-left:20px;">';
					foreach ( $import_res['errors'] as $err ) {
						$msg .= '<li>' . esc_html( $err ) . '</li>';
					}
					$msg .= '</ul>';
				}

				$this->add_notice( 'success', $msg );
			}
		}

		// 11. Save Plugin Settings.
		if ( isset( $_POST['iaq_save_settings'] ) ) {
			check_admin_referer( 'iaq_save_settings_nonce' );

			$brand_color = sanitize_hex_color( $_POST['brand_color'] );
			if ( empty( $brand_color ) ) {
				$brand_color = '#117E3A';
			}

			$settings = array(
				'brand_color'                    => $brand_color,
				'default_duration'               => absint( $_POST['default_duration'] ),
				'default_marks'                  => max( 0.01, floatval( $_POST['default_marks'] ) ),
				'default_negative_marks'         => max( 0.00, floatval( $_POST['default_negative_marks'] ) ),
				'default_pass_percentage'        => isset( $_POST['default_pass_percentage'] ) ? floatval( $_POST['default_pass_percentage'] ) : 40.00,
				'default_max_attempts'           => isset( $_POST['default_max_attempts'] ) ? absint( $_POST['default_max_attempts'] ) : 0,
				'default_shuffle_questions'      => isset( $_POST['default_shuffle_questions'] ) ? ( ! empty( $_POST['default_shuffle_questions'] ) ? 1 : 0 ) : 1,
				'default_shuffle_options'        => isset( $_POST['default_shuffle_options'] ) ? ( ! empty( $_POST['default_shuffle_options'] ) ? 1 : 0 ) : 1,
				'default_access_type'            => ! empty( $_POST['default_access_type'] ) && in_array( $_POST['default_access_type'], array( 'free', 'paid' ), true ) ? $_POST['default_access_type'] : 'free',
				'default_show_answers_mode'      => ! empty( $_POST['default_show_answers_mode'] ) && in_array( $_POST['default_show_answers_mode'], array( 'after_submission', 'immediate', 'never' ), true ) ? $_POST['default_show_answers_mode'] : 'after_submission',
				'default_instructions'           => ! empty( $_POST['default_instructions'] ) ? wp_kses_post( wp_unslash( $_POST['default_instructions'] ) ) : '',
				'delete_data_on_uninstall'       => ! empty( $_POST['delete_data_on_uninstall'] ) ? 1 : 0,
				'enable_hindi_support'           => ! empty( $_POST['enable_hindi_support'] ) ? 1 : 0,
				// Student Learning Features Toggles
				'enable_bookmarks'               => isset( $_POST['enable_bookmarks'] ) ? 1 : 0,
				'enable_wrong_notebook'          => isset( $_POST['enable_wrong_notebook'] ) ? 1 : 0,
				'enable_custom_practice'         => isset( $_POST['enable_custom_practice'] ) ? 1 : 0,
				'enable_leaderboard'             => isset( $_POST['enable_leaderboard'] ) ? 1 : 0,
				'enable_study_streaks'           => isset( $_POST['enable_study_streaks'] ) ? 1 : 0,
				// Leaderboard Configuration
				'leaderboard_name_type'          => ! empty( $_POST['leaderboard_name_type'] ) && in_array( $_POST['leaderboard_name_type'], array( 'pseudonym', 'display_name', 'first_name' ), true ) ? $_POST['leaderboard_name_type'] : 'pseudonym',
				'leaderboard_limit'              => isset( $_POST['leaderboard_limit'] ) ? max( 5, min( 50, absint( $_POST['leaderboard_limit'] ) ) ) : 10,
				// Custom Practice Limits
				'custom_practice_max_questions'  => isset( $_POST['custom_practice_max_questions'] ) ? max( 5, min( 100, absint( $_POST['custom_practice_max_questions'] ) ) ) : 50,
				// Bulk CSV Import Limit (Defaults to 200)
				'bulk_import_max_limit'          => isset( $_POST['bulk_import_max_limit'] ) ? max( 10, min( 1000, absint( $_POST['bulk_import_max_limit'] ) ) ) : 200,
				// Recommendations & Streaks Rules
				'recommendations_focus_weak_areas'=> isset( $_POST['recommendations_focus_weak_areas'] ) ? 1 : 0,
				'streak_qualifying_activity'     => ! empty( $_POST['streak_qualifying_activity'] ) ? sanitize_key( $_POST['streak_qualifying_activity'] ) : 'quiz_completed',
				// Mobile Application Update & Version Settings
				'app_latest_version'             => ! empty( $_POST['app_latest_version'] ) ? sanitize_text_field( wp_unslash( $_POST['app_latest_version'] ) ) : '1.1.0',
				'app_min_version'                => ! empty( $_POST['app_min_version'] ) ? sanitize_text_field( wp_unslash( $_POST['app_min_version'] ) ) : '1.0.0',
				'app_force_update'               => ! empty( $_POST['app_force_update'] ) ? 1 : 0,
				'app_update_title'               => ! empty( $_POST['app_update_title'] ) ? sanitize_text_field( wp_unslash( $_POST['app_update_title'] ) ) : __( 'Exciting New Update Available!', 'indianagri-quiz-engine' ),
				'app_update_message'             => ! empty( $_POST['app_update_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['app_update_message'] ) ) : '',
				'app_release_notes'              => ! empty( $_POST['app_release_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['app_release_notes'] ) ) : '',
				'app_update_url'                 => ! empty( $_POST['app_update_url'] ) ? esc_url_raw( wp_unslash( $_POST['app_update_url'] ) ) : 'https://github.com/veerta777/IndianAgri-Quiz-Engine-/releases/latest/download/IndianAgriExam.apk',
				'app_update_date'                => ! empty( $_POST['app_update_date'] ) ? sanitize_text_field( wp_unslash( $_POST['app_update_date'] ) ) : current_time( 'Y-m-d' ),
			);

			update_option( 'iaq_settings', $settings );
			update_option( 'iaq_delete_data_on_uninstall', $settings['delete_data_on_uninstall'] );
			update_option( 'iaq_leaderboard_enabled', $settings['enable_leaderboard'] );
			update_option( 'iaq_leaderboard_name_type', $settings['leaderboard_name_type'] );
			update_option( 'iaq_custom_practice_max_questions', $settings['custom_practice_max_questions'] );
			update_option( 'iaq_bulk_import_max_limit', $settings['bulk_import_max_limit'] );
			update_option( 'iaq_enable_bookmarks', $settings['enable_bookmarks'] );
			update_option( 'iaq_enable_wrong_notebook', $settings['enable_wrong_notebook'] );
			update_option( 'iaq_enable_study_streaks', $settings['enable_study_streaks'] );
			// Update individual options for fast REST query
			update_option( 'iaq_app_latest_version', $settings['app_latest_version'] );
			update_option( 'iaq_app_min_version', $settings['app_min_version'] );
			update_option( 'iaq_app_force_update', $settings['app_force_update'] );
			update_option( 'iaq_app_update_title', $settings['app_update_title'] );
			update_option( 'iaq_app_update_message', $settings['app_update_message'] );
			update_option( 'iaq_app_release_notes', $settings['app_release_notes'] );
			update_option( 'iaq_app_update_url', $settings['app_update_url'] );
			update_option( 'iaq_app_update_date', $settings['app_update_date'] );

			$this->add_notice( 'success', __( 'Plugin settings saved successfully.', 'indianagri-quiz-engine' ) );
		}

		// 12. Save Quiz.
		if ( isset( $_POST['iaq_save_quiz'] ) ) {
			check_admin_referer( 'iaq_save_quiz_nonce' );

			$result = IAQ_Quiz_Builder::save_quiz( $_POST );
			if ( is_wp_error( $result ) ) {
				$this->add_notice( 'error', $result->get_error_message() );
			} else {
				$is_update = ! empty( $_POST['id'] );
				$message   = $is_update ? 'quiz_updated' : 'quiz_created';
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-quizzes', 'message' => $message ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 13. Delete Single Quiz.
		if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['id'] ) && isset( $_GET['page'] ) && 'iaq-quizzes' === $_GET['page'] ) {
			check_admin_referer( 'iaq_delete_quiz_' . absint( $_GET['id'] ) );
			IAQ_Quiz_Builder::delete_quiz( absint( $_GET['id'] ) );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-quizzes', 'message' => 'quiz_deleted' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 14. Bulk Actions on Quizzes.
		if ( isset( $_POST['iaq_bulk_quizzes_submit'] ) && ! empty( $_POST['bulk_action'] ) && ! empty( $_POST['quiz_ids'] ) ) {
			check_admin_referer( 'iaq_bulk_quizzes_nonce' );
			$action = sanitize_key( $_POST['bulk_action'] );
			$ids    = array_map( 'absint', (array) $_POST['quiz_ids'] );

			if ( 'delete' === $action ) {
				$count = IAQ_Quiz_Builder::bulk_delete_quizzes( $ids );
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-quizzes', 'message' => 'bulk_quizzes_deleted', 'count' => $count ), admin_url( 'admin.php' ) ) );
				exit;
			} elseif ( in_array( $action, array( 'published', 'draft', 'archived' ), true ) ) {
				$count = IAQ_Quiz_Builder::bulk_update_quiz_status( $ids, $action );
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-quizzes', 'message' => 'bulk_quizzes_status', 'count' => $count ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 15. Assign Selected Questions to Quiz.
		if ( isset( $_POST['iaq_add_selected_questions'] ) && ! empty( $_POST['quiz_id'] ) ) {
			check_admin_referer( 'iaq_assign_questions_nonce' );
			$quiz_id = absint( $_POST['quiz_id'] );
			$q_ids   = ! empty( $_POST['selected_question_ids'] ) ? array_map( 'absint', (array) $_POST['selected_question_ids'] ) : array();

			if ( empty( $q_ids ) ) {
				$this->add_notice( 'warning', __( 'Please select at least one question to add.', 'indianagri-quiz-engine' ) );
			} else {
				$added = IAQ_Quiz_Builder::assign_questions_to_quiz( $quiz_id, $q_ids );
				wp_safe_redirect(
					add_query_arg(
						array(
							'page'    => 'iaq-quizzes',
							'action'  => 'manage_questions',
							'id'      => $quiz_id,
							'message' => 'questions_assigned',
							'count'   => $added,
						),
						admin_url( 'admin.php' )
					)
				);
				exit;
			}
		}

		// 16. Auto-Assign Questions to Quiz.
		if ( isset( $_POST['iaq_do_auto_assign'] ) && ! empty( $_POST['quiz_id'] ) ) {
			check_admin_referer( 'iaq_auto_assign_nonce' );
			$quiz_id  = absint( $_POST['quiz_id'] );
			$criteria = array(
				'count'         => absint( $_POST['auto_count'] ),
				'subject_id'    => absint( $_POST['auto_subject_id'] ),
				'difficulty'    => sanitize_key( $_POST['auto_difficulty'] ),
			);

			$added = IAQ_Quiz_Builder::auto_assign_questions( $quiz_id, $criteria );
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'iaq-quizzes',
						'action'  => 'manage_questions',
						'id'      => $quiz_id,
						'message' => 'questions_auto_assigned',
						'count'   => $added,
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		// 17. Remove Question from Quiz.
		if ( isset( $_GET['action'] ) && 'remove_question' === $_GET['action'] && ! empty( $_GET['id'] ) && ! empty( $_GET['question_id'] ) && isset( $_GET['page'] ) && 'iaq-quizzes' === $_GET['page'] ) {
			$quiz_id = absint( $_GET['id'] );
			$q_id    = absint( $_GET['question_id'] );
			check_admin_referer( 'iaq_remove_q_' . $quiz_id . '_' . $q_id );

			IAQ_Quiz_Builder::remove_question_from_quiz( $quiz_id, $q_id );
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'iaq-quizzes',
						'action'  => 'manage_questions',
						'id'      => $quiz_id,
						'message' => 'question_removed',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		// 18. Duplicate Quiz.
		if ( isset( $_GET['action'] ) && 'duplicate' === $_GET['action'] && isset( $_GET['id'] ) && isset( $_GET['page'] ) && 'iaq-quizzes' === $_GET['page'] ) {
			check_admin_referer( 'iaq_duplicate_quiz_' . absint( $_GET['id'] ) );
			$new_quiz_id = IAQ_Quiz_Builder::duplicate_quiz( absint( $_GET['id'] ) );
			if ( is_wp_error( $new_quiz_id ) ) {
				$this->add_notice( 'error', $new_quiz_id->get_error_message() );
			} else {
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-quizzes', 'message' => 'quiz_duplicated' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 19. Save Examination.
		if ( isset( $_POST['iaq_save_examination'] ) ) {
			check_admin_referer( 'iaq_save_examination_nonce' );
			$res = IAQ_Examinations::save_examination( $_POST );
			if ( is_wp_error( $res ) ) {
				$this->add_notice( 'error', $res->get_error_message() );
			} else {
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-examinations', 'message' => 'exam_saved' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 20. Duplicate Examination.
		if ( isset( $_GET['action'] ) && 'duplicate' === $_GET['action'] && isset( $_GET['id'] ) && isset( $_GET['page'] ) && 'iaq-examinations' === $_GET['page'] ) {
			check_admin_referer( 'iaq_duplicate_exam_' . absint( $_GET['id'] ) );
			$res = IAQ_Examinations::duplicate_examination( absint( $_GET['id'] ) );
			if ( is_wp_error( $res ) ) {
				$this->add_notice( 'error', $res->get_error_message() );
			} else {
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-examinations', 'message' => 'exam_duplicated' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 21. Delete Examination.
		if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['id'] ) && isset( $_GET['page'] ) && 'iaq-examinations' === $_GET['page'] ) {
			check_admin_referer( 'iaq_delete_exam_' . absint( $_GET['id'] ) );
			IAQ_Examinations::delete_examination( absint( $_GET['id'] ) );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-examinations', 'message' => 'exam_deleted' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 22. Save Subscription Plan.
		if ( isset( $_POST['iaq_save_plan'] ) ) {
			check_admin_referer( 'iaq_save_plan_nonce' );
			$res = IAQ_Access_Control::save_plan( $_POST );
			if ( is_wp_error( $res ) ) {
				$this->add_notice( 'error', $res->get_error_message() );
			} else {
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-subscription-plans', 'message' => 'plan_saved' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 23. Delete Subscription Plan.
		if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['id'] ) && isset( $_GET['page'] ) && 'iaq-subscription-plans' === $_GET['page'] ) {
			check_admin_referer( 'iaq_delete_plan_' . absint( $_GET['id'] ) );
			IAQ_Access_Control::delete_plan( absint( $_GET['id'] ) );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-subscription-plans', 'message' => 'plan_deleted' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 24. Manual Grant Student Subscription.
		if ( isset( $_POST['iaq_manual_grant_subscription'] ) ) {
			check_admin_referer( 'iaq_manual_grant_subscription_nonce' );
			$user_id   = absint( $_POST['user_id'] );
			$plan_id   = absint( $_POST['plan_id'] );
			$order_ref = ! empty( $_POST['order_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['order_ref'] ) ) : 'MANUAL-ADMIN-GRANT';

			$res = IAQ_Access_Control::grant_subscription( $user_id, $plan_id, $order_ref );
			if ( is_wp_error( $res ) ) {
				$this->add_notice( 'error', $res->get_error_message() );
			} else {
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-user-subscriptions', 'message' => 'sub_granted' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 25. Revoke Student Subscription.
		if ( isset( $_GET['action'] ) && 'revoke' === $_GET['action'] && isset( $_GET['id'] ) && isset( $_GET['page'] ) && 'iaq-user-subscriptions' === $_GET['page'] ) {
			check_admin_referer( 'iaq_revoke_sub_' . absint( $_GET['id'] ) );
			IAQ_Access_Control::revoke_subscription( absint( $_GET['id'] ) );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-user-subscriptions', 'message' => 'sub_revoked' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 26. Save Book.
		if ( isset( $_POST['iaq_save_book'] ) ) {
			check_admin_referer( 'iaq_save_book_nonce' );
			$res = IAQ_Practice_Sets::save_book( $_POST );
			if ( is_wp_error( $res ) ) {
				$this->add_notice( 'error', $res->get_error_message() );
			} else {
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-books', 'message' => 'book_saved' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 27. Delete or Archive Book.
		if ( isset( $_GET['iaq_action'] ) && 'delete_book' === $_GET['iaq_action'] && isset( $_GET['id'] ) && isset( $_GET['page'] ) && 'iaq-books' === $_GET['page'] ) {
			check_admin_referer( 'iaq_delete_book_' . absint( $_GET['id'] ) );
			IAQ_Practice_Sets::delete_book( absint( $_GET['id'] ) );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-books', 'message' => 'book_deleted' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 28. Assign Quiz to Book.
		if ( isset( $_POST['iaq_assign_book_quiz'] ) ) {
			check_admin_referer( 'iaq_assign_book_quiz_nonce' );
			$b_id  = absint( $_POST['book_id'] );
			$q_id  = absint( $_POST['quiz_id'] );
			$order = isset( $_POST['sort_order'] ) ? intval( $_POST['sort_order'] ) : 0;
			IAQ_Practice_Sets::assign_quiz_to_book( $b_id, $q_id, $order );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-books', 'action' => 'manage_sets', 'book_id' => $b_id, 'message' => 'quiz_assigned_to_book' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 28b. Create Practice Set for Book (with optional Bulk CSV Import).
		if ( isset( $_POST['iaq_create_book_practice_set'] ) ) {
			check_admin_referer( 'iaq_create_book_practice_set_nonce' );
			$b_id       = absint( $_POST['book_id'] );
			$book       = IAQ_Practice_Sets::get_book( $b_id );
			$sort_order = isset( $_POST['sort_order'] ) ? max( 1, intval( $_POST['sort_order'] ) ) : 1;
			$set_title  = ! empty( $_POST['set_title'] ) ? sanitize_text_field( wp_unslash( $_POST['set_title'] ) ) : sprintf( __( 'Practice Set %d', 'indianagri-quiz-engine' ), $sort_order );
			$subject_id = ! empty( $_POST['subject_id'] ) ? absint( $_POST['subject_id'] ) : ( $book ? (int) $book->subject_id : 0 );

			// Save Quiz
			$quiz_data = array(
				'title'               => $set_title,
				'slug'                => sanitize_title( ( $book ? $book->slug . '-' : '' ) . $set_title ),
				'quiz_type'           => 'practice',
				'book_id'             => $b_id,
				'subject_id'          => $subject_id,
				'topic_id'            => 0,
				'duration_minutes'    => isset( $_POST['duration_minutes'] ) ? absint( $_POST['duration_minutes'] ) : 20,
				'correct_marks'       => isset( $_POST['correct_marks'] ) ? floatval( $_POST['correct_marks'] ) : 1.00,
				'negative_marks'      => isset( $_POST['negative_marks'] ) ? floatval( $_POST['negative_marks'] ) : 0.25,
				'pass_percentage'     => isset( $_POST['pass_percentage'] ) ? floatval( $_POST['pass_percentage'] ) : 50.00,
				'show_answers_mode'   => 'immediate',
				'status'              => 'published',
				'access_type'         => isset( $_POST['access_type'] ) && in_array( sanitize_text_field( $_POST['access_type'] ), array( 'free', 'paid' ), true ) ? sanitize_text_field( $_POST['access_type'] ) : ( $book && 'free' === $book->access_type ? 'free' : 'paid' ),
				'shuffle_questions'   => 1,
				'shuffle_options'     => 1,
				'display_order'       => $sort_order,
			);

			$quiz_id = IAQ_Quiz_Builder::save_quiz( $quiz_data );
			if ( is_wp_error( $quiz_id ) ) {
				$this->add_notice( 'error', $quiz_id->get_error_message() );
			} else {
				// Attach quiz to this book.
				IAQ_Practice_Sets::assign_quiz_to_book( $b_id, $quiz_id, $sort_order );

				// Check if bulk CSV or JSON was uploaded.
				$imported_count = 0;
				if ( ! empty( $_FILES['csv_file']['tmp_name'] ) && is_uploaded_file( $_FILES['csv_file']['tmp_name'] ) ) {
					$ext = strtolower( pathinfo( $_FILES['csv_file']['name'], PATHINFO_EXTENSION ) );
					if ( in_array( $ext, array( 'csv', 'json' ), true ) ) {
						$import_res = IAQ_Import_Export::import_from_file( $_FILES['csv_file']['tmp_name'], $quiz_id, null, $_FILES['csv_file']['name'] );
						if ( ! empty( $import_res['success'] ) ) {
							$imported_count = (int) $import_res['imported'];
						}
					}
				}

				if ( $imported_count > 0 ) {
					wp_safe_redirect(
						add_query_arg(
							array(
								'page'    => 'iaq-books',
								'action'  => 'manage_sets',
								'book_id' => $b_id,
								'message' => 'practice_set_created_with_import',
								'count'   => $imported_count,
							),
							admin_url( 'admin.php' )
						)
					);
				} else {
					wp_safe_redirect(
						add_query_arg(
							array(
								'page'    => 'iaq-books',
								'action'  => 'manage_sets',
								'book_id' => $b_id,
								'message' => 'practice_set_created',
							),
							admin_url( 'admin.php' )
						)
					);
				}
				exit;
			}
		}

		// 28c. Bulk Import Questions Directly to a Practice Set.
		if ( isset( $_POST['iaq_bulk_import_to_quiz'] ) ) {
			check_admin_referer( 'iaq_bulk_import_to_quiz_nonce' );
			$b_id    = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
			$ex_id   = isset( $_POST['exam_id'] ) ? absint( $_POST['exam_id'] ) : 0;
			$s_id    = isset( $_POST['subject_id'] ) ? absint( $_POST['subject_id'] ) : 0;
			$c_id    = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
			$quiz_id = absint( $_POST['quiz_id'] );

			if ( empty( $_FILES['csv_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['csv_file']['tmp_name'] ) ) {
				$this->add_notice( 'error', __( 'Please choose a valid JSON or CSV file to upload.', 'indianagri-quiz-engine' ) );
			} else {
				$ext = strtolower( pathinfo( $_FILES['csv_file']['name'], PATHINFO_EXTENSION ) );
				if ( ! in_array( $ext, array( 'csv', 'json' ), true ) ) {
					$this->add_notice( 'error', __( 'Invalid file extension. Only .json and .csv files are supported.', 'indianagri-quiz-engine' ) );
				} else {
					$import_res = IAQ_Import_Export::import_from_file( $_FILES['csv_file']['tmp_name'], $quiz_id, null, $_FILES['csv_file']['name'] );
					if ( empty( $import_res['success'] ) ) {
						$this->add_notice( 'error', $import_res['message'] );
					} else {
						if ( $c_id && $ex_id && $s_id ) {
							$redirect = add_query_arg(
								array(
									'page'        => 'iaq-mock-tests',
									'category_id' => $c_id,
									'exam_id'     => $ex_id,
									'subject_id'  => $s_id,
									'message'     => 'practice_set_imported',
									'count'       => (int) $import_res['imported'],
								),
								admin_url( 'admin.php' )
							);
						} elseif ( $b_id ) {
							$redirect = add_query_arg(
								array(
									'page'    => 'iaq-books',
									'action'  => 'manage_sets',
									'book_id' => $b_id,
									'message' => 'practice_set_imported',
									'count'   => (int) $import_res['imported'],
								),
								admin_url( 'admin.php' )
							);
						} elseif ( $ex_id && $s_id ) {
							$redirect = add_query_arg(
								array(
									'page'       => 'iaq-examinations',
									'action'     => 'manage_subject_quizzes',
									'exam_id'    => $ex_id,
									'subject_id' => $s_id,
									'message'    => 'practice_set_imported',
									'count'      => (int) $import_res['imported'],
								),
								admin_url( 'admin.php' )
							);
						} else {
							$redirect = add_query_arg(
								array(
									'page'    => 'iaq-quizzes',
									'message' => 'practice_set_imported',
									'count'   => (int) $import_res['imported'],
								),
								admin_url( 'admin.php' )
							);
						}
						wp_safe_redirect( $redirect );
						exit;
					}
				}
			}
		}

		// 29. Remove Quiz from Book.
		if ( isset( $_GET['iaq_action'] ) && 'remove_book_quiz' === $_GET['iaq_action'] && isset( $_GET['book_id'] ) && isset( $_GET['quiz_id'] ) ) {
			$b_id = absint( $_GET['book_id'] );
			$q_id = absint( $_GET['quiz_id'] );
			check_admin_referer( 'iaq_remove_book_quiz_' . $b_id . '_' . $q_id );
			IAQ_Practice_Sets::remove_quiz_from_book( $b_id, $q_id );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-books', 'action' => 'manage_sets', 'book_id' => $b_id, 'message' => 'quiz_removed_from_book' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 30. Assign Subject to Examination.
		if ( isset( $_POST['iaq_assign_exam_subject'] ) ) {
			check_admin_referer( 'iaq_assign_exam_subject_nonce' );
			$ex_id = absint( $_POST['exam_id'] );
			$s_id  = absint( $_POST['subject_id'] );
			$order = isset( $_POST['display_order'] ) ? intval( $_POST['display_order'] ) : 0;
			IAQ_Practice_Sets::assign_subject_to_exam( $ex_id, $s_id, $order );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-examinations', 'action' => 'manage_subjects', 'exam_id' => $ex_id, 'message' => 'exam_subject_saved' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 31. Remove Subject from Examination.
		if ( isset( $_GET['iaq_action'] ) && 'remove_exam_subject' === $_GET['iaq_action'] && isset( $_GET['exam_id'] ) && isset( $_GET['subject_id'] ) ) {
			$ex_id = absint( $_GET['exam_id'] );
			$s_id  = absint( $_GET['subject_id'] );
			check_admin_referer( 'iaq_remove_exam_sub_' . $ex_id . '_' . $s_id );
			IAQ_Practice_Sets::remove_subject_from_exam( $ex_id, $s_id );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-examinations', 'action' => 'manage_subjects', 'exam_id' => $ex_id, 'message' => 'exam_subject_removed' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 32. Assign Quiz to Examination-Subject.
		if ( isset( $_POST['iaq_assign_exam_subject_quiz'] ) ) {
			check_admin_referer( 'iaq_assign_esq_nonce' );
			$ex_id = absint( $_POST['exam_id'] );
			$s_id  = absint( $_POST['subject_id'] );
			$q_id  = absint( $_POST['quiz_id'] );
			$order = isset( $_POST['sort_order'] ) ? intval( $_POST['sort_order'] ) : 0;
			IAQ_Practice_Sets::assign_quiz_to_exam_subject( $ex_id, $s_id, $q_id, $order );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-examinations', 'action' => 'manage_subject_quizzes', 'exam_id' => $ex_id, 'subject_id' => $s_id, 'message' => 'esq_saved' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 32b. Create Practice Set for Examination + Subject (with optional Bulk CSV/JSON Import).
		if ( isset( $_POST['iaq_create_exam_subject_practice_set'] ) ) {
			check_admin_referer( 'iaq_create_esq_practice_set_nonce' );
			$ex_id      = absint( $_POST['exam_id'] );
			$s_id       = absint( $_POST['subject_id'] );
			$exam       = IAQ_Examinations::get_examination( $ex_id );
			$subject    = IAQ_Question_Bank::get_subject( $s_id );
			$sort_order = isset( $_POST['sort_order'] ) ? max( 1, intval( $_POST['sort_order'] ) ) : 1;
			$set_title  = ! empty( $_POST['set_title'] ) ? sanitize_text_field( wp_unslash( $_POST['set_title'] ) ) : sprintf( __( 'Practice Set %d', 'indianagri-quiz-engine' ), $sort_order );

			// Save Quiz
			$quiz_data = array(
				'title'               => $set_title,
				'slug'                => sanitize_title( ( $exam ? $exam->code . '-' : '' ) . ( $subject ? $subject->slug . '-' : '' ) . $set_title ),
				'quiz_type'           => 'practice',
				'exam_category'       => $exam ? $exam->name : '',
				'subject_id'          => $s_id,
				'topic_id'            => 0,
				'duration_minutes'    => isset( $_POST['duration_minutes'] ) ? absint( $_POST['duration_minutes'] ) : 20,
				'correct_marks'       => isset( $_POST['correct_marks'] ) ? floatval( $_POST['correct_marks'] ) : 1.00,
				'negative_marks'      => isset( $_POST['negative_marks'] ) ? floatval( $_POST['negative_marks'] ) : 0.25,
				'pass_percentage'     => isset( $_POST['pass_percentage'] ) ? floatval( $_POST['pass_percentage'] ) : 50.00,
				'show_answers_mode'   => 'immediate',
				'status'              => 'published',
				'access_type'         => isset( $_POST['access_type'] ) && in_array( sanitize_text_field( $_POST['access_type'] ), array( 'free', 'paid' ), true ) ? sanitize_text_field( $_POST['access_type'] ) : 'paid',
				'shuffle_questions'   => 1,
				'shuffle_options'     => 1,
				'display_order'       => $sort_order,
			);

			$quiz_id = IAQ_Quiz_Builder::save_quiz( $quiz_data );
			if ( is_wp_error( $quiz_id ) ) {
				$this->add_notice( 'error', $quiz_id->get_error_message() );
			} else {
				// Attach quiz to this exam-subject combination.
				IAQ_Practice_Sets::assign_quiz_to_exam_subject( $ex_id, $s_id, $quiz_id, $sort_order );

				// Check if bulk CSV or JSON was uploaded.
				$imported_count = 0;
				if ( ! empty( $_FILES['csv_file']['tmp_name'] ) && is_uploaded_file( $_FILES['csv_file']['tmp_name'] ) ) {
					$ext = strtolower( pathinfo( $_FILES['csv_file']['name'], PATHINFO_EXTENSION ) );
					if ( in_array( $ext, array( 'csv', 'json' ), true ) ) {
						$import_res = IAQ_Import_Export::import_from_file( $_FILES['csv_file']['tmp_name'], $quiz_id, null, $_FILES['csv_file']['name'] );
						if ( ! empty( $import_res['success'] ) ) {
							$imported_count = (int) $import_res['imported'];
						}
					}
				}

				if ( $imported_count > 0 ) {
					wp_safe_redirect(
						add_query_arg(
							array(
								'page'       => 'iaq-examinations',
								'action'     => 'manage_subject_quizzes',
								'exam_id'    => $ex_id,
								'subject_id' => $s_id,
								'message'    => 'practice_set_created_with_import',
								'count'      => $imported_count,
							),
							admin_url( 'admin.php' )
						)
					);
				} else {
					wp_safe_redirect(
						add_query_arg(
							array(
								'page'       => 'iaq-examinations',
								'action'     => 'manage_subject_quizzes',
								'exam_id'    => $ex_id,
								'subject_id' => $s_id,
								'message'    => 'practice_set_created',
							),
							admin_url( 'admin.php' )
						)
					);
				}
				exit;
			}
		}


		// 33. Remove Quiz from Examination-Subject.
		if ( isset( $_GET['iaq_action'] ) && 'remove_exam_subject_quiz' === $_GET['iaq_action'] && isset( $_GET['exam_id'] ) && isset( $_GET['subject_id'] ) && isset( $_GET['quiz_id'] ) ) {
			$ex_id = absint( $_GET['exam_id'] );
			$s_id  = absint( $_GET['subject_id'] );
			$q_id  = absint( $_GET['quiz_id'] );
			check_admin_referer( 'iaq_remove_esq_' . $ex_id . '_' . $s_id . '_' . $q_id );
			IAQ_Practice_Sets::remove_quiz_from_exam_subject( $ex_id, $s_id, $q_id );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-examinations', 'action' => 'manage_subject_quizzes', 'exam_id' => $ex_id, 'subject_id' => $s_id, 'message' => 'esq_removed' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 34. Save Mock Category.
		if ( isset( $_POST['iaq_save_mock_category'] ) ) {
			check_admin_referer( 'iaq_save_mock_category_nonce' );
			$res = IAQ_Mock_Tests::save_category( $_POST );
			if ( is_wp_error( $res ) ) {
				$this->add_notice( 'error', $res->get_error_message() );
			} else {
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-mock-tests', 'tab' => 'categories', 'message' => 'mock_category_saved' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 35. Delete Mock Category.
		if ( isset( $_GET['iaq_action'] ) && 'delete_mock_category' === $_GET['iaq_action'] && isset( $_GET['id'] ) ) {
			check_admin_referer( 'iaq_delete_mock_cat_' . absint( $_GET['id'] ) );
			IAQ_Mock_Tests::delete_category( absint( $_GET['id'] ) );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-mock-tests', 'tab' => 'categories', 'message' => 'mock_category_deleted' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 36. Save State / University.
		if ( isset( $_POST['iaq_save_state_university'] ) ) {
			check_admin_referer( 'iaq_save_state_university_nonce' );
			$res = IAQ_Mock_Tests::save_state_university( $_POST );
			if ( is_wp_error( $res ) ) {
				$this->add_notice( 'error', $res->get_error_message() );
			} else {
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-mock-tests', 'tab' => 'states_univs', 'message' => 'state_univ_saved' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 37. Delete State / University.
		if ( isset( $_GET['iaq_action'] ) && 'delete_state_university' === $_GET['iaq_action'] && isset( $_GET['id'] ) ) {
			check_admin_referer( 'iaq_delete_state_univ_' . absint( $_GET['id'] ) );
			IAQ_Mock_Tests::delete_state_university( absint( $_GET['id'] ) );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-mock-tests', 'tab' => 'states_univs', 'message' => 'state_univ_deleted' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 38. Save Vacancy.
		if ( isset( $_POST['iaq_save_mock_vacancy'] ) ) {
			check_admin_referer( 'iaq_save_mock_vacancy_nonce' );
			$res = IAQ_Mock_Tests::save_vacancy( $_POST );
			if ( is_wp_error( $res ) ) {
				$this->add_notice( 'error', $res->get_error_message() );
			} else {
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-mock-tests', 'tab' => 'vacancies', 'message' => 'vacancy_saved' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 39. Delete Vacancy.
		if ( isset( $_GET['iaq_action'] ) && 'delete_mock_vacancy' === $_GET['iaq_action'] && isset( $_GET['id'] ) ) {
			check_admin_referer( 'iaq_delete_mock_vac_' . absint( $_GET['id'] ) );
			IAQ_Mock_Tests::delete_vacancy( absint( $_GET['id'] ) );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-mock-tests', 'tab' => 'vacancies', 'message' => 'vacancy_deleted' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 40. Save Series.
		if ( isset( $_POST['iaq_save_mock_series'] ) ) {
			check_admin_referer( 'iaq_save_mock_series_nonce' );
			$res = IAQ_Mock_Tests::save_series( $_POST );
			if ( is_wp_error( $res ) ) {
				$this->add_notice( 'error', $res->get_error_message() );
			} else {
				wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-mock-tests', 'tab' => 'series', 'message' => 'series_saved' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// 41. Delete Series.
		if ( isset( $_GET['iaq_action'] ) && 'delete_mock_series' === $_GET['iaq_action'] && isset( $_GET['id'] ) ) {
			check_admin_referer( 'iaq_delete_mock_ser_' . absint( $_GET['id'] ) );
			IAQ_Mock_Tests::delete_series( absint( $_GET['id'] ) );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-mock-tests', 'tab' => 'series', 'message' => 'series_deleted' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 42. Attach Mock Quiz.
		if ( isset( $_POST['iaq_attach_mock_quiz'] ) ) {
			check_admin_referer( 'iaq_attach_mock_quiz_nonce' );
			$quiz_id = absint( $_POST['quiz_id'] );
			$context = array(
				'category_id' => ! empty( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : null,
				'exam_id'     => ! empty( $_POST['exam_id'] ) ? absint( $_POST['exam_id'] ) : null,
				'subject_id'  => ! empty( $_POST['subject_id'] ) ? absint( $_POST['subject_id'] ) : null,
				'vacancy_id'  => ! empty( $_POST['vacancy_id'] ) ? absint( $_POST['vacancy_id'] ) : null,
				'state_id'    => ! empty( $_POST['state_id'] ) ? absint( $_POST['state_id'] ) : null,
				'series_id'   => ! empty( $_POST['series_id'] ) ? absint( $_POST['series_id'] ) : null,
				'sort_order'  => ! empty( $_POST['sort_order'] ) ? absint( $_POST['sort_order'] ) : 1,
			);
			IAQ_Mock_Tests::attach_quiz_to_mock_context( $quiz_id, $context );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-mock-tests', 'tab' => 'quizzes', 'category_id' => $context['category_id'], 'exam_id' => $context['exam_id'], 'message' => 'quiz_attached' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 43. Detach Mock Quiz.
		if ( isset( $_GET['iaq_action'] ) && 'detach_mock_quiz' === $_GET['iaq_action'] && isset( $_GET['rel_id'] ) ) {
			$rel_id = absint( $_GET['rel_id'] );
			check_admin_referer( 'iaq_detach_mock_rel_' . $rel_id );
			IAQ_Mock_Tests::detach_quiz_from_mock_context( $rel_id );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iaq-mock-tests', 'tab' => 'quizzes', 'message' => 'quiz_detached' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// 44. Save Examination under Mock Category.
		if ( isset( $_POST['iaq_save_category_exam'] ) ) {
			check_admin_referer( 'iaq_save_category_exam_nonce' );
			$cat_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
			$res    = IAQ_Examinations::save_examination( $_POST );
			if ( is_wp_error( $res ) ) {
				$this->add_notice( 'error', $res->get_error_message() );
			} else {
				$redirect = add_query_arg(
					array(
						'page'        => 'iaq-mock-tests',
						'category_id' => $cat_id,
						'message'     => 'exam_saved',
					),
					admin_url( 'admin.php' )
				);
				wp_safe_redirect( $redirect );
				exit;
			}
		}

		// 45. Delete Examination under Mock Category.
		if ( isset( $_GET['iaq_action'] ) && 'delete_category_exam' === $_GET['iaq_action'] && isset( $_GET['exam_id'] ) ) {
			$exam_id = absint( $_GET['exam_id'] );
			$cat_id  = isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] ) : 0;
			check_admin_referer( 'iaq_delete_cat_exam_' . $exam_id );
			IAQ_Examinations::delete_examination( $exam_id );
			$redirect = add_query_arg(
				array(
					'page'        => 'iaq-mock-tests',
					'category_id' => $cat_id,
					'message'     => 'exam_deleted',
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $redirect );
			exit;
		}

		// 46. Assign Subject to Examination in Mock Hierarchy.
		if ( isset( $_POST['iaq_assign_mock_hierarchy_subject'] ) ) {
			check_admin_referer( 'iaq_assign_mock_hierarchy_subject_nonce' );
			$cat_id  = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
			$exam_id = isset( $_POST['exam_id'] ) ? absint( $_POST['exam_id'] ) : 0;
			$subj_id = isset( $_POST['subject_id'] ) ? absint( $_POST['subject_id'] ) : 0;
			$order   = isset( $_POST['display_order'] ) ? absint( $_POST['display_order'] ) : 0;

			// Quick create subject if name provided
			if ( ! empty( $_POST['new_subject_name'] ) ) {
				$new_name = sanitize_text_field( $_POST['new_subject_name'] );
				$new_sub  = IAQ_Question_Bank::save_subject(
					array(
						'name'        => $new_name,
						'slug'        => sanitize_title( $new_name ),
						'description' => '',
					)
				);
				if ( ! is_wp_error( $new_sub ) && $new_sub ) {
					$subj_id = absint( $new_sub );
				}
			}

			if ( $exam_id && $subj_id ) {
				IAQ_Practice_Sets::assign_subject_to_exam( $exam_id, $subj_id, $order );
				$redirect = add_query_arg(
					array(
						'page'        => 'iaq-mock-tests',
						'category_id' => $cat_id,
						'exam_id'     => $exam_id,
						'message'     => 'exam_subject_saved',
					),
					admin_url( 'admin.php' )
				);
				wp_safe_redirect( $redirect );
				exit;
			}
		}

		// 47. Remove Subject from Examination in Mock Hierarchy.
		if ( isset( $_GET['iaq_action'] ) && 'remove_mock_hierarchy_subject' === $_GET['iaq_action'] && isset( $_GET['exam_id'] ) && isset( $_GET['subject_id'] ) ) {
			$exam_id = absint( $_GET['exam_id'] );
			$subj_id = absint( $_GET['subject_id'] );
			$cat_id  = isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] ) : 0;
			check_admin_referer( 'iaq_remove_mock_subj_' . $exam_id . '_' . $subj_id );
			IAQ_Practice_Sets::remove_subject_from_exam( $exam_id, $subj_id );
			$redirect = add_query_arg(
				array(
					'page'        => 'iaq-mock-tests',
					'category_id' => $cat_id,
					'exam_id'     => $exam_id,
					'message'     => 'exam_subject_removed',
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $redirect );
			exit;
		}

		// 48. Create Mock Test under Exam + Subject with Optional Bulk Import.
		if ( isset( $_POST['iaq_create_mock_context_quiz'] ) ) {
			check_admin_referer( 'iaq_create_mock_context_quiz_nonce' );
			$cat_id     = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
			$exam_id    = isset( $_POST['exam_id'] ) ? absint( $_POST['exam_id'] ) : 0;
			$subj_id    = isset( $_POST['subject_id'] ) ? absint( $_POST['subject_id'] ) : 0;
			$title      = ! empty( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
			$sort_order = isset( $_POST['display_order'] ) ? absint( $_POST['display_order'] ) : 1;

			$exam_obj   = $exam_id ? IAQ_Examinations::get_examination( $exam_id ) : null;
			$subj_obj   = $subj_id ? IAQ_Question_Bank::get_subject( $subj_id ) : null;

			if ( empty( $title ) ) {
				$prefix = $exam_obj ? $exam_obj->name : 'Mock';
				$sub_nm = $subj_obj ? $subj_obj->name : '';
				$title  = trim( "{$prefix} {$sub_nm} - Mock Test {$sort_order}" );
			}

			$quiz_data = array(
				'title'             => $title,
				'slug'              => sanitize_title( $title ),
				'quiz_type'         => 'mock',
				'exam_category'     => $exam_obj ? $exam_obj->name : '',
				'subject_id'        => $subj_id,
				'topic_id'          => 0,
				'duration_minutes'  => isset( $_POST['duration_minutes'] ) ? absint( $_POST['duration_minutes'] ) : 120,
				'correct_marks'     => isset( $_POST['correct_marks'] ) ? floatval( $_POST['correct_marks'] ) : 4.00,
				'negative_marks'    => isset( $_POST['negative_marks'] ) ? floatval( $_POST['negative_marks'] ) : 1.00,
				'pass_percentage'   => isset( $_POST['pass_percentage'] ) ? floatval( $_POST['pass_percentage'] ) : 50.00,
				'show_answers_mode' => 'after_submission',
				'status'            => 'published',
				'access_type'       => isset( $_POST['access_type'] ) && in_array( $_POST['access_type'], array( 'free', 'paid' ), true ) ? $_POST['access_type'] : 'free',
				'shuffle_questions' => 1,
				'shuffle_options'   => 1,
				'display_order'     => $sort_order,
			);

			$quiz_id = IAQ_Quiz_Builder::save_quiz( $quiz_data );
			if ( is_wp_error( $quiz_id ) ) {
				$this->add_notice( 'error', $quiz_id->get_error_message() );
			} else {
				// Attach quiz to mock context
				IAQ_Mock_Tests::attach_quiz_to_mock_context(
					$quiz_id,
					array(
						'category_id' => $cat_id,
						'exam_id'     => $exam_id,
						'subject_id'  => $subj_id,
						'sort_order'  => $sort_order,
					)
				);

				// Also assign to exam_subject_quizzes for unified practice/exam tracking
				IAQ_Practice_Sets::assign_quiz_to_exam_subject( $exam_id, $subj_id, $quiz_id, $sort_order );

				// Bulk import file if provided
				$imported_count = 0;
				if ( ! empty( $_FILES['csv_file']['tmp_name'] ) && is_uploaded_file( $_FILES['csv_file']['tmp_name'] ) ) {
					$ext = strtolower( pathinfo( $_FILES['csv_file']['name'], PATHINFO_EXTENSION ) );
					if ( in_array( $ext, array( 'csv', 'json' ), true ) ) {
						$import_res = IAQ_Import_Export::import_from_file( $_FILES['csv_file']['tmp_name'], $quiz_id, null, $_FILES['csv_file']['name'] );
						if ( ! empty( $import_res['success'] ) ) {
							$imported_count = (int) $import_res['imported'];
						}
					}
				}

				$msg = $imported_count > 0 ? 'practice_set_created_with_import' : 'practice_set_created';
				$redirect = add_query_arg(
					array(
						'page'        => 'iaq-mock-tests',
						'category_id' => $cat_id,
						'exam_id'     => $exam_id,
						'subject_id'  => $subj_id,
						'message'     => $msg,
						'count'       => $imported_count,
					),
					admin_url( 'admin.php' )
				);
				wp_safe_redirect( $redirect );
				exit;
			}
		}

		// 49. Detach / Delete Mock Context Quiz.
		if ( isset( $_GET['iaq_action'] ) && 'delete_mock_context_quiz' === $_GET['iaq_action'] && isset( $_GET['quiz_id'] ) ) {
			$quiz_id = absint( $_GET['quiz_id'] );
			$cat_id  = isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] ) : 0;
			$exam_id = isset( $_GET['exam_id'] ) ? absint( $_GET['exam_id'] ) : 0;
			$subj_id = isset( $_GET['subject_id'] ) ? absint( $_GET['subject_id'] ) : 0;
			$rel_id  = isset( $_GET['rel_id'] ) ? absint( $_GET['rel_id'] ) : 0;

			check_admin_referer( 'iaq_delete_mock_quiz_' . $quiz_id );
			if ( $rel_id ) {
				IAQ_Mock_Tests::detach_quiz_from_mock_context( $rel_id );
			}
			IAQ_Quiz_Builder::delete_quiz( $quiz_id );

			$redirect = add_query_arg(
				array(
					'page'        => 'iaq-mock-tests',
					'category_id' => $cat_id,
					'exam_id'     => $exam_id,
					'subject_id'  => $subj_id,
					'message'     => 'quiz_deleted',
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * AJAX endpoint to return topics JSON for selected subject.
	 */
	public function ajax_get_topics_by_subject() {
		check_ajax_referer( 'iaq_admin_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_iaq_quizzes' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$subject_id = isset( $_GET['subject_id'] ) ? absint( $_GET['subject_id'] ) : 0;
		$topics     = IAQ_Question_Bank::get_topics( $subject_id );

		wp_send_json_success( array( 'topics' => $topics ) );
	}

	/**
	 * Helper to register a notice.
	 *
	 * @param string $type Notice type: 'success', 'error', 'warning', 'info'.
	 * @param string $message Notice content.
	 */
	public function add_notice( $type, $message ) {
		$this->notices[] = array(
			'type'    => $type,
			'message' => $message,
		);
	}

	/**
	 * Display registered admin notices or query-param triggered messages.
	 */
	public function render_notices() {
		if ( isset( $_GET['message'] ) ) {
			$msg_key = sanitize_key( $_GET['message'] );
			$count   = isset( $_GET['count'] ) ? absint( $_GET['count'] ) : 0;

			$map = array(
				'question_created'        => array( 'success', __( 'Question created successfully.', 'indianagri-quiz-engine' ) ),
				'question_updated'        => array( 'success', __( 'Question updated successfully.', 'indianagri-quiz-engine' ) ),
				'question_deleted'        => array( 'success', __( 'Question deleted.', 'indianagri-quiz-engine' ) ),
				'bulk_deleted'            => array( 'success', sprintf( __( '%d questions deleted successfully.', 'indianagri-quiz-engine' ), $count ) ),
				'bulk_status_updated'     => array( 'success', sprintf( __( '%d questions status updated.', 'indianagri-quiz-engine' ), $count ) ),
				'subject_saved'           => array( 'success', __( 'Subject saved successfully.', 'indianagri-quiz-engine' ) ),
				'subject_deleted'         => array( 'success', __( 'Subject deleted.', 'indianagri-quiz-engine' ) ),
				'topic_saved'             => array( 'success', __( 'Topic saved successfully.', 'indianagri-quiz-engine' ) ),
				'topic_deleted'           => array( 'success', __( 'Topic deleted.', 'indianagri-quiz-engine' ) ),
				'quiz_created'            => array( 'success', __( 'Quiz created successfully. You can now assign questions.', 'indianagri-quiz-engine' ) ),
				'quiz_updated'            => array( 'success', __( 'Quiz settings updated successfully.', 'indianagri-quiz-engine' ) ),
				'quiz_deleted'            => array( 'success', __( 'Quiz deleted successfully.', 'indianagri-quiz-engine' ) ),
				'bulk_quizzes_deleted'    => array( 'success', sprintf( __( '%d quizzes deleted successfully.', 'indianagri-quiz-engine' ), $count ) ),
				'bulk_quizzes_status'     => array( 'success', sprintf( __( '%d quizzes status updated.', 'indianagri-quiz-engine' ), $count ) ),
				'questions_assigned'      => array( 'success', sprintf( __( '%d questions assigned to quiz successfully.', 'indianagri-quiz-engine' ), $count ) ),
				'questions_auto_assigned' => array( 'success', sprintf( __( '%d questions auto-assigned to quiz from Question Bank.', 'indianagri-quiz-engine' ), $count ) ),
				'question_removed'        => array( 'success', __( 'Question removed from quiz.', 'indianagri-quiz-engine' ) ),
				'exam_saved'              => array( 'success', __( 'Examination saved successfully.', 'indianagri-quiz-engine' ) ),
				'exam_deleted'            => array( 'success', __( 'Examination deleted successfully.', 'indianagri-quiz-engine' ) ),
				'exam_duplicated'         => array( 'success', __( 'Examination duplicated successfully.', 'indianagri-quiz-engine' ) ),
				'plan_saved'              => array( 'success', __( 'Subscription plan saved successfully.', 'indianagri-quiz-engine' ) ),
				'plan_deleted'            => array( 'success', __( 'Subscription plan deleted successfully.', 'indianagri-quiz-engine' ) ),
				'sub_granted'             => array( 'success', __( 'Subscription entitlement granted successfully to student.', 'indianagri-quiz-engine' ) ),
				'sub_revoked'             => array( 'success', __( 'Subscription entitlement revoked successfully.', 'indianagri-quiz-engine' ) ),
				'quiz_duplicated'         => array( 'success', __( 'Quiz duplicated successfully as draft.', 'indianagri-quiz-engine' ) ),
				'book_saved'              => array( 'success', __( 'Practice book saved successfully.', 'indianagri-quiz-engine' ) ),
				'book_deleted'            => array( 'success', __( 'Practice book archived successfully.', 'indianagri-quiz-engine' ) ),
				'quiz_assigned_to_book'            => array( 'success', __( 'Practice set assigned to book successfully.', 'indianagri-quiz-engine' ) ),
				'quiz_removed_from_book'           => array( 'success', __( 'Practice set removed from book.', 'indianagri-quiz-engine' ) ),
				'practice_set_created'             => array( 'success', __( 'Practice set created and attached to book successfully.', 'indianagri-quiz-engine' ) ),
				'practice_set_created_with_import' => array( 'success', sprintf( __( 'Practice set created and %d questions bulk imported and assigned successfully!', 'indianagri-quiz-engine' ), $count ) ),
				'practice_set_imported'            => array( 'success', sprintf( __( '%d questions bulk imported and assigned to practice set successfully!', 'indianagri-quiz-engine' ), $count ) ),
				'exam_subject_saved'               => array( 'success', __( 'Subject associated with examination successfully.', 'indianagri-quiz-engine' ) ),
				'exam_subject_removed'    => array( 'success', __( 'Subject removed from examination.', 'indianagri-quiz-engine' ) ),
				'esq_saved'               => array( 'success', __( 'Practice set assigned to examination subject.', 'indianagri-quiz-engine' ) ),
				'esq_removed'             => array( 'success', __( 'Practice set removed from examination subject.', 'indianagri-quiz-engine' ) ),
				'mock_category_saved'     => array( 'success', __( 'Mock test category saved successfully.', 'indianagri-quiz-engine' ) ),
				'mock_category_deleted'   => array( 'success', __( 'Mock test category deleted successfully.', 'indianagri-quiz-engine' ) ),
				'state_univ_saved'        => array( 'success', __( 'State or University entry saved successfully.', 'indianagri-quiz-engine' ) ),
				'state_univ_deleted'      => array( 'success', __( 'State or University entry deleted successfully.', 'indianagri-quiz-engine' ) ),
				'vacancy_saved'           => array( 'success', __( 'Recruitment vacancy saved successfully.', 'indianagri-quiz-engine' ) ),
				'vacancy_deleted'         => array( 'success', __( 'Recruitment vacancy deleted successfully.', 'indianagri-quiz-engine' ) ),
				'series_saved'            => array( 'success', __( 'Mock test series saved successfully.', 'indianagri-quiz-engine' ) ),
				'series_deleted'          => array( 'success', __( 'Mock test series deleted successfully.', 'indianagri-quiz-engine' ) ),
				'quiz_attached'           => array( 'success', __( 'Mock test context attachment saved successfully.', 'indianagri-quiz-engine' ) ),
				'quiz_detached'           => array( 'success', __( 'Mock test detached from hierarchy context.', 'indianagri-quiz-engine' ) ),
			);

			if ( isset( $map[ $msg_key ] ) ) {
				$this->notices[] = array(
					'type'    => $map[ $msg_key ][0],
					'message' => $map[ $msg_key ][1],
				);
			}
		}

		foreach ( $this->notices as $notice ) {
			printf(
				'<div class="notice notice-%1$s is-dismissible iaq-notice"><p>%2$s</p></div>',
				esc_attr( $notice['type'] ),
				wp_kses_post( $notice['message'] )
			);
		}
	}

	/**
	 * Render Question Bank main table page.
	 */
	public function render_question_bank_page() {
		// If edit action requested, route to question form template.
		if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] && ! empty( $_GET['id'] ) ) {
			$question_id = absint( $_GET['id'] );
			$question    = IAQ_Question_Bank::get_question( $question_id );
			include IAQ_PLUGIN_DIR . 'templates/admin/question-form.php';
			return;
		}

		include IAQ_PLUGIN_DIR . 'templates/admin/question-bank.php';
	}

	/**
	 * Render Add New Question view.
	 */
	public function render_add_question_page() {
		$question = null;
		include IAQ_PLUGIN_DIR . 'templates/admin/question-form.php';
	}

	/**
	 * Render Quizzes list page or routed sub-actions.
	 */
	public function render_quizzes_page() {
		$action  = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
		$quiz_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( 'edit' === $action && $quiz_id > 0 ) {
			$quiz = IAQ_Quiz_Builder::get_quiz( $quiz_id );
			include IAQ_PLUGIN_DIR . 'templates/admin/quiz-form.php';
			return;
		}

		if ( 'manage_questions' === $action && $quiz_id > 0 ) {
			$quiz = IAQ_Quiz_Builder::get_quiz( $quiz_id );
			include IAQ_PLUGIN_DIR . 'templates/admin/quiz-questions.php';
			return;
		}

		include IAQ_PLUGIN_DIR . 'templates/admin/quizzes-list.php';
	}

	/**
	 * Render Add New Quiz view.
	 */
	public function render_add_quiz_page() {
		$quiz = null;
		include IAQ_PLUGIN_DIR . 'templates/admin/quiz-form.php';
	}

	/**
	 * Render Subjects & Topics management page.
	 */
	public function render_subjects_topics_page() {
		include IAQ_PLUGIN_DIR . 'templates/admin/subjects-topics.php';
	}

	/**
	 * Render Import/Export page.
	 */
	public function render_import_export_page() {
		include IAQ_PLUGIN_DIR . 'templates/admin/import-export.php';
	}

	/**
	 * Render Settings page.
	 */
	public function render_settings_page() {
		$settings = get_option(
			'iaq_settings',
			array(
				'brand_color'              => '#117E3A',
				'default_duration'         => 60,
				'default_marks'            => 1.00,
				'default_negative_marks'   => 0.25,
				'delete_data_on_uninstall' => 0,
				'enable_hindi_support'     => 1,
			)
		);
		include IAQ_PLUGIN_DIR . 'templates/admin/settings.php';
	}

	/**
	 * Render Admin Dashboard and Analytics view.
	 */
	public function render_dashboard_page() {
		include IAQ_PLUGIN_DIR . 'templates/admin/dashboard.php';
	}

	/**
	 * Render Examinations management view.
	 */
	public function render_examinations_page() {
		include IAQ_PLUGIN_DIR . 'templates/admin/examinations.php';
	}

	/**
	 * Render Subscription Plans catalog view.
	 */
	public function render_subscription_plans_page() {
		include IAQ_PLUGIN_DIR . 'templates/admin/subscription-plans.php';
	}

	/**
	 * Render Student Subscriptions audit & entitlements view.
	 */
	public function render_user_subscriptions_page() {
		include IAQ_PLUGIN_DIR . 'templates/admin/user-subscriptions.php';
	}

	/**
	 * Render Examination Attempts evaluation view.
	 */
	public function render_attempts_page() {
		include IAQ_PLUGIN_DIR . 'templates/admin/attempts-list.php';
	}

	/**
	 * Render Practice Books management page.
	 */
	public function render_books_page() {
		include IAQ_PLUGIN_DIR . 'templates/admin/books.php';
	}

	/**
	 * Render Mock Tests Hierarchy management view.
	 */
	public function render_mock_tests_page() {
		include IAQ_PLUGIN_DIR . 'templates/admin/mock-tests.php';
	}
}
