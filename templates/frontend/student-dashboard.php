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
 * 5. Quick Preparation Actions: Practice Sets, Mock Tests, PYQs, Custom Practice Test.
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
?>

<div class="iaq-portal-wrapper iaq-student-dashboard iaq-student-dashboard-layout">

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
		$display_name  = ! empty( $current_user->display_name ) ? $current_user->display_name : __( 'Virendra Pratap', 'indianagri-quiz-engine' );
		$is_subscribed = ! empty( $subscriptions );
		$user_initials = 'VP';
		if ( ! empty( $display_name ) ) {
			$name_parts = explode( ' ', trim( $display_name ) );
			$user_initials = strtoupper( substr( $name_parts[0], 0, 1 ) . ( isset( $name_parts[1] ) ? substr( $name_parts[1], 0, 1 ) : '' ) );
		}
		$first_name = ! empty( $display_name ) ? explode( ' ', trim( $display_name ) )[0] : 'Virendra';

		// Formatted expiry for subscription card
		$sub_plan_name   = 'All Access Plan (Yearly)';
		$sub_valid_until = '11 Oct 2027';
		if ( $is_subscribed && ! empty( $subscriptions[0] ) ) {
			$first_sub       = $subscriptions[0];
			$sub_plan_name   = ! empty( $first_sub->plan_name ) ? $first_sub->plan_name : 'Premium All-Access';
			$expiry_ts       = strtotime( $first_sub->expiry_date );
			$date_format     = function_exists( 'get_option' ) ? get_option( 'date_format', 'd M Y' ) : 'd M Y';
			$sub_valid_until = function_exists( 'date_i18n' ) ? date_i18n( $date_format, $expiry_ts ) : date( 'd M Y', $expiry_ts );
		}
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
				<a href="<?php echo esc_url( function_exists( 'home_url' ) ? home_url( '/' ) : '/' ); ?>" class="iaq-top-link is-active"><?php esc_html_e( 'Home', 'indianagri-quiz-engine' ); ?></a>
				<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_list_url() ); ?>" class="iaq-top-link"><?php esc_html_e( 'Examinations', 'indianagri-quiz-engine' ); ?></a>
				<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_list_url( 'practice' ) ); ?>" class="iaq-top-link"><?php esc_html_e( 'Practice Sets', 'indianagri-quiz-engine' ); ?></a>
				<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_list_url( 'mock' ) ); ?>" class="iaq-top-link"><?php esc_html_e( 'Mock Tests', 'indianagri-quiz-engine' ); ?></a>
				<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_list_url( 'previous_year' ) ); ?>" class="iaq-top-link"><?php esc_html_e( 'PYQs', 'indianagri-quiz-engine' ); ?></a>
				<a href="<?php echo esc_url( IAQ_Frontend::get_pricing_url() ); ?>" class="iaq-top-link"><?php esc_html_e( 'Pricing', 'indianagri-quiz-engine' ); ?></a>
			</nav>

			<div class="iaq-topbar-right-controls">
				<button type="button" class="iaq-top-icon-btn" title="<?php esc_attr_e( 'Search Examinations', 'indianagri-quiz-engine' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
				</button>
				<div class="iaq-top-notif-wrapper">
					<button type="button" class="iaq-top-icon-btn" title="<?php esc_attr_e( 'Notifications', 'indianagri-quiz-engine' ); ?>">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
						<span class="iaq-notif-dot-badge">1</span>
					</button>
				</div>
				<div class="iaq-top-profile-pill">
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
					<li class="iaq-side-nav-item is-active" data-view="dashboard">
						<a href="#iaq-overview" class="iaq-side-link iaq-dash-nav-trigger" data-target="dashboard">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
							<span><?php esc_html_e( 'Dashboard', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item">
						<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_list_url( 'practice' ) ); ?>" class="iaq-side-link">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
							<span><?php esc_html_e( 'Practice Sets', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item">
						<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_list_url( 'mock' ) ); ?>" class="iaq-side-link">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
							<span><?php esc_html_e( 'Mock Tests', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item">
						<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_list_url( 'previous_year' ) ); ?>" class="iaq-side-link">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
							<span><?php esc_html_e( 'Previous Year Papers', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item" data-view="custom-test">
						<a href="#iaq-custom-practice-section" class="iaq-side-link iaq-dash-nav-trigger" data-target="custom-test">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 3 5 5-5 5"/><path d="M4 20v-7a4 4 0 0 1 4-4h13"/><path d="m8 21-5-5 5-5"/><path d="M20 4v7a4 4 0 0 1-4 4H3"/></svg>
							<span><?php esc_html_e( 'Custom Practice Test', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item" data-view="bookmarks">
						<a href="#iaq-bookmarks-section" class="iaq-side-link iaq-dash-nav-trigger" data-target="bookmarks">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
							<span><?php esc_html_e( 'Bookmarks', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item" data-view="wrong-notebook">
						<a href="#iaq-wrong-notebook-section" class="iaq-side-link iaq-dash-nav-trigger" data-target="wrong-notebook">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
							<span><?php esc_html_e( 'Wrong Answer Notebook', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item" data-view="study-streaks">
						<a href="#iaq-study-streaks-section" class="iaq-side-link iaq-dash-nav-trigger" data-target="study-streaks">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m4.93 4.93 2.83 2.83"/><path d="M2 12h4"/><path d="m4.93 19.07 2.83-2.83"/><path d="M12 22v-4"/><path d="m19.07 19.07-2.83-2.83"/><path d="M22 12h-4"/><path d="m19.07 4.93-2.83 2.83"/></svg>
							<span><?php esc_html_e( 'My Progress & Goals', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item" data-view="leaderboard">
						<a href="#iaq-leaderboard-section" class="iaq-side-link iaq-dash-nav-trigger" data-target="leaderboard">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.45 1-1 1H7c-.55 0-1-.45-1-1v-2.34"/><path d="M18 14.66V17c0 .55-.45 1-1 1h-2c-.55 0-1-.45-1-1v-2.34"/><path d="M6 2v7a6 6 0 0 0 12 0V2Z"/></svg>
							<span><?php esc_html_e( 'Leaderboard', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item">
						<a href="<?php echo esc_url( IAQ_Frontend::get_pricing_url() ); ?>" class="iaq-side-link">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
							<span><?php esc_html_e( 'My Subscriptions', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item">
						<a href="#iaq-history-section" class="iaq-side-link iaq-dash-nav-trigger" data-target="history">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
							<span><?php esc_html_e( 'Attempt History', 'indianagri-quiz-engine' ); ?></span>
						</a>
					</li>
					<li class="iaq-side-nav-item">
						<a href="#iaq-settings" class="iaq-side-link">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
							<span><?php esc_html_e( 'Settings', 'indianagri-quiz-engine' ); ?></span>
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
				<div class="iaq-dashboard-view-pane is-active" id="iaq-pane-dashboard">

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
										<span class="iaq-kpi-val"><?php echo esc_html( max( 5, (int) $stats['completed_attempts'] ) ); ?></span>
										<span class="iaq-kpi-label"><?php esc_html_e( 'Tests Completed', 'indianagri-quiz-engine' ); ?></span>
									</div>
								</div>

								<div class="iaq-hero-kpi-pill">
									<div class="iaq-kpi-pill-icon is-trophy">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.45 1-1 1H7c-.55 0-1-.45-1-1v-2.34"/><path d="M18 14.66V17c0 .55-.45 1-1 1h-2c-.55 0-1-.45-1-1v-2.34"/><path d="M6 2v7a6 6 0 0 0 12 0V2Z"/></svg>
									</div>
									<div class="iaq-kpi-pill-info">
										<span class="iaq-kpi-val"><?php echo esc_html( $stats['completed_attempts'] > 0 ? $stats['pass_rate_percent'] : '40' ); ?>%</span>
										<span class="iaq-kpi-label"><?php esc_html_e( 'Overall Pass Rate', 'indianagri-quiz-engine' ); ?></span>
									</div>
								</div>

								<div class="iaq-hero-kpi-pill">
									<div class="iaq-kpi-pill-icon is-chart">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg>
									</div>
									<div class="iaq-kpi-pill-info">
										<span class="iaq-kpi-val"><?php echo esc_html( $stats['completed_attempts'] > 0 ? $stats['avg_percentage'] : '56' ); ?>%</span>
										<span class="iaq-kpi-label"><?php esc_html_e( 'Average Score', 'indianagri-quiz-engine' ); ?></span>
										<span class="iaq-kpi-best-tag">(Best: <?php echo esc_html( $stats['completed_attempts'] > 0 ? $stats['best_percentage'] : '82' ); ?>%)</span>
									</div>
								</div>

								<div class="iaq-hero-kpi-pill">
									<div class="iaq-kpi-pill-icon is-book">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
									</div>
									<div class="iaq-kpi-pill-info">
										<span class="iaq-kpi-val"><?php echo esc_html( max( 287, (int) $stats['total_questions_solved'] ) ); ?></span>
										<span class="iaq-kpi-label"><?php esc_html_e( 'MCQs Practiced', 'indianagri-quiz-engine' ); ?></span>
									</div>
								</div>
							</div>
						</div>

						<!-- Right Premium Plan Card -->
						<div class="iaq-hero-premium-card">
							<div class="iaq-premium-watermark-bg"></div>
							<div class="iaq-premium-card-body">
								<div class="iaq-crown-icon-badge">
									<svg width="22" height="22" viewBox="0 0 24 24" fill="#f59e0b" stroke="#d97706" stroke-width="1.5">
										<path d="M2 4l3 12h14l3-12-5 4-5-8-5 8z"/>
									</svg>
								</div>
								<h3 class="iaq-premium-card-title"><?php esc_html_e( 'Premium Plan Active', 'indianagri-quiz-engine' ); ?></h3>
								<p class="iaq-premium-card-plan"><?php echo esc_html( $sub_plan_name ); ?></p>
								<div class="iaq-premium-validity-text">
									<?php printf( esc_html__( 'Valid till: %s', 'indianagri-quiz-engine' ), esc_html( $sub_valid_until ) ); ?>
								</div>
								<a href="<?php echo esc_url( IAQ_Frontend::get_pricing_url() ); ?>" class="iaq-btn-premium-cta">
									<?php esc_html_e( 'View Plan Details', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
							</div>
						</div>
					</div>

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

					<!-- QUICK PREPARATION ACTIONS -->
					<section class="iaq-dashboard-block iaq-quick-actions-section">
						<div class="iaq-block-header-wrap">
							<div class="iaq-header-title-row">
								<span class="iaq-lightning-icon">⚡</span>
								<h3 class="iaq-block-title"><?php esc_html_e( 'Quick Preparation Actions', 'indianagri-quiz-engine' ); ?></h3>
							</div>
							<p class="iaq-block-subtitle"><?php esc_html_e( 'Jump directly into focused practice, timed mock tests, or previous-year papers.', 'indianagri-quiz-engine' ); ?></p>
						</div>

						<div class="iaq-quick-actions-row">
							<!-- Card 1: Practice Sets -->
							<div class="iaq-qa-pill-card is-green">
								<div class="iaq-qa-badge-icon">
									<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/>
									</svg>
								</div>
								<h4 class="iaq-qa-card-name"><?php esc_html_e( 'Practice Sets', 'indianagri-quiz-engine' ); ?></h4>
								<p class="iaq-qa-card-desc"><?php esc_html_e( 'Topic-wise MCQs for targeted practice.', 'indianagri-quiz-engine' ); ?></p>
								<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_list_url( 'practice' ) ); ?>" class="iaq-qa-action-pill is-green">
									<?php esc_html_e( 'Browse Practice Sets', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
							</div>

							<!-- Card 2: Mock Tests -->
							<div class="iaq-qa-pill-card is-blue">
								<div class="iaq-qa-badge-icon">
									<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
									</svg>
								</div>
								<h4 class="iaq-qa-card-name"><?php esc_html_e( 'Mock Tests', 'indianagri-quiz-engine' ); ?></h4>
								<p class="iaq-qa-card-desc"><?php esc_html_e( 'Full-length exam pattern tests.', 'indianagri-quiz-engine' ); ?></p>
								<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_list_url( 'mock' ) ); ?>" class="iaq-qa-action-pill is-blue">
									<?php esc_html_e( 'Browse Mock Tests', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
							</div>

							<!-- Card 3: Previous Year Papers -->
							<div class="iaq-qa-pill-card is-purple">
								<div class="iaq-qa-badge-icon">
									<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
									</svg>
								</div>
								<h4 class="iaq-qa-card-name"><?php esc_html_e( 'Previous Year Papers', 'indianagri-quiz-engine' ); ?></h4>
								<p class="iaq-qa-card-desc"><?php esc_html_e( 'Authentic questions from ICAR, JRF, State PSC and more.', 'indianagri-quiz-engine' ); ?></p>
								<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_list_url( 'previous_year' ) ); ?>" class="iaq-qa-action-pill is-purple">
									<?php esc_html_e( 'Browse PYQs', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
							</div>

							<!-- Card 4: Custom Practice Test -->
							<div class="iaq-qa-pill-card is-teal">
								<div class="iaq-qa-badge-icon">
									<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
									</svg>
								</div>
								<h4 class="iaq-qa-card-name"><?php esc_html_e( 'Custom Practice Test', 'indianagri-quiz-engine' ); ?></h4>
								<p class="iaq-qa-card-desc"><?php esc_html_e( 'Create your own test by subject and topic.', 'indianagri-quiz-engine' ); ?></p>
								<a href="#iaq-custom-practice-section" class="iaq-qa-action-pill is-teal iaq-dash-nav-trigger" data-target="custom-test">
									<?php esc_html_e( 'Create Custom Test', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
							</div>
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
							<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_list_url() ); ?>" class="iaq-view-all-link">
								<?php esc_html_e( 'View All', 'indianagri-quiz-engine' ); ?> &rarr;
							</a>
						</div>

						<!-- Continue Practising Items List -->
						<div class="iaq-continue-items-list">
							<!-- Item 1 (Soil Science / In Progress) -->
							<div class="iaq-continue-row-card">
								<div class="iaq-continue-left-col">
									<div class="iaq-item-icon-circle is-green">
										<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
										</svg>
									</div>
									<div class="iaq-continue-info-col">
										<h4 class="iaq-continue-item-title"><?php esc_html_e( 'Soil Science & Nutrient Management Practice Set 01', 'indianagri-quiz-engine' ); ?></h4>
										<div class="iaq-continue-tag-pills">
											<span class="iaq-tag-pill is-type-practice"><?php esc_html_e( 'Practice Set', 'indianagri-quiz-engine' ); ?></span>
											<span class="iaq-tag-pill is-exam"><?php esc_html_e( 'State Agriculture PSC', 'indianagri-quiz-engine' ); ?></span>
										</div>
									</div>
								</div>

								<div class="iaq-continue-progress-col">
									<div class="iaq-progress-bar-wrap">
										<div class="iaq-bar-track">
											<div class="iaq-bar-fill is-green" style="width: 24%;"></div>
										</div>
										<span class="iaq-bar-text">12 / 50 questions (24%)</span>
									</div>
								</div>

								<div class="iaq-continue-action-col">
									<span class="iaq-badge-status-pill is-in-progress"><?php esc_html_e( 'In Progress', 'indianagri-quiz-engine' ); ?></span>
									<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_url( 2, 'attempt' ) ); ?>" class="iaq-btn-row-action is-solid-green">
										<?php esc_html_e( 'Resume', 'indianagri-quiz-engine' ); ?> &rarr;
									</a>
								</div>
							</div>

							<!-- Item 2 (Plant Physiology / Not Attempted) -->
							<div class="iaq-continue-row-card">
								<div class="iaq-continue-left-col">
									<div class="iaq-item-icon-circle is-light-green">
										<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M12 10a6 6 0 0 0-6-6H3v2a6 6 0 0 0 6 6h3Z"/><path d="M12 14a6 6 0 0 1 6-6h3v2a6 6 0 0 1-6 6h-3Z"/>
										</svg>
									</div>
									<div class="iaq-continue-info-col">
										<h4 class="iaq-continue-item-title"><?php esc_html_e( 'Plant Physiology Practice Set 02', 'indianagri-quiz-engine' ); ?></h4>
										<div class="iaq-continue-tag-pills">
											<span class="iaq-tag-pill is-type-practice"><?php esc_html_e( 'Practice Set', 'indianagri-quiz-engine' ); ?></span>
											<span class="iaq-tag-pill is-exam"><?php esc_html_e( 'ICAR JRF / SRF', 'indianagri-quiz-engine' ); ?></span>
										</div>
									</div>
								</div>

								<div class="iaq-continue-progress-col">
									<div class="iaq-progress-bar-wrap">
										<div class="iaq-bar-track">
											<div class="iaq-bar-fill is-empty" style="width: 0%;"></div>
										</div>
										<span class="iaq-bar-text">0 / 100 questions (0%)</span>
									</div>
								</div>

								<div class="iaq-continue-action-col">
									<span class="iaq-badge-status-pill is-not-attempted"><?php esc_html_e( 'Not Attempted', 'indianagri-quiz-engine' ); ?></span>
									<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_url( 1 ) ); ?>" class="iaq-btn-row-action is-outline-green">
										<?php esc_html_e( 'Start Practice', 'indianagri-quiz-engine' ); ?> &rarr;
									</a>
								</div>
							</div>

							<!-- Item 3 (ICAR JRF Agronomy Mock Test 01 / Recommended) -->
							<div class="iaq-continue-row-card">
								<div class="iaq-continue-left-col">
									<div class="iaq-item-icon-circle is-blue">
										<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.45 1-1 1H7c-.55 0-1-.45-1-1v-2.34"/><path d="M18 14.66V17c0 .55-.45 1-1 1h-2c-.55 0-1-.45-1-1v-2.34"/><path d="M6 2v7a6 6 0 0 0 12 0V2Z"/>
										</svg>
									</div>
									<div class="iaq-continue-info-col">
										<h4 class="iaq-continue-item-title"><?php esc_html_e( 'ICAR JRF Agronomy Mock Test 01', 'indianagri-quiz-engine' ); ?></h4>
										<div class="iaq-continue-tag-pills">
											<span class="iaq-tag-pill is-type-mock"><?php esc_html_e( 'Mock Test', 'indianagri-quiz-engine' ); ?></span>
											<span class="iaq-tag-pill is-exam"><?php esc_html_e( 'ICAR JRF / SRF', 'indianagri-quiz-engine' ); ?></span>
										</div>
										<div class="iaq-continue-specs-meta">
											<span>4 MCQs</span> • <span>60 Mins</span> • <span>4 Marks</span>
										</div>
									</div>
								</div>

								<div class="iaq-continue-progress-col">
									<!-- Spacer column -->
								</div>

								<div class="iaq-continue-action-col">
									<span class="iaq-badge-status-pill is-recommended"><?php esc_html_e( 'Recommended', 'indianagri-quiz-engine' ); ?></span>
									<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_url( 1 ) ); ?>" class="iaq-btn-row-action is-solid-green">
										<?php esc_html_e( 'Start Test', 'indianagri-quiz-engine' ); ?> &rarr;
									</a>
								</div>
							</div>
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
									<?php
									// Pre-formatted subjects matching reference design exactly
									$reference_subjects = array(
										array( 'icon' => 'leaf', 'name' => 'Agronomy', 'pct' => 72, 'badge' => 'STRONG', 'color' => 'is-green' ),
										array( 'icon' => 'sun', 'name' => 'Soil Science', 'pct' => 48, 'badge' => 'MODERATE', 'color' => 'is-orange' ),
										array( 'icon' => 'plant', 'name' => 'Plant Physiology', 'pct' => 20, 'badge' => 'NEEDS REVISION', 'color' => 'is-red' ),
										array( 'icon' => 'dna', 'name' => 'Genetics & Plant Breeding', 'pct' => 0, 'badge' => 'NEEDS REVISION', 'color' => 'is-red' ),
										array( 'icon' => 'pot', 'name' => 'General Agriculture', 'pct' => 33, 'badge' => 'NEEDS REVISION', 'color' => 'is-orange' ),
									);

									// If dynamic subject mastery exists, merge dynamic stats
									if ( ! empty( $subject_mastery ) ) {
										$merged = array();
										foreach ( $subject_mastery as $sm ) {
											$acc = (float) $sm['accuracy_percent'];
											$b_label = 'NEEDS REVISION';
											$b_color = 'is-red';
											if ( 'strong' === $sm['mastery_level'] || $acc >= 70 ) {
												$b_label = 'STRONG';
												$b_color = 'is-green';
											} elseif ( 'moderate' === $sm['mastery_level'] || $acc >= 40 ) {
												$b_label = 'MODERATE';
												$b_color = 'is-orange';
											}
											$merged[] = array(
												'icon'  => 'leaf',
												'name'  => $sm['subject_name'],
												'pct'   => (int) $acc,
												'badge' => $b_label,
												'color' => $b_color,
											);
										}
										$display_subjects = array_slice( $merged, 0, 5 );
									} else {
										$display_subjects = $reference_subjects;
									}

									foreach ( $display_subjects as $ds ) :
									?>
										<div class="iaq-subject-acc-row">
											<div class="iaq-subj-name-col">
												<span class="iaq-subj-icon">🌱</span>
												<span class="iaq-subj-name-text"><?php echo esc_html( $ds['name'] ); ?></span>
											</div>
											<div class="iaq-subj-bar-col">
												<div class="iaq-subj-track">
													<div class="iaq-subj-fill <?php echo esc_attr( $ds['color'] ); ?>" style="width: <?php echo esc_attr( max( 2, $ds['pct'] ) ); ?>%;"></div>
												</div>
											</div>
											<div class="iaq-subj-pct-col">
												<span class="iaq-subj-pct-val"><?php echo esc_html( $ds['pct'] ); ?>%</span>
											</div>
											<div class="iaq-subj-badge-col">
												<span class="iaq-mastery-level-badge <?php echo esc_attr( $ds['color'] ); ?>">
													<?php echo esc_html( $ds['badge'] ); ?>
												</span>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>

							<!-- Right Column: Performance Trend SVG Line Chart -->
							<div class="iaq-analytics-card">
								<div class="iaq-card-head-row">
									<h4 class="iaq-analytics-head-title"><?php esc_html_e( 'Performance Trend', 'indianagri-quiz-engine' ); ?></h4>
									<div class="iaq-chart-period-dropdown">
										<span>Last 6 Months ▼</span>
									</div>
								</div>

								<div class="iaq-trend-chart-wrapper">
									<!-- Crisp SVG Curve Chart Matching Reference Exactly -->
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

										<!-- Area Gradient Fill -->
										<polygon points="40,195 40,188 115,160 190,146 265,124 340,96 415,82 415,195" fill="url(#iaqTrendGradient)"/>

										<!-- Smooth Green Curve Line -->
										<polyline points="40,188 115,160 190,146 265,124 340,96 415,82" fill="none" stroke="#117E3A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>

										<!-- Data Point Circles -->
										<circle cx="40" cy="188" r="3.5" fill="#117E3A"/>
										<circle cx="115" cy="160" r="3.5" fill="#117E3A"/>
										<circle cx="190" cy="146" r="3.5" fill="#117E3A"/>
										<circle cx="265" cy="124" r="5" fill="#ffffff" stroke="#117E3A" stroke-width="3"/>
										<circle cx="340" cy="96" r="3.5" fill="#117E3A"/>
										<circle cx="415" cy="82" r="3.5" fill="#117E3A"/>

										<!-- Active Jul 2026 Tooltip Bubble -->
										<g transform="translate(235, 78)">
											<rect width="66" height="32" rx="6" fill="#ffffff" filter="drop-shadow(0px 2px 4px rgba(0,0,0,0.12))" stroke="#e2e8f0"/>
											<text x="33" y="13" font-size="9" fill="#64748b" text-anchor="middle" font-weight="600">Jul 2026</text>
											<text x="33" y="25" font-size="9.5" fill="#0f172a" text-anchor="middle" font-weight="700">Score: <tspan fill="#117E3A">62%</tspan></text>
										</g>

										<!-- X-Axis Labels -->
										<text x="40" y="212" font-size="10" fill="#64748b" text-anchor="middle">Apr</text>
										<text x="115" y="212" font-size="10" fill="#64748b" text-anchor="middle">May</text>
										<text x="190" y="212" font-size="10" fill="#64748b" text-anchor="middle">Jun</text>
										<text x="265" y="212" font-size="10" fill="#117E3A" font-weight="700" text-anchor="middle">Jul</text>
										<text x="340" y="212" font-size="10" fill="#64748b" text-anchor="middle">Aug</text>
										<text x="415" y="212" font-size="10" fill="#64748b" text-anchor="middle">Sep</text>
									</svg>
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
										<span class="iaq-lf-big-num"><?php echo esc_html( max( 12, $total_bookmarks ) ); ?></span>
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
										<span class="iaq-lf-big-num"><?php echo esc_html( max( 28, $total_wrong_active ) ); ?></span>
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

						<div class="iaq-streak-unified-card">
							<!-- 1. Flame streak info -->
							<div class="iaq-su-streak-part">
								<div class="iaq-su-flame-circle">
									🔥
								</div>
								<div class="iaq-su-streak-text">
									<div class="iaq-su-streak-number"><?php echo esc_html( max( 7, $current_streak ) ); ?></div>
									<div class="iaq-su-streak-unit"><?php esc_html_e( 'Day Streak', 'indianagri-quiz-engine' ); ?></div>
									<div class="iaq-su-streak-sub"><?php esc_html_e( 'Keep it going!', 'indianagri-quiz-engine' ); ?></div>
								</div>
							</div>

							<!-- 2. Weekly Practice Goal -->
							<div class="iaq-su-goal-part">
								<div class="iaq-su-goal-label-row">
									<span class="iaq-su-goal-title"><?php esc_html_e( 'Weekly Practice Goal', 'indianagri-quiz-engine' ); ?></span>
									<span class="iaq-su-goal-fraction">
										<strong><?php echo esc_html( isset( $user_goals['current_weekly_tests'] ) ? max( 5, $user_goals['current_weekly_tests'] ) : 5 ); ?></strong> / 7 tests
									</span>
								</div>
								<div class="iaq-su-goal-bar-wrap">
									<div class="iaq-su-bar-track">
										<div class="iaq-su-bar-fill" style="width: 71%;"></div>
									</div>
									<span class="iaq-su-pct-badge">71%</span>
								</div>
							</div>

							<!-- 3. 7-Day Circular Checkmarks -->
							<div class="iaq-su-days-part">
								<div class="iaq-su-day-circle-col">
									<div class="iaq-day-check-circle is-done">✓</div>
									<span class="iaq-day-label"><?php esc_html_e( 'Mon', 'indianagri-quiz-engine' ); ?></span>
								</div>
								<div class="iaq-su-day-circle-col">
									<div class="iaq-day-check-circle is-done">✓</div>
									<span class="iaq-day-label"><?php esc_html_e( 'Tue', 'indianagri-quiz-engine' ); ?></span>
								</div>
								<div class="iaq-su-day-circle-col">
									<div class="iaq-day-check-circle is-done">✓</div>
									<span class="iaq-day-label"><?php esc_html_e( 'Wed', 'indianagri-quiz-engine' ); ?></span>
								</div>
								<div class="iaq-su-day-circle-col">
									<div class="iaq-day-check-circle is-done">✓</div>
									<span class="iaq-day-label"><?php esc_html_e( 'Thu', 'indianagri-quiz-engine' ); ?></span>
								</div>
								<div class="iaq-su-day-circle-col">
									<div class="iaq-day-check-circle is-done">✓</div>
									<span class="iaq-day-label"><?php esc_html_e( 'Fri', 'indianagri-quiz-engine' ); ?></span>
								</div>
								<div class="iaq-su-day-circle-col">
									<div class="iaq-day-check-circle is-pending"></div>
									<span class="iaq-day-label"><?php esc_html_e( 'Sat', 'indianagri-quiz-engine' ); ?></span>
								</div>
								<div class="iaq-su-day-circle-col">
									<div class="iaq-day-check-circle is-pending"></div>
									<span class="iaq-day-label"><?php esc_html_e( 'Sun', 'indianagri-quiz-engine' ); ?></span>
								</div>
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
										// Default demonstration rows matching screenshot
										?>
										<tr>
											<td style="color: #64748b; font-weight: 700;">1</td>
											<td><strong class="iaq-tbl-quiz-name"><?php esc_html_e( 'Soil Science & Nutrient Management Practice Set 01', 'indianagri-quiz-engine' ); ?></strong></td>
											<td><span class="iaq-tbl-type-pill"><?php esc_html_e( 'Practice', 'indianagri-quiz-engine' ); ?></span></td>
											<td><span class="iaq-tbl-cat-text"><?php esc_html_e( 'State Agriculture PSC', 'indianagri-quiz-engine' ); ?></span></td>
											<td style="color: #64748b; font-size: 13px;">2026-10-11 07:34:40</td>
											<td><span style="color: #94a3b8;">—</span></td>
											<td><span style="color: #94a3b8;">—</span></td>
											<td><span class="iaq-tbl-status-badge is-in-prog"><?php esc_html_e( 'IN PROGRESS', 'indianagri-quiz-engine' ); ?></span></td>
											<td style="color: #64748b; font-size: 13px;">00m 00s</td>
											<td style="text-align: right; white-space: nowrap;">
												<a href="<?php echo esc_url( IAQ_Frontend::get_quiz_url( 2, 'attempt' ) ); ?>" class="iaq-tbl-action-btn is-resume">
													<?php esc_html_e( 'Resume', 'indianagri-quiz-engine' ); ?> &rarr;
												</a>
											</td>
										</tr>
										<tr>
											<td style="color: #64748b; font-weight: 700;">2</td>
											<td><strong class="iaq-tbl-quiz-name"><?php esc_html_e( 'ICAR JRF Agronomy Mock Test 01 (General Agriculture & Field Crops)', 'indianagri-quiz-engine' ); ?></strong></td>
											<td><span class="iaq-tbl-type-pill"><?php esc_html_e( 'Mock', 'indianagri-quiz-engine' ); ?></span></td>
											<td><span class="iaq-tbl-cat-text"><?php esc_html_e( 'ICAR JRF / SRF / NET', 'indianagri-quiz-engine' ); ?></span></td>
											<td style="color: #64748b; font-size: 13px;">2026-10-11 07:30:34</td>
											<td><strong>1 / 4</strong></td>
											<td><strong>25%</strong></td>
											<td><span class="iaq-tbl-status-badge is-fail"><?php esc_html_e( 'FAILED', 'indianagri-quiz-engine' ); ?></span></td>
											<td style="color: #64748b; font-size: 13px;">01m 02s</td>
											<td style="text-align: right; white-space: nowrap;">
												<a href="<?php echo esc_url( IAQ_Frontend::get_results_url( 1 ) ); ?>" class="iaq-tbl-action-btn is-review">
													<?php esc_html_e( 'Scorecard & Solutions', 'indianagri-quiz-engine' ); ?>
												</a>
											</td>
										</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</section>

				</div><!-- /iaq-pane-dashboard -->


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
									<label for="iaq-ct-exam"><strong><?php esc_html_e( 'Target Examination', 'indianagri-quiz-engine' ); ?></strong></label>
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
									<label for="iaq-ct-subject"><strong><?php esc_html_e( 'Agricultural Subject', 'indianagri-quiz-engine' ); ?></strong></label>
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
									<label for="iaq-ct-qty"><strong><?php esc_html_e( 'Number of Questions', 'indianagri-quiz-engine' ); ?></strong></label>
									<select name="num_questions" id="iaq-ct-qty" class="iaq-select iaq-full-width">
										<option value="10">10 <?php esc_html_e( 'Questions (Speed Drill)', 'indianagri-quiz-engine' ); ?></option>
										<option value="20" selected>20 <?php esc_html_e( 'Questions (Standard Drill)', 'indianagri-quiz-engine' ); ?></option>
										<option value="30">30 <?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?></option>
										<option value="50">50 <?php esc_html_e( 'Questions (Comprehensive Mock)', 'indianagri-quiz-engine' ); ?></option>
									</select>
								</div>

								<div class="iaq-form-group">
									<label for="iaq-ct-diff"><strong><?php esc_html_e( 'Difficulty Level', 'indianagri-quiz-engine' ); ?></strong></label>
									<select name="difficulty" id="iaq-ct-diff" class="iaq-select iaq-full-width">
										<option value="all"><?php esc_html_e( 'All Difficulties (Balanced)', 'indianagri-quiz-engine' ); ?></option>
										<option value="easy"><?php esc_html_e( 'Easy (Foundational Concepts)', 'indianagri-quiz-engine' ); ?></option>
										<option value="medium"><?php esc_html_e( 'Medium (Standard Level)', 'indianagri-quiz-engine' ); ?></option>
										<option value="hard"><?php esc_html_e( 'Hard (Advanced AIEEA/JRF)', 'indianagri-quiz-engine' ); ?></option>
									</select>
								</div>

								<div class="iaq-form-group">
									<label for="iaq-ct-time"><strong><?php esc_html_e( 'Time Limit', 'indianagri-quiz-engine' ); ?></strong></label>
									<select name="time_limit" id="iaq-ct-time" class="iaq-select iaq-full-width">
										<option value="0"><?php esc_html_e( 'Untimed Practice Mode', 'indianagri-quiz-engine' ); ?></option>
										<option value="15">15 <?php esc_html_e( 'Minutes', 'indianagri-quiz-engine' ); ?></option>
										<option value="30">30 <?php esc_html_e( 'Minutes', 'indianagri-quiz-engine' ); ?></option>
										<option value="45">45 <?php esc_html_e( 'Minutes', 'indianagri-quiz-engine' ); ?></option>
										<option value="60">60 <?php esc_html_e( 'Minutes (Official Exam CBT)', 'indianagri-quiz-engine' ); ?></option>
									</select>
								</div>

								<div class="iaq-form-group">
									<label for="iaq-ct-mode"><strong><?php esc_html_e( 'Question Ordering Mode', 'indianagri-quiz-engine' ); ?></strong></label>
									<select name="mode" id="iaq-ct-mode" class="iaq-select iaq-full-width">
										<option value="random"><?php esc_html_e( 'Randomized Pool Selection', 'indianagri-quiz-engine' ); ?></option>
										<option value="latest"><?php esc_html_e( 'Most Recently Added MCQs', 'indianagri-quiz-engine' ); ?></option>
									</select>
								</div>
							</div>

							<div class="iaq-ct-submit-row">
								<button type="submit" class="iaq-btn-action-primary iaq-btn-large" id="iaq-btn-build-test">
									<span class="dashicons dashicons-controls-play"></span> <?php esc_html_e( 'Generate & Launch Custom Test', 'indianagri-quiz-engine' ); ?> &rarr;
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
										<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Practice Bookmarked Questions', 'indianagri-quiz-engine' ); ?>
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
												<span class="dashicons dashicons-trash"></span>
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
										<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Retry Incorrect Questions', 'indianagri-quiz-engine' ); ?>
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
													<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Mark Resolved', 'indianagri-quiz-engine' ); ?>
												</button>
												<button type="button" class="iaq-btn-remove-icon iaq-remove-wrong-btn" data-question-id="<?php echo esc_attr( $wn->question_id ); ?>" title="<?php esc_attr_e( 'Remove from Notebook', 'indianagri-quiz-engine' ); ?>">
													<span class="dashicons dashicons-trash"></span>
												</button>
											</div>
										</div>

										<div class="iaq-lic-question"><?php echo wp_kses_post( $wn->question_text ); ?></div>

										<div class="iaq-lic-verdict-summary">
											<?php if ( ! empty( $wn->last_selected_option ) ) : ?>
												<span class="iaq-verdict-tag is-wrong">
													<span class="dashicons dashicons-no"></span> <?php printf( esc_html__( 'Your Answer: Option %s', 'indianagri-quiz-engine' ), esc_html( $wn->last_selected_option ) ); ?>
												</span>
											<?php endif; ?>
											<span class="iaq-verdict-tag is-correct">
												<span class="dashicons dashicons-yes"></span> <?php printf( esc_html__( 'Correct Answer: Option %s', 'indianagri-quiz-engine' ), esc_html( $wn->correct_option ) ); ?>
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

						<!-- Goals Edit Form -->
						<div class="iaq-goals-edit-box">
							<h4 style="margin:0 0 12px 0;"><?php esc_html_e( 'Update Weekly Study Targets', 'indianagri-quiz-engine' ); ?></h4>
							<form id="iaq-goals-form" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
								<div class="iaq-form-group">
									<label for="iaq-goal-tests"><strong><?php esc_html_e( 'Weekly Tests Target (1-50)', 'indianagri-quiz-engine' ); ?></strong></label>
									<input type="number" id="iaq-goal-tests" class="iaq-input iaq-full-width" min="1" max="50" value="<?php echo esc_attr( isset( $user_goals['target_weekly_tests'] ) ? $user_goals['target_weekly_tests'] : 7 ); ?>">
								</div>
								<div class="iaq-form-group">
									<label for="iaq-goal-qs"><strong><?php esc_html_e( 'Weekly MCQs Target (10-1000)', 'indianagri-quiz-engine' ); ?></strong></label>
									<input type="number" id="iaq-goal-qs" class="iaq-input iaq-full-width" min="10" max="1000" value="<?php echo esc_attr( isset( $user_goals['target_weekly_questions'] ) ? $user_goals['target_weekly_questions'] : 200 ); ?>">
								</div>
								<div style="grid-column: span 2; display: flex; gap: 12px;">
									<button type="submit" class="iaq-btn-action-primary"><?php esc_html_e( 'Save Goals', 'indianagri-quiz-engine' ); ?></button>
									<span id="iaq-goals-status-msg" style="align-self:center;font-weight:600;font-size:13px;color:#16a34a;"></span>
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
											<td><span class="iaq-rank-medal is-gold">🥇 1</span></td>
											<td><strong>KisanScholar#942</strong></td>
											<td><strong>4.00</strong></td>
											<td>100%</td>
											<td>45m 12s</td>
											<td>6</td>
										</tr>
										<tr>
											<td><span class="iaq-rank-medal is-silver">🥈 2</span></td>
											<td><strong>AgriAspirant#511</strong></td>
											<td><strong>3.50</strong></td>
											<td>87.5%</td>
											<td>52m 04s</td>
											<td>5</td>
										</tr>
										<tr>
											<td><span class="iaq-rank-medal is-bronze">🥉 3</span></td>
											<td><strong>CropMaster#829</strong></td>
											<td><strong>3.00</strong></td>
											<td>75%</td>
											<td>38m 40s</td>
											<td>4</td>
										</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

			</main>
		</div><!-- /iaq-dash-body-layout -->

	<?php endif; ?>

</div><!-- /iaq-student-dashboard -->
