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
				<span class="dashicons dashicons-awards"></span> <?php esc_html_e( 'Mock Tests', 'indianagri-quiz-engine' ); ?>
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
				<span class="dashicons dashicons-search"></span>
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
				<h2><span class="dashicons dashicons-category"></span> <?php esc_html_e( 'Choose Your Examination Category', 'indianagri-quiz-engine' ); ?></h2>
				<p><?php esc_html_e( 'Select from the 10 national and state agricultural examination streams below to access dedicated full-length mock tests.', 'indianagri-quiz-engine' ); ?></p>
			</div>

			<div class="iaq-mt-categories-grid">
				<?php if ( ! empty( $categories ) ) : ?>
					<?php foreach ( $categories as $cat ) : ?>
						<div class="iaq-mt-category-card">
							<div class="iaq-mt-cat-header">
								<div class="iaq-mt-cat-icon">
									<span class="dashicons <?php echo esc_attr( ! empty( $cat->icon ) ? $cat->icon : 'dashicons-awards' ); ?>"></span>
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
									<span class="dashicons dashicons-arrow-right-alt"></span>
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
					<h2><span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e( 'Select Target State', 'indianagri-quiz-engine' ); ?></h2>
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
					<h2><span class="dashicons dashicons-building"></span> <?php esc_html_e( 'Select University Entrance Type', 'indianagri-quiz-engine' ); ?></h2>
					<p><?php esc_html_e( 'Choose State Agricultural Universities or entrance program level.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<div class="iaq-mt-universities-grid">
					<?php foreach ( $universities as $un ) : ?>
						<a href="<?php echo esc_url( $page_url( 'university', array( 'category_id' => $category->id, 'university_id' => $un->id ) ) ); ?>" class="iaq-mt-univ-card">
							<span class="dashicons dashicons-welcome-learn-more"></span>
							<div class="iaq-mt-univ-name"><?php echo esc_html( $un->name ); ?></div>
							<span class="iaq-mt-univ-arrow">&rarr;</span>
						</a>
					<?php endforeach; ?>
				</div>

			<!-- Subject-Wise Browsing (Category 8) -->
			<?php elseif ( ! empty( $hierarchy_cfg['has_subjects'] ) && empty( $hierarchy_cfg['has_exams'] ) && ! empty( $subjects ) ) : ?>
				<div class="iaq-mt-section-header">
					<h2><span class="dashicons dashicons-category"></span> <?php esc_html_e( 'Select Agricultural Discipline', 'indianagri-quiz-engine' ); ?></h2>
					<p><?php esc_html_e( 'Attempt full syllabus or chapter mock tests in your specialized discipline.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<div class="iaq-mt-subjects-grid">
					<?php foreach ( $subjects as $sub ) : ?>
						<a href="<?php echo esc_url( $page_url( 'subject', array( 'category_id' => $category->id, 'subject_id' => $sub->id ) ) ); ?>" class="iaq-mt-subject-card">
							<div class="iaq-mt-sub-title"><?php echo esc_html( $sub->name ); ?></div>
							<p class="iaq-mt-sub-desc"><?php echo esc_html( wp_trim_words( $sub->description, 10 ) ); ?></p>
							<div class="iaq-mt-sub-cta"><?php esc_html_e( 'View Mock Tests &rarr;', 'indianagri-quiz-engine' ); ?></div>
						</a>
					<?php endforeach; ?>
				</div>

			<!-- Examination-Based Browsing (Categories 1, 2, 3, 4, 5, 10) -->
			<?php elseif ( ! empty( $exams ) ) : ?>
				<div class="iaq-mt-section-header">
					<h2><span class="dashicons dashicons-welcome-learn-more"></span> <?php echo esc_html( $hierarchy_cfg['drill_label'] ); ?></h2>
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
					<h2><span class="dashicons dashicons-awards"></span> <?php esc_html_e( 'Available Mock Tests', 'indianagri-quiz-engine' ); ?></h2>
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
						<span class="dashicons dashicons-welcome-learn-more"></span>
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
					<h2><span class="dashicons dashicons-networking"></span> <?php esc_html_e( 'Select Stream / Sub-Examination', 'indianagri-quiz-engine' ); ?></h2>
				</div>
				<div class="iaq-mt-subexams-grid">
					<?php foreach ( $sub_exams as $se ) : ?>
						<a href="<?php echo esc_url( $page_url( 'exam', array( 'category_id' => ! empty( $category ) ? $category->id : 0, 'exam_id' => $se->id ) ) ); ?>" class="iaq-mt-subexam-card">
							<span class="dashicons dashicons-awards"></span>
							<strong><?php echo esc_html( $se->name ); ?></strong>
							<span><?php esc_html_e( 'Explore &rarr;', 'indianagri-quiz-engine' ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- Vacancies (e.g. UPSSSC -> Technical Assistant, Sugarcane Officer) -->
			<?php if ( ! empty( $vacancies ) ) : ?>
				<div class="iaq-mt-section-header">
					<h2><span class="dashicons dashicons-groups"></span> <?php esc_html_e( 'Select Post / Vacancy', 'indianagri-quiz-engine' ); ?></h2>
					<p><?php esc_html_e( 'Mock tests configured for specific recruitment posts.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<div class="iaq-mt-vacancies-grid">
					<?php foreach ( $vacancies as $vac ) : ?>
						<a href="<?php echo esc_url( $page_url( 'vacancy', array( 'category_id' => ! empty( $category ) ? $category->id : 0, 'exam_id' => $exam->id, 'vacancy_id' => $vac->id ) ) ); ?>" class="iaq-mt-vacancy-card">
							<span class="dashicons dashicons-businessman"></span>
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
					<h2><span class="dashicons dashicons-book"></span> <?php esc_html_e( 'Select Examination Subject / Discipline', 'indianagri-quiz-engine' ); ?></h2>
					<p><?php esc_html_e( 'Choose a specialized subject to access its sequential mock test drills.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<div class="iaq-mt-subjects-grid">
					<?php foreach ( $exam_subjects as $es ) : ?>
						<a href="<?php echo esc_url( $page_url( 'exam_subject', array( 'category_id' => ! empty( $category ) ? $category->id : 0, 'exam_id' => $exam->id, 'subject_id' => $es->id ) ) ); ?>" class="iaq-mt-subject-card">
							<div class="iaq-mt-sub-title"><?php echo esc_html( $es->name ); ?></div>
							<div class="iaq-mt-sub-cta"><?php esc_html_e( 'Start Subject Mock Tests &rarr;', 'indianagri-quiz-engine' ); ?></div>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- Direct Mock Tests under this Exam (if no further sub-levels exist or direct tests attached) -->
			<?php if ( empty( $sub_exams ) && empty( $vacancies ) && empty( $exam_subjects ) ) : ?>
				<div class="iaq-mt-section-header">
					<h2><span class="dashicons dashicons-welcome-learn-more"></span> <?php esc_html_e( 'Examination Mock Tests', 'indianagri-quiz-engine' ); ?></h2>
					<p><?php esc_html_e( 'Sequential mock test papers restarting at Mock Test 1.', 'indianagri-quiz-engine' ); ?></p>
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
						<span class="dashicons dashicons-welcome-learn-more"></span>
						<h3><?php esc_html_e( 'No Mock Tests Found for this Examination', 'indianagri-quiz-engine' ); ?></h3>
						<p><?php esc_html_e( 'New test series will be available shortly.', 'indianagri-quiz-engine' ); ?></p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

		</div>

	<!-- VIEW 4: FINAL MOCK TESTS LIST (FOR EXAM-SUBJECT, VACANCY, STATE, OR SERIES) -->
	<?php else : ?>

		<div class="iaq-mt-tests-list-view">
			<div class="iaq-mt-section-header">
				<h2><span class="dashicons dashicons-awards"></span> <?php esc_html_e( 'Available Mock Tests', 'indianagri-quiz-engine' ); ?></h2>
				<p><?php esc_html_e( 'Tests are ordered sequentially as Mock Test 1, Mock Test 2... Practice under real timed exam conditions.', 'indianagri-quiz-engine' ); ?></p>
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
					<span class="dashicons dashicons-welcome-learn-more"></span>
					<h3><?php esc_html_e( 'No Mock Tests Found in this Section', 'indianagri-quiz-engine' ); ?></h3>
					<p><?php esc_html_e( 'Please check back soon or try selecting another examination category.', 'indianagri-quiz-engine' ); ?></p>
					<a href="<?php echo esc_url( $page_url() ); ?>" class="iaq-btn iaq-btn-outline" style="margin-top:14px;">
						&larr; <?php esc_html_e( 'Back to Mock Tests Dashboard', 'indianagri-quiz-engine' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>

	<?php endif; ?>

</div>
