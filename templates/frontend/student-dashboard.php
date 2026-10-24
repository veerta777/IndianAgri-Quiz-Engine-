<?php
/**
 * Frontend: Student Examination Dashboard.
 * Rendered via shortcode [iaq_student_dashboard].
 *
 * Implements the authentic IndianAgriExam.com student learning platform layout:
 * 1. Top Bar with IndianAgriExam.com branding, navigation, notifications & student profile.
 * 2. Left Sidebar with navigation menu, active indicators, and bottom "Learn. Practice. Dominate." sprout art.
 * 3. Hero Row: Namaste Welcome card with 4 KPI pills + Premium Plan card with golden crown.
 * 4. In-Progress Examination Alert Banner with orange timer and instant resume.
 * 5. Quick Preparation Actions: Practice Sets, Mock Tests, Custom Practice Test.
 * 6. Continue Practising: horizontal cards with badges, progress bars, and status.
 * 7. Your Performance & Subject Mastery: 2-column Subject Accuracy bars + SVG Performance Trend line chart.
 * 8. Bookmarks & Wrong Answer Notebook cards with big counters and illustrations.
 * 9. Study Streak & Goals: Flame streak, weekly goal progress bar, 7-day circular checkmarks.
 * 10. Recent Attempt History table with Scorecard & Solutions and Resume buttons.
 * 11. Interactive drawers/tabs for Custom Practice Test Builder, Bookmarks, Wrong Notebook, and Leaderboard.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$types = IAQ_Quiz_Builder::get_quiz_types();

// Safe defaults for student learning feature variables
$bookmarks            = isset( $bookmarks ) ? $bookmarks : array();
$total_bookmarks      = isset( $total_bookmarks ) ? $total_bookmarks : 0;
$wrong_notebook       = isset( $wrong_notebook ) ? $wrong_notebook : array();
$total_wrong_active   = isset( $total_wrong_active ) ? $total_wrong_active : 0;
$total_wrong_resolved = isset( $total_wrong_resolved ) ? $total_wrong_resolved : 0;
$current_streak       = isset( $current_streak ) ? $current_streak : 0;
$best_streak          = isset( $best_streak ) ? $best_streak : 0;
$study_calendar       = isset( $study_calendar ) ? $study_calendar : array();
$user_goals           = isset( $user_goals ) ? $user_goals : array();
$examinations         = isset( $examinations ) ? $examinations : array();
$subjects             = isset( $subjects ) ? $subjects : array();
$leaderboard_enabled  = isset( $leaderboard_enabled ) ? (bool) $leaderboard_enabled : true;
$leaderboard_data     = isset( $leaderboard_data ) ? $leaderboard_data : null;
$enable_bookmarks     = (bool) get_option( 'iaq_enable_bookmarks', 1 );
$enable_wrong_notebook= (bool) get_option( 'iaq_enable_wrong_notebook', 1 );
$enable_study_streaks = (bool) get_option( 'iaq_enable_study_streaks', 1 );
$active_tab              = isset( $active_tab ) ? $active_tab : 'dashboard';
$practice_sets_html      = isset( $practice_sets_html ) ? $practice_sets_html : '';
$mock_tests_html         = isset( $mock_tests_html ) ? $mock_tests_html : '';
$subscription_plans_html = isset( $subscription_plans_html ) ? $subscription_plans_html : '';
?>

<div class="iaq-student-portal iaq-portal-fullscreen" id="iaq-student-portal">
	<div class="iaq-student-dashboard iaq-student-dashboard-layout" id="iaq-student-dashboard">

	<?php if ( ! is_user_logged_in() ) : ?>
		<!-- Non-Logged In Guest State (Server-Side Enforced) -->
		<div class="iaq-portal-card iaq-guest-gate">
			<div class="iaq-gate-icon">
				<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#117E3A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
			</div>
			<h2><?php esc_html_e( 'Student Examination Dashboard', 'indianagri-quiz-engine' ); ?></h2>
			<p><?php esc_html_e( 'Please sign in to your IndianAgriExam account to view your examination history, resume in-progress mock tests, track cumulative performance analytics, and access personalized learning tools.', 'indianagri-quiz-engine' ); ?></p>
			<div class="iaq-gate-actions">
				<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="iaq-btn-action-primary">
					<span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'Log In to Student Portal', 'indianagri-quiz-engine' ); ?>
				</a>
			</div>
		</div>
	<?php else : ?>

		<?php
		$current_user  = wp_get_current_user();
		$display_name  = ! empty( $current_user->display_name ) ? $current_user->display_name : ( ! empty( $current_user->user_login ) ? $current_user->user_login : __( 'Candidate', 'indianagri-quiz-engine' ) );
		$is_subscribed = ! empty( $subscriptions );
		$user_initials = 'ST';
		if ( ! empty( $display_name ) ) {
			$name_parts = explode( ' ', trim( $display_name ) );
			$user_initials = strtoupper( substr( $name_parts[0], 0, 1 ) . ( isset( $name_parts[1] ) ? substr( $name_parts[1], 0, 1 ) : '' ) );
		}
		$first_name = ! empty( $display_name ) ? explode( ' ', trim( $display_name ) )[0] : $display_name;

		// Formatted subscription status and validity
		$sub_plan_name           = __( 'Standard Free Membership', 'indianagri-quiz-engine' );
		$sub_valid_until         = __( 'Standard Access', 'indianagri-quiz-engine' );
		$is_student_active       = IAQ_Access_Control::is_student_active( $current_user->ID );
		$is_subscription_expired = ( ! $is_student_active && 'expired' === get_user_meta( $current_user->ID, '_iaq_subscription_status', true ) );

		if ( $is_subscribed && ! empty( $subscriptions[0] ) ) {
			$first_sub       = $subscriptions[0];
			$sub_plan_name   = ! empty( $first_sub->plan_name ) ? $first_sub->plan_name : __( 'Premium All-Access', 'indianagri-quiz-engine' );
			$expiry_ts       = strtotime( $first_sub->expiry_date );
			$date_format     = function_exists( 'get_option' ) ? get_option( 'date_format', 'd M Y' ) : 'd M Y';
			$sub_valid_until = function_exists( 'date_i18n' ) ? date_i18n( $date_format, $expiry_ts ) : date( 'd M Y', $expiry_ts );
		}
		$performance_trend = isset( $performance_trend ) ? $performance_trend : array();
		?>

		<!-- 1. TOP BAR -->
		<header class="iaq-dash-topbar">
			<div class="iaq-topbar-left">
				<button type="button" class="iaq-dash-menu-toggle" id="iaq-dash-menu-toggle" aria-label="<?php esc_attr_e( 'Toggle navigation menu', 'indianagri-quiz-engine' ); ?>">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
						<line x1="3" y1="12" x2="21" y2="12"></line>
						<line x1="3" y1="6" x2="21" y2="6"></line>
						<line x1="3" y1="18" x2="21" y2="18"></line>
					</svg>
				</button>
				<div class="iaq-topbar-logo">
					<div class="iaq-logo-icon-box">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M12 10a6 6 0 0 0-6-6H3v2a6 6 0 0 0 6 6h3Z"/>
							<path d="M12 14a6 6 0 0 1 6-6h3v2a6 6 0 0 1-6 6h-3Z"/>
							<path d="M12 2v20"/>
						</svg>
					</div>
					<div class="iaq-logo-text-block">
						<span class="iaq-logo-site-title">IndianAgriExam.com</span>
						<span class="iaq-logo-site-sub">Agriculture Exam Preparation Platform</span>
					</div>
				</div>
			</div>

			<nav class="iaq-topbar-nav-links">
				<a href="#iaq-overview" class="iaq-top-link iaq-dash-nav-trigger <?php echo 'dashboard' === $active_tab ? 'is-active' : ''; ?>" data-target="dashboard"><?php esc_html_e( 'Dashboard', 'indianagri-quiz-engine' ); ?></a>
				<a href="#iaq-pane-practice-sets" class="iaq-top-link iaq-dash-nav-trigger <?php echo 'practice-sets' === $active_tab ? 'is-active' : ''; ?>" data-target="practice-sets"><?php esc_html_e( 'Practice Sets', 'indianagri-quiz-engine' ); ?></a>
				<a href="#iaq-pane-mock-tests" class="iaq-top-link iaq-dash-nav-trigger <?php echo 'mock-tests' === $active_tab ? 'is-active' : ''; ?>" data-target="mock-tests"><?php esc_html_e( 'Mock Tests', 'indianagri-quiz-engine' ); ?></a>
				<a href="#iaq-pane-examinations" class="iaq-top-link iaq-dash-nav-trigger <?php echo 'examinations' === $active_tab ? 'is-active' : ''; ?>" data-target="examinations"><?php esc_html_e( 'Examinations', 'indianagri-quiz-engine' ); ?></a>
				<a href="#iaq-custom-practice-section" class="iaq-top-link iaq-dash-nav-trigger <?php echo 'custom-test' === $active_tab ? 'is-active' : ''; ?>" data-target="custom-test"><?php esc_html_e( 'Custom Test', 'indianagri-quiz-engine' ); ?></a>
				<a href="#iaq-pane-subscriptions" class="iaq-top-link iaq-dash-nav-trigger <?php echo 'subscriptions' === $active_tab ? 'is-active' : ''; ?>" data-target="subscriptions"><?php esc_html_e( 'Subscriptions', 'indianagri-quiz-engine' ); ?></a>
			</nav>

			<div class="iaq-topbar-right-controls">
				<button type="button" class="iaq-top-icon-btn iaq-dash-nav-trigger" data-target="examinations" title="<?php esc_attr_e( 'Search Examinations', 'indianagri-quiz-engine' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
				</button>
				<div class="iaq-top-notif-wrapper">
					<button type="button" class="iaq-top-icon-btn iaq-dash-nav-trigger" data-target="study-streaks" title="<?php esc_attr_e( 'Notifications', 'indianagri-quiz-engine' ); ?>">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
						<span class="iaq-notif-dot-badge">1</span>
					</button>
				</div>
				<div class="iaq-top-profile-pill iaq-dash-nav-trigger" data-target="profile" role="button" tabindex="0" title="<?php esc_attr_e( 'View Profile / Account', 'indianagri-quiz-engine' ); ?>">
					<div class="iaq-avatar-circle"><?php echo esc_html( $user_initials ); ?></div>
					<span class="iaq-profile-first-name"><?php echo esc_html( $first_name ); ?></span>
					<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
				</div>
			</div>
		</header>

		<!-- 2. APP SHELL WITH SIDEBAR & MAIN WORKSPACE -->
		<div class="iaq-dash-body-layout">

			<!-- LEFT SIDEBAR -->
			<aside class="iaq-dash-sidebar">
				<div class="iaq-sidebar-mobile-header">
					<div class="iaq-sidebar-mobile-brand">
						<div class="iaq-logo-icon-box">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M12 10a6 6 0 0 0-6-6H3v2a6 6 0 0 0 6 6h3Z"/>
								<path d="M12 14a6 6 0 0 1 6-6h3v2a6 6 0 0 1-6 6h-3Z"/>
								<path d="M12 2v20"/>
							</svg>
						</div>
						<span class="iaq-sidebar-mobile-title"><?php esc_html_e( 'Menu', 'indianagri-quiz-engine' ); ?></span>
					</div>
					<button type="button" class="iaq-sidebar-close-btn" id="iaq-sidebar-close-btn" aria-label="<?php esc_attr_e( 'Close menu', 'indianagri-quiz-engine' ); ?>">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
							<line x1="18" y1="6" x2="6" y2="18"></line>
							<line x1="6" y1="6" x2="18" y2="18"></line>
						</svg>
					</button>
				</div>
				<ul class="iaq-side-nav-menu">
					<li class="iaq-side-nav-item <?php echo 'dashboard' === $active_tab ? 'is-active' : ''; ?>" data-view="dashboard">
						<a href="#iaq-overview" class="iaq-side-link iaq-dash-nav-trigger" data-target="dashboard">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
							<span><?php esc_html_e( 'Home', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item <?php echo 'practice-sets' === $active_tab ? 'is-active' : ''; ?>" data-view="practice-sets">
						<a href="#iaq-pane-practice-sets" class="iaq-side-link iaq-dash-nav-trigger" data-target="practice-sets">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
							<span><?php esc_html_e( 'Practice Sets', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item <?php echo 'mock-tests' === $active_tab ? 'is-active' : ''; ?>" data-view="mock-tests">
						<a href="#iaq-pane-mock-tests" class="iaq-side-link iaq-dash-nav-trigger" data-target="mock-tests">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
							<span><?php esc_html_e( 'Mock Tests', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item <?php echo 'examinations' === $active_tab ? 'is-active' : ''; ?>" data-view="examinations">
						<a href="#iaq-pane-examinations" class="iaq-side-link iaq-dash-nav-trigger" data-target="examinations">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
							<span><?php esc_html_e( 'Examinations', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item <?php echo 'history' === $active_tab ? 'is-active' : ''; ?>" data-view="history">
						<a href="#iaq-history-section" class="iaq-side-link iaq-dash-nav-trigger" data-target="history">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
							<span><?php esc_html_e( 'My Results / History', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item <?php echo 'bookmarks' === $active_tab ? 'is-active' : ''; ?>" data-view="bookmarks">
						<a href="#iaq-bookmarks-section" class="iaq-side-link iaq-dash-nav-trigger" data-target="bookmarks">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
							<span><?php esc_html_e( 'Bookmarks', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item <?php echo 'wrong-notebook' === $active_tab ? 'is-active' : ''; ?>" data-view="wrong-notebook">
						<a href="#iaq-wrong-notebook-section" class="iaq-side-link iaq-dash-nav-trigger" data-target="wrong-notebook">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
							<span><?php esc_html_e( 'Wrong Question Notebook', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item <?php echo 'custom-test' === $active_tab ? 'is-active' : ''; ?>" data-view="custom-test">
						<a href="#iaq-custom-practice-section" class="iaq-side-link iaq-dash-nav-trigger" data-target="custom-test">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 3 5 5-5 5"/><path d="M4 20v-7a4 4 0 0 1 4-4h13"/><path d="m8 21-5-5 5-5"/><path d="M20 4v7a4 4 0 0 1-4 4H3"/></svg>
							<span><?php esc_html_e( 'Custom Practice Test', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item <?php echo 'leaderboard' === $active_tab ? 'is-active' : ''; ?>" data-view="leaderboard">
						<a href="#iaq-leaderboard-section" class="iaq-side-link iaq-dash-nav-trigger" data-target="leaderboard">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.45 1-1 1H7c-.55 0-1-.45-1-1v-2.34"/><path d="M18 14.66V17c0 .55-.45 1-1 1h-2c-.55 0-1-.45-1-1v-2.34"/><path d="M6 2v7a6 6 0 0 0 12 0V2Z"/></svg>
							<span><?php esc_html_e( 'Leaderboard', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item <?php echo 'study-streaks' === $active_tab ? 'is-active' : ''; ?>" data-view="study-streaks">
						<a href="#iaq-study-streaks-section" class="iaq-side-link iaq-dash-nav-trigger" data-target="study-streaks">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m4.93 4.93 2.83 2.83"/><path d="M2 12h4"/><path d="m4.93 19.07 2.83-2.83"/><path d="M12 22v-4"/><path d="m19.07 19.07-2.83-2.83"/><path d="M22 12h-4"/><path d="m19.07 4.93-2.83 2.83"/></svg>
							<span><?php esc_html_e( 'Study Streaks & Goals', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item <?php echo 'subscriptions' === $active_tab ? 'is-active' : ''; ?>" data-view="subscriptions">
						<a href="#iaq-pane-subscriptions" class="iaq-side-link iaq-dash-nav-trigger" data-target="subscriptions">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
							<span><?php esc_html_e( 'My Subscription', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item <?php echo 'profile' === $active_tab ? 'is-active' : ''; ?>" data-view="profile">
						<a href="#iaq-pane-profile" class="iaq-side-link iaq-dash-nav-trigger" data-target="profile">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/></svg>
							<span><?php esc_html_e( 'Profile / Account', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
				</ul>

				<!-- Bottom Sprout Illustration & Slogan -->
				<div class="iaq-sidebar-bottom-badge">
					<div class="iaq-sprout-art-svg">
						<svg width="84" height="84" viewBox="0 0 100 100" fill="none">
							<path d="M20 78c18-2 30-10 30-28 0-12-8-22-22-26 2 12 8 22 22 26-10 6-20 18-30 28Z" fill="#117E3A"/>
							<path d="M50 50c0-14 10-24 24-28-2 12-8 22-22 26 8 6 16 16 26 24-16-2-28-8-28-22Z" fill="#16a34a"/>
							<path d="M15 82c20-6 35-6 70 0" stroke="#117E3A" stroke-width="4" stroke-linecap="round"/>
							<path d="M22 88c16-5 28-5 56 0" stroke="#117E3A" stroke-width="3" stroke-linecap="round"/>
						</svg>
					</div>
					<div class="iaq-brand-slogan">
						<div class="iaq-slogan-word">Learn.</div>
						<div class="iaq-slogan-word">Practice.</div>
						<div class="iaq-slogan-word is-accent">Dominate.</div>
					</div>
				</div>
			</aside>
			<div class="iaq-dash-backdrop" id="iaq-dash-backdrop"></div>

			<!-- RIGHT MAIN CONTENT -->
			<main class="iaq-dash-main-area">

				<!-- VIEW PANE: DASHBOARD OVERVIEW -->
				<div class="iaq-dashboard-view-pane <?php echo 'dashboard' === $active_tab ? 'is-active' : ''; ?>" id="iaq-pane-dashboard">

					<!-- HERO ROW: WELCOME + SUBSCRIPTION -->
					<div class="iaq-hero-two-column-row">

						<!-- Left Welcome Card with 4 KPI Pills -->
						<div class="iaq-hero-welcome-card">
							<div class="iaq-welcome-top-intro">
								<div class="iaq-user-avatar-badge">
									<svg width="34" height="34" viewBox="0 0 24 24" fill="currentColor">
										<path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
									</svg>
								</div>
								<div class="iaq-welcome-text-group">
									<h2 class="iaq-welcome-user-heading">
										<?php printf( esc_html__( 'Namaste, %s!', 'indianagri-quiz-engine' ), esc_html( $display_name ) ); ?>
									</h2>
									<p class="iaq-welcome-user-subtitle">
										<?php esc_html_e( 'Keep practising to achieve your target in Agricultural Competitive Examinations.', 'indianagri-quiz-engine' ); ?>
									</p>
								</div>
							</div>

							<!-- 4 KPI Metrics Pills (Test-compliant class: iaq-dashboard-kpis) -->
							<div class="iaq-dashboard-kpis iaq-hero-kpi-pills-row">
								<div class="iaq-hero-kpi-pill">
									<div class="iaq-kpi-pill-icon is-green">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
									</div>
									<div class="iaq-kpi-pill-info">
										<span class="iaq-kpi-val"><?php echo esc_html( (int) $stats['completed_attempts'] ); ?></span>
										<span class="iaq-kpi-label"><?php esc_html_e( 'Tests Completed', 'indianagri-quiz-engine' ); ?></span>
									</div>
								</div>

								<div class="iaq-hero-kpi-pill">
									<div class="iaq-kpi-pill-icon is-trophy">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.45 1-1 1H7c-.55 0-1-.45-1-1v-2.34"/><path d="M18 14.66V17c0 .55-.45 1-1 1h-2c-.55 0-1-.45-1-1v-2.34"/><path d="M6 2v7a6 6 0 0 0 12 0V2Z"/></svg>
									</div>
									<div class="iaq-kpi-pill-info">
										<span class="iaq-kpi-val"><?php echo esc_html( round( (float) $stats['pass_rate_percent'], 1 ) ); ?>%</span>
										<span class="iaq-kpi-label"><?php esc_html_e( 'Overall Pass Rate', 'indianagri-quiz-engine' ); ?></span>
									</div>
								</div>

								<div class="iaq-hero-kpi-pill">
									<div class="iaq-kpi-pill-icon is-chart">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg>
									</div>
									<div class="iaq-kpi-pill-info">
										<span class="iaq-kpi-val"><?php echo esc_html( round( (float) $stats['avg_percentage'], 1 ) ); ?>%</span>
										<span class="iaq-kpi-label"><?php esc_html_e( 'Average Score', 'indianagri-quiz-engine' ); ?></span>
										<?php if ( (float) $stats['best_percentage'] > 0 ) : ?>
											<span class="iaq-kpi-best-tag">(<?php printf( esc_html__( 'Best: %s%%', 'indianagri-quiz-engine' ), esc_html( round( (float) $stats['best_percentage'], 1 ) ) ); ?>)</span>
										<?php endif; ?>
									</div>
								</div>

								<div class="iaq-hero-kpi-pill">
									<div class="iaq-kpi-pill-icon is-book">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
									</div>
									<div class="iaq-kpi-pill-info">
										<span class="iaq-kpi-val"><?php echo esc_html( (int) $stats['total_questions_solved'] ); ?></span>
										<span class="iaq-kpi-label"><?php esc_html_e( 'MCQs Practiced', 'indianagri-quiz-engine' ); ?></span>
									</div>
								</div>
							</div>
						</div>

						<!-- Right Premium Plan Card -->
						<div class="iaq-hero-premium-card" style="<?php echo $is_subscription_expired ? 'border-color:#fecdd3;background:linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);' : ''; ?>">
							<div class="iaq-premium-watermark-bg"></div>
							<div class="iaq-premium-card-body">
								<div class="iaq-crown-icon-badge" style="<?php echo $is_subscription_expired ? 'background:#fee2e2;' : ( ! $is_subscribed ? 'background:#f1f5f9;' : '' ); ?>">
									<?php if ( $is_subscription_expired ) : ?>
										<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
										</svg>
									<?php elseif ( $is_subscribed ) : ?>
										<svg width="22" height="22" viewBox="0 0 24 24" fill="#f59e0b" stroke="#d97706" stroke-width="1.5">
											<path d="M2 4l3 12h14l3-12-5 4-5-8-5 8z"/>
										</svg>
									<?php else : ?>
										<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
										</svg>
									<?php endif; ?>
								</div>
								<h3 class="iaq-premium-card-title" style="<?php echo $is_subscription_expired ? 'color:#9f1239;' : ( ! $is_subscribed ? 'color:#334155;' : '' ); ?>">
									<?php
									if ( $is_subscription_expired ) {
										esc_html_e( 'Subscription Complete', 'indianagri-quiz-engine' );
									} elseif ( $is_subscribed ) {
										esc_html_e( 'Premium Plan Active', 'indianagri-quiz-engine' );
									} else {
										esc_html_e( 'Free Student Plan', 'indianagri-quiz-engine' );
									}
									?>
								</h3>
								<p class="iaq-premium-card-plan" style="<?php echo $is_subscription_expired ? 'color:#be123c;' : ( ! $is_subscribed ? 'color:#475569;' : '' ); ?>">
									<?php
									if ( $is_subscription_expired ) {
										esc_html_e( 'Access Deactivated (Expired)', 'indianagri-quiz-engine' );
									} else {
										echo esc_html( $sub_plan_name );
									}
									?>
								</p>
								<div class="iaq-premium-validity-text" style="<?php echo $is_subscription_expired ? 'color:#e11d48;font-weight:600;' : ( ! $is_subscribed ? 'color:#64748b;' : '' ); ?>">
									<?php if ( $is_subscription_expired ) : ?>
										<?php esc_html_e( 'Status: Completed & Locked', 'indianagri-quiz-engine' ); ?>
									<?php elseif ( $is_subscribed ) : ?>
										<?php printf( esc_html__( 'Valid till: %s', 'indianagri-quiz-engine' ), esc_html( $sub_valid_until ) ); ?>
									<?php else : ?>
										<?php esc_html_e( 'Unlock comprehensive mocks & answers', 'indianagri-quiz-engine' ); ?>
									<?php endif; ?>
								</div>
								<a href="#iaq-pane-subscriptions" class="iaq-btn-premium-cta iaq-dash-nav-trigger" data-target="subscriptions" style="<?php echo $is_subscription_expired ? 'background:#e11d48;color:#fff;border-color:#e11d48;' : ( ! $is_subscribed ? 'background:#117E3A;color:#fff;border-color:#117E3A;' : '' ); ?>">
									<?php
									if ( $is_subscription_expired ) {
										esc_html_e( 'Renew Plan & Reactivate', 'indianagri-quiz-engine' );
									} elseif ( $is_subscribed ) {
										esc_html_e( 'View Plan Details', 'indianagri-quiz-engine' );
									} else {
										esc_html_e( 'Upgrade to Premium Plan', 'indianagri-quiz-engine' );
									}
									?> &rarr;
								</a>
							</div>
						</div>
					</div>

					<!-- SUBSCRIPTION DEACTIVATED ALERT BANNER -->
					<?php if ( $is_subscription_expired ) : ?>
						<div class="iaq-subscription-deactivated-banner" style="background:#fff1f2;border:1px solid #fecdd3;border-left:5px solid #e11d48;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:16px;box-shadow:0 4px 12px rgba(225,29,72,0.06);">
							<div style="display:flex;align-items:center;gap:14px;">
								<div style="background:#ffe4e6;color:#e11d48;width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
									<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
								</div>
								<div>
									<div style="font-weight:700;font-size:15px;color:#9f1239;margin-bottom:2px;">
										<?php esc_html_e( 'Subscription Complete — Student Access Deactivated', 'indianagri-quiz-engine' ); ?>
									</div>
									<div style="font-size:13px;color:#be123c;">
										<?php esc_html_e( 'Your previous examination subscription has finished. Premium CBT mock tests and practice sets are locked until your plan is renewed.', 'indianagri-quiz-engine' ); ?>
									</div>
								</div>
							</div>
							<a href="#iaq-pane-subscriptions" class="iaq-btn-action-primary iaq-dash-nav-trigger" data-target="subscriptions" style="background:#e11d48;color:#ffffff;white-space:nowrap;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;font-size:13px;display:inline-flex;align-items:center;gap:6px;">
								<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Renew Plan Now', 'indianagri-quiz-engine' ); ?>
							</a>
						</div>
					<?php endif; ?>

					<!-- IN-PROGRESS EXAMINATION ALERT BANNER -->
					<?php if ( ! empty( $active_attempt ) ) : ?>
						<?php
						$remaining_str = '38m 25s';
						if ( ! empty( $active_attempt['time_spent_seconds'] ) && ! empty( $active_attempt['duration_minutes'] ) ) {
							$total_seconds = $active_attempt['duration_minutes'] * 60;
							$left_seconds  = max( 0, $total_seconds - (int) $active_attempt['time_spent_seconds'] );
							$left_m        = floor( $left_seconds / 60 );
							$left_s        = $left_seconds % 60;
							$remaining_str = sprintf( '%02dm %02ds', $left_m, $left_s );
						}
						?>
						<div class="iaq-active-resume-banner iaq-in-progress-banner">
							<div class="iaq-in-progress-left">
								<div class="iaq-in-progress-clock-badge">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
										<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
									</svg>
								</div>
								<div class="iaq-in-progress-details">
									<span class="iaq-in-progress-alert-label"><?php esc_html_e( 'IN-PROGRESS EXAMINATION DETECTED', 'indianagri-quiz-engine' ); ?></span>
									<h3 class="iaq-in-progress-quiz-title"><?php echo esc_html( $active_attempt['quiz_title'] ); ?></h3>
									<div class="iaq-in-progress-meta-row">
										<span><strong>Category:</strong> <?php echo esc_html( $active_attempt['exam_category'] ); ?></span>
										<span class="iaq-meta-pipe">|</span>
										<span><strong>Started:</strong> <?php echo esc_html( $active_attempt['start_time'] ); ?></span>
										<span class="iaq-meta-pipe">|</span>
										<span class="iaq-time-rem-badge"><strong>Time Remaining:</strong> <?php echo esc_html( $remaining_str ); ?></span>
									</div>
								</div>
							</div>
							<div class="iaq-in-progress-right-cta">
								<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_url( $active_attempt['quiz_id'], 'attempt' ) ); ?>" class="iaq-btn-resume-orange">
									<?php esc_html_e( 'Resume Examination', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
								<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_url( $active_attempt['quiz_id'] ) ); ?>" class="iaq-link-exam-details">
									<?php esc_html_e( 'View Exam Details', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
							</div>
						</div>
					<?php endif; ?>

					<!-- QUICK PREPARATION ACTIONS / BROWSE PRACTICE SETS -->
					<section class="iaq-dashboard-block iaq-quick-actions-section">
						<div class="iaq-block-header-wrap">
							<div class="iaq-header-title-row">
								<span class="iaq-lightning-icon">⚡</span>
								<h3 class="iaq-block-title"><?php esc_html_e( 'Browse Practice Sets & Learning Pathways', 'indianagri-quiz-engine' ); ?></h3>
							</div>
							<p class="iaq-block-subtitle"><?php esc_html_e( 'Choose your preferred practice pathway: solve chapter-wise books, master core subjects, or target competitive exams.', 'indianagri-quiz-engine' ); ?></p>
						</div>

						<div class="iaq-quick-actions-row">
							<!-- Card 1: Practice by Books -->
							<div class="iaq-qa-pill-card is-green" style="cursor:pointer;" onclick="window.location.href='<?php echo esc_url( add_query_arg( array( 'iaq_ps_view' => 'books' ), get_permalink() ) ); ?>';">
								<div class="iaq-qa-badge-icon">
									<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/>
									</svg>
								</div>
								<h4 class="iaq-qa-card-name"><?php esc_html_e( 'Practice by Books', 'indianagri-quiz-engine' ); ?></h4>
								<p class="iaq-qa-card-desc"><?php esc_html_e( 'Chapter-wise MCQs from standard reference literature & textbooks.', 'indianagri-quiz-engine' ); ?></p>
								<a href="<?php echo esc_url( add_query_arg( array( 'iaq_ps_view' => 'books' ), get_permalink() ) ); ?>" class="iaq-qa-action-pill is-green" onclick="event.stopPropagation();">
									<?php esc_html_e( 'Browse Practice Books', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
							</div>

							<!-- Card 2: Practice by Subjects -->
							<div class="iaq-qa-pill-card is-blue">
								<div class="iaq-qa-badge-icon">
									<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<rect width="7" height="7" x="3" y="3" rx="1.5"/>
										<rect width="7" height="7" x="14" y="3" rx="1.5"/>
										<rect width="7" height="7" x="14" y="14" rx="1.5"/>
										<rect width="7" height="7" x="3" y="14" rx="1.5"/>
									</svg>
								</div>
								<h4 class="iaq-qa-card-name"><?php esc_html_e( 'Practice by Subjects', 'indianagri-quiz-engine' ); ?></h4>
								<p class="iaq-qa-card-desc"><?php esc_html_e( 'Topic-wise chapter drills arranged sequentially across core disciplines.', 'indianagri-quiz-engine' ); ?></p>
								<a href="<?php echo esc_url( add_query_arg( array( 'iaq_ps_view' => 'subjects' ), get_permalink() ) ); ?>" class="iaq-qa-action-pill is-blue">
									<?php esc_html_e( 'Browse by Subject', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
							</div>

							<!-- Card 3: Browse by Examination -->
							<div class="iaq-qa-pill-card is-teal">
								<div class="iaq-qa-badge-icon">
									<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<circle cx="12" cy="8" r="6"/>
										<path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
									</svg>
								</div>
								<h4 class="iaq-qa-card-name"><?php esc_html_e( 'Browse by Examination', 'indianagri-quiz-engine' ); ?></h4>
								<p class="iaq-qa-card-desc"><?php esc_html_e( 'Target competitive entrance tests like ICAR JRF, ASRB NET, CUET PG & IBPS AFO.', 'indianagri-quiz-engine' ); ?></p>
								<a href="<?php echo esc_url( add_query_arg( array( 'iaq_ps_view' => 'exams' ), get_permalink() ) ); ?>" class="iaq-qa-action-pill is-teal">
									<?php esc_html_e( 'Browse by Examination', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
							</div>
						</div>

						<div class="iaq-qa-shortcuts-bar" style="display:flex;align-items:center;gap:12px;margin-top:16px;flex-wrap:wrap;">
							<span style="font-size:12.5px;color:#64748b;font-weight:700;"><?php esc_html_e( 'Direct shortcuts:', 'indianagri-quiz-engine' ); ?></span>
							<a href="#iaq-pane-mock-tests" class="iaq-dash-nav-trigger" data-target="mock-tests" style="display:inline-flex;align-items:center;gap:5px;font-size:12.5px;font-weight:700;color:#1d4ed8;background:#eff6ff;padding:5px 14px;border-radius:20px;text-decoration:none;border:1px solid #bfdbfe;">
								⚡ <?php esc_html_e( 'Full-Length Mock Tests', 'indianagri-quiz-engine' ); ?> &rarr;
							</a>
							<a href="#iaq-custom-practice-section" class="iaq-dash-nav-trigger" data-target="custom-test" style="display:inline-flex;align-items:center;gap:5px;font-size:12.5px;font-weight:700;color:#0f766e;background:#f0fdfa;padding:5px 14px;border-radius:20px;text-decoration:none;border:1px solid #99f6e4;">
								⚙️ <?php esc_html_e( 'Create Custom Test', 'indianagri-quiz-engine' ); ?> &rarr;
							</a>
						</div>
					</section>

					<!-- CONTINUE PRACTISING -->
					<section class="iaq-dashboard-block iaq-continue-practising-section">
						<div class="iaq-block-header-wrap iaq-flex-between">
							<div>
								<div class="iaq-header-title-row">
									<span class="iaq-play-icon">▶</span>
									<h3 class="iaq-block-title"><?php esc_html_e( 'Continue Practising', 'indianagri-quiz-engine' ); ?></h3>
								</div>
								<p class="iaq-block-subtitle"><?php esc_html_e( 'Resume active attempts, retake past tests, or try recommended practice sets.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<a href="#iaq-pane-practice-sets" class="iaq-view-all-link iaq-dash-nav-trigger" data-target="practice-sets">
								<?php esc_html_e( 'View All', 'indianagri-quiz-engine' ); ?> &rarr;
							</a>
						</div>

						<!-- Continue Practising Items List -->
						<div class="iaq-continue-items-list">
							<?php if ( ! empty( $continue_quizzes ) ) : ?>
								<?php foreach ( $continue_quizzes as $cq ) :
									$is_cq_in_prog = ( 'in_progress' === $cq['status'] );
									$is_cq_mock    = ( 'mock' === $cq['quiz_type'] );
									$cq_url        = $is_cq_in_prog ? IAQ_Frontend::get_quiz_url( $cq['quiz_id'], 'attempt' ) : IAQ_Frontend::get_quiz_url( $cq['quiz_id'] );
									$action_text   = $is_cq_in_prog ? __( 'Resume Attempt', 'indianagri-quiz-engine' ) : ( 'retake' === $cq['action_type'] ? __( 'Retake Test', 'indianagri-quiz-engine' ) : __( 'Start Practice', 'indianagri-quiz-engine' ) );
									$badge_cls     = $is_cq_in_prog ? 'is-in-progress' : ( 'completed' === $cq['status'] ? 'is-completed' : 'is-recommended' );
								?>
									<div class="iaq-continue-row-card">
										<div class="iaq-continue-left-col">
											<div class="iaq-item-icon-circle <?php echo $is_cq_mock ? 'is-blue' : 'is-green'; ?>">
												<?php if ( $is_cq_mock ) : ?>
													<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
														<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
													</svg>
												<?php else : ?>
													<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
														<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
													</svg>
												<?php endif; ?>
											</div>
											<div class="iaq-continue-info-col">
												<h4 class="iaq-continue-item-title"><?php echo esc_html( $cq['title'] ); ?></h4>
												<div class="iaq-continue-tag-pills">
													<span class="iaq-tag-pill <?php echo $is_cq_mock ? 'is-type-mock' : 'is-type-practice'; ?>">
														<?php echo $is_cq_mock ? esc_html__( 'Mock Test', 'indianagri-quiz-engine' ) : esc_html__( 'Practice Set', 'indianagri-quiz-engine' ); ?>
													</span>
													<?php if ( ! empty( $cq['exam_category'] ) ) : ?>
														<span class="iaq-tag-pill is-exam"><?php echo esc_html( $cq['exam_category'] ); ?></span>
													<?php endif; ?>
												</div>
												<div class="iaq-continue-specs-meta" style="font-size:12px;color:#64748b;margin-top:4px;">
													<span><?php echo esc_html( $cq['total_questions'] ); ?> <?php esc_html_e( 'MCQs', 'indianagri-quiz-engine' ); ?></span> •
													<span><?php echo esc_html( $cq['duration_minutes'] ); ?> <?php esc_html_e( 'Mins', 'indianagri-quiz-engine' ); ?></span>
													<?php if ( ! empty( $cq['total_marks'] ) ) : ?>
														• <span><?php echo esc_html( $cq['total_marks'] ); ?> <?php esc_html_e( 'Marks', 'indianagri-quiz-engine' ); ?></span>
													<?php endif; ?>
												</div>
											</div>
										</div>

										<div class="iaq-continue-progress-col">
											<?php if ( $is_cq_in_prog ) : ?>
												<div class="iaq-progress-bar-wrap">
													<div class="iaq-bar-track">
														<div class="iaq-bar-fill is-green" style="width: 50%;"></div>
													</div>
													<span class="iaq-bar-text"><?php esc_html_e( 'Test Session Active', 'indianagri-quiz-engine' ); ?></span>
												</div>
											<?php elseif ( ! empty( $cq['last_percentage'] ) ) : ?>
												<div class="iaq-progress-bar-wrap">
													<div class="iaq-bar-track">
														<div class="iaq-bar-fill is-green" style="width: <?php echo esc_attr( min( 100, (float) $cq['last_percentage'] ) ); ?>%;"></div>
													</div>
													<span class="iaq-bar-text"><?php printf( esc_html__( 'Last Score: %s%%', 'indianagri-quiz-engine' ), esc_html( $cq['last_percentage'] ) ); ?></span>
												</div>
											<?php endif; ?>
										</div>

										<div class="iaq-continue-action-col">
											<span class="iaq-badge-status-pill <?php echo esc_attr( $badge_cls ); ?>"><?php echo esc_html( $cq['badge_text'] ); ?></span>
											<a href="<?php echo esc_url( $cq_url ); ?>" class="iaq-btn-row-action is-solid-green">
												<?php echo esc_html( $action_text ); ?> &rarr;
											</a>
										</div>
									</div>
								<?php endforeach; ?>
							<?php else : ?>
								<div class="iaq-empty-sub-card" style="padding:24px;text-align:center;">
									<div style="font-size:28px;margin-bottom:6px;">🌱</div>
									<h4 style="margin:0 0 6px 0;"><?php esc_html_e( 'No unfinished tests currently in progress.', 'indianagri-quiz-engine' ); ?></h4>
									<p style="margin:0 0 14px 0;font-size:13px;color:#64748b;"><?php esc_html_e( 'Begin practicing topic-wise questions or take a full-length mock examination.', 'indianagri-quiz-engine' ); ?></p>
									<a href="#iaq-pane-practice-sets" class="iaq-btn-action-primary iaq-dash-nav-trigger" data-target="practice-sets" style="font-size:13px;padding:8px 18px;">
										<?php esc_html_e( 'Explore Practice Sets', 'indianagri-quiz-engine' ); ?> &rarr;
									</a>
								</div>
							<?php endif; ?>
						</div>
					</section>

					<!-- PERFORMANCE & SUBJECT MASTERY (2-COLUMN GRID) -->
					<section class="iaq-dashboard-block iaq-subject-analytics-section">
						<div class="iaq-block-header-wrap">
							<div class="iaq-header-title-row">
								<span class="iaq-chart-icon">📊</span>
								<h3 class="iaq-block-title"><?php esc_html_e( 'Your Performance & Subject Mastery', 'indianagri-quiz-engine' ); ?></h3>
							</div>
							<p class="iaq-block-subtitle"><?php esc_html_e( 'See how you are performing across all tests and subjects.', 'indianagri-quiz-engine' ); ?></p>
						</div>

						<div class="iaq-analytics-two-col-grid">

							<!-- Left Column: Subject-wise Accuracy -->
							<div class="iaq-analytics-card">
								<div class="iaq-card-head-row">
									<h4 class="iaq-analytics-head-title"><?php esc_html_e( 'Subject-wise Accuracy', 'indianagri-quiz-engine' ); ?></h4>
									<a href="#iaq-mastery-grid-anchor" class="iaq-card-head-link">
										<?php esc_html_e( 'View Detailed Analysis', 'indianagri-quiz-engine' ); ?> &rarr;
									</a>
								</div>

								<!-- Mastery Bars List (Test-compliant class: iaq-mastery-grid) -->
								<div class="iaq-mastery-grid iaq-subject-accuracy-bars-list" id="iaq-mastery-grid-anchor">
									<?php if ( ! empty( $subject_mastery ) ) : ?>
										<?php foreach ( array_slice( $subject_mastery, 0, 5 ) as $sm ) :
											$acc     = round( (float) $sm['accuracy_percent'], 1 );
											$b_label = __( 'NEEDS REVISION', 'indianagri-quiz-engine' );
											$b_color = 'is-red';
											if ( 'strong' === $sm['mastery_level'] || $acc >= 75 ) {
												$b_label = __( 'STRONG', 'indianagri-quiz-engine' );
												$b_color = 'is-green';
											} elseif ( 'moderate' === $sm['mastery_level'] || $acc >= 45 ) {
												$b_label = __( 'MODERATE', 'indianagri-quiz-engine' );
												$b_color = 'is-orange';
											}
										?>
											<div class="iaq-subject-acc-row">
												<div class="iaq-subj-name-col">
													<span class="iaq-subj-icon">🌱</span>
													<span class="iaq-subj-name-text"><?php echo esc_html( $sm['subject_name'] ); ?></span>
												</div>
												<div class="iaq-subj-bar-col">
													<div class="iaq-subj-track">
														<div class="iaq-subj-fill <?php echo esc_attr( $b_color ); ?>" style="width: <?php echo esc_attr( max( 2, min( 100, $acc ) ) ); ?>%;"></div>
													</div>
												</div>
												<div class="iaq-subj-pct-col">
													<span class="iaq-subj-pct-val"><?php echo esc_html( $acc ); ?>%</span>
												</div>
												<div class="iaq-subj-badge-col">
													<span class="iaq-mastery-level-badge <?php echo esc_attr( $b_color ); ?>">
														<?php echo esc_html( $b_label ); ?>
													</span>
												</div>
											</div>
										<?php endforeach; ?>
									<?php else : ?>
										<div class="iaq-empty-sub-card" style="padding:28px 20px;text-align:center;">
											<div style="font-size:24px;margin-bottom:6px;">📈</div>
											<p style="margin:0 0 10px 0;font-size:13px;color:#64748b;">
												<?php esc_html_e( 'No subject accuracy data yet. Complete practice sets or mock tests to view your diagnostic accuracy breakdown.', 'indianagri-quiz-engine' ); ?>
											</p>
											<a href="#iaq-pane-practice-sets" class="iaq-btn-action-primary iaq-dash-nav-trigger" data-target="practice-sets" style="font-size:12px;padding:6px 14px;">
												<?php esc_html_e( 'Start Practice Now', 'indianagri-quiz-engine' ); ?>
											</a>
										</div>
									<?php endif; ?>
								</div>
							</div>

							<!-- Right Column: Performance Trend SVG Line Chart -->
							<div class="iaq-analytics-card">
								<div class="iaq-card-head-row">
									<h4 class="iaq-analytics-head-title"><?php esc_html_e( 'Performance Trend', 'indianagri-quiz-engine' ); ?></h4>
									<div class="iaq-chart-period-dropdown">
										<span><?php esc_html_e( 'Recent Tests', 'indianagri-quiz-engine' ); ?> ▼</span>
									</div>
								</div>

								<div class="iaq-trend-chart-wrapper">
									<?php
									$has_trend = ! empty( $performance_trend );
									$pts_count = $has_trend ? count( $performance_trend ) : 0;
									$min_x     = 40;
									$max_x     = 415;
									$base_y    = 195; // 0%
									$top_y     = 30;  // 100%
									$h_span    = $base_y - $top_y;

									$calculated_points = array();
									if ( $has_trend ) {
										foreach ( $performance_trend as $idx => $pt ) {
											$cx = ( $pts_count > 1 ) ? round( $min_x + ( $idx * ( ( $max_x - $min_x ) / ( $pts_count - 1 ) ) ) ) : 230;
											$pct = min( 100, max( 0, (float) $pt['percentage'] ) );
											$cy = round( $base_y - ( ( $pct / 100 ) * $h_span ) );
											$calculated_points[] = array(
												'x'          => $cx,
												'y'          => $cy,
												'percentage' => $pct,
												'label'      => $pt['label'],
												'title'      => $pt['quiz_title'],
											);
										}
									}
									?>
									<svg class="iaq-trend-chart-svg" viewBox="0 0 460 220" preserveAspectRatio="none">
										<defs>
											<linearGradient id="iaqTrendGradient" x1="0" y1="0" x2="0" y2="1">
												<stop offset="0%" stop-color="#117E3A" stop-opacity="0.32"/>
												<stop offset="70%" stop-color="#16a34a" stop-opacity="0.10"/>
												<stop offset="100%" stop-color="#ffffff" stop-opacity="0.0"/>
											</linearGradient>
										</defs>

										<!-- Horizontal Grid Lines -->
										<line x1="30" y1="30" x2="440" y2="30" stroke="#f1f5f9" stroke-width="1"/>
										<text x="10" y="34" font-size="10" fill="#94a3b8">100</text>

										<line x1="30" y1="65" x2="440" y2="65" stroke="#f1f5f9" stroke-width="1"/>
										<text x="14" y="69" font-size="10" fill="#94a3b8">80</text>

										<line x1="30" y1="100" x2="440" y2="100" stroke="#f1f5f9" stroke-width="1"/>
										<text x="14" y="104" font-size="10" fill="#94a3b8">60</text>

										<line x1="30" y1="135" x2="440" y2="135" stroke="#f1f5f9" stroke-width="1"/>
										<text x="14" y="139" font-size="10" fill="#94a3b8">40</text>

										<line x1="30" y1="170" x2="440" y2="170" stroke="#f1f5f9" stroke-width="1"/>
										<text x="14" y="174" font-size="10" fill="#94a3b8">20</text>

										<line x1="30" y1="195" x2="440" y2="195" stroke="#e2e8f0" stroke-width="1"/>
										<text x="18" y="198" font-size="10" fill="#94a3b8">0</text>

										<?php if ( $has_trend && count( $calculated_points ) > 0 ) :
											// Build polygon & polyline coordinates
											$poly_coords = array();
											foreach ( $calculated_points as $cp ) {
												$poly_coords[] = $cp['x'] . ',' . $cp['y'];
											}
											$line_points_str = implode( ' ', $poly_coords );
											$first_x = $calculated_points[0]['x'];
											$last_x  = end( $calculated_points )['x'];
											$area_points_str = $first_x . ',' . $base_y . ' ' . $line_points_str . ' ' . $last_x . ',' . $base_y;
											$latest_pt = end( $calculated_points );
										?>
											<!-- Area Gradient Fill -->
											<polygon points="<?php echo esc_attr( $area_points_str ); ?>" fill="url(#iaqTrendGradient)"/>

											<!-- Smooth Green Curve Line -->
											<polyline points="<?php echo esc_attr( $line_points_str ); ?>" fill="none" stroke="#117E3A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>

											<!-- Data Point Circles -->
											<?php foreach ( $calculated_points as $idx => $cp ) : ?>
												<circle cx="<?php echo esc_attr( $cp['x'] ); ?>" cy="<?php echo esc_attr( $cp['y'] ); ?>" r="<?php echo ( $idx === count( $calculated_points ) - 1 ) ? '5' : '3.5'; ?>" fill="<?php echo ( $idx === count( $calculated_points ) - 1 ) ? '#ffffff' : '#117E3A'; ?>" stroke="#117E3A" stroke-width="<?php echo ( $idx === count( $calculated_points ) - 1 ) ? '3' : '1'; ?>"/>
												<text x="<?php echo esc_attr( $cp['x'] ); ?>" y="212" font-size="10" fill="<?php echo ( $idx === count( $calculated_points ) - 1 ) ? '#117E3A' : '#64748b'; ?>" font-weight="<?php echo ( $idx === count( $calculated_points ) - 1 ) ? '700' : '400'; ?>" text-anchor="middle">
													<?php echo esc_html( $cp['label'] ); ?>
												</text>
											<?php endforeach; ?>

											<!-- Latest Score Tooltip Bubble -->
											<?php
											$tooltip_x = max( 10, min( 380, $latest_pt['x'] - 35 ) );
											$tooltip_y = max( 10, $latest_pt['y'] - 38 );
											?>
											<g transform="translate(<?php echo esc_attr( $tooltip_x ); ?>, <?php echo esc_attr( $tooltip_y ); ?>)">
												<rect width="70" height="30" rx="6" fill="#ffffff" filter="drop-shadow(0px 2px 4px rgba(0,0,0,0.12))" stroke="#e2e8f0"/>
												<text x="35" y="12" font-size="8.5" fill="#64748b" text-anchor="middle" font-weight="600"><?php echo esc_html( $latest_pt['label'] ); ?></text>
												<text x="35" y="24" font-size="9.5" fill="#0f172a" text-anchor="middle" font-weight="700">Score: <tspan fill="#117E3A"><?php echo esc_html( $latest_pt['percentage'] ); ?>%</tspan></text>
											</g>
										<?php else : ?>
											<!-- Empty State Baseline Line -->
											<line x1="40" y1="195" x2="415" y2="195" stroke="#94a3b8" stroke-dasharray="4" stroke-width="1.5"/>
											<text x="230" y="110" font-size="12" fill="#64748b" text-anchor="middle" font-weight="600"><?php esc_html_e( 'No test attempts recorded yet', 'indianagri-quiz-engine' ); ?></text>
											<text x="230" y="128" font-size="10" fill="#94a3b8" text-anchor="middle"><?php esc_html_e( 'Complete your first practice set to chart performance trends', 'indianagri-quiz-engine' ); ?></text>
										<?php endif; ?>
									</svg>

									<?php if ( $has_trend && count( $calculated_points ) > 0 ) : ?>
										<!-- Accessible Screen-Reader Table Alternative -->
										<div class="iaq-sr-only">
											<table aria-label="<?php esc_attr_e( 'Historical Performance Trend Data Table', 'indianagri-quiz-engine' ); ?>">
												<caption><?php esc_html_e( 'Chronological test performance trend', 'indianagri-quiz-engine' ); ?></caption>
												<thead>
													<tr>
														<th scope="col"><?php esc_html_e( 'Attempt Sequence', 'indianagri-quiz-engine' ); ?></th>
														<th scope="col"><?php esc_html_e( 'Test Name', 'indianagri-quiz-engine' ); ?></th>
														<th scope="col"><?php esc_html_e( 'Score Percentage', 'indianagri-quiz-engine' ); ?></th>
													</tr>
												</thead>
												<tbody>
													<?php foreach ( $calculated_points as $cp ) : ?>
														<tr>
															<td><?php echo esc_html( $cp['label'] ); ?></td>
															<td><?php echo esc_html( $cp['title'] ); ?></td>
															<td><?php echo esc_html( $cp['percentage'] ); ?>%</td>
														</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</section>

					<!-- BOOKMARKS & WRONG ANSWER NOTEBOOK (2 CARDS) -->
					<section class="iaq-dashboard-block iaq-learning-tools-section">
						<div class="iaq-block-header-wrap iaq-flex-between">
							<div>
								<div class="iaq-header-title-row">
									<span class="iaq-bookmark-icon">🔖</span>
									<h3 class="iaq-block-title"><?php esc_html_e( 'Bookmarks & Wrong Answer Notebook', 'indianagri-quiz-engine' ); ?></h3>
								</div>
								<p class="iaq-block-subtitle"><?php esc_html_e( 'Revise important questions and learn from your mistakes.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<a href="#iaq-learning-modules" class="iaq-view-all-link">
								<?php esc_html_e( 'View All', 'indianagri-quiz-engine' ); ?> &rarr;
							</a>
						</div>

						<div class="iaq-learning-two-cards-grid">

							<!-- Left Card: Bookmarked Questions -->
							<div class="iaq-learning-feature-card is-bookmark">
								<div class="iaq-lf-card-content">
									<div class="iaq-lf-icon-badge is-yellow">
										<svg width="22" height="22" viewBox="0 0 24 24" fill="#f59e0b" stroke="#d97706" stroke-width="1.5">
											<path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>
										</svg>
									</div>
									<h4 class="iaq-lf-card-title"><?php esc_html_e( 'Bookmarked Questions', 'indianagri-quiz-engine' ); ?></h4>
									<div class="iaq-lf-big-num-row">
										<span class="iaq-lf-big-num"><?php echo esc_html( (int) $total_bookmarks ); ?></span>
										<span class="iaq-lf-big-num-sub"><?php esc_html_e( 'Questions you saved for revision.', 'indianagri-quiz-engine' ); ?></span>
									</div>
									<a href="#iaq-bookmarks-section" class="iaq-btn-lf-pill iaq-dash-nav-trigger" data-target="bookmarks">
										<?php esc_html_e( 'View Bookmarks', 'indianagri-quiz-engine' ); ?> &rarr;
									</a>
								</div>
								<div class="iaq-lf-card-art is-yellow">
									<svg width="80" height="70" viewBox="0 0 100 80" fill="none">
										<path d="M50 25c-15-8-32-6-42 0v40c10-6 27-8 42 0 15-8 32-6 42 0V25c-10-6-27-8-42 0Z" fill="#fef08a" stroke="#facc15" stroke-width="2"/>
										<line x1="50" y1="25" x2="50" y2="65" stroke="#facc15" stroke-width="2"/>
									</svg>
								</div>
							</div>

							<!-- Right Card: Wrong Answer Notebook -->
							<div class="iaq-learning-feature-card is-wrong">
								<div class="iaq-lf-card-content">
									<div class="iaq-lf-icon-badge is-red">
										<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round">
											<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
										</svg>
									</div>
									<h4 class="iaq-lf-card-title"><?php esc_html_e( 'Wrong Answer Notebook', 'indianagri-quiz-engine' ); ?></h4>
									<div class="iaq-lf-big-num-row">
										<span class="iaq-lf-big-num"><?php echo esc_html( (int) $total_wrong_active ); ?></span>
										<span class="iaq-lf-big-num-sub"><?php esc_html_e( 'Questions you answered incorrectly.', 'indianagri-quiz-engine' ); ?></span>
									</div>
									<a href="#iaq-wrong-notebook-section" class="iaq-btn-lf-pill iaq-dash-nav-trigger" data-target="wrong-notebook">
										<?php esc_html_e( 'View Wrong Answers', 'indianagri-quiz-engine' ); ?> &rarr;
									</a>
								</div>
								<div class="iaq-lf-card-art is-red">
									<svg width="70" height="80" viewBox="0 0 80 90" fill="none">
										<rect x="15" y="10" width="55" height="70" rx="8" fill="#ffe4e6" stroke="#f43f5e" stroke-width="2"/>
										<rect x="30" y="5" width="25" height="10" rx="3" fill="#fb7185"/>
										<line x1="28" y1="30" x2="58" y2="30" stroke="#fb7185" stroke-width="2.5" stroke-linecap="round"/>
										<line x1="28" y1="45" x2="58" y2="45" stroke="#fb7185" stroke-width="2.5" stroke-linecap="round"/>
										<line x1="28" y1="60" x2="58" y2="60" stroke="#fb7185" stroke-width="2.5" stroke-linecap="round"/>
									</svg>
								</div>
							</div>
						</div>
					</section>

					<!-- STUDY STREAK & GOALS -->
					<section class="iaq-dashboard-block iaq-streak-section">
						<div class="iaq-block-header-wrap iaq-flex-between">
							<div>
								<div class="iaq-header-title-row">
									<span class="iaq-fire-icon">🔥</span>
									<h3 class="iaq-block-title"><?php esc_html_e( 'Study Streak & Goals', 'indianagri-quiz-engine' ); ?></h3>
								</div>
								<p class="iaq-block-subtitle"><?php esc_html_e( 'Build a consistent study habit and achieve your preparation goals.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<a href="#iaq-study-streaks-section" class="iaq-view-all-link iaq-dash-nav-trigger" data-target="study-streaks">
								<?php esc_html_e( 'Set Goals', 'indianagri-quiz-engine' ); ?> &rarr;
							</a>
						</div>

						<?php
						$goal_curr   = isset( $user_goals['current_weekly_tests'] ) ? (int) $user_goals['current_weekly_tests'] : 0;
						$goal_target = isset( $user_goals['target_weekly_tests'] ) ? (int) $user_goals['target_weekly_tests'] : 5;
						$goal_pct    = ( $goal_target > 0 ) ? min( 100, round( ( $goal_curr / $goal_target ) * 100 ) ) : 0;
						$last_7_days = ! empty( $study_calendar ) ? array_slice( $study_calendar, -7 ) : array();
						?>
						<div class="iaq-streak-unified-card">
							<!-- 1. Flame streak info -->
							<div class="iaq-su-streak-part">
								<div class="iaq-su-flame-circle">
									🔥
								</div>
								<div class="iaq-su-streak-text">
									<div class="iaq-su-streak-number"><?php echo esc_html( (int) $current_streak ); ?></div>
									<div class="iaq-su-streak-unit"><?php esc_html_e( 'Day Streak', 'indianagri-quiz-engine' ); ?></div>
									<div class="iaq-su-streak-sub">
										<?php echo $current_streak > 0 ? esc_html__( 'Keep it going!', 'indianagri-quiz-engine' ) : esc_html__( 'Complete a test today!', 'indianagri-quiz-engine' ); ?>
									</div>
								</div>
							</div>

							<!-- 2. Weekly Practice Goal -->
							<div class="iaq-su-goal-part">
								<div class="iaq-su-goal-label-row">
									<span class="iaq-su-goal-title"><?php esc_html_e( 'Weekly Practice Goal', 'indianagri-quiz-engine' ); ?></span>
									<span class="iaq-su-goal-fraction">
										<strong><?php echo esc_html( $goal_curr ); ?></strong> / <?php echo esc_html( $goal_target ); ?> <?php esc_html_e( 'tests', 'indianagri-quiz-engine' ); ?>
									</span>
								</div>
								<div class="iaq-su-goal-bar-wrap">
									<div class="iaq-su-bar-track">
										<div class="iaq-su-bar-fill" style="width: <?php echo esc_attr( $goal_pct ); ?>%;"></div>
									</div>
									<span class="iaq-su-pct-badge"><?php echo esc_html( $goal_pct ); ?>%</span>
								</div>
							</div>

							<!-- 3. 7-Day Circular Checkmarks -->
							<div class="iaq-su-days-part">
								<?php if ( ! empty( $last_7_days ) ) : ?>
									<?php foreach ( $last_7_days as $day_info ) : ?>
										<div class="iaq-su-day-circle-col">
											<div class="iaq-day-check-circle <?php echo ! empty( $day_info['is_active'] ) ? 'is-done' : 'is-pending'; ?>">
												<?php echo ! empty( $day_info['is_active'] ) ? '✓' : ''; ?>
											</div>
											<span class="iaq-day-label"><?php echo esc_html( $day_info['day'] ); ?></span>
										</div>
									<?php endforeach; ?>
								<?php else : ?>
									<?php
									$standard_days = array( 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' );
									foreach ( $standard_days as $sd ) :
									?>
										<div class="iaq-su-day-circle-col">
											<div class="iaq-day-check-circle is-pending"></div>
											<span class="iaq-day-label"><?php echo esc_html( $sd ); ?></span>
										</div>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>
					</section>

					<!-- RECENT ATTEMPT HISTORY TABLE -->
					<section class="iaq-dashboard-block iaq-history-section">
						<div class="iaq-block-header-wrap iaq-flex-between">
							<div>
								<div class="iaq-header-title-row">
									<span class="iaq-clock-icon">🕒</span>
									<h3 class="iaq-block-title"><?php esc_html_e( 'Recent Attempt History', 'indianagri-quiz-engine' ); ?></h3>
								</div>
								<p class="iaq-block-subtitle"><?php esc_html_e( 'View your past results, review solutions, or resume interrupted tests.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<a href="#iaq-history-section" class="iaq-view-all-link">
								<?php esc_html_e( 'View All Attempts', 'indianagri-quiz-engine' ); ?> &rarr;
							</a>
						</div>

						<div class="iaq-clean-table-responsive">
							<table class="iaq-history-table">
								<thead>
									<tr>
										<th style="width: 38px;">#</th>
										<th><?php esc_html_e( 'Examination Title', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Type', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Category', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Date', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Score', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Percentage', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Result', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Time Taken', 'indianagri-quiz-engine' ); ?></th>
										<th style="text-align: right;"><?php esc_html_e( 'Action', 'indianagri-quiz-engine' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php
									// Format rows matching reference table
									$row_num = 1;
									if ( ! empty( $history ) ) :
										foreach ( $history as $h ) :
											$is_in_prog = ( 'in_progress' === $h['status'] );
											$is_comp    = ( 'completed' === $h['status'] );
											?>
											<tr>
												<td style="color: #64748b; font-weight: 700;"><?php echo esc_html( $row_num++ ); ?></td>
												<td>
													<strong class="iaq-tbl-quiz-name"><?php echo esc_html( $h['quiz_title'] ); ?></strong>
												</td>
												<td>
													<span class="iaq-tbl-type-pill"><?php echo esc_html( ucfirst( $h['quiz_type'] ) ); ?></span>
												</td>
												<td>
													<span class="iaq-tbl-cat-text"><?php echo esc_html( $h['exam_category'] ); ?></span>
												</td>
												<td style="color: #64748b; font-size: 13px;">
													<?php echo esc_html( $h['start_time'] ); ?>
												</td>
												<td>
													<?php if ( $is_comp ) : ?>
														<strong><?php echo esc_html( $h['score'] ); ?></strong> / <?php echo esc_html( $h['total_marks'] ); ?>
													<?php else : ?>
														<span style="color: #94a3b8;">—</span>
													<?php endif; ?>
												</td>
												<td>
													<?php if ( $is_comp ) : ?>
														<strong><?php echo esc_html( $h['percentage'] ); ?>%</strong>
													<?php else : ?>
														<span style="color: #94a3b8;">—</span>
													<?php endif; ?>
												</td>
												<td>
													<?php if ( $is_in_prog ) : ?>
														<span class="iaq-tbl-status-badge is-in-prog"><?php esc_html_e( 'IN PROGRESS', 'indianagri-quiz-engine' ); ?></span>
													<?php elseif ( $is_comp ) : ?>
														<span class="iaq-tbl-status-badge <?php echo $h['passed'] ? 'is-pass' : 'is-fail'; ?>">
															<?php echo $h['passed'] ? esc_html__( 'PASSED', 'indianagri-quiz-engine' ) : esc_html__( 'FAILED', 'indianagri-quiz-engine' ); ?>
														</span>
													<?php else : ?>
														<span class="iaq-tbl-status-badge"><?php echo esc_html( strtoupper( $h['status'] ) ); ?></span>
													<?php endif; ?>
												</td>
												<td style="color: #64748b; font-size: 13px;">
													<?php echo esc_html( $h['time_spent_formatted'] ); ?>
												</td>
												<td style="text-align: right; white-space: nowrap;">
													<?php if ( $is_in_prog ) : ?>
														<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_url( $h['quiz_id'], 'attempt' ) ); ?>" class="iaq-tbl-action-btn is-resume">
															<?php esc_html_e( 'Resume', 'indianagri-quiz-engine' ); ?> &rarr;
														</a>
													<?php elseif ( $is_comp ) : ?>
														<a href="<?php echo esc_url( IAQ_Frontend::get_results_url( $h['id'] ) ); ?>" class="iaq-tbl-action-btn is-review">
															<?php esc_html_e( 'Scorecard & Solutions', 'indianagri-quiz-engine' ); ?>
														</a>
													<?php endif; ?>
												</td>
											</tr>
										<?php
										endforeach;
									else :
										?>
										<tr>
											<td colspan="10" style="text-align:center;padding:36px;color:#64748b;">
												<div style="font-size:28px;margin-bottom:8px;">📝</div>
												<div style="font-size:15px;font-weight:700;color:#0f172a;margin-bottom:4px;">
													<?php esc_html_e( 'No examination attempts found.', 'indianagri-quiz-engine' ); ?>
												</div>
												<p style="margin:0 0 14px 0;font-size:13px;">
													<?php esc_html_e( 'Start your first practice set or mock test to view scorecards and solutions here.', 'indianagri-quiz-engine' ); ?>
												</p>
												<a href="#iaq-pane-practice-sets" class="iaq-btn-action-primary iaq-dash-nav-trigger" data-target="practice-sets" style="font-size:12px;padding:7px 16px;">
													<?php esc_html_e( 'Start Practicing', 'indianagri-quiz-engine' ); ?> &rarr;
												</a>
											</td>
										</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</section>

				</div><!-- /iaq-pane-dashboard -->

				<!-- VIEW PANE: PRACTICE SETS (INTEGRATED) -->
				<div class="iaq-dashboard-view-pane <?php echo 'practice-sets' === $active_tab ? 'is-active' : ''; ?>" id="iaq-pane-practice-sets">
					<div class="iaq-pane-content-card" style="padding: 20px 24px;">
						<div class="iaq-pane-head-row" style="margin-bottom: 20px;">
							<div>
								<h3 class="iaq-pane-title"><?php esc_html_e( 'Agricultural Practice Sets Hub', 'indianagri-quiz-engine' ); ?></h3>
								<p class="iaq-pane-subtitle"><?php esc_html_e( 'Practice high-yield agricultural MCQs categorized by authoritative books, core subjects, and competitive exams.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<button type="button" class="iaq-btn-back-dash iaq-dash-nav-trigger" data-target="dashboard">
								&larr; <?php esc_html_e( 'Back to Dashboard', 'indianagri-quiz-engine' ); ?>
							</button>
						</div>
						<?php echo $practice_sets_html; ?>
					</div>
				</div>

				<!-- VIEW PANE: MOCK TESTS (INTEGRATED) -->
				<div class="iaq-dashboard-view-pane <?php echo 'mock-tests' === $active_tab ? 'is-active' : ''; ?>" id="iaq-pane-mock-tests">
					<div class="iaq-pane-content-card" style="padding: 20px 24px;">
						<div class="iaq-pane-head-row" style="margin-bottom: 20px;">
							<div>
								<h3 class="iaq-pane-title"><?php esc_html_e( 'Full-Length Mock Test Examinations', 'indianagri-quiz-engine' ); ?></h3>
								<p class="iaq-pane-subtitle"><?php esc_html_e( 'Realistic exam-pattern mock tests with negative marking, time countdowns, and instant scorecard rankings.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<button type="button" class="iaq-btn-back-dash iaq-dash-nav-trigger" data-target="dashboard">
								&larr; <?php esc_html_e( 'Back to Dashboard', 'indianagri-quiz-engine' ); ?>
							</button>
						</div>
						<?php echo $mock_tests_html; ?>
					</div>
				</div>

				<!-- VIEW PANE: EXAMINATIONS (INTEGRATED) -->
				<div class="iaq-dashboard-view-pane <?php echo 'examinations' === $active_tab ? 'is-active' : ''; ?>" id="iaq-pane-examinations">
					<div class="iaq-pane-content-card" style="padding: 24px;">
						<div class="iaq-pane-head-row" style="margin-bottom: 22px;">
							<div>
								<h3 class="iaq-pane-title"><?php esc_html_e( 'Target Agricultural Examinations', 'indianagri-quiz-engine' ); ?></h3>
								<p class="iaq-pane-subtitle"><?php esc_html_e( 'Select your target examination to access official pattern mock tests, previous year papers, and subject practice sets.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<button type="button" class="iaq-btn-back-dash iaq-dash-nav-trigger" data-target="dashboard">
								&larr; <?php esc_html_e( 'Back to Dashboard', 'indianagri-quiz-engine' ); ?>
							</button>
						</div>

						<div class="iaq-exam-catalog-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:18px;">
							<?php if ( ! empty( $examinations ) ) : ?>
								<?php foreach ( $examinations as $exam_item ) : ?>
									<div class="iaq-exam-portal-card" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;display:flex;flex-direction:column;justify-content:space-between;transition:transform 0.15s ease, box-shadow 0.15s ease;">
										<div>
											<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
												<span class="iaq-tag-pill is-exam" style="font-weight:700;font-size:11px;background:#e0f2fe;color:#0369a1;"><?php echo esc_html( $exam_item->code ); ?></span>
												<span style="font-size:11px;color:#16a34a;font-weight:700;display:inline-flex;align-items:center;gap:4px;">● <?php esc_html_e( 'Active Exam', 'indianagri-quiz-engine' ); ?></span>
											</div>
											<h4 style="margin:0 0 8px 0;font-size:16px;font-weight:800;color:#0f172a;line-height:1.35;">
												<?php echo esc_html( $exam_item->name ); ?>
											</h4>
											<?php if ( ! empty( $exam_item->description ) ) : ?>
												<p style="margin:0 0 16px 0;font-size:12.5px;color:#64748b;line-height:1.5;">
													<?php echo esc_html( wp_trim_words( $exam_item->description, 18 ) ); ?>
												</p>
											<?php else : ?>
												<p style="margin:0 0 16px 0;font-size:12.5px;color:#64748b;line-height:1.5;">
													<?php esc_html_e( 'Comprehensive agricultural syllabus coverage with dedicated practice sets and mock tests.', 'indianagri-quiz-engine' ); ?>
												</p>
											<?php endif; ?>
										</div>
										<div style="display:flex;gap:8px;padding-top:14px;border-top:1px solid #e2e8f0;">
											<a href="#iaq-pane-mock-tests" class="iaq-qa-action-pill is-blue iaq-dash-nav-trigger" data-target="mock-tests" style="flex:1;text-align:center;font-size:12px;padding:7px 10px;text-decoration:none;">
												<?php esc_html_e( 'Mock Tests', 'indianagri-quiz-engine' ); ?> &rarr;
											</a>
											<a href="#iaq-pane-practice-sets" class="iaq-qa-action-pill is-green iaq-dash-nav-trigger" data-target="practice-sets" style="flex:1;text-align:center;font-size:12px;padding:7px 10px;text-decoration:none;">
												<?php esc_html_e( 'Practice Sets', 'indianagri-quiz-engine' ); ?> &rarr;
											</a>
										</div>
									</div>
								<?php endforeach; ?>
							<?php else : ?>
								<div class="iaq-empty-sub-card" style="grid-column:1/-1;">
									<h4><?php esc_html_e( 'No active examinations listed yet.', 'indianagri-quiz-engine' ); ?></h4>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- VIEW PANE: MY RESULTS / ATTEMPT HISTORY (INTEGRATED) -->
				<div class="iaq-dashboard-view-pane <?php echo 'history' === $active_tab ? 'is-active' : ''; ?>" id="iaq-pane-history">
					<div class="iaq-pane-content-card" style="padding: 24px;">
						<div class="iaq-pane-head-row" style="margin-bottom: 22px;">
							<div>
								<h3 class="iaq-pane-title"><?php esc_html_e( 'Examination Results &amp; Attempt History', 'indianagri-quiz-engine' ); ?></h3>
								<p class="iaq-pane-subtitle"><?php esc_html_e( 'Comprehensive record of all practice sets and full-length mock examinations attempted by your account.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<button type="button" class="iaq-btn-back-dash iaq-dash-nav-trigger" data-target="dashboard">
								&larr; <?php esc_html_e( 'Back to Dashboard', 'indianagri-quiz-engine' ); ?>
							</button>
						</div>

						<div class="iaq-clean-table-responsive">
							<table class="iaq-history-table">
								<thead>
									<tr>
										<th style="width: 38px;">#</th>
										<th><?php esc_html_e( 'Examination Title', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Type', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Category', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Date', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Score', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Percentage', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Result', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Time Taken', 'indianagri-quiz-engine' ); ?></th>
										<th style="text-align: right;"><?php esc_html_e( 'Action', 'indianagri-quiz-engine' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php
									$pane_row_num = 1;
									if ( ! empty( $history ) ) :
										foreach ( $history as $h ) :
											$is_in_prog = ( 'in_progress' === $h['status'] );
											$is_comp    = ( 'completed' === $h['status'] );
											?>
											<tr>
												<td style="color: #64748b; font-weight: 700;"><?php echo esc_html( $pane_row_num++ ); ?></td>
												<td>
													<strong class="iaq-tbl-quiz-name"><?php echo esc_html( $h['quiz_title'] ); ?></strong>
												</td>
												<td>
													<span class="iaq-tbl-type-pill is-<?php echo esc_attr( $h['quiz_type'] ); ?>">
														<?php echo esc_html( ucfirst( $h['quiz_type'] ) ); ?>
													</span>
												</td>
												<td>
													<span class="iaq-tbl-cat-text"><?php echo esc_html( $h['exam_category'] ); ?></span>
												</td>
												<td style="color: #64748b; font-size: 13px;">
													<?php echo esc_html( $h['start_time'] ); ?>
												</td>
												<td>
													<strong><?php echo esc_html( $h['score'] ); ?></strong>
													<span style="color: #94a3b8; font-size: 12px;"> / <?php echo esc_html( $h['total_marks'] ); ?></span>
												</td>
												<td>
													<strong><?php echo esc_html( $h['percentage'] ); ?>%</strong>
												</td>
												<td>
													<?php if ( $is_comp ) : ?>
														<span class="iaq-tbl-status-badge <?php echo $h['passed'] ? 'is-pass' : 'is-fail'; ?>">
															<?php echo $h['passed'] ? esc_html__( 'PASSED', 'indianagri-quiz-engine' ) : esc_html__( 'FAILED', 'indianagri-quiz-engine' ); ?>
														</span>
													<?php else : ?>
														<span class="iaq-tbl-status-badge"><?php echo esc_html( strtoupper( $h['status'] ) ); ?></span>
													<?php endif; ?>
												</td>
												<td style="color: #64748b; font-size: 13px;">
													<?php echo esc_html( $h['time_spent_formatted'] ); ?>
												</td>
												<td style="text-align: right; white-space: nowrap;">
													<?php if ( $is_in_prog ) : ?>
														<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_url( $h['quiz_id'], 'attempt' ) ); ?>" class="iaq-tbl-action-btn is-resume">
															<?php esc_html_e( 'Resume', 'indianagri-quiz-engine' ); ?> &rarr;
														</a>
													<?php elseif ( $is_comp ) : ?>
														<a href="<?php echo esc_url( IAQ_Frontend::get_results_url( $h['id'] ) ); ?>" class="iaq-tbl-action-btn is-review">
															<?php esc_html_e( 'Scorecard & Solutions', 'indianagri-quiz-engine' ); ?>
														</a>
													<?php endif; ?>
												</td>
											</tr>
										<?php
										endforeach;
									else :
										?>
										<tr>
											<td colspan="10" style="text-align:center;padding:36px;color:#64748b;">
												<div style="font-size:32px;margin-bottom:8px;">📝</div>
												<div style="font-size:15px;font-weight:700;color:#0f172a;"><?php esc_html_e( 'No examination attempts found.', 'indianagri-quiz-engine' ); ?></div>
												<p style="margin:4px 0 16px 0;font-size:13px;"><?php esc_html_e( 'Begin practicing high-yield agricultural questions or test your knowledge with full-length mocks.', 'indianagri-quiz-engine' ); ?></p>
												<a href="#iaq-pane-practice-sets" class="iaq-btn-action-primary iaq-dash-nav-trigger" data-target="practice-sets" style="font-size:12px;padding:7px 16px;">
													<?php esc_html_e( 'Start Practice Drill', 'indianagri-quiz-engine' ); ?>
												</a>
											</td>
										</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<!-- VIEW PANE: SUBSCRIPTIONS & PLANS (INTEGRATED) -->
				<div class="iaq-dashboard-view-pane <?php echo 'subscriptions' === $active_tab ? 'is-active' : ''; ?>" id="iaq-pane-subscriptions">
					<div class="iaq-pane-content-card" style="padding: 20px 24px;">
						<div class="iaq-pane-head-row" style="margin-bottom: 20px;">
							<div>
								<h3 class="iaq-pane-title"><?php esc_html_e( 'Subscription & Membership Plans', 'indianagri-quiz-engine' ); ?></h3>
								<p class="iaq-pane-subtitle"><?php esc_html_e( 'Unlock comprehensive question banks, full mock series, and detailed scientific answer keys.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<button type="button" class="iaq-btn-back-dash iaq-dash-nav-trigger" data-target="dashboard">
								&larr; <?php esc_html_e( 'Back to Dashboard', 'indianagri-quiz-engine' ); ?>
							</button>
						</div>
						<?php echo $subscription_plans_html; ?>
					</div>
				</div>

				<!-- VIEW PANE: CUSTOM PRACTICE TEST BUILDER -->
				<!-- VIEW PANE: CUSTOM PRACTICE TEST BUILDER -->
				<div class="iaq-dashboard-view-pane" id="iaq-pane-custom-test">
					<div class="iaq-pane-content-card" id="iaq-custom-practice-section">
						<div class="iaq-pane-head-row">
							<div>
								<h3 class="iaq-pane-title"><?php esc_html_e( 'Custom Practice Test Builder', 'indianagri-quiz-engine' ); ?></h3>
								<p class="iaq-pane-subtitle"><?php esc_html_e( 'Build a custom examination drill tailored to your target exam, subjects, difficulty, and question count.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<button type="button" class="iaq-btn-back-dash iaq-dash-nav-trigger" data-target="dashboard">
								&larr; <?php esc_html_e( 'Back to Dashboard', 'indianagri-quiz-engine' ); ?>
							</button>
						</div>

						<form id="iaq-custom-test-form" class="iaq-custom-form-container">
							<div class="iaq-custom-form-grid">
								<div class="iaq-form-group">
									<label for="iaq-ct-exam">
										<span class="iaq-form-label-icon">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
												<path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
												<path d="M6 12v5c3 3 9 3 12 0v-5"/>
											</svg>
										</span>
										<?php esc_html_e( 'Target Examination', 'indianagri-quiz-engine' ); ?>
									</label>
									<select name="exam_category" id="iaq-ct-exam" class="iaq-select iaq-full-width">
										<option value=""><?php esc_html_e( 'All Eligible Examinations', 'indianagri-quiz-engine' ); ?></option>
										<?php foreach ( $examinations as $ex ) : ?>
											<option value="<?php echo esc_attr( $ex->code ); ?>">
												<?php echo esc_html( $ex->name ); ?> (<?php echo esc_html( $ex->code ); ?>)
											</option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="iaq-form-group">
									<label for="iaq-ct-subject">
										<span class="iaq-form-label-icon">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
												<path d="M12 2a10 10 0 0 1 10 10c0 5.523-4.477 10-10 10S2 17.523 2 12A10 10 0 0 1 12 2z"/>
												<path d="M12 6v6l4 2"/>
											</svg>
										</span>
										<?php esc_html_e( 'Agricultural Subject', 'indianagri-quiz-engine' ); ?>
									</label>
									<select name="subject_id" id="iaq-ct-subject" class="iaq-select iaq-full-width">
										<option value="0"><?php esc_html_e( 'All Agricultural Subjects', 'indianagri-quiz-engine' ); ?></option>
										<?php foreach ( $subjects as $sb ) : ?>
											<option value="<?php echo esc_attr( $sb->id ); ?>">
												<?php echo esc_html( $sb->name ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="iaq-form-group">
									<label for="iaq-ct-qty">
										<span class="iaq-form-label-icon">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
												<line x1="8" y1="6" x2="21" y2="6"/>
												<line x1="8" y1="12" x2="21" y2="12"/>
												<line x1="8" y1="18" x2="21" y2="18"/>
												<line x1="3" y1="6" x2="3.01" y2="6"/>
												<line x1="3" y1="12" x2="3.01" y2="12"/>
												<line x1="3" y1="18" x2="3.01" y2="18"/>
											</svg>
										</span>
										<?php esc_html_e( 'Number of Questions', 'indianagri-quiz-engine' ); ?>
									</label>
									<select name="num_questions" id="iaq-ct-qty" class="iaq-select iaq-full-width">
										<option value="10">10 <?php esc_html_e( 'Questions (Speed Drill)', 'indianagri-quiz-engine' ); ?></option>
										<option value="20" selected>20 <?php esc_html_e( 'Questions (Standard Drill)', 'indianagri-quiz-engine' ); ?></option>
										<option value="30">30 <?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?></option>
										<option value="50">50 <?php esc_html_e( 'Questions (Comprehensive Mock)', 'indianagri-quiz-engine' ); ?></option>
									</select>
								</div>

								<div class="iaq-form-group">
									<label for="iaq-ct-diff">
										<span class="iaq-form-label-icon">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
												<line x1="18" y1="20" x2="18" y2="10"/>
												<line x1="12" y1="20" x2="12" y2="4"/>
												<line x1="6" y1="20" x2="6" y2="14"/>
											</svg>
										</span>
										<?php esc_html_e( 'Difficulty Level', 'indianagri-quiz-engine' ); ?>
									</label>
									<select name="difficulty" id="iaq-ct-diff" class="iaq-select iaq-full-width">
										<option value="all"><?php esc_html_e( 'All Difficulties (Balanced)', 'indianagri-quiz-engine' ); ?></option>
										<option value="easy"><?php esc_html_e( 'Easy (Foundational Concepts)', 'indianagri-quiz-engine' ); ?></option>
										<option value="medium"><?php esc_html_e( 'Medium (Standard Level)', 'indianagri-quiz-engine' ); ?></option>
										<option value="hard"><?php esc_html_e( 'Hard (Advanced AIEEA/JRF)', 'indianagri-quiz-engine' ); ?></option>
									</select>
								</div>

								<div class="iaq-form-group">
									<label for="iaq-ct-time">
										<span class="iaq-form-label-icon">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
												<circle cx="12" cy="12" r="10"/>
												<polyline points="12 6 12 12 16 14"/>
											</svg>
										</span>
										<?php esc_html_e( 'Time Limit', 'indianagri-quiz-engine' ); ?>
									</label>
									<select name="time_limit" id="iaq-ct-time" class="iaq-select iaq-full-width">
										<option value="0"><?php esc_html_e( 'Untimed Practice Mode', 'indianagri-quiz-engine' ); ?></option>
										<option value="15">15 <?php esc_html_e( 'Minutes', 'indianagri-quiz-engine' ); ?></option>
										<option value="30">30 <?php esc_html_e( 'Minutes', 'indianagri-quiz-engine' ); ?></option>
										<option value="45">45 <?php esc_html_e( 'Minutes', 'indianagri-quiz-engine' ); ?></option>
										<option value="60">60 <?php esc_html_e( 'Minutes (Official Exam CBT)', 'indianagri-quiz-engine' ); ?></option>
									</select>
								</div>

								<div class="iaq-form-group">
									<label for="iaq-ct-mode">
										<span class="iaq-form-label-icon">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
												<polyline points="16 3 21 3 21 8"/>
												<line x1="4" y1="20" x2="21" y2="3"/>
												<polyline points="21 16 21 21 16 21"/>
												<line x1="15" y1="15" x2="21" y2="21"/>
												<line x1="4" y1="4" x2="9" y2="9"/>
											</svg>
										</span>
										<?php esc_html_e( 'Question Ordering Mode', 'indianagri-quiz-engine' ); ?>
									</label>
									<select name="mode" id="iaq-ct-mode" class="iaq-select iaq-full-width">
										<option value="random"><?php esc_html_e( 'Randomized Pool Selection', 'indianagri-quiz-engine' ); ?></option>
										<option value="latest"><?php esc_html_e( 'Most Recently Added MCQs', 'indianagri-quiz-engine' ); ?></option>
									</select>
								</div>
							</div>

							<div class="iaq-ct-submit-row">
								<button type="submit" class="iaq-btn-action-primary iaq-btn-large" id="iaq-btn-build-test">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
										<polygon points="5 3 19 12 5 21 5 3"/>
									</svg>
									<span><?php esc_html_e( 'Generate & Launch Custom Test', 'indianagri-quiz-engine' ); ?></span>
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
										<line x1="5" y1="12" x2="19" y2="12"></line>
										<polyline points="12 5 19 12 12 19"></polyline>
									</svg>
								</button>
								<span class="iaq-ct-status" id="iaq-ct-status-msg"></span>
							</div>
						</form>
					</div>
				</div>


				<!-- VIEW PANE: BOOKMARKED QUESTIONS -->
				<div class="iaq-dashboard-view-pane" id="iaq-pane-bookmarks">
					<div class="iaq-pane-content-card" id="iaq-bookmarks-section">
						<div class="iaq-pane-head-row">
							<div>
								<h3 class="iaq-pane-title"><?php esc_html_e( 'Bookmarked Questions for Revision', 'indianagri-quiz-engine' ); ?></h3>
								<p class="iaq-pane-subtitle"><?php esc_html_e( 'Review tricky questions you saved during practice drills and launch targeted revision sessions.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<div style="display:flex;gap:10px;">
								<?php if ( ! empty( $bookmarks ) ) : ?>
									<button type="button" class="iaq-btn-action-primary" id="iaq-btn-start-revision-quiz">
										<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;">
											<path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
										</svg>
										<?php esc_html_e( 'Practice Bookmarked Questions', 'indianagri-quiz-engine' ); ?>
									</button>
								<?php endif; ?>
								<button type="button" class="iaq-btn-back-dash iaq-dash-nav-trigger" data-target="dashboard">
									&larr; <?php esc_html_e( 'Back', 'indianagri-quiz-engine' ); ?>
								</button>
							</div>
						</div>

						<?php if ( empty( $bookmarks ) ) : ?>
							<div class="iaq-empty-sub-card">
								<div class="iaq-empty-icon">🔖</div>
								<h4><?php esc_html_e( 'No Bookmarked Questions Yet', 'indianagri-quiz-engine' ); ?></h4>
								<p><?php esc_html_e( 'You can bookmark high-yield MCQs during mock tests or when reviewing solutions. They will appear here for rapid revision.', 'indianagri-quiz-engine' ); ?></p>
								<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_list_url( 'practice' ) ); ?>" class="iaq-btn-action-primary">
									<?php esc_html_e( 'Explore Practice Sets', 'indianagri-quiz-engine' ); ?>
								</a>
							</div>
						<?php else : ?>
							<div class="iaq-learning-cards-list" id="iaq-bookmarks-container">
								<?php foreach ( $bookmarks as $bm ) : ?>
									<div class="iaq-learning-item-card" id="iaq-bookmark-row-<?php echo esc_attr( $bm->question_id ); ?>">
										<div class="iaq-lic-header">
											<div class="iaq-lic-badges">
												<?php if ( ! empty( $bm->subject_name ) ) : ?>
													<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $bm->subject_name ); ?></span>
												<?php endif; ?>
												<span class="iaq-pill" style="background:#f1f5f9;color:#64748b;"><?php echo esc_html( ucfirst( $bm->difficulty ) ); ?></span>
											</div>
											<button type="button" class="iaq-btn-remove-icon iaq-remove-bookmark-btn" data-question-id="<?php echo esc_attr( $bm->question_id ); ?>" title="<?php esc_attr_e( 'Remove Bookmark', 'indianagri-quiz-engine' ); ?>">
												<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
													<polyline points="3 6 5 6 21 6"/>
													<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
												</svg>
											</button>
										</div>
										<div class="iaq-lic-question"><?php echo wp_kses_post( $bm->question_text ); ?></div>
										<div class="iaq-lic-options-grid">
											<span class="iaq-lic-opt <?php echo ( 'A' === $bm->correct_option ) ? 'is-correct' : ''; ?>"><strong>A.</strong> <?php echo esc_html( $bm->option_a ); ?></span>
											<span class="iaq-lic-opt <?php echo ( 'B' === $bm->correct_option ) ? 'is-correct' : ''; ?>"><strong>B.</strong> <?php echo esc_html( $bm->option_b ); ?></span>
											<span class="iaq-lic-opt <?php echo ( 'C' === $bm->correct_option ) ? 'is-correct' : ''; ?>"><strong>C.</strong> <?php echo esc_html( $bm->option_c ); ?></span>
											<span class="iaq-lic-opt <?php echo ( 'D' === $bm->correct_option ) ? 'is-correct' : ''; ?>"><strong>D.</strong> <?php echo esc_html( $bm->option_d ); ?></span>
										</div>
										<?php if ( ! empty( $bm->explanation ) ) : ?>
											<div class="iaq-lic-explanation">
												<strong><?php esc_html_e( 'Explanation:', 'indianagri-quiz-engine' ); ?></strong> <?php echo wp_kses_post( $bm->explanation ); ?>
											</div>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>


				<!-- VIEW PANE: WRONG ANSWER NOTEBOOK -->
				<div class="iaq-dashboard-view-pane" id="iaq-pane-wrong-notebook">
					<div class="iaq-pane-content-card" id="iaq-wrong-notebook-section">
						<div class="iaq-pane-head-row">
							<div>
								<h3 class="iaq-pane-title"><?php esc_html_e( 'Wrong Answer Notebook', 'indianagri-quiz-engine' ); ?></h3>
								<p class="iaq-pane-subtitle"><?php esc_html_e( 'Server-tracked questions answered incorrectly in completed tests for targeted error resolution.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<div style="display:flex;gap:10px;">
								<?php if ( ! empty( $wrong_notebook ) ) : ?>
									<button type="button" class="iaq-btn-action-primary is-danger" id="iaq-btn-retry-wrong-quiz">
										<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;">
											<path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
										</svg>
										<?php esc_html_e( 'Retry Incorrect Questions', 'indianagri-quiz-engine' ); ?>
									</button>
								<?php endif; ?>
								<button type="button" class="iaq-btn-back-dash iaq-dash-nav-trigger" data-target="dashboard">
									&larr; <?php esc_html_e( 'Back', 'indianagri-quiz-engine' ); ?>
								</button>
							</div>
						</div>

						<?php if ( empty( $wrong_notebook ) ) : ?>
							<div class="iaq-empty-sub-card">
								<div class="iaq-empty-icon">✅</div>
								<h4><?php esc_html_e( 'Your Wrong Answer Notebook is Clean!', 'indianagri-quiz-engine' ); ?></h4>
								<p><?php esc_html_e( 'Questions you answer incorrectly will appear here automatically so you can practice them until mastered.', 'indianagri-quiz-engine' ); ?></p>
								<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_list_url( 'practice' ) ); ?>" class="iaq-btn-action-primary">
									<?php esc_html_e( 'Practice Questions Now', 'indianagri-quiz-engine' ); ?>
								</a>
							</div>
						<?php else : ?>
							<div class="iaq-learning-cards-list" id="iaq-wrong-container">
								<?php foreach ( $wrong_notebook as $wn ) : ?>
									<div class="iaq-learning-item-card is-wrong" id="iaq-wrong-row-<?php echo esc_attr( $wn->question_id ); ?>">
										<div class="iaq-lic-header">
											<div class="iaq-lic-badges">
												<?php if ( ! empty( $wn->subject_name ) ) : ?>
													<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $wn->subject_name ); ?></span>
												<?php endif; ?>
												<span class="iaq-pill" style="background:#fee2e2;color:#991b1b;font-weight:700;">
													<?php printf( esc_html__( 'Failed %dx', 'indianagri-quiz-engine' ), $wn->times_incorrect ); ?>
												</span>
											</div>
											<div class="iaq-lic-actions">
												<button type="button" class="iaq-btn-sm-text iaq-resolve-wrong-btn" data-question-id="<?php echo esc_attr( $wn->question_id ); ?>">
													<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;">
														<polyline points="20 6 9 17 4 12"/>
													</svg>
													<?php esc_html_e( 'Mark Resolved', 'indianagri-quiz-engine' ); ?>
												</button>
												<button type="button" class="iaq-btn-remove-icon iaq-remove-wrong-btn" data-question-id="<?php echo esc_attr( $wn->question_id ); ?>" title="<?php esc_attr_e( 'Remove from Notebook', 'indianagri-quiz-engine' ); ?>">
													<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
														<polyline points="3 6 5 6 21 6"/>
														<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
													</svg>
												</button>
											</div>
										</div>

										<div class="iaq-lic-question"><?php echo wp_kses_post( $wn->question_text ); ?></div>

										<div class="iaq-lic-verdict-summary">
											<?php if ( ! empty( $wn->last_selected_option ) ) : ?>
												<span class="iaq-verdict-tag is-wrong">
													<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;">
														<line x1="18" y1="6" x2="6" y2="18"/>
														<line x1="6" y1="6" x2="18" y2="18"/>
													</svg>
													<?php printf( esc_html__( 'Your Answer: Option %s', 'indianagri-quiz-engine' ), esc_html( $wn->last_selected_option ) ); ?>
												</span>
											<?php endif; ?>
											<span class="iaq-verdict-tag is-correct">
												<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;">
													<polyline points="20 6 9 17 4 12"/>
												</svg>
												<?php printf( esc_html__( 'Correct Answer: Option %s', 'indianagri-quiz-engine' ), esc_html( $wn->correct_option ) ); ?>
											</span>
										</div>

										<?php if ( ! empty( $wn->explanation ) ) : ?>
											<div class="iaq-lic-explanation">
												<strong><?php esc_html_e( 'Scientific Explanation:', 'indianagri-quiz-engine' ); ?></strong> <?php echo wp_kses_post( $wn->explanation ); ?>
											</div>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>


				<!-- VIEW PANE: STUDY STREAKS & GOALS -->
				<div class="iaq-dashboard-view-pane" id="iaq-pane-study-streaks">
					<div class="iaq-pane-content-card" id="iaq-study-streaks-section">
						<div class="iaq-pane-head-row">
							<div>
								<h3 class="iaq-pane-title"><?php esc_html_e( 'Study Streaks & Weekly Goals', 'indianagri-quiz-engine' ); ?></h3>
								<p class="iaq-pane-subtitle"><?php esc_html_e( 'Configure personal weekly test targets and view consistency calendars.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<button type="button" class="iaq-btn-back-dash iaq-dash-nav-trigger" data-target="dashboard">
								&larr; <?php esc_html_e( 'Back to Dashboard', 'indianagri-quiz-engine' ); ?>
							</button>
						</div>

						<!-- Streaks & Goals Metrics Grid -->
						<div class="iaq-streaks-grid">
							<!-- 1. Current Streak Card -->
							<div class="iaq-streak-card">
								<div class="iaq-streak-flame-wrap">
									<div class="iaq-streak-flame <?php echo $current_streak > 0 ? 'is-burning' : ''; ?>">
										🔥
									</div>
									<div class="iaq-streak-numbers">
										<div class="iaq-streak-count"><?php echo esc_html( (int) $current_streak ); ?></div>
										<div class="iaq-streak-unit"><?php esc_html_e( 'Days Consecutive Streak', 'indianagri-quiz-engine' ); ?></div>
									</div>
								</div>
								<div class="iaq-streak-subtext">
									<?php if ( $current_streak > 0 ) : ?>
										<?php printf( esc_html__( 'Outstanding consistency! All-time personal best: %d days.', 'indianagri-quiz-engine' ), (int) $best_streak ); ?>
									<?php else : ?>
										<?php printf( esc_html__( 'Complete any practice test or mock exam today to ignite your streak! Personal best: %d days.', 'indianagri-quiz-engine' ), (int) $best_streak ); ?>
									<?php endif; ?>
								</div>
							</div>

							<!-- 2. Weekly Goals Progress Card -->
							<?php
							$curr_tests   = isset( $user_goals['current_weekly_tests'] ) ? (int) $user_goals['current_weekly_tests'] : 0;
							$target_tests = isset( $user_goals['target_weekly_tests'] ) ? (int) $user_goals['target_weekly_tests'] : 5;
							$tests_pct    = isset( $user_goals['tests_progress_percent'] ) ? (int) $user_goals['tests_progress_percent'] : ( ( $target_tests > 0 ) ? min( 100, round( ( $curr_tests / $target_tests ) * 100 ) ) : 0 );

							$curr_qs      = isset( $user_goals['current_weekly_questions'] ) ? (int) $user_goals['current_weekly_questions'] : 0;
							$target_qs    = isset( $user_goals['target_weekly_questions'] ) ? (int) $user_goals['target_weekly_questions'] : 50;
							$qs_pct       = isset( $user_goals['qs_progress_percent'] ) ? (int) $user_goals['qs_progress_percent'] : ( ( $target_qs > 0 ) ? min( 100, round( ( $curr_qs / $target_qs ) * 100 ) ) : 0 );
							?>
							<div class="iaq-goals-card">
								<div class="iaq-goals-card-header">
									<h4><?php esc_html_e( 'Weekly Preparation Progress', 'indianagri-quiz-engine' ); ?></h4>
									<span class="iaq-pill iaq-pill-category"><?php esc_html_e( 'Active Week', 'indianagri-quiz-engine' ); ?></span>
								</div>
								<div class="iaq-goal-progress-wrap" style="margin-bottom:14px;">
									<div class="iaq-goal-label-row">
										<span><?php esc_html_e( 'Completed Tests:', 'indianagri-quiz-engine' ); ?> <strong><?php echo esc_html( $curr_tests ); ?> / <?php echo esc_html( $target_tests ); ?></strong></span>
										<span><strong><?php echo esc_html( $tests_pct ); ?>%</strong></span>
									</div>
									<div class="iaq-goal-bar-track">
										<div class="iaq-goal-bar-fill is-primary" style="width: <?php echo esc_attr( $tests_pct ); ?>%;"></div>
									</div>
								</div>
								<div class="iaq-goal-progress-wrap">
									<div class="iaq-goal-label-row">
										<span><?php esc_html_e( 'Solved MCQs:', 'indianagri-quiz-engine' ); ?> <strong><?php echo esc_html( $curr_qs ); ?> / <?php echo esc_html( $target_qs ); ?></strong></span>
										<span><strong><?php echo esc_html( $qs_pct ); ?>%</strong></span>
									</div>
									<div class="iaq-goal-bar-track">
										<div class="iaq-goal-bar-fill is-accent" style="width: <?php echo esc_attr( $qs_pct ); ?>%;"></div>
									</div>
								</div>
							</div>
						</div>

						<!-- 3. 14-Day Activity Consistency Calendar -->
						<div class="iaq-activity-calendar-wrap">
							<h4 class="iaq-calendar-title">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
									<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
									<line x1="16" y1="2" x2="16" y2="6"/>
									<line x1="8" y1="2" x2="8" y2="6"/>
									<line x1="3" y1="10" x2="21" y2="10"/>
								</svg>
								<?php esc_html_e( '14-Day Study Consistency Calendar', 'indianagri-quiz-engine' ); ?>
							</h4>
							<div class="iaq-activity-dots-grid">
								<?php if ( ! empty( $study_calendar ) ) : ?>
									<?php foreach ( $study_calendar as $day ) : ?>
										<div class="iaq-activity-dot-col" title="<?php printf( esc_attr__( '%s: %d MCQs solved across %d sessions', 'indianagri-quiz-engine' ), esc_attr( $day['date'] ), (int) $day['questions_solved'], (int) $day['events_count'] ); ?>">
											<div class="iaq-activity-dot <?php echo ! empty( $day['is_active'] ) ? 'is-active' : ''; ?>"></div>
											<span class="iaq-activity-dot-day"><?php echo esc_html( $day['day'] ); ?></span>
										</div>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>

						<!-- 4. Goals Edit Form with Premium Styling -->
						<div class="iaq-goals-edit-box">
							<div class="iaq-goals-card-header">
								<h4 style="margin:0;display:flex;align-items:center;gap:8px;">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#117E3A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
										<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
									</svg>
									<?php esc_html_e( 'Customize Weekly Target Benchmarks', 'indianagri-quiz-engine' ); ?>
								</h4>
							</div>
							<p style="margin:4px 0 20px 0;font-size:13px;color:#64748b;">
								<?php esc_html_e( 'Set realistic targets aligned with your upcoming entrance exams (ICAR AIEEA, JRF, State CET, or IBPS-AFO).', 'indianagri-quiz-engine' ); ?>
							</p>
							<form id="iaq-goals-form" class="iaq-custom-form-container">
								<div class="iaq-custom-form-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 20px;">
									<div class="iaq-form-group">
										<label for="iaq-goal-tests">
											<span class="iaq-form-label-icon">
												<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
													<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
													<polyline points="14 2 14 8 20 8"/>
													<line x1="16" y1="13" x2="8" y2="13"/>
													<line x1="16" y1="17" x2="8" y2="17"/>
													<polyline points="10 9 9 9 8 9"/>
												</svg>
											</span>
											<?php esc_html_e( 'Weekly Tests Target (1-50)', 'indianagri-quiz-engine' ); ?>
										</label>
										<input type="number" id="iaq-goal-tests" class="iaq-input iaq-full-width" min="1" max="50" value="<?php echo esc_attr( isset( $user_goals['target_weekly_tests'] ) ? $user_goals['target_weekly_tests'] : 5 ); ?>">
										<span style="font-size:12px;color:#64748b;"><?php esc_html_e( 'Target number of mock tests or practice drills per week.', 'indianagri-quiz-engine' ); ?></span>
									</div>
									<div class="iaq-form-group">
										<label for="iaq-goal-qs">
											<span class="iaq-form-label-icon">
												<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
													<circle cx="12" cy="12" r="10"/>
													<line x1="12" y1="8" x2="12" y2="12"/>
													<line x1="12" y1="16" x2="12.01" y2="16"/>
												</svg>
											</span>
											<?php esc_html_e( 'Weekly MCQs Target (10-1000)', 'indianagri-quiz-engine' ); ?>
										</label>
										<input type="number" id="iaq-goal-qs" class="iaq-input iaq-full-width" min="10" max="1000" value="<?php echo esc_attr( isset( $user_goals['target_weekly_questions'] ) ? $user_goals['target_weekly_questions'] : 50 ); ?>">
										<span style="font-size:12px;color:#64748b;"><?php esc_html_e( 'Target volume of questions to practice each week.', 'indianagri-quiz-engine' ); ?></span>
									</div>
								</div>
								<div class="iaq-ct-submit-row" style="padding-top:16px;">
									<button type="submit" class="iaq-btn-action-primary iaq-btn-large">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
											<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
											<polyline points="17 21 17 13 7 13 7 21"/>
											<polyline points="7 3 7 8 15 8"/>
										</svg>
										<span><?php esc_html_e( 'Save Study Goals', 'indianagri-quiz-engine' ); ?></span>
									</button>
									<span id="iaq-goals-status-msg" style="align-self:center;font-weight:600;font-size:14px;color:#16a34a;"></span>
								</div>
							</form>
						</div>
					</div>
				</div>


				<!-- VIEW PANE: LEADERBOARD -->
				<div class="iaq-dashboard-view-pane" id="iaq-pane-leaderboard">
					<div class="iaq-pane-content-card" id="iaq-leaderboard-section">
						<div class="iaq-pane-head-row">
							<div>
								<h3 class="iaq-pane-title"><?php esc_html_e( 'Agricultural Scholar Leaderboard', 'indianagri-quiz-engine' ); ?></h3>
								<p class="iaq-pane-subtitle"><?php esc_html_e( 'Verified rankings of top candidates based on completed mock examination scores.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<button type="button" class="iaq-btn-back-dash iaq-dash-nav-trigger" data-target="dashboard">
								&larr; <?php esc_html_e( 'Back to Dashboard', 'indianagri-quiz-engine' ); ?>
							</button>
						</div>

						<?php if ( ! empty( $leaderboard_data['current_user_rank'] ) ) : ?>
							<div class="iaq-user-rank-banner">
								<span class="iaq-ur-badge">#<?php echo esc_html( $leaderboard_data['current_user_rank']['rank'] ); ?></span>
								<div class="iaq-ur-details">
									<strong><?php esc_html_e( 'Your Leaderboard Rank', 'indianagri-quiz-engine' ); ?></strong>
									<span><?php printf( esc_html__( 'Best Score: %s Marks (%s%%)', 'indianagri-quiz-engine' ), $leaderboard_data['current_user_rank']['score'], $leaderboard_data['current_user_rank']['percentage'] ); ?></span>
								</div>
							</div>
						<?php endif; ?>

						<div class="iaq-table-responsive">
							<table class="iaq-leaderboard-table">
								<thead>
									<tr>
										<th style="width:70px;"><?php esc_html_e( 'Rank', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Candidate Pseudonym', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Best Score', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Accuracy %', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Time Taken', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Tests Taken', 'indianagri-quiz-engine' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php if ( ! empty( $leaderboard_data['entries'] ) ) : ?>
										<?php foreach ( $leaderboard_data['entries'] as $entry ) : ?>
											<tr class="<?php echo ! empty( $entry['is_current_user'] ) ? 'is-current-user-row' : ''; ?>">
												<td>
													<?php if ( 1 === $entry['rank'] ) : ?>
														<span class="iaq-rank-medal is-gold">🥇 1</span>
													<?php elseif ( 2 === $entry['rank'] ) : ?>
														<span class="iaq-rank-medal is-silver">🥈 2</span>
													<?php elseif ( 3 === $entry['rank'] ) : ?>
														<span class="iaq-rank-medal is-bronze">🥉 3</span>
													<?php else : ?>
														<span class="iaq-rank-num">#<?php echo esc_html( $entry['rank'] ); ?></span>
													<?php endif; ?>
												</td>
												<td>
													<strong><?php echo esc_html( $entry['display_name'] ); ?></strong>
													<?php if ( ! empty( $entry['is_current_user'] ) ) : ?>
														<span class="iaq-pill iaq-pill-free" style="margin-left:6px;"><?php esc_html_e( 'You', 'indianagri-quiz-engine' ); ?></span>
													<?php endif; ?>
												</td>
												<td><strong><?php echo esc_html( $entry['score'] ); ?></strong></td>
												<td><?php echo esc_html( $entry['percentage'] ); ?>%</td>
												<td>
													<?php
													$mins = floor( $entry['time_spent_seconds'] / 60 );
													$secs = $entry['time_spent_seconds'] % 60;
													echo esc_html( sprintf( '%dm %02ds', $mins, $secs ) );
													?>
												</td>
												<td><?php echo esc_html( $entry['total_attempts'] ); ?></td>
											</tr>
										<?php endforeach; ?>
									<?php else : ?>
										<tr>
											<td colspan="6" style="text-align:center;padding:36px;color:#64748b;">
												<div style="font-size:28px;margin-bottom:8px;">🏆</div>
												<div style="font-size:15px;font-weight:700;color:#0f172a;margin-bottom:4px;">
													<?php esc_html_e( 'No student rankings recorded yet.', 'indianagri-quiz-engine' ); ?>
												</div>
												<p style="margin:0 0 14px 0;font-size:13px;">
													<?php esc_html_e( 'Be the first candidate to complete a full mock examination and earn your spot on the leaderboard!', 'indianagri-quiz-engine' ); ?>
												</p>
												<a href="#iaq-pane-mock-tests" class="iaq-btn-action-primary iaq-dash-nav-trigger" data-target="mock-tests" style="font-size:12px;padding:7px 16px;">
													<?php esc_html_e( 'Take Mock Examination', 'indianagri-quiz-engine' ); ?> &rarr;
												</a>
											</td>
										</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<!-- VIEW PANE: PROFILE & ACCOUNT (INTEGRATED) -->
				<div class="iaq-dashboard-view-pane <?php echo 'profile' === $active_tab ? 'is-active' : ''; ?>" id="iaq-pane-profile">
					<div class="iaq-pane-content-card" style="padding: 26px;">
						<div class="iaq-pane-head-row" style="margin-bottom: 24px;">
							<div>
								<h3 class="iaq-pane-title"><?php esc_html_e( 'Candidate Profile & Account Settings', 'indianagri-quiz-engine' ); ?></h3>
								<p class="iaq-pane-subtitle"><?php esc_html_e( 'Review your student enrollment details, academic stats, and active membership entitlements.', 'indianagri-quiz-engine' ); ?></p>
							</div>
							<button type="button" class="iaq-btn-back-dash iaq-dash-nav-trigger" data-target="dashboard">
								&larr; <?php esc_html_e( 'Back to Dashboard', 'indianagri-quiz-engine' ); ?>
							</button>
						</div>

						<!-- Profile Top Hero Banner -->
						<div style="background:linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);border:1px solid #bbf7d0;border-radius:12px;padding:24px;display:flex;align-items:center;gap:20px;margin-bottom:24px;flex-wrap:wrap;">
							<div style="width:68px;height:68px;border-radius:50%;background:#117E3A;color:#fff;font-size:24px;font-weight:900;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(17,126,58,0.25);">
								<?php echo esc_html( $user_initials ); ?>
							</div>
							<div style="flex:1;min-width:200px;">
								<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
									<h3 style="margin:0;font-size:20px;font-weight:800;color:#0f172a;"><?php echo esc_html( $display_name ); ?></h3>
									<span class="iaq-tag-pill is-type-practice" style="background:#dcfce7;color:#166534;font-weight:700;"><?php esc_html_e( 'Enrolled Aspirant', 'indianagri-quiz-engine' ); ?></span>
								</div>
								<div style="font-size:13px;color:#475569;">
									<?php echo esc_html( ! empty( $current_user->user_email ) ? $current_user->user_email : __( 'Email not specified', 'indianagri-quiz-engine' ) ); ?>
								</div>
							</div>
							<div style="text-align:right;">
								<a href="#iaq-pane-subscriptions" class="iaq-btn-action-primary iaq-dash-nav-trigger" data-target="subscriptions" style="font-size:12px;padding:7px 14px;">
									<span class="dashicons dashicons-admin-generic" style="font-size:14px;vertical-align:middle;"></span> <?php esc_html_e( 'Subscription Info', 'indianagri-quiz-engine' ); ?>
								</a>
							</div>
						</div>

						<!-- Profile Data Grid -->
						<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:18px;margin-bottom:24px;">
							<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;">
								<div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;"><?php esc_html_e( 'Username', 'indianagri-quiz-engine' ); ?></div>
								<div style="font-size:15px;font-weight:700;color:#0f172a;"><?php echo esc_html( ! empty( $current_user->user_login ) ? $current_user->user_login : 'student' ); ?></div>
							</div>

							<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;">
								<div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;"><?php esc_html_e( 'Subscription Tier', 'indianagri-quiz-engine' ); ?></div>
								<div style="font-size:15px;font-weight:700;color:<?php echo $is_subscribed ? '#16a34a' : '#d97706'; ?>;">
									<?php echo esc_html( $sub_plan_name ); ?>
								</div>
							</div>

							<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;">
								<div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;"><?php esc_html_e( 'Access Expiry', 'indianagri-quiz-engine' ); ?></div>
								<div style="font-size:15px;font-weight:700;color:#0f172a;"><?php echo esc_html( $sub_valid_until ); ?></div>
							</div>

							<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;">
								<div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;"><?php esc_html_e( 'Academic Rank', 'indianagri-quiz-engine' ); ?></div>
								<div style="font-size:15px;font-weight:700;color:#0f172a;">
									<?php echo ! empty( $leaderboard_data['current_user_rank']['rank'] ) ? '#' . esc_html( $leaderboard_data['current_user_rank']['rank'] ) : esc_html__( 'Unranked (Take 1 Mock Test)', 'indianagri-quiz-engine' ); ?>
								</div>
							</div>
						</div>

						<!-- Account Security and Signout Bar -->
						<div style="display:flex;justify-content:space-between;align-items:center;padding-top:20px;border-top:1px solid #e2e8f0;flex-wrap:wrap;gap:12px;">
							<div style="font-size:13px;color:#64748b;">
								<?php esc_html_e( 'Logged in as verified IndianAgriExam candidate.', 'indianagri-quiz-engine' ); ?>
							</div>
							<a href="<?php echo esc_url( function_exists( 'wp_logout_url' ) ? wp_logout_url( get_permalink() ) : '#' ); ?>" class="iaq-btn-action-primary is-danger" style="text-decoration:none;font-size:13px;padding:8px 18px;display:inline-flex;align-items:center;gap:6px;">
								<span class="dashicons dashicons-exit"></span> <?php esc_html_e( 'Log Out of Portal', 'indianagri-quiz-engine' ); ?>
							</a>
						</div>
					</div>
				</div>

			</main>
		</div><!-- /iaq-dash-body-layout -->

	<?php endif; ?>

	</div><!-- /iaq-student-dashboard -->
</div><!-- /iaq-student-portal -->
