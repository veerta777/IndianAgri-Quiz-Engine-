<?php
/**
 * Frontend: Mock Tests Module Multi-View Template.
 * Implements the 10 Main Categories, Examinations, Subjects, Vacancies, States, and Mock Series.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id     = get_current_user_id();
$view        = isset( $view ) ? $view : 'landing';
$search_term = isset( $_GET['mt_search'] ) ? sanitize_text_field( wp_unslash( $_GET['mt_search'] ) ) : '';

$page_url = function( $subview = '', $params = array() ) {
	return IAQ_Mock_Tests::get_url( $subview, $params );
};
?>

<div class="iaq-mock-module-wrap" id="iaq-mock-tests-engine">

	<!-- Header Banner & Breadcrumbs -->
	<div class="iaq-mt-banner">
		<div class="iaq-mt-breadcrumbs">
			<a href="<?php echo esc_url( $page_url() ); ?>" class="iaq-mt-bc-item">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg> <?php esc_html_e( 'Mock Tests', 'indianagri-quiz-engine' ); ?>
			</a>

			<?php if ( ! empty( $category ) ) : ?>
				<span class="iaq-mt-bc-sep">&rsaquo;</span>
				<a href="<?php echo esc_url( $page_url( 'category', array( 'category_id' => $category->id ) ) ); ?>" class="iaq-mt-bc-item">
					<?php echo esc_html( $category->name ); ?>
				</a>
			<?php endif; ?>

			<?php if ( ! empty( $state ) ) : ?>
				<span class="iaq-mt-bc-sep">&rsaquo;</span>
				<span class="iaq-mt-bc-current"><?php echo esc_html( $state->name ); ?></span>
			<?php endif; ?>

			<?php if ( ! empty( $university ) ) : ?>
				<span class="iaq-mt-bc-sep">&rsaquo;</span>
				<span class="iaq-mt-bc-current"><?php echo esc_html( $university->name ); ?></span>
			<?php endif; ?>

			<?php if ( ! empty( $exam ) ) : ?>
				<span class="iaq-mt-bc-sep">&rsaquo;</span>
				<a href="<?php echo esc_url( $page_url( 'exam', array( 'category_id' => ! empty( $category ) ? $category->id : 0, 'exam_id' => $exam->id ) ) ); ?>" class="iaq-mt-bc-item">
					<?php echo esc_html( $exam->name ); ?>
				</a>
			<?php endif; ?>

			<?php if ( ! empty( $vacancy ) ) : ?>
				<span class="iaq-mt-bc-sep">&rsaquo;</span>
				<span class="iaq-mt-bc-current"><?php echo esc_html( $vacancy->title ); ?></span>
			<?php endif; ?>

			<?php if ( ! empty( $subject ) ) : ?>
				<span class="iaq-mt-bc-sep">&rsaquo;</span>
				<span class="iaq-mt-bc-current"><?php echo esc_html( $subject->name ); ?></span>
			<?php endif; ?>

			<?php if ( ! empty( $series ) ) : ?>
				<span class="iaq-mt-bc-sep">&rsaquo;</span>
				<span class="iaq-mt-bc-current"><?php echo esc_html( $series->title ); ?></span>
			<?php endif; ?>
		</div>

		<div class="iaq-mt-header-main">
			<div class="iaq-mt-badge"><?php esc_html_e( 'IndianAgriExam.com Mock Test Engine', 'indianagri-quiz-engine' ); ?></div>
			<h1 class="iaq-mt-title">
				<?php
				if ( 'landing' === $view ) {
					esc_html_e( 'Agricultural Mock Tests & National Test Series', 'indianagri-quiz-engine' );
				} elseif ( ! empty( $subject ) && ! empty( $exam ) ) {
					printf( esc_html__( '%s &rarr; %s Mock Tests', 'indianagri-quiz-engine' ), esc_html( $exam->name ), esc_html( $subject->name ) );
				} elseif ( ! empty( $vacancy ) ) {
					echo esc_html( $vacancy->title ) . ' ' . esc_html__( 'Mock Tests', 'indianagri-quiz-engine' );
				} elseif ( ! empty( $exam ) ) {
					echo esc_html( $exam->name ) . ' ' . esc_html__( 'Mock Test Portal', 'indianagri-quiz-engine' );
				} elseif ( ! empty( $state ) ) {
					echo esc_html( $state->name ) . ' ' . esc_html__( 'Agriculture Exams & Mock Tests', 'indianagri-quiz-engine' );
				} elseif ( ! empty( $subject ) ) {
					echo esc_html( $subject->name ) . ' ' . esc_html__( 'Mock Tests', 'indianagri-quiz-engine' );
				} elseif ( ! empty( $category ) ) {
					echo esc_html( $category->name );
				} else {
					esc_html_e( 'Mock Examination Series', 'indianagri-quiz-engine' );
				}
				?>
			</h1>
			<p class="iaq-mt-subtitle">
				<?php
				if ( 'landing' === $view ) {
					esc_html_e( 'Full-length, bilingual, timed online mock examinations curated for ICAR, CUET, UPSSSC, State PSCs, IBPS AFO, and University Entrances.', 'indianagri-quiz-engine' );
				} elseif ( ! empty( $category ) && ! empty( $category->description ) ) {
					echo esc_html( $category->description );
				} elseif ( ! empty( $exam ) && ! empty( $exam->description ) ) {
					echo esc_html( $exam->description );
				} else {
					esc_html_e( 'Timed mock tests with negative marking, bilingual support, and scientific solutions.', 'indianagri-quiz-engine' );
				}
				?>
			</p>
		</div>
	</div>

	<!-- Search & Filter Bar -->
	<div class="iaq-mt-search-bar">
		<form method="get" action="" class="iaq-mt-search-form">
			<?php if ( isset( $_GET['page'] ) ) : ?>
				<input type="hidden" name="page" value="<?php echo esc_attr( sanitize_key( $_GET['page'] ) ); ?>">
			<?php endif; ?>
			<?php if ( isset( $_GET['iaq_mt_view'] ) ) : ?>
				<input type="hidden" name="iaq_mt_view" value="<?php echo esc_attr( sanitize_key( $_GET['iaq_mt_view'] ) ); ?>">
			<?php endif; ?>
			<?php if ( ! empty( $category ) ) : ?>
				<input type="hidden" name="category_id" value="<?php echo esc_attr( $category->id ); ?>">
			<?php endif; ?>
			<?php if ( ! empty( $exam ) ) : ?>
				<input type="hidden" name="exam_id" value="<?php echo esc_attr( $exam->id ); ?>">
			<?php endif; ?>

			<div class="iaq-mt-search-input-wrap">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
				<input type="text" name="mt_search" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search mock tests by exam, subject, or keyword...', 'indianagri-quiz-engine' ); ?>" class="iaq-mt-search-input">
			</div>
			<button type="submit" class="iaq-btn iaq-btn-primary iaq-btn-search"><?php esc_html_e( 'Search', 'indianagri-quiz-engine' ); ?></button>
			<?php if ( ! empty( $search_term ) ) : ?>
				<a href="<?php echo esc_url( remove_query_arg( 'mt_search' ) ); ?>" class="iaq-btn iaq-btn-outline"><?php esc_html_e( 'Clear', 'indianagri-quiz-engine' ); ?></a>
			<?php endif; ?>
		</form>
	</div>

	<!-- VIEW 1: ROOT LANDING PAGE (10 MAIN CATEGORIES) -->
	<?php if ( 'landing' === $view ) : ?>
		<div class="iaq-mt-categories-section">
			<div class="iaq-mt-section-header">
				<h2><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#117E3A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px;flex-shrink:0;"><rect width="7" height="7" x="3" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="14" rx="1.5"/><rect width="7" height="7" x="3" y="14" rx="1.5"/></svg> <?php esc_html_e( 'Choose Your Examination Category', 'indianagri-quiz-engine' ); ?></h2>
				<p><?php esc_html_e( 'Select from the 10 national and state agricultural examination streams below to access dedicated full-length mock tests.', 'indianagri-quiz-engine' ); ?></p>
			</div>

			<div class="iaq-mt-categories-grid">
				<?php if ( ! empty( $categories ) ) : ?>
					<?php foreach ( $categories as $cat ) : ?>
						<div class="iaq-mt-category-card">
							<div class="iaq-mt-cat-header">
								<div class="iaq-mt-cat-icon">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
								</div>
								<?php if ( ! empty( $cat->badge_text ) ) : ?>
									<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $cat->badge_text ); ?></span>
								<?php endif; ?>
							</div>

							<div class="iaq-mt-cat-body">
								<h3 class="iaq-mt-cat-title">
									<a href="<?php echo esc_url( $page_url( 'category', array( 'category_id' => $cat->id ) ) ); ?>">
										<?php echo esc_html( $cat->name ); ?>
									</a>
								</h3>
								<p class="iaq-mt-cat-desc">
									<?php echo esc_html( $cat->description ); ?>
								</p>
							</div>

							<div class="iaq-mt-cat-footer">
								<a href="<?php echo esc_url( $page_url( 'category', array( 'category_id' => $cat->id ) ) ); ?>" class="iaq-btn iaq-btn-primary iaq-btn-cat">
									<span><?php esc_html_e( 'Explore Mock Tests', 'indianagri-quiz-engine' ); ?></span>
									&rarr;
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p><?php esc_html_e( 'No categories configured yet.', 'indianagri-quiz-engine' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

	<!-- VIEW 2: CATEGORY LEVEL (EXAMINATIONS / STATES / SUBJECTS / VACANCIES) -->
	<?php elseif ( 'category' === $view && ! empty( $category ) ) : ?>
		
		<?php
		$hierarchy_cfg = IAQ_Mock_Tests::get_category_hierarchy_config( $category->slug );
		?>

		<div class="iaq-mt-category-view">

			<!-- State-wise Browsing (Category 6) -->
			<?php if ( ! empty( $hierarchy_cfg['has_states'] ) && ! empty( $states ) ) : ?>
				<div class="iaq-mt-section-header">
					<h2><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#117E3A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px;flex-shrink:0;"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg> <?php esc_html_e( 'Select Target State', 'indianagri-quiz-engine' ); ?></h2>
					<p><?php esc_html_e( 'Choose a state to access its Public Service Commission and Agriculture Department recruitment mock tests.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<div class="iaq-mt-states-grid">
					<?php foreach ( $states as $st ) : ?>
						<a href="<?php echo esc_url( $page_url( 'state', array( 'category_id' => $category->id, 'state_id' => $st->id ) ) ); ?>" class="iaq-mt-state-card">
							<div class="iaq-mt-state-code"><?php echo esc_html( $st->code ); ?></div>
							<div class="iaq-mt-state-name"><?php echo esc_html( $st->name ); ?></div>
							<span class="iaq-mt-state-arrow">&rarr;</span>
						</a>
					<?php endforeach; ?>
				</div>

			<!-- University Entrance Browsing (Category 7) -->
			<?php elseif ( ! empty( $hierarchy_cfg['has_universities'] ) && ! empty( $universities ) ) : ?>
				<div class="iaq-mt-section-header">
					<h2><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#117E3A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px;flex-shrink:0;"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg> <?php esc_html_e( 'Select University Entrance Type', 'indianagri-quiz-engine' ); ?></h2>
					<p><?php esc_html_e( 'Choose State Agricultural Universities or entrance program level.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<div class="iaq-mt-universities-grid">
					<?php foreach ( $universities as $un ) : ?>
						<a href="<?php echo esc_url( $page_url( 'university', array( 'category_id' => $category->id, 'university_id' => $un->id ) ) ); ?>" class="iaq-mt-univ-card">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#117E3A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
							<div class="iaq-mt-univ-name"><?php echo esc_html( $un->name ); ?></div>
							<span class="iaq-mt-univ-arrow">&rarr;</span>
						</a>
					<?php endforeach; ?>
				</div>

			<!-- Subject-Wise Browsing (Category 8) -->
			<?php elseif ( ! empty( $hierarchy_cfg['has_subjects'] ) && empty( $hierarchy_cfg['has_exams'] ) && ! empty( $subjects ) ) : ?>
				<div class="iaq-mt-section-header">
					<h2><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><rect width="7" height="7" x="3" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="14" rx="1.5"/><rect width="7" height="7" x="3" y="14" rx="1.5"/></svg> <?php esc_html_e( 'Select Agricultural Discipline', 'indianagri-quiz-engine' ); ?></h2>
					<p><?php esc_html_e( 'Attempt full syllabus or subject mock tests in your specialized discipline.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<div class="iaq-mt-subjects-grid">
					<?php foreach ( $subjects as $sub ) : ?>
						<div class="iaq-mt-subject-card">
							<div class="iaq-mt-subject-top">
								<div class="iaq-mt-subject-icon">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
								</div>
							</div>
							<h3 class="iaq-mt-sub-title">
								<a href="<?php echo esc_url( $page_url( 'subject', array( 'category_id' => $category->id, 'subject_id' => $sub->id ) ) ); ?>" style="color:#0f172a;text-decoration:none;">
									<?php echo esc_html( $sub->name ); ?>
								</a>
							</h3>
							<?php if ( ! empty( $sub->description ) ) : ?>
								<p class="iaq-mt-sub-desc"><?php echo esc_html( wp_trim_words( $sub->description, 12 ) ); ?></p>
							<?php endif; ?>
							<div class="iaq-mt-subject-action">
								<a href="<?php echo esc_url( $page_url( 'subject', array( 'category_id' => $category->id, 'subject_id' => $sub->id ) ) ); ?>" class="iaq-btn iaq-btn-primary iaq-btn-exam iaq-full-width">
									<?php esc_html_e( 'View Mock Tests', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

			<!-- Examination-Based Browsing (Categories 1, 2, 3, 4, 5, 10) -->
			<?php elseif ( ! empty( $exams ) ) : ?>
				<div class="iaq-mt-section-header">
					<h2><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#117E3A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px;flex-shrink:0;"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg> <?php echo esc_html( $hierarchy_cfg['drill_label'] ); ?></h2>
					<p><?php esc_html_e( 'Select your target examination below to view its specific subjects, vacancies, or mock tests.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<div class="iaq-mt-exams-grid">
					<?php foreach ( $exams as $ex ) : ?>
						<div class="iaq-mt-exam-card">
							<div class="iaq-mt-exam-code"><?php echo esc_html( $ex->code ); ?></div>
							<h3 class="iaq-mt-exam-title">
								<a href="<?php echo esc_url( $page_url( 'exam', array( 'category_id' => $category->id, 'exam_id' => $ex->id ) ) ); ?>">
									<?php echo esc_html( $ex->name ); ?>
								</a>
							</h3>
							<?php if ( ! empty( $ex->description ) ) : ?>
								<p class="iaq-mt-exam-desc"><?php echo esc_html( wp_trim_words( $ex->description, 14 ) ); ?></p>
							<?php endif; ?>
							<a href="<?php echo esc_url( $page_url( 'exam', array( 'category_id' => $category->id, 'exam_id' => $ex->id ) ) ); ?>" class="iaq-btn iaq-btn-primary iaq-btn-exam">
								<?php esc_html_e( 'Select Examination &rarr;', 'indianagri-quiz-engine' ); ?>
							</a>
						</div>
					<?php endforeach; ?>
				</div>

			<!-- Directly Available Mock Tests (Category 9: General Agriculture or Flat Categories) -->
			<?php else : ?>
				<div class="iaq-mt-section-header">
					<h2><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#117E3A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px;flex-shrink:0;"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg> <?php esc_html_e( 'Available Mock Tests', 'indianagri-quiz-engine' ); ?></h2>
					<p><?php esc_html_e( 'Select a test below to begin your examination.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<?php if ( ! empty( $quizzes ) ) : ?>
					<div class="iaq-mt-tests-grid">
						<?php
						foreach ( $quizzes as $quiz ) {
							include IAQ_PLUGIN_DIR . 'templates/frontend/mock-test-card.php';
						}
						?>
					</div>
				<?php else : ?>
					<div class="iaq-mt-empty-state">
						<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
						<h3><?php esc_html_e( 'No Mock Tests Published Yet', 'indianagri-quiz-engine' ); ?></h3>
						<p><?php esc_html_e( 'Tests under this category will be published soon by the examination committee.', 'indianagri-quiz-engine' ); ?></p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

		</div>

	<!-- VIEW 3: EXAMINATION DRILL-DOWN (SUB-EXAMS, VACANCIES, SUBJECTS, OR TESTS) -->
	<?php elseif ( 'exam' === $view && ! empty( $exam ) ) : ?>

		<div class="iaq-mt-exam-drilldown-view">

			<!-- Sub-Exams (e.g. ASRB -> ARS and NET) -->
			<?php if ( ! empty( $sub_exams ) ) : ?>
				<div class="iaq-mt-section-header">
					<h2><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#117E3A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px;flex-shrink:0;"><circle cx="18" cy="18" r="3"/><circle cx="6" cy="6" r="3"/><circle cx="18" cy="6" r="3"/><path d="M6 9v12"/><path d="M9 6h6"/><path d="M18 9v6"/></svg> <?php esc_html_e( 'Select Stream / Sub-Examination', 'indianagri-quiz-engine' ); ?></h2>
				</div>
				<div class="iaq-mt-subexams-grid">
					<?php foreach ( $sub_exams as $se ) : ?>
						<a href="<?php echo esc_url( $page_url( 'exam', array( 'category_id' => ! empty( $category ) ? $category->id : 0, 'exam_id' => $se->id ) ) ); ?>" class="iaq-mt-subexam-card">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#117E3A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
							<strong><?php echo esc_html( $se->name ); ?></strong>
							<span><?php esc_html_e( 'Explore &rarr;', 'indianagri-quiz-engine' ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- Vacancies (e.g. UPSSSC -> Technical Assistant, Sugarcane Officer) -->
			<?php if ( ! empty( $vacancies ) ) : ?>
				<div class="iaq-mt-section-header">
					<h2><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> <?php esc_html_e( 'Select Post / Vacancy', 'indianagri-quiz-engine' ); ?></h2>
					<p><?php esc_html_e( 'Select a recruitment post to view its dedicated mock tests.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<div class="iaq-mt-vacancies-grid">
					<?php foreach ( $vacancies as $vac ) : ?>
						<a href="<?php echo esc_url( $page_url( 'vacancy', array( 'category_id' => ! empty( $category ) ? $category->id : 0, 'exam_id' => $exam->id, 'vacancy_id' => $vac->id ) ) ); ?>" class="iaq-mt-vacancy-card">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#117E3A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
							<div class="iaq-mt-vac-title"><?php echo esc_html( $vac->title ); ?></div>
							<?php if ( ! empty( $vac->posts_count ) ) : ?>
								<span class="iaq-pill iaq-pill-category"><?php printf( esc_html__( '%d Posts', 'indianagri-quiz-engine' ), (int) $vac->posts_count ); ?></span>
							<?php endif; ?>
							<span class="iaq-mt-vac-cta"><?php esc_html_e( 'View Tests &rarr;', 'indianagri-quiz-engine' ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- Assigned Subjects (e.g. ICAR AIEEA -> Agronomy, Soil Science, Horticulture) -->
			<?php if ( ! empty( $exam_subjects ) ) : ?>
				<div class="iaq-mt-section-header">
					<h2><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg> <?php esc_html_e( 'Select a Subject under this Examination', 'indianagri-quiz-engine' ); ?></h2>
					<p><?php esc_html_e( 'Choose a subject to view available full-length mock tests and question papers.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<div class="iaq-mt-subjects-grid">
					<?php foreach ( $exam_subjects as $es ) : ?>
						<?php
						$mtests = IAQ_Mock_Tests::get_context_mock_tests( array( 'category_id' => ! empty( $category ) ? $category->id : 0, 'exam_id' => $exam->id, 'subject_id' => $es->id ) );
						$mcount = is_array( $mtests ) ? count( $mtests ) : 0;
						?>
						<div class="iaq-mt-subject-card">
							<div class="iaq-mt-subject-top">
								<div class="iaq-mt-subject-icon">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
								</div>
								<span class="iaq-pill" style="background:#f0fdf4;color:#117E3A;font-weight:700;">
									<?php
									printf(
										esc_html( _n( '%d Mock Test Available', '%d Mock Tests Available', $mcount, 'indianagri-quiz-engine' ) ),
										$mcount
									);
									?>
								</span>
							</div>

							<h3 class="iaq-mt-sub-title">
								<a href="<?php echo esc_url( $page_url( 'exam_subject', array( 'category_id' => ! empty( $category ) ? $category->id : 0, 'exam_id' => $exam->id, 'subject_id' => $es->id ) ) ); ?>" style="color:#0f172a;text-decoration:none;">
									<?php echo esc_html( $es->name ); ?>
								</a>
							</h3>

							<?php if ( ! empty( $es->description ) ) : ?>
								<p class="iaq-mt-sub-desc">
									<?php echo esc_html( wp_trim_words( $es->description, 16 ) ); ?>
								</p>
							<?php endif; ?>

							<div class="iaq-mt-subject-action">
								<a href="<?php echo esc_url( $page_url( 'exam_subject', array( 'category_id' => ! empty( $category ) ? $category->id : 0, 'exam_id' => $exam->id, 'subject_id' => $es->id ) ) ); ?>" class="iaq-btn iaq-btn-primary iaq-btn-exam iaq-full-width">
									<?php esc_html_e( 'View Mock Tests', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- Direct Mock Tests under this Exam (if no further sub-levels exist or direct tests attached) -->
			<?php if ( empty( $sub_exams ) && empty( $vacancies ) && empty( $exam_subjects ) ) : ?>
				<div class="iaq-mt-section-header">
					<h2><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> <?php esc_html_e( 'Examination Mock Tests', 'indianagri-quiz-engine' ); ?></h2>
					<p><?php esc_html_e( 'Full-length mock test papers designed under real timed exam conditions.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<?php if ( ! empty( $quizzes ) ) : ?>
					<div class="iaq-mt-tests-grid">
						<?php
						foreach ( $quizzes as $quiz ) {
							include IAQ_PLUGIN_DIR . 'templates/frontend/mock-test-card.php';
						}
						?>
					</div>
				<?php else : ?>
					<div class="iaq-mt-empty-state">
						<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
						<h3><?php esc_html_e( 'No Mock Tests Found for this Examination', 'indianagri-quiz-engine' ); ?></h3>
						<p><?php esc_html_e( 'No mock tests have been published for this examination yet.', 'indianagri-quiz-engine' ); ?></p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

		</div>

	<!-- VIEW 4: FINAL MOCK TESTS LIST (FOR EXAM-SUBJECT, VACANCY, STATE, OR SERIES) -->
	<?php else : ?>

		<div class="iaq-mt-tests-list-view">
			<div class="iaq-mt-section-header">
				<h2><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg> <?php esc_html_e( 'Available Mock Tests', 'indianagri-quiz-engine' ); ?></h2>
				<p><?php esc_html_e( 'Practice under real timed exam conditions with negative marking, bilingual interface, and scientific explanations.', 'indianagri-quiz-engine' ); ?></p>
			</div>

			<?php if ( ! empty( $quizzes ) ) : ?>
				<div class="iaq-mt-tests-grid">
					<?php
					foreach ( $quizzes as $quiz ) {
						include IAQ_PLUGIN_DIR . 'templates/frontend/mock-test-card.php';
					}
					?>
				</div>
			<?php else : ?>
				<div class="iaq-mt-empty-state">
					<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
					<h3><?php esc_html_e( 'No Mock Tests Found in this Section', 'indianagri-quiz-engine' ); ?></h3>
					<p><?php esc_html_e( 'No mock tests are currently available in this section.', 'indianagri-quiz-engine' ); ?></p>
					<a href="<?php echo esc_url( $page_url() ); ?>" class="iaq-btn iaq-btn-outline" style="margin-top:14px;">
						&larr; <?php esc_html_e( 'Back to Mock Tests Dashboard', 'indianagri-quiz-engine' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>

	<?php endif; ?>

</div>
