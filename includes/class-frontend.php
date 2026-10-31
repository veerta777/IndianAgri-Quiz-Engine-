<?php
/**
 * Frontend shortcodes, template loaders, and examination landing cards.
 * Compatible with Elementor Free and standard WordPress pages.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IAQ_Frontend
 */
class IAQ_Frontend {

	/**
	 * Initialize frontend hooks and shortcode registration.
	 */
	public function init() {
		add_shortcode( 'iaq_quiz', array( $this, 'render_quiz_shortcode' ) );
		add_shortcode( 'iaq_quiz_list', array( $this, 'render_quiz_list_shortcode' ) );
		add_shortcode( 'iaq_practice_sets', array( $this, 'render_practice_sets_shortcode' ) );
		add_shortcode( 'iaq_mock_tests', array( $this, 'render_mock_tests_shortcode' ) );
		add_shortcode( 'iaq_student_dashboard', array( $this, 'render_student_dashboard' ) );
		add_shortcode( 'iaq_quiz_results', array( $this, 'render_results_shortcode' ) );
		add_shortcode( 'iaq_subscription_plans', array( $this, 'render_subscription_plans_shortcode' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		add_filter( 'wp_resource_hints', array( $this, 'add_resource_hints' ), 10, 2 );

		// Student Learning Features AJAX Endpoints
		add_action( 'wp_ajax_iaq_toggle_bookmark', array( $this, 'ajax_toggle_bookmark' ) );
		add_action( 'wp_ajax_iaq_create_revision_quiz', array( $this, 'ajax_create_revision_quiz' ) );
		add_action( 'wp_ajax_iaq_update_notebook_status', array( $this, 'ajax_update_notebook_status' ) );
		add_action( 'wp_ajax_iaq_remove_notebook_entry', array( $this, 'ajax_remove_notebook_entry' ) );
		add_action( 'wp_ajax_iaq_create_retry_quiz', array( $this, 'ajax_create_retry_quiz' ) );
		add_action( 'wp_ajax_iaq_generate_custom_test', array( $this, 'ajax_generate_custom_test' ) );
		add_action( 'wp_ajax_iaq_update_study_goals', array( $this, 'ajax_update_study_goals' ) );
		add_action( 'wp_ajax_iaq_get_leaderboard', array( $this, 'ajax_get_leaderboard' ) );

		// Mobile App Download URL handler / redirect
		add_action( 'template_redirect', array( $this, 'handle_mobile_app_download_redirect' ) );
	}

	/**
	 * Preconnect to Google Fonts domains for faster loading.
	 *
	 * @param array  $urls Array of resources.
	 * @param string $relation_type Relation type ('preconnect', 'dns-prefetch').
	 * @return array
	 */
	public function add_resource_hints( $urls, $relation_type ) {
		if ( 'preconnect' === $relation_type ) {
			$urls[] = array(
				'href' => 'https://fonts.googleapis.com',
			);
			$urls[] = array(
				'href'        => 'https://fonts.gstatic.com',
				'crossorigin' => 'anonymous',
			);
		}
		return $urls;
	}

	/**
	 * Register frontend stylesheet and quiz script.
	 */
	public function register_frontend_assets() {
		wp_register_style(
			'iaq-google-fonts',
			'https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700&family=Poppins:wght@400;500;600;700;800;900&display=swap',
			array(),
			null
		);

		wp_enqueue_style(
			'iaq-frontend-styles',
			IAQ_PLUGIN_URL . 'assets/css/frontend.css',
			array( 'dashicons', 'iaq-google-fonts' ),
			IAQ_VERSION
		);

		wp_register_script(
			'iaq-quiz-engine',
			IAQ_PLUGIN_URL . 'assets/js/quiz-engine.js',
			array( 'jquery' ),
			IAQ_VERSION,
			true
		);

		wp_localize_script(
			'iaq-quiz-engine',
			'iaqQuizData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'iaq_quiz_nonce' ),
				'i18n'    => array(
					'starting'      => __( 'Initializing examination session...', 'indianagri-quiz-engine' ),
					'saving'        => __( 'Saving...', 'indianagri-quiz-engine' ),
					'saved'         => __( 'Synchronized', 'indianagri-quiz-engine' ),
					'submitting'    => __( 'Submitting examination...', 'indianagri-quiz-engine' ),
					'confirmSubmit' => __( 'Are you sure you want to submit your examination?', 'indianagri-quiz-engine' ),
					'leaveConfirm'  => __( 'You have an examination in progress. Leaving this page may cause progress loss.', 'indianagri-quiz-engine' ),
				),
			)
		);
	}

	/**
	 * Ensure frontend assets are enqueued.
	 */
	private function enqueue_frontend_assets() {
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'iaq-frontend-styles' );
		wp_enqueue_script( 'iaq-quiz-engine' );
	}

	/**
	 * Render single quiz shortcode: [iaq_quiz id="123"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_quiz_shortcode( $atts ) {
		$this->enqueue_frontend_assets();

		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'iaq_quiz'
		);

		$quiz_id = absint( $atts['id'] );
		if ( ! $quiz_id ) {
			return '<div class="iaq-notice-box iaq-notice-warning">' . esc_html__( 'Please provide a valid quiz ID (e.g., [iaq_quiz id="123"]).', 'indianagri-quiz-engine' ) . '</div>';
		}

		$quiz = IAQ_Quiz_Builder::get_quiz( $quiz_id );
		if ( ! $quiz || ( 'published' !== $quiz->status && ! current_user_can( 'manage_options' ) ) ) {
			return '<div class="iaq-notice-box iaq-notice-error">' . esc_html__( 'The requested quiz was not found or is currently unpublished.', 'indianagri-quiz-engine' ) . '</div>';
		}

		// Check for active in-progress attempt.
		$active_attempt = null;
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			global $wpdb;
			$table_attempts = $wpdb->prefix . 'iaq_quiz_attempts';
			$active_attempt = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table_attempts} WHERE quiz_id = %d AND user_id = %d AND status = 'in_progress' ORDER BY id DESC LIMIT 1",
					$quiz_id,
					$user_id
				)
			);
		}

		ob_start();
		include IAQ_PLUGIN_DIR . 'templates/frontend/quiz-overview.php';
		include IAQ_PLUGIN_DIR . 'templates/frontend/quiz-interface.php';
		return ob_get_clean();
	}

	/**
	 * Render quiz list shortcode: [iaq_quiz_list type="mock" category="ICAR AIEEA"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_quiz_list_shortcode( $atts ) {
		$this->enqueue_frontend_assets();

		$atts = shortcode_atts(
			array(
				'type'     => '',
				'category' => '',
				'limit'    => 12,
			),
			$atts,
			'iaq_quiz_list'
		);

		if ( 'practice' === $atts['type'] || isset( $_GET['iaq_ps_view'] ) ) {
			return $this->render_practice_sets_shortcode( $atts );
		}

		if ( 'mock' === $atts['type'] || isset( $_GET['iaq_mt_view'] ) ) {
			return $this->render_mock_tests_shortcode( $atts );
		}

		$query_args = array(
			'status'        => 'published',
			'quiz_type'     => sanitize_key( $atts['type'] ),
			'exam_category' => sanitize_text_field( $atts['category'] ),
			'per_page'      => max( 1, absint( $atts['limit'] ) ),
			'page'          => 1,
			'orderby'       => 'id',
			'order'         => 'DESC',
		);

		$quizzes = IAQ_Quiz_Builder::get_quizzes( $query_args );

		ob_start();
		include IAQ_PLUGIN_DIR . 'templates/frontend/quiz-list.php';
		return ob_get_clean();
	}

	/**
	 * Render Practice Sets multi-hierarchy browsing shortcode: [iaq_practice_sets]
	 * Supports:
	 * 1. Practice by Book
	 * 2. Practice by Subjects
	 * 3. Practice by Exams
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_practice_sets_shortcode( $atts = array() ) {
		$this->enqueue_frontend_assets();

		// If a quiz attempt was initiated, render the examination overview/screen
		if ( isset( $_GET['iaq_quiz_id'] ) && absint( $_GET['iaq_quiz_id'] ) > 0 ) {
			$q_id      = absint( $_GET['iaq_quiz_id'] );
			$back_url  = remove_query_arg( 'iaq_quiz_id' );
			$quiz_html = $this->render_quiz_shortcode( array( 'id' => $q_id ) );
			return '<div class="iaq-practice-attempt-wrapper"><div style="margin-bottom:16px;"><a href="' . esc_url( $back_url ) . '" class="iaq-btn iaq-btn-outline"><span class="dashicons dashicons-arrow-left-alt"></span> ' . esc_html__( 'Back to Practice Sets', 'indianagri-quiz-engine' ) . '</a></div>' . $quiz_html . '</div>';
		}

		$atts = shortcode_atts(
			array(
				'category'   => '',
				'view'       => '',
				'book_id'    => 0,
				'subject_id' => 0,
				'exam_id'    => 0,
				'limit'      => 12,
			),
			$atts,
			'iaq_practice_sets'
		);

		// Determine view mode
		$view = 'landing';
		if ( isset( $_GET['iaq_ps_view'] ) ) {
			$view = sanitize_key( $_GET['iaq_ps_view'] );
		} elseif ( ! empty( $atts['view'] ) ) {
			$view = sanitize_key( $atts['view'] );
		} elseif ( ! empty( $atts['category'] ) ) {
			$view = sanitize_key( $atts['category'] );
		}

		// Ensure clicking Practice by Book always opens the catalog of available books
		if ( 'books' === $view || ( 'book' === $view && ( empty( $_GET['book_id'] ) && empty( $atts['book_id'] ) ) ) ) {
			$view = 'books';
		}

		$user_id       = get_current_user_id();
		$books         = array();
		$book          = null;
		$subjects      = array();
		$subject       = null;
		$exams         = array();
		$exam          = null;
		$exam_subjects = array();
		$quizzes       = array();

		switch ( $view ) {
			case 'books':
				$search = isset( $_GET['ps_search'] ) ? sanitize_text_field( wp_unslash( $_GET['ps_search'] ) ) : '';
				$books  = IAQ_Practice_Sets::get_books( array( 'status' => 'published', 'search' => $search ) );
				break;

			case 'book':
				$b_id = isset( $_GET['book_id'] ) ? absint( $_GET['book_id'] ) : ( isset( $atts['book_id'] ) ? absint( $atts['book_id'] ) : 0 );
				$book = $b_id > 0 ? IAQ_Practice_Sets::get_book( $b_id ) : null;
				if ( ! $book ) {
					// Fallback to all available books catalog so candidate always sees available books
					$view   = 'books';
					$search = isset( $_GET['ps_search'] ) ? sanitize_text_field( wp_unslash( $_GET['ps_search'] ) ) : '';
					$books  = IAQ_Practice_Sets::get_books( array( 'status' => 'published', 'search' => $search ) );
				} else {
					$quizzes = IAQ_Practice_Sets::get_book_quizzes( $book->id, 'published', $user_id );
				}
				break;

			case 'subjects':
				$search   = isset( $_GET['ps_search'] ) ? sanitize_text_field( wp_unslash( $_GET['ps_search'] ) ) : '';
				$subjects = IAQ_Practice_Sets::get_subjects_catalog( array( 'search' => $search ) );
				break;

			case 'subject':
				$s_id    = isset( $_GET['subject_id'] ) ? absint( $_GET['subject_id'] ) : absint( $atts['subject_id'] );
				$subject = IAQ_Question_Bank::get_subject( $s_id );
				$quizzes = $subject ? IAQ_Practice_Sets::get_subject_quizzes( $subject->id, 'published', $user_id ) : array();
				break;

			case 'exams':
				$search = isset( $_GET['ps_search'] ) ? sanitize_text_field( wp_unslash( $_GET['ps_search'] ) ) : '';
				$exams  = IAQ_Practice_Sets::get_exams_catalog( array( 'search' => $search ) );
				break;

			case 'exam':
				$e_id          = isset( $_GET['exam_id'] ) ? absint( $_GET['exam_id'] ) : absint( $atts['exam_id'] );
				$exam          = IAQ_Examinations::get_examination( $e_id );
				$exam_subjects = $exam ? IAQ_Practice_Sets::get_exam_subjects( $exam->id ) : array();
				break;

			case 'exam_subject':
				$e_id    = isset( $_GET['exam_id'] ) ? absint( $_GET['exam_id'] ) : absint( $atts['exam_id'] );
				$s_id    = isset( $_GET['subject_id'] ) ? absint( $_GET['subject_id'] ) : absint( $atts['subject_id'] );
				$exam    = IAQ_Examinations::get_examination( $e_id );
				$subject = IAQ_Question_Bank::get_subject( $s_id );
				$quizzes = ( $exam && $subject ) ? IAQ_Practice_Sets::get_exam_subject_quizzes( $exam->id, $subject->id, 'published', $user_id ) : array();
				break;

			case 'landing':
			default:
				$view     = 'landing';
				$books    = IAQ_Practice_Sets::get_books( array( 'status' => 'published', 'limit' => 6 ) );
				$subjects = IAQ_Practice_Sets::get_subjects_catalog();
				$exams    = IAQ_Practice_Sets::get_exams_catalog();
				break;
		}

		ob_start();
		include IAQ_PLUGIN_DIR . 'templates/frontend/practice-sets.php';
		return ob_get_clean();
	}

	/**
	 * Render Mock Tests multi-hierarchy browsing shortcode: [iaq_mock_tests]
	 * Supports the 10 Main Mock Test Categories, Examinations, Subjects, Vacancies, States, and Series.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_mock_tests_shortcode( $atts = array() ) {
		$this->enqueue_frontend_assets();

		// If a quiz attempt was initiated, render the examination overview/screen
		if ( isset( $_GET['iaq_quiz_id'] ) && absint( $_GET['iaq_quiz_id'] ) > 0 ) {
			$q_id      = absint( $_GET['iaq_quiz_id'] );
			$back_url  = remove_query_arg( 'iaq_quiz_id' );
			$quiz_html = $this->render_quiz_shortcode( array( 'id' => $q_id ) );
			return '<div class="iaq-mock-attempt-wrapper"><div style="margin-bottom:16px;"><a href="' . esc_url( $back_url ) . '" class="iaq-btn iaq-btn-outline"><span class="dashicons dashicons-arrow-left-alt"></span> ' . esc_html__( 'Back to Mock Tests', 'indianagri-quiz-engine' ) . '</a></div>' . $quiz_html . '</div>';
		}

		$atts = shortcode_atts(
			array(
				'category'    => '',
				'category_id' => 0,
				'exam_id'     => 0,
				'subject_id'  => 0,
				'vacancy_id'  => 0,
				'state_id'    => 0,
				'series_id'   => 0,
				'view'        => '',
				'limit'       => 12,
			),
			$atts,
			'iaq_mock_tests'
		);

		// Determine view mode
		$view = 'landing';
		if ( isset( $_GET['iaq_mt_view'] ) ) {
			$view = sanitize_key( $_GET['iaq_mt_view'] );
		} elseif ( ! empty( $atts['view'] ) ) {
			$view = sanitize_key( $atts['view'] );
		} elseif ( ! empty( $atts['category_id'] ) || ! empty( $atts['category'] ) ) {
			$view = 'category';
		}

		$cat_id    = isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] ) : absint( $atts['category_id'] );
		$exam_id   = isset( $_GET['exam_id'] ) ? absint( $_GET['exam_id'] ) : absint( $atts['exam_id'] );
		$sub_id    = isset( $_GET['subject_id'] ) ? absint( $_GET['subject_id'] ) : absint( $atts['subject_id'] );
		$vac_id    = isset( $_GET['vacancy_id'] ) ? absint( $_GET['vacancy_id'] ) : absint( $atts['vacancy_id'] );
		$state_id  = isset( $_GET['state_id'] ) ? absint( $_GET['state_id'] ) : absint( $atts['state_id'] );
		$univ_id   = isset( $_GET['university_id'] ) ? absint( $_GET['university_id'] ) : 0;
		$series_id = isset( $_GET['series_id'] ) ? absint( $_GET['series_id'] ) : absint( $atts['series_id'] );

		// If category slug/code provided in atts or GET
		if ( ! $cat_id && ! empty( $atts['category'] ) ) {
			$cat_by_slug = IAQ_Mock_Tests::get_category( $atts['category'] );
			if ( $cat_by_slug ) {
				$cat_id = (int) $cat_by_slug->id;
			}
		}

		$category      = $cat_id ? IAQ_Mock_Tests::get_category( $cat_id ) : null;
		$exam          = $exam_id ? IAQ_Examinations::get_examination( $exam_id ) : null;
		$subject       = $sub_id ? IAQ_Question_Bank::get_subject( $sub_id ) : null;
		$state         = null;
		$university    = null;
		$vacancy       = null;
		$series        = $series_id ? IAQ_Mock_Tests::get_series( $series_id ) : null;

		$categories    = array();
		$exams         = array();
		$sub_exams     = array();
		$subjects      = array();
		$exam_subjects = array();
		$states        = array();
		$universities  = array();
		$vacancies     = array();
		$quizzes       = array();

		// Auto-resolve view if not explicitly landing or when drilling down
		if ( 'landing' === $view && ( $cat_id || $exam_id || $sub_id || $vac_id || $state_id || $univ_id || $series_id ) ) {
			if ( $series_id || $vac_id || ( $exam_id && $sub_id ) ) {
				$view = 'tests';
			} elseif ( $exam_id ) {
				$view = 'exam';
			} elseif ( $cat_id ) {
				$view = 'category';
			}
		}

		switch ( $view ) {
			case 'category':
				if ( $category ) {
					$cfg = IAQ_Mock_Tests::get_category_hierarchy_config( $category->slug );
					if ( ! empty( $cfg['has_states'] ) ) {
						$states = IAQ_Mock_Tests::get_states();
					} elseif ( ! empty( $cfg['has_universities'] ) ) {
						$universities = IAQ_Mock_Tests::get_universities();
					} elseif ( ! empty( $cfg['has_subjects'] ) && empty( $cfg['has_exams'] ) ) {
						$subjects = IAQ_Question_Bank::get_subjects();
					} elseif ( ! empty( $cfg['has_exams'] ) ) {
						$exams = IAQ_Mock_Tests::get_category_exams( $category->id );
					} else {
						// General Agriculture or flat category -> direct tests
						$quizzes = IAQ_Mock_Tests::get_context_mock_tests( array( 'category_id' => $category->id ) );
					}
				}
				break;

			case 'exam':
				if ( $exam ) {
					$sub_exams     = IAQ_Mock_Tests::get_sub_exams( $exam->id );
					$vacancies     = IAQ_Mock_Tests::get_vacancies( $exam->id );
					$exam_subjects = IAQ_Practice_Sets::get_exam_subjects( $exam->id );
					if ( empty( $sub_exams ) && empty( $vacancies ) && empty( $exam_subjects ) ) {
						$quizzes = IAQ_Mock_Tests::get_context_mock_tests( array( 'category_id' => $cat_id, 'exam_id' => $exam->id ) );
					}
				}
				break;

			case 'state':
				if ( $state_id ) {
					global $wpdb;
					$tbl_states = IAQ_Database::get_table_name( 'mock_states_universities' );
					$state      = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_states} WHERE id = %d AND type = 'state'", $state_id ) );
					if ( $category ) {
						$exams = IAQ_Mock_Tests::get_category_exams( $category->id, 0 );
					}
					// If no exams found for this state, fetch context mock tests
					if ( empty( $exams ) ) {
						$quizzes = IAQ_Mock_Tests::get_context_mock_tests( array( 'category_id' => $cat_id, 'state_id' => $state_id ) );
					}
				}
				break;

			case 'university':
				if ( $univ_id ) {
					global $wpdb;
					$tbl_states = IAQ_Database::get_table_name( 'mock_states_universities' );
					$university = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_states} WHERE id = %d AND type = 'university'", $univ_id ) );
					$quizzes    = IAQ_Mock_Tests::get_context_mock_tests( array( 'category_id' => $cat_id, 'university_id' => $univ_id ) );
				}
				break;

			case 'vacancy':
				if ( $vac_id ) {
					global $wpdb;
					$tbl_vac = IAQ_Database::get_table_name( 'mock_vacancies' );
					$vacancy = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl_vac} WHERE id = %d", $vac_id ) );
					$quizzes = IAQ_Mock_Tests::get_context_mock_tests( array( 'category_id' => $cat_id, 'exam_id' => $exam_id, 'vacancy_id' => $vac_id ) );
				}
				break;

			case 'exam_subject':
			case 'subject':
				$quizzes = IAQ_Mock_Tests::get_context_mock_tests( array( 'category_id' => $cat_id, 'exam_id' => $exam_id, 'subject_id' => $sub_id ) );
				break;

			case 'series':
			case 'tests':
				$quizzes = IAQ_Mock_Tests::get_context_mock_tests(
					array(
						'category_id' => $cat_id,
						'exam_id'     => $exam_id,
						'subject_id'  => $sub_id,
						'vacancy_id'  => $vac_id,
						'state_id'    => $state_id,
						'series_id'   => $series_id,
					)
				);
				break;

			case 'landing':
			default:
				$view       = 'landing';
				$categories = IAQ_Mock_Tests::get_categories( array( 'status' => 'active' ) );
				break;
		}

		ob_start();
		include IAQ_PLUGIN_DIR . 'templates/frontend/mock-tests.php';
		return ob_get_clean();
	}

	/**
	 * Render Student Examination Dashboard [iaq_student_dashboard] (Phase 5).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_student_dashboard( $atts = array() ) {
		$this->enqueue_frontend_assets();

		$atts = shortcode_atts(
			array(
				'limit' => 20,
			),
			$atts,
			'iaq_student_dashboard'
		);

		$limit = absint( $atts['limit'] );
		if ( ! $limit ) {
			$limit = 20;
		}

		$user_id = is_user_logged_in() ? get_current_user_id() : 0;

		// Handle direct scorecard review inside student dashboard
		if ( isset( $_GET['attempt_id'] ) && absint( $_GET['attempt_id'] ) > 0 ) {
			$attempt_id   = absint( $_GET['attempt_id'] );
			$back_url     = remove_query_arg( 'attempt_id' );
			$results_html = $this->render_results_shortcode( array( 'id' => $attempt_id ) );
			return '<div class="iaq-student-portal iaq-portal-fullscreen" id="iaq-student-portal"><div class="iaq-student-dashboard-subview"><div class="iaq-exam-return-nav" style="margin-bottom:16px;"><a href="' . esc_url( $back_url ) . '" class="iaq-btn iaq-btn-outline"><span class="dashicons dashicons-arrow-left-alt"></span> ' . esc_html__( 'Back to Student Dashboard', 'indianagri-quiz-engine' ) . '</a></div>' . $results_html . '</div></div>';
		}

		// Handle direct examination taking inside student dashboard
		if ( isset( $_GET['iaq_quiz_id'] ) && absint( $_GET['iaq_quiz_id'] ) > 0 ) {
			$q_id      = absint( $_GET['iaq_quiz_id'] );
			$back_url  = remove_query_arg( array( 'iaq_quiz_id', 'iaq_action' ) );
			$quiz_html = $this->render_quiz_shortcode( array( 'id' => $q_id ) );
			return '<div class="iaq-student-portal iaq-portal-fullscreen" id="iaq-student-portal"><div class="iaq-student-dashboard-subview"><div class="iaq-exam-return-nav" style="margin-bottom:16px;"><a href="' . esc_url( $back_url ) . '" class="iaq-btn iaq-btn-outline"><span class="dashicons dashicons-arrow-left-alt"></span> ' . esc_html__( 'Back to Student Dashboard', 'indianagri-quiz-engine' ) . '</a></div>' . $quiz_html . '</div></div>';
		}

		$stats            = IAQ_Quiz_Attempt::get_user_dashboard_stats( $user_id );
		$history          = $user_id > 0 ? IAQ_Quiz_Attempt::get_user_attempt_history( $user_id, $limit ) : array();
		$active_attempt   = $user_id > 0 ? IAQ_Quiz_Attempt::get_user_active_attempt( $user_id ) : null;
		$subject_mastery  = $user_id > 0 ? IAQ_Quiz_Attempt::get_user_overall_subject_mastery( $user_id ) : array();
		$subscriptions    = $user_id > 0 ? IAQ_Access_Control::get_user_subscriptions( $user_id, 'active' ) : array();
		$available_plans  = empty( $subscriptions ) ? IAQ_Access_Control::get_plans( array( 'status' => 'active' ) ) : array();
		$continue_quizzes  = IAQ_Quiz_Attempt::get_user_continue_practicing_quizzes( $user_id, 4 );
		$quiz_counts       = IAQ_Quiz_Builder::get_published_quiz_counts();
		$performance_trend = $user_id > 0 ? IAQ_Quiz_Attempt::get_user_performance_trend( $user_id, 6 ) : array();

		// Additional Student Learning Features Datasets
		$bookmarks           = $user_id > 0 ? IAQ_Bookmarks::get_user_bookmarks( $user_id, array( 'limit' => 20 ) ) : array();
		$total_bookmarks     = $user_id > 0 ? IAQ_Bookmarks::get_bookmark_count( $user_id ) : 0;
		$wrong_notebook      = $user_id > 0 ? IAQ_Wrong_Notebook::get_user_notebook( $user_id, array( 'status' => 'active', 'limit' => 20 ) ) : array();
		$total_wrong_active  = $user_id > 0 ? IAQ_Wrong_Notebook::get_notebook_count( $user_id, array( 'status' => 'active' ) ) : 0;
		$total_wrong_resolved= $user_id > 0 ? IAQ_Wrong_Notebook::get_notebook_count( $user_id, array( 'status' => 'resolved' ) ) : 0;
		$current_streak      = $user_id > 0 ? IAQ_Study_Streaks::get_current_streak( $user_id ) : 0;
		$best_streak         = $user_id > 0 ? IAQ_Study_Streaks::get_best_streak( $user_id ) : 0;
		$study_calendar      = $user_id > 0 ? IAQ_Study_Streaks::get_activity_calendar( $user_id, 14 ) : array();
		$user_goals          = $user_id > 0 ? IAQ_Study_Streaks::get_user_goals( $user_id ) : array();
		$examinations        = IAQ_Examinations::get_examinations( array( 'status' => 'active' ) );
		$subjects            = IAQ_Question_Bank::get_subjects( array( 'status' => 'active' ) );
		$leaderboard_enabled = IAQ_Leaderboard::is_enabled();
		$leaderboard_data    = $leaderboard_enabled ? IAQ_Leaderboard::get_leaderboard( array( 'limit' => 10, 'current_user' => $user_id ) ) : null;

		// Render integrated modules into dashboard panes
		$practice_sets_html      = $this->render_practice_sets_shortcode();
		$mock_tests_html         = $this->render_mock_tests_shortcode();
		$subscription_plans_html = $this->render_subscription_plans_shortcode();

		// Determine initial active view pane
		$active_tab = 'dashboard';
		if ( isset( $_GET['iaq_ps_view'] ) || isset( $_GET['book_id'] ) || ( isset( $_GET['iaq_tab'] ) && 'practice-sets' === $_GET['iaq_tab'] ) ) {
			$active_tab = 'practice-sets';
		} elseif ( isset( $_GET['iaq_mt_view'] ) || isset( $_GET['category_id'] ) || isset( $_GET['exam_id'] ) || isset( $_GET['state_id'] ) || isset( $_GET['univ_id'] ) || isset( $_GET['vacancy_id'] ) || isset( $_GET['series_id'] ) || ( isset( $_GET['iaq_tab'] ) && 'mock-tests' === $_GET['iaq_tab'] ) ) {
			$active_tab = 'mock-tests';
		} elseif ( isset( $_GET['iaq_tab'] ) ) {
			$active_tab = sanitize_key( $_GET['iaq_tab'] );
		}

		ob_start();
		include IAQ_PLUGIN_DIR . 'templates/frontend/student-dashboard.php';
		return ob_get_clean();
	}

	/**
	 * Get direct link to a quiz overview or attempt CBT interface.
	 *
	 * @param int    $quiz_id Quiz ID.
	 * @param string $action Optional action parameter (e.g. 'attempt').
	 * @param string $base_url Optional base URL to append query arguments to.
	 * @return string
	 */
	public static function get_quiz_url( $quiz_id, $action = '', $base_url = '' ) {
		$args = array( 'iaq_quiz_id' => absint( $quiz_id ) );
		if ( ! empty( $action ) ) {
			$args['iaq_action'] = sanitize_key( $action );
		}

		// Resolve base URL safely (handles AJAX and non-AJAX contexts)
		if ( empty( $base_url ) ) {
			if ( ! empty( $_POST['current_url'] ) ) {
				$base_url = esc_url_raw( wp_unslash( $_POST['current_url'] ) );
			} elseif ( ! empty( $_GET['current_url'] ) ) {
				$base_url = esc_url_raw( wp_unslash( $_GET['current_url'] ) );
			} elseif ( ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				$referer = function_exists( 'wp_get_referer' ) ? wp_get_referer() : '';
				if ( ! empty( $referer ) && false === strpos( $referer, 'admin-ajax.php' ) ) {
					$base_url = $referer;
				}
			}
		}

		if ( empty( $base_url ) && function_exists( 'get_permalink' ) ) {
			$permalink = get_permalink();
			if ( ! empty( $permalink ) && false === strpos( $permalink, 'admin-ajax.php' ) ) {
				$base_url = $permalink;
			}
		}

		if ( empty( $base_url ) ) {
			$base_url = function_exists( 'home_url' ) ? home_url( '/' ) : '/';
		}

		// Strip any fragment/hash so query args are not placed after the hash
		if ( false !== strpos( $base_url, '#' ) ) {
			$base_url = strtok( $base_url, '#' );
		}

		// Strip existing quiz/action/attempt parameters so they don't compound
		$base_url = remove_query_arg( array( 'iaq_quiz_id', 'iaq_action', 'attempt_id' ), $base_url );

		if ( ( isset( $_GET['page'] ) && 'iaq-preview-student-dashboard' === $_GET['page'] ) || ( false !== strpos( $base_url, 'iaq-preview-student-dashboard' ) ) ) {
			$args['page'] = 'iaq-preview-student-quiz';
		}
		return apply_filters( 'iaq_quiz_url', add_query_arg( $args, $base_url ), $quiz_id, $action );
	}

	/**
	 * Get direct link to quiz listings grid.
	 *
	 * @param string $type Optional quiz type ('practice', 'mock', 'previous_year').
	 * @param string $category Optional category name.
	 * @return string
	 */
	public static function get_quiz_list_url( $type = '', $category = '' ) {
		$args = array();
		if ( 'practice' === $type ) {
			$args['iaq_tab'] = 'practice-sets';
		} elseif ( 'mock' === $type ) {
			$args['iaq_tab'] = 'mock-tests';
		} else {
			$args['iaq_tab'] = 'examinations';
		}
		if ( ! empty( $category ) ) {
			$args['exam_category'] = sanitize_text_field( $category );
		}
		if ( isset( $_GET['page'] ) && 'iaq-preview-student-dashboard' === $_GET['page'] ) {
			$args['page'] = 'iaq-preview-student-list';
		}
		return apply_filters( 'iaq_quiz_list_url', add_query_arg( $args, get_permalink() ), $type, $category );
	}

	/**
	 * Get direct link to scorecard results view.
	 *
	 * @param int $attempt_id Attempt ID.
	 * @return string
	 */
	public static function get_results_url( $attempt_id ) {
		$args = array( 'attempt_id' => absint( $attempt_id ) );
		if ( isset( $_GET['page'] ) && 'iaq-preview-student-dashboard' === $_GET['page'] ) {
			$args['page'] = 'iaq-preview-student-results';
		}
		return apply_filters( 'iaq_results_url', add_query_arg( $args, get_permalink() ), $attempt_id );
	}

	/**
	 * Get direct link to subscription plans catalog.
	 *
	 * @return string
	 */
	public static function get_pricing_url() {
		$args = array( 'iaq_tab' => 'subscriptions' );
		if ( isset( $_GET['page'] ) && 'iaq-preview-student-dashboard' === $_GET['page'] ) {
			$args['page'] = 'iaq-preview-subscription-plans';
		}
		return apply_filters( 'iaq_pricing_page_url', add_query_arg( $args, get_permalink() ) );
	}

	/**
	 * Render Quiz Results & Scorecard shortcode: [iaq_quiz_results id="123"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_results_shortcode( $atts = array() ) {
		$this->enqueue_frontend_assets();

		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'iaq_quiz_results'
		);

		$attempt_id = absint( $atts['id'] );
		if ( ! $attempt_id && isset( $_GET['attempt_id'] ) ) {
			$attempt_id = absint( $_GET['attempt_id'] );
		}

		$user_id = get_current_user_id();

		if ( ! $attempt_id && $user_id > 0 ) {
			global $wpdb;
			$table_attempts = $wpdb->prefix . 'iaq_quiz_attempts';
			$attempt_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table_attempts} WHERE user_id = %d AND status = 'completed' ORDER BY id DESC LIMIT 1",
					$user_id
				)
			);
		}

		if ( ! $attempt_id ) {
			return '<div class="iaq-notice-box iaq-notice-info">' . esc_html__( 'No completed examination attempt found to display.', 'indianagri-quiz-engine' ) . '</div>';
		}

		$scorecard = IAQ_Quiz_Attempt::get_attempt_scorecard( $attempt_id, $user_id );
		if ( is_wp_error( $scorecard ) ) {
			return '<div class="iaq-notice-box iaq-notice-error">' . esc_html( $scorecard->get_error_message() ) . '</div>';
		}

		$questions = IAQ_Quiz_Attempt::get_attempt_review_questions( $attempt_id, $user_id );
		$breakdown = IAQ_Quiz_Attempt::get_attempt_subject_breakdown( $attempt_id, $user_id );

		ob_start();
		include IAQ_PLUGIN_DIR . 'templates/frontend/quiz-scorecard.php';
		return ob_get_clean();
	}

	/**
	 * Render Subscription Plans Pricing Catalog: [iaq_subscription_plans]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_subscription_plans_shortcode( $atts = array() ) {
		$this->enqueue_frontend_assets();

		ob_start();
		include IAQ_PLUGIN_DIR . 'templates/frontend/subscription-plans.php';
		return ob_get_clean();
	}

	/**
	 * AJAX: Toggle Bookmark on a Question.
	 */
	public function ajax_toggle_bookmark() {
		check_ajax_referer( 'iaq_quiz_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in to bookmark questions.', 'indianagri-quiz-engine' ) ) );
		}

		$question_id = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;
		$notes       = isset( $_POST['notes'] ) ? sanitize_textarea_field( $_POST['notes'] ) : '';

		$result = IAQ_Bookmarks::toggle_bookmark( $user_id, $question_id, $notes );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX: Generate Bookmark Revision Quiz.
	 */
	public function ajax_create_revision_quiz() {
		check_ajax_referer( 'iaq_quiz_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Authentication required.', 'indianagri-quiz-engine' ) ) );
		}

		$quiz_id = IAQ_Bookmarks::create_revision_quiz( $user_id );
		if ( is_wp_error( $quiz_id ) ) {
			wp_send_json_error( array( 'message' => $quiz_id->get_error_message() ) );
		}

		$current_url = ! empty( $_POST['current_url'] ) ? esc_url_raw( wp_unslash( $_POST['current_url'] ) ) : '';
		wp_send_json_success(
			array(
				'quiz_id'      => $quiz_id,
				'redirect_url' => self::get_quiz_url( $quiz_id, 'attempt', $current_url ),
			)
		);
	}

	/**
	 * AJAX: Update status of Wrong Answer Notebook entry.
	 */
	public function ajax_update_notebook_status() {
		check_ajax_referer( 'iaq_quiz_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Authentication required.', 'indianagri-quiz-engine' ) ) );
		}

		$question_id = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;
		$status      = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'resolved';

		$updated = IAQ_Wrong_Notebook::update_status( $user_id, $question_id, $status );
		wp_send_json_success( array( 'updated' => $updated ) );
	}

	/**
	 * AJAX: Remove entry from Wrong Answer Notebook.
	 */
	public function ajax_remove_notebook_entry() {
		check_ajax_referer( 'iaq_quiz_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Authentication required.', 'indianagri-quiz-engine' ) ) );
		}

		$question_id = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;
		$removed     = IAQ_Wrong_Notebook::remove_entry( $user_id, $question_id );
		wp_send_json_success( array( 'removed' => $removed ) );
	}

	/**
	 * AJAX: Generate Retry Quiz for Wrong Answer Notebook.
	 */
	public function ajax_create_retry_quiz() {
		check_ajax_referer( 'iaq_quiz_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Authentication required.', 'indianagri-quiz-engine' ) ) );
		}

		$args = array();
		if ( ! empty( $_POST['subject_id'] ) ) {
			$args['subject_id'] = absint( $_POST['subject_id'] );
		}

		$quiz_id = IAQ_Wrong_Notebook::create_retry_quiz( $user_id, $args );
		if ( is_wp_error( $quiz_id ) ) {
			wp_send_json_error( array( 'message' => $quiz_id->get_error_message() ) );
		}

		$current_url = ! empty( $_POST['current_url'] ) ? esc_url_raw( wp_unslash( $_POST['current_url'] ) ) : '';
		wp_send_json_success(
			array(
				'quiz_id'      => $quiz_id,
				'redirect_url' => self::get_quiz_url( $quiz_id, 'attempt', $current_url ),
			)
		);
	}

	/**
	 * AJAX: Generate Custom Practice Test.
	 */
	public function ajax_generate_custom_test() {
		check_ajax_referer( 'iaq_quiz_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Authentication required.', 'indianagri-quiz-engine' ) ) );
		}

		$quiz_id = IAQ_Custom_Practice::generate_custom_test( $user_id, $_POST );
		if ( is_wp_error( $quiz_id ) ) {
			wp_send_json_error( array( 'message' => $quiz_id->get_error_message() ) );
		}

		$current_url = ! empty( $_POST['current_url'] ) ? esc_url_raw( wp_unslash( $_POST['current_url'] ) ) : '';
		wp_send_json_success(
			array(
				'quiz_id'      => $quiz_id,
				'redirect_url' => self::get_quiz_url( $quiz_id, 'attempt', $current_url ),
			)
		);
	}

	/**
	 * AJAX: Update Student Study Goals.
	 */
	public function ajax_update_study_goals() {
		check_ajax_referer( 'iaq_quiz_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Authentication required.', 'indianagri-quiz-engine' ) ) );
		}

		$weekly_tests = isset( $_POST['weekly_tests'] ) ? absint( $_POST['weekly_tests'] ) : 5;
		$weekly_qs    = isset( $_POST['weekly_questions'] ) ? absint( $_POST['weekly_questions'] ) : 50;

		$success = IAQ_Study_Streaks::update_user_goals( $user_id, $weekly_tests, $weekly_qs );
		wp_send_json_success( array( 'success' => $success ) );
	}

	/**
	 * AJAX: Fetch Leaderboard Data.
	 */
	public function ajax_get_leaderboard() {
		$scope         = isset( $_POST['scope'] ) ? sanitize_key( $_POST['scope'] ) : 'overall';
		$quiz_id       = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0;
		$exam_category = isset( $_POST['exam_category'] ) ? sanitize_text_field( $_POST['exam_category'] ) : '';
		$period        = isset( $_POST['period'] ) ? sanitize_key( $_POST['period'] ) : 'all_time';
		$page          = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;

		$data = IAQ_Leaderboard::get_leaderboard(
			array(
				'scope'         => $scope,
				'quiz_id'       => $quiz_id,
				'exam_category' => $exam_category,
				'period'        => $period,
				'page'          => $page,
				'limit'         => 10,
				'current_user'  => get_current_user_id(),
			)
		);

		wp_send_json_success( $data );
	}

	/**
	 * Handle redirect for mobile app download requests.
	 * Redirects requests for /mobile-app/ or ?iaq_download_app=1 to the direct APK download asset.
	 */
	public function handle_mobile_app_download_redirect() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$is_mobile_app_slug = ( false !== strpos( $uri, '/mobile-app' ) );
		$is_query_download  = isset( $_GET['iaq_download_app'] );

		if ( $is_mobile_app_slug || $is_query_download ) {
			$download_url = get_option( 'iaq_app_update_url', 'https://github.com/veerta777/IndianAgri-Quiz-Engine-/releases/latest/download/IndianAgriExam.apk' );
			if ( empty( $download_url ) || false !== strpos( $download_url, 'indianagriexam.com/mobile-app' ) ) {
				$download_url = 'https://github.com/veerta777/IndianAgri-Quiz-Engine-/releases/latest/download/IndianAgriExam.apk';
			}
			wp_redirect( $download_url, 302 );
			exit;
		}
	}
}
