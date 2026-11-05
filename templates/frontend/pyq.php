<?php
/**
 * Frontend: Previous Year Question Papers (PYQ) Multi-View Template.
 * Reuses the existing Mock Test hierarchy (10 main categories, exams, subjects, vacancies) with zero duplication.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id     = get_current_user_id();
$view        = isset( $view ) ? $view : 'landing';
$search_term = isset( $_GET['pyq_search'] ) ? sanitize_text_field( wp_unslash( $_GET['pyq_search'] ) ) : '';
$year_filter = isset( $_GET['pyq_year'] ) ? absint( $_GET['pyq_year'] ) : 0;

$page_url = function( $subview = '', $params = array() ) {
	return IAQ_PYQ::get_url( $subview, $params );
};

$available_years = IAQ_PYQ::get_available_years();
?>

<div class="iaq-mock-module-wrap iaq-pyq-module-wrap" id="iaq-pyq-engine">

	<!-- Header Banner & Breadcrumbs -->
	<div class="iaq-mt-banner" style="background: linear-gradient(135deg, #1e1b4b 0%, #2e1065 50%, #4338ca 100%);">
		<div class="iaq-mt-breadcrumbs">
			<a href="<?php echo esc_url( $page_url() ); ?>" class="iaq-mt-bc-item">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> <?php esc_html_e( 'Previous Year Papers (PYQ)', 'indianagri-quiz-engine' ); ?>
			</a>

			<?php if ( ! empty( $category ) ) : ?>
				<span class="iaq-mt-bc-sep">&rsaquo;</span>
				<a href="<?php echo esc_url( $page_url( 'category', array( 'category_id' => $category->id ) ) ); ?>" class="iaq-mt-bc-item">
					<?php echo esc_html( $category->name ); ?>
				</a>
			<?php endif; ?>

			<?php if ( ! empty( $exam ) ) : ?>
				<span class="iaq-mt-bc-sep">&rsaquo;</span>
				<a href="<?php echo esc_url( $page_url( 'exam', array( 'category_id' => ! empty( $category ) ? $category->id : 0, 'exam_id' => $exam->id ) ) ); ?>" class="iaq-mt-bc-item">
					<?php echo esc_html( $exam->name ); ?>
				</a>
			<?php endif; ?>

			<?php if ( ! empty( $subject ) ) : ?>
				<span class="iaq-mt-bc-sep">&rsaquo;</span>
				<span class="iaq-mt-bc-current"><?php echo esc_html( $subject->name ); ?></span>
			<?php endif; ?>

			<?php if ( ! empty( $vacancy ) ) : ?>
				<span class="iaq-mt-bc-sep">&rsaquo;</span>
				<span class="iaq-mt-bc-current"><?php echo esc_html( $vacancy->title ); ?></span>
			<?php endif; ?>
		</div>

		<div class="iaq-mt-header-main">
			<div class="iaq-mt-badge" style="background:rgba(255,255,255,0.18);color:#e0e7ff;"><?php esc_html_e( 'Official Past Examination Repository', 'indianagri-quiz-engine' ); ?></div>
			<h1 class="iaq-mt-title">
				<?php
				if ( 'landing' === $view ) {
					esc_html_e( 'Previous Year Question Papers (PYQ)', 'indianagri-quiz-engine' );
				} elseif ( ! empty( $subject ) && ! empty( $exam ) ) {
					printf( esc_html__( '%s &rarr; %s Past Papers', 'indianagri-quiz-engine' ), esc_html( $exam->name ), esc_html( $subject->name ) );
				} elseif ( ! empty( $vacancy ) ) {
					echo esc_html( $vacancy->title ) . ' ' . esc_html__( 'Previous Year Question Papers', 'indianagri-quiz-engine' );
				} elseif ( ! empty( $exam ) ) {
					echo esc_html( $exam->name ) . ' ' . esc_html__( 'Previous Year Papers', 'indianagri-quiz-engine' );
				} elseif ( ! empty( $category ) ) {
					echo esc_html( $category->name ) . ' — ' . esc_html__( 'Official PYQs', 'indianagri-quiz-engine' );
				} else {
					esc_html_e( 'Previous Year Question Papers', 'indianagri-quiz-engine' );
				}
				?>
			</h1>
			<p class="iaq-mt-subtitle" style="color:#c7d2fe;">
				<?php
				if ( 'landing' === $view ) {
					esc_html_e( 'Access authentic past examination papers for ICAR, JRF, SRF, ASRB NET, CUET, UPSSSC, State PSCs, and IBPS AFO in real CBT exam mode or instant practice mode.', 'indianagri-quiz-engine' );
				} elseif ( ! empty( $category ) && ! empty( $category->description ) ) {
					echo esc_html( $category->description );
				} elseif ( ! empty( $exam ) && ! empty( $exam->description ) ) {
					echo esc_html( $exam->description );
				} else {
					esc_html_e( 'Select an examination or year to solve official past papers with detailed scientific explanations.', 'indianagri-quiz-engine' );
				}
				?>
			</p>
		</div>

		<!-- Year Filtering Toolbar -->
		<?php if ( ! empty( $available_years ) ) : ?>
			<div class="iaq-pyq-year-toolbar" style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
				<span style="color:#e0e7ff;font-size:12px;font-weight:700;margin-right:4px;">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
					<?php esc_html_e( 'Filter Year:', 'indianagri-quiz-engine' ); ?>
				</span>
				<a href="<?php echo esc_url( remove_query_arg( 'pyq_year' ) ); ?>" class="iaq-pill <?php echo 0 === $year_filter ? 'iaq-pill-active' : ''; ?>" style="background:<?php echo 0 === $year_filter ? '#ffffff' : 'rgba(255,255,255,0.18)'; ?>;color:<?php echo 0 === $year_filter ? '#1e1b4b' : '#ffffff'; ?>;text-decoration:none;font-weight:700;font-size:12px;padding:4px 10px;border-radius:20px;">
					<?php esc_html_e( 'All Years', 'indianagri-quiz-engine' ); ?>
				</a>
				<?php foreach ( $available_years as $y ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'pyq_year', $y ) ); ?>" class="iaq-pill <?php echo $year_filter === (int) $y ? 'iaq-pill-active' : ''; ?>" style="background:<?php echo $year_filter === (int) $y ? '#ffffff' : 'rgba(255,255,255,0.18)'; ?>;color:<?php echo $year_filter === (int) $y ? '#1e1b4b' : '#ffffff'; ?>;text-decoration:none;font-weight:700;font-size:12px;padding:4px 10px;border-radius:20px;">
						<?php echo esc_html( $y ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<!-- VIEW 1: LANDING VIEW — 10 MAIN HIERARCHY CATEGORIES REUSED -->
	<?php if ( 'landing' === $view ) : ?>

		<div class="iaq-mt-landing-view">
			<div class="iaq-mt-section-header">
				<h2>
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;color:#4338ca;"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
					<?php esc_html_e( 'Browse Previous Year Papers by Examination Category', 'indianagri-quiz-engine' ); ?>
				</h2>
				<p><?php esc_html_e( 'Select an examination category below to access all official previous year papers organized by syllabus and year.', 'indianagri-quiz-engine' ); ?></p>
			</div>

			<div class="iaq-mt-categories-grid">
				<?php foreach ( $categories as $cat ) : ?>
					<?php
					$cat_papers = IAQ_PYQ::get_context_pyq_papers( array( 'category_id' => $cat->id, 'year' => $year_filter ) );
					$paper_count = is_array( $cat_papers ) ? count( $cat_papers ) : 0;
					$badge_label = ! empty( $cat->badge_text ) ? $cat->badge_text : __( 'Official Archive', 'indianagri-quiz-engine' );
					?>
					<div class="iaq-mt-cat-card" style="border-top: 3px solid #6366f1;">
						<div class="iaq-mt-cat-header">
							<div class="iaq-mt-cat-icon-wrap" style="background:linear-gradient(135deg, #1e1b4b, #4338ca);">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
							</div>
							<span class="iaq-pill" style="background:#ede9fe;color:#5b21b6;font-weight:700;">
								<?php echo esc_html( $badge_label ); ?>
							</span>
						</div>

						<h3 class="iaq-mt-cat-title">
							<a href="<?php echo esc_url( $page_url( 'category', array( 'category_id' => $cat->id ) ) ); ?>" style="color:#0f172a;text-decoration:none;">
								<?php echo esc_html( $cat->name ); ?>
							</a>
						</h3>

						<p class="iaq-mt-cat-desc">
							<?php echo esc_html( $cat->description ); ?>
						</p>

						<div class="iaq-mt-cat-footer" style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:12px;border-top:1px solid #f1f5f9;">
							<span class="iaq-mt-count" style="font-size:12px;font-weight:700;color:#4338ca;">
								<?php
								printf(
									esc_html( _n( '%d PYQ Paper Available', '%d PYQ Papers Available', $paper_count, 'indianagri-quiz-engine' ) ),
									$paper_count
								);
								?>
							</span>

							<a href="<?php echo esc_url( $page_url( 'category', array( 'category_id' => $cat->id ) ) ); ?>" class="iaq-btn iaq-btn-primary" style="background:#4338ca;border-color:#3730a3;font-size:12px;padding:6px 12px;">
								<?php esc_html_e( 'Explore PYQs', 'indianagri-quiz-engine' ); ?> &rarr;
							</a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Direct All PYQ Papers if any exist -->
			<?php if ( ! empty( $quizzes ) ) : ?>
				<div class="iaq-mt-section-header" style="margin-top:40px;">
					<h2>
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;color:#10b981;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
						<?php esc_html_e( 'Latest Previous Year Question Papers', 'indianagri-quiz-engine' ); ?>
					</h2>
					<p><?php esc_html_e( 'Recently added official past examination papers ready to attempt.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<div class="iaq-mt-tests-grid">
					<?php
					foreach ( $quizzes as $paper ) {
						include IAQ_PLUGIN_DIR . 'templates/frontend/pyq-card.php';
					}
					?>
				</div>
			<?php endif; ?>

		</div>

	<!-- VIEW 2: CATEGORY DRILLDOWN -->
	<?php elseif ( 'category' === $view && ! empty( $category ) ) : ?>

		<div class="iaq-mt-category-view">

			<!-- Examinations under this Category -->
			<?php if ( ! empty( $examinations ) ) : ?>
				<div class="iaq-mt-section-header">
					<h2><?php esc_html_e( 'Select Examination for Official Papers', 'indianagri-quiz-engine' ); ?></h2>
					<p><?php esc_html_e( 'Choose your specific exam target to access year-wise question papers.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<div class="iaq-mt-exams-grid">
					<?php foreach ( $examinations as $ex ) : ?>
						<?php
						$ex_papers = IAQ_PYQ::get_context_pyq_papers( array( 'category_id' => $category->id, 'exam_id' => $ex->id, 'year' => $year_filter ) );
						$pcount    = is_array( $ex_papers ) ? count( $ex_papers ) : 0;
						?>
						<div class="iaq-mt-exam-card">
							<div class="iaq-mt-exam-top">
								<span class="iaq-pill iaq-pill-code" style="background:#e0e7ff;color:#3730a3;font-weight:700;"><?php echo esc_html( $ex->code ); ?></span>
								<span class="iaq-pill" style="background:#f0fdf4;color:#047857;font-weight:700;">
									<?php
									printf(
										esc_html( _n( '%d PYQ Paper', '%d PYQ Papers', $pcount, 'indianagri-quiz-engine' ) ),
										$pcount
									);
									?>
								</span>
							</div>

							<h3 class="iaq-mt-exam-title">
								<a href="<?php echo esc_url( $page_url( 'exam', array( 'category_id' => $category->id, 'exam_id' => $ex->id ) ) ); ?>" style="color:#0f172a;text-decoration:none;">
									<?php echo esc_html( $ex->name ); ?>
								</a>
							</h3>

							<?php if ( ! empty( $ex->description ) ) : ?>
								<p class="iaq-mt-exam-desc">
									<?php echo esc_html( wp_trim_words( $ex->description, 18 ) ); ?>
								</p>
							<?php endif; ?>

							<div class="iaq-mt-exam-action">
								<a href="<?php echo esc_url( $page_url( 'exam', array( 'category_id' => $category->id, 'exam_id' => $ex->id ) ) ); ?>" class="iaq-btn iaq-btn-primary iaq-btn-exam iaq-full-width" style="background:#4338ca;border-color:#3730a3;">
									<?php esc_html_e( 'View PYQ Papers', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- Direct Category PYQ Papers -->
			<div class="iaq-mt-section-header" style="margin-top:30px;">
				<h2>
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
					<?php esc_html_e( 'Available Previous Year Papers', 'indianagri-quiz-engine' ); ?>
				</h2>
			</div>

			<?php if ( ! empty( $quizzes ) ) : ?>
				<div class="iaq-mt-tests-grid">
					<?php
					foreach ( $quizzes as $paper ) {
						include IAQ_PLUGIN_DIR . 'templates/frontend/pyq-card.php';
					}
					?>
				</div>
			<?php elseif ( empty( $examinations ) ) : ?>
				<div class="iaq-mt-empty-state" style="padding:40px 20px;text-align:center;background:#f8fafc;border-radius:12px;border:1px dashed #cbd5e1;margin-top:20px;">
					<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
					<h3 style="color:#334155;margin:0 0 8px;font-size:18px;"><?php esc_html_e( 'No Previous Year Question Papers are currently available.', 'indianagri-quiz-engine' ); ?></h3>
					<p style="color:#64748b;margin:0 0 16px;"><?php esc_html_e( 'Official past papers for this category will be uploaded shortly by the administration.', 'indianagri-quiz-engine' ); ?></p>
					<a href="<?php echo esc_url( $page_url() ); ?>" class="iaq-btn iaq-btn-outline">
						&larr; <?php esc_html_e( 'Back to All Categories', 'indianagri-quiz-engine' ); ?>
					</a>
				</div>
			<?php endif; ?>

		</div>

	<!-- VIEW 3: EXAMINATION DRILLDOWN -->
	<?php elseif ( 'exam' === $view && ! empty( $exam ) ) : ?>

		<div class="iaq-mt-exam-detail-view">

			<!-- Subjects under this Examination -->
			<?php if ( ! empty( $exam_subjects ) ) : ?>
				<div class="iaq-mt-section-header">
					<h2><?php esc_html_e( 'Subjects & Disciplines', 'indianagri-quiz-engine' ); ?></h2>
					<p><?php esc_html_e( 'Choose your discipline or subject specialization to access official question papers.', 'indianagri-quiz-engine' ); ?></p>
				</div>
				<div class="iaq-mt-subjects-grid">
					<?php foreach ( $exam_subjects as $es ) : ?>
						<?php
						$mtests = IAQ_PYQ::get_context_pyq_papers( array( 'category_id' => ! empty( $category ) ? $category->id : 0, 'exam_id' => $exam->id, 'subject_id' => $es->id, 'year' => $year_filter ) );
						$mcount = is_array( $mtests ) ? count( $mtests ) : 0;
						?>
						<div class="iaq-mt-subject-card">
							<div class="iaq-mt-subject-top">
								<div class="iaq-mt-subject-icon" style="background:#ede9fe;color:#6366f1;">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
								</div>
								<span class="iaq-pill" style="background:#f0fdf4;color:#047857;font-weight:700;">
									<?php
									printf(
										esc_html( _n( '%d PYQ Available', '%d PYQs Available', $mcount, 'indianagri-quiz-engine' ) ),
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

							<div class="iaq-mt-subject-action">
								<a href="<?php echo esc_url( $page_url( 'exam_subject', array( 'category_id' => ! empty( $category ) ? $category->id : 0, 'exam_id' => $exam->id, 'subject_id' => $es->id ) ) ); ?>" class="iaq-btn iaq-btn-primary iaq-btn-exam iaq-full-width" style="background:#4338ca;border-color:#3730a3;">
									<?php esc_html_e( 'View Question Papers', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- Papers under this Exam -->
			<div class="iaq-mt-section-header" style="margin-top:30px;">
				<h2>
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
					<?php esc_html_e( 'Official Question Papers', 'indianagri-quiz-engine' ); ?>
				</h2>
			</div>

			<?php if ( ! empty( $quizzes ) ) : ?>
				<div class="iaq-mt-tests-grid">
					<?php
					foreach ( $quizzes as $paper ) {
						include IAQ_PLUGIN_DIR . 'templates/frontend/pyq-card.php';
					}
					?>
				</div>
			<?php else : ?>
				<div class="iaq-mt-empty-state" style="padding:40px 20px;text-align:center;background:#f8fafc;border-radius:12px;border:1px dashed #cbd5e1;margin-top:20px;">
					<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
					<h3 style="color:#334155;margin:0 0 8px;font-size:18px;"><?php esc_html_e( 'No Previous Year Question Papers are currently available.', 'indianagri-quiz-engine' ); ?></h3>
					<p style="color:#64748b;margin:0 0 16px;"><?php esc_html_e( 'No past papers matching this criteria have been published yet.', 'indianagri-quiz-engine' ); ?></p>
					<a href="<?php echo esc_url( $page_url() ); ?>" class="iaq-btn iaq-btn-outline">
						&larr; <?php esc_html_e( 'Back to All Categories', 'indianagri-quiz-engine' ); ?>
					</a>
				</div>
			<?php endif; ?>

		</div>

	<!-- VIEW 4: FINAL PAPERS LIST FOR DISCIPLINE / SUBVIEW -->
	<?php else : ?>

		<div class="iaq-mt-tests-list-view">
			<div class="iaq-mt-section-header">
				<h2>
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
					<?php esc_html_e( 'Available Previous Year Papers', 'indianagri-quiz-engine' ); ?>
				</h2>
				<p><?php esc_html_e( 'Solve authentic past questions under real examination conditions with immediate feedback or timed submission.', 'indianagri-quiz-engine' ); ?></p>
			</div>

			<?php if ( ! empty( $quizzes ) ) : ?>
				<div class="iaq-mt-tests-grid">
					<?php
					foreach ( $quizzes as $paper ) {
						include IAQ_PLUGIN_DIR . 'templates/frontend/pyq-card.php';
					}
					?>
				</div>
			<?php else : ?>
				<div class="iaq-mt-empty-state" style="padding:40px 20px;text-align:center;background:#f8fafc;border-radius:12px;border:1px dashed #cbd5e1;margin-top:20px;">
					<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
					<h3 style="color:#334155;margin:0 0 8px;font-size:18px;"><?php esc_html_e( 'No Previous Year Question Papers are currently available.', 'indianagri-quiz-engine' ); ?></h3>
					<p style="color:#64748b;margin:0 0 16px;"><?php esc_html_e( 'No past papers found in this section.', 'indianagri-quiz-engine' ); ?></p>
					<a href="<?php echo esc_url( $page_url() ); ?>" class="iaq-btn iaq-btn-outline">
						&larr; <?php esc_html_e( 'Back to PYQ Dashboard', 'indianagri-quiz-engine' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>

	<?php endif; ?>

</div>
