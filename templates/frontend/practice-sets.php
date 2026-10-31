<?php
/**
 * Frontend: Practice Sets Module Template.
 * Implements Practice by Book, Practice by Subjects, and Practice by Exams.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id     = get_current_user_id();
$view        = isset( $view ) ? $view : 'landing';
$search_term = isset( $_GET['ps_search'] ) ? sanitize_text_field( wp_unslash( $_GET['ps_search'] ) ) : '';
$access_filt = isset( $_GET['ps_access'] ) ? sanitize_key( $_GET['ps_access'] ) : '';

// Helper to generate URLs while preserving existing page context
$page_url = function( $subview = '', $params = array() ) {
	return IAQ_Practice_Sets::get_url( $subview, $params );
};
?>

<div class="iaq-practice-module-wrap" id="iaq-practice-sets-engine">

	<?php if ( 'landing' !== $view && 'books' !== $view ) : ?>
	<!-- Header Banner & Breadcrumb Navigation -->
	<div class="iaq-ps-banner">
		<div class="iaq-ps-breadcrumbs">
			<a href="<?php echo esc_url( $page_url() ); ?>" class="iaq-ps-bc-item">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg> <?php esc_html_e( 'Practice Sets', 'indianagri-quiz-engine' ); ?>
			</a>

			<?php if ( 'books' === $view || 'book' === $view ) : ?>
				<span class="iaq-ps-bc-sep">&rsaquo;</span>
				<a href="<?php echo esc_url( $page_url( 'books' ) ); ?>" class="iaq-ps-bc-item">
					<?php esc_html_e( 'Practice by Book', 'indianagri-quiz-engine' ); ?>
				</a>
				<?php if ( 'book' === $view && ! empty( $book ) ) : ?>
					<span class="iaq-ps-bc-sep">&rsaquo;</span>
					<span class="iaq-ps-bc-current"><?php echo esc_html( $book->title ); ?></span>
				<?php endif; ?>

			<?php elseif ( 'subjects' === $view || 'subject' === $view ) : ?>
				<span class="iaq-ps-bc-sep">&rsaquo;</span>
				<a href="<?php echo esc_url( $page_url( 'subjects' ) ); ?>" class="iaq-ps-bc-item">
					<?php esc_html_e( 'Practice by Subjects', 'indianagri-quiz-engine' ); ?>
				</a>
				<?php if ( 'subject' === $view && ! empty( $subject ) ) : ?>
					<span class="iaq-ps-bc-sep">&rsaquo;</span>
					<span class="iaq-ps-bc-current"><?php echo esc_html( $subject->name ); ?></span>
				<?php endif; ?>

			<?php elseif ( 'exams' === $view || 'exam' === $view || 'exam_subject' === $view ) : ?>
				<span class="iaq-ps-bc-sep">&rsaquo;</span>
				<a href="<?php echo esc_url( $page_url( 'exams' ) ); ?>" class="iaq-ps-bc-item">
					<?php esc_html_e( 'Practice by Exams', 'indianagri-quiz-engine' ); ?>
				</a>
				<?php if ( ( 'exam' === $view || 'exam_subject' === $view ) && ! empty( $exam ) ) : ?>
					<span class="iaq-ps-bc-sep">&rsaquo;</span>
					<a href="<?php echo esc_url( $page_url( 'exam', array( 'exam_id' => $exam->id ) ) ); ?>" class="iaq-ps-bc-item">
						<?php echo esc_html( $exam->name ); ?>
					</a>
				<?php endif; ?>
				<?php if ( 'exam_subject' === $view && ! empty( $subject ) ) : ?>
					<span class="iaq-ps-bc-sep">&rsaquo;</span>
					<span class="iaq-ps-bc-current"><?php echo esc_html( $subject->name ); ?></span>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<div class="iaq-ps-header-main">
			<div class="iaq-ps-badge"><?php esc_html_e( 'IndianAgriExam.com Practice Engine', 'indianagri-quiz-engine' ); ?></div>
			<h1 class="iaq-ps-title">
				<?php
				if ( 'book' === $view && ! empty( $book ) ) {
					echo esc_html( $book->title );
				} elseif ( 'subjects' === $view ) {
					esc_html_e( 'Practice by Subjects — Topic-Wise Chapter Drills', 'indianagri-quiz-engine' );
				} elseif ( 'subject' === $view && ! empty( $subject ) ) {
					printf( esc_html__( '%s — Practice Sets', 'indianagri-quiz-engine' ), esc_html( $subject->name ) );
				} elseif ( 'exams' === $view ) {
					esc_html_e( 'Practice by Exams — Target Competitive Entrance Tests', 'indianagri-quiz-engine' );
				} elseif ( 'exam' === $view && ! empty( $exam ) ) {
					$clean_name = preg_replace( '/[^a-z0-9]/i', '', $exam->name );
					$clean_code = preg_replace( '/[^a-z0-9]/i', '', $exam->code ?? '' );
					if ( ! empty( $exam->code ) && ! empty( $clean_code ) && false === stripos( $clean_name, $clean_code ) ) {
						printf( esc_html__( '%1$s (%2$s) — Subject Selection', 'indianagri-quiz-engine' ), esc_html( $exam->name ), esc_html( $exam->code ) );
					} else {
						printf( esc_html__( '%s — Subject Selection', 'indianagri-quiz-engine' ), esc_html( $exam->name ) );
					}
				} elseif ( 'exam_subject' === $view && ! empty( $exam ) && ! empty( $subject ) ) {
					printf( esc_html__( '%1$s → %2$s Practice Sets', 'indianagri-quiz-engine' ), esc_html( $exam->name ), esc_html( $subject->name ) );
				}
				?>
			</h1>
			<p class="iaq-ps-subtitle">
				<?php
				if ( 'book' === $view && ! empty( $book ) ) {
					if ( ! empty( $book->author ) ) {
						printf( esc_html__( 'Authored by %s. Select a practice set below to begin solving.', 'indianagri-quiz-engine' ), esc_html( $book->author ) );
					} else {
						esc_html_e( 'Select a practice set below to begin solving questions.', 'indianagri-quiz-engine' );
					}
				} elseif ( 'subjects' === $view ) {
					esc_html_e( 'Master fundamental disciplines including Agronomy, Soil Science, Horticulture, Genetics, Plant Pathology, and Entomology.', 'indianagri-quiz-engine' );
				} elseif ( 'subject' === $view && ! empty( $subject ) ) {
					esc_html_e( 'Step-by-step topic drills arranged sequentially. Solve tests, review explanations, and track your accuracy.', 'indianagri-quiz-engine' );
				} elseif ( 'exams' === $view ) {
					esc_html_e( 'Focused practice questions aligned with the syllabus and question patterns of major agricultural entrance and recruitment tests.', 'indianagri-quiz-engine' );
				} elseif ( 'exam' === $view && ! empty( $exam ) ) {
					esc_html_e( 'Select an approved subject under this competitive examination to access tailored practice sets.', 'indianagri-quiz-engine' );
				} elseif ( 'exam_subject' === $view && ! empty( $exam ) && ! empty( $subject ) ) {
					printf( esc_html__( 'Practice sets specifically designed for %1$s in %2$s.', 'indianagri-quiz-engine' ), esc_html( $exam->name ), esc_html( $subject->name ) );
				}
				?>
			</p>
		</div>
	</div>
	<?php endif; ?>

	<?php
	// =========================================================================
	// VIEW 1: ROOT LANDING VIEW (3 HERO CATEGORIES)
	// =========================================================================
	if ( 'landing' === $view ) :
		$iaq_img_base     = defined( 'IAQ_PLUGIN_URL' ) ? rtrim( IAQ_PLUGIN_URL, '/' ) : '';
		$iaq_books_img    = $iaq_img_base . '/assets/images/pathway_books.jpg';
		$iaq_subjects_img = $iaq_img_base . '/assets/images/pathway_subjects.jpg';
		$iaq_exams_img    = $iaq_img_base . '/assets/images/pathway_exams.jpg';
		$books_link       = $page_url( 'books' );
		$subjects_link    = $page_url( 'subjects' );
		$exams_link       = $page_url( 'exams' );
		?>
		<div class="iaq-pathways-grid iaq-ps-landing-grid">
			<!-- Card 1: Practice by Books -->
			<div class="iaq-pathway-card is-books iaq-qa-pill-card is-green" id="card-practice-by-book" onclick="window.location.href='<?php echo esc_url( $books_link ); ?>';">
				<div class="iaq-pathway-card-top">
					<div class="iaq-pathway-info">
						<div class="iaq-pathway-icon-squircle">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
								<path d="M6 6h10"/>
								<path d="M6 10h10"/>
							</svg>
						</div>
						<span class="iaq-pathway-overline"><?php esc_html_e( 'CHAPTER-WISE PRACTICE', 'indianagri-quiz-engine' ); ?></span>
						<h3 class="iaq-pathway-name"><?php esc_html_e( 'Practice by', 'indianagri-quiz-engine' ); ?> <span class="iaq-accent-text is-green"><?php esc_html_e( 'Books', 'indianagri-quiz-engine' ); ?></span></h3>
						<p class="iaq-pathway-desc"><?php esc_html_e( 'Chapter-wise MCQs from standard reference literature & textbooks.', 'indianagri-quiz-engine' ); ?></p>
					</div>
					<div class="iaq-pathway-art">
						<img src="<?php echo esc_url( $iaq_books_img ); ?>" alt="<?php esc_attr_e( 'Practice by Books', 'indianagri-quiz-engine' ); ?>" loading="lazy" />
					</div>
				</div>

				<div class="iaq-pathway-features">
					<span class="iaq-pathway-feat">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
						<?php esc_html_e( 'Standard Books', 'indianagri-quiz-engine' ); ?>
					</span>
					<span class="iaq-pathway-feat">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
						<?php esc_html_e( 'Chapter-wise MCQs', 'indianagri-quiz-engine' ); ?>
					</span>
					<span class="iaq-pathway-feat">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
						<?php esc_html_e( 'Build Strong Basics', 'indianagri-quiz-engine' ); ?>
					</span>
				</div>

				<a href="<?php echo esc_url( $books_link ); ?>" class="iaq-pathway-btn is-green iaq-qa-action-pill" onclick="event.stopPropagation();">
					<span class="iaq-pathway-btn-text"><?php esc_html_e( 'Browse Practice Books &rarr;', 'indianagri-quiz-engine' ); ?></span>
					<span class="iaq-pathway-btn-arrow">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
					</span>
				</a>
			</div>

			<!-- Card 2: Practice by Subjects -->
			<div class="iaq-pathway-card is-subjects iaq-qa-pill-card is-blue" id="card-practice-by-subjects" onclick="window.location.href='<?php echo esc_url( $subjects_link ); ?>';">
				<div class="iaq-pathway-card-top">
					<div class="iaq-pathway-info">
						<div class="iaq-pathway-icon-squircle">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
								<rect width="7" height="7" x="3" y="3" rx="1.5"/>
								<rect width="7" height="7" x="14" y="3" rx="1.5"/>
								<rect width="7" height="7" x="14" y="14" rx="1.5"/>
								<rect width="7" height="7" x="3" y="14" rx="1.5"/>
							</svg>
						</div>
						<span class="iaq-pathway-overline"><?php esc_html_e( 'TOPIC-WISE PRACTICE', 'indianagri-quiz-engine' ); ?></span>
						<h3 class="iaq-pathway-name"><?php esc_html_e( 'Practice by', 'indianagri-quiz-engine' ); ?> <span class="iaq-accent-text is-blue"><?php esc_html_e( 'Subjects', 'indianagri-quiz-engine' ); ?></span></h3>
						<p class="iaq-pathway-desc"><?php esc_html_e( 'Topic-wise chapter drills arranged sequentially across core disciplines.', 'indianagri-quiz-engine' ); ?></p>
					</div>
					<div class="iaq-pathway-art">
						<img src="<?php echo esc_url( $iaq_subjects_img ); ?>" alt="<?php esc_attr_e( 'Practice by Subjects', 'indianagri-quiz-engine' ); ?>" loading="lazy" />
					</div>
				</div>

				<div class="iaq-pathway-features">
					<span class="iaq-pathway-feat">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
						<?php esc_html_e( 'Topic-wise Drills', 'indianagri-quiz-engine' ); ?>
					</span>
					<span class="iaq-pathway-feat">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
						<?php esc_html_e( 'All Major Subjects', 'indianagri-quiz-engine' ); ?>
					</span>
					<span class="iaq-pathway-feat">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
						<?php esc_html_e( 'Strengthen Key Concepts', 'indianagri-quiz-engine' ); ?>
					</span>
				</div>

				<a href="<?php echo esc_url( $subjects_link ); ?>" class="iaq-pathway-btn is-blue iaq-qa-action-pill" onclick="event.stopPropagation();">
					<span class="iaq-pathway-btn-text"><?php esc_html_e( 'Browse by Subject &rarr;', 'indianagri-quiz-engine' ); ?></span>
					<span class="iaq-pathway-btn-arrow">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
					</span>
				</a>
			</div>

			<!-- Card 3: Browse by Examination -->
			<div class="iaq-pathway-card is-exams iaq-qa-pill-card is-teal" id="card-practice-by-exams" onclick="window.location.href='<?php echo esc_url( $exams_link ); ?>';">
				<div class="iaq-pathway-card-top">
					<div class="iaq-pathway-info">
						<div class="iaq-pathway-icon-squircle">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
								<circle cx="12" cy="8" r="6"/>
								<path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
							</svg>
						</div>
						<span class="iaq-pathway-overline"><?php esc_html_e( 'COMPETITIVE EXAMS', 'indianagri-quiz-engine' ); ?></span>
						<h3 class="iaq-pathway-name"><?php esc_html_e( 'Browse by', 'indianagri-quiz-engine' ); ?> <span class="iaq-accent-text is-orange"><?php esc_html_e( 'Examination', 'indianagri-quiz-engine' ); ?></span></h3>
						<p class="iaq-pathway-desc"><span class="iaq-sr-only" style="display:none;">Practice by Exams: </span><?php esc_html_e( 'Target competitive entrance tests like ICAR JRF, ASRB NET, CUET PG & IBPS AFO.', 'indianagri-quiz-engine' ); ?></p>
					</div>
					<div class="iaq-pathway-art">
						<img src="<?php echo esc_url( $iaq_exams_img ); ?>" alt="<?php esc_attr_e( 'Browse by Examination', 'indianagri-quiz-engine' ); ?>" loading="lazy" />
					</div>
				</div>

				<div class="iaq-pathway-features">
					<span class="iaq-pathway-feat">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
						<?php esc_html_e( 'Exam-wise Practice Sets', 'indianagri-quiz-engine' ); ?>
					</span>
					<span class="iaq-pathway-feat">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
						<?php esc_html_e( 'Previous Year Papers (PYQs)', 'indianagri-quiz-engine' ); ?>
					</span>
					<span class="iaq-pathway-feat">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
						<?php esc_html_e( 'Mock Tests & Analysis', 'indianagri-quiz-engine' ); ?>
					</span>
				</div>

				<a href="<?php echo esc_url( $exams_link ); ?>" class="iaq-pathway-btn is-orange iaq-qa-action-pill" onclick="event.stopPropagation();">
					<span class="iaq-pathway-btn-text"><?php esc_html_e( 'Browse by Examination &rarr;', 'indianagri-quiz-engine' ); ?></span>
					<span class="iaq-pathway-btn-arrow">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
					</span>
				</a>
			</div>
		</div>
	<?php
	// =========================================================================
	// VIEW 2: PRACTICE BY BOOK — BOOKS CATALOG
	// =========================================================================
	elseif ( 'books' === $view ) :
		$iaq_img_base  = defined( 'IAQ_PLUGIN_URL' ) ? rtrim( IAQ_PLUGIN_URL, '/' ) : '';
		$iaq_books_img = $iaq_img_base . '/assets/images/hero_book_stack.jpg';
		?>
		<style>
		.iaq-book-catalog-hero {
			background: linear-gradient(135deg, rgba(240, 253, 244, 0.95) 0%, rgba(220, 252, 231, 0.55) 50%, rgba(255, 255, 255, 0.98) 100%) !important;
			border: 1.5px solid rgba(187, 247, 208, 0.85) !important;
			border-radius: 20px !important;
			padding: 24px 28px !important;
			display: flex !important;
			flex-direction: row !important;
			align-items: center !important;
			justify-content: space-between !important;
			gap: 24px !important;
			margin-bottom: 24px !important;
			position: relative !important;
			overflow: hidden !important;
			box-shadow: 0 10px 28px -6px rgba(22, 163, 74, 0.08) !important;
		}
		.iaq-book-hero-left {
			flex: 1 1 auto !important;
			min-width: 0 !important;
		}
		.iaq-book-hero-breadcrumb {
			display: inline-flex !important;
			align-items: center !important;
			gap: 6px !important;
			background: #ffffff !important;
			border: 1px solid #dcfce7 !important;
			padding: 4px 12px !important;
			border-radius: 20px !important;
			margin-bottom: 12px !important;
			font-size: 13px !important;
		}
		.iaq-book-hero-title {
			font-size: 26px !important;
			font-weight: 800 !important;
			color: #0f172a !important;
			margin: 0 0 8px 0 !important;
			line-height: 1.25 !important;
			letter-spacing: -0.02em !important;
		}
		.iaq-book-hero-sub {
			font-size: 14px !important;
			color: #475569 !important;
			margin: 0 0 16px 0 !important;
			line-height: 1.5 !important;
		}
		.iaq-book-hero-pills {
			display: flex !important;
			flex-direction: row !important;
			align-items: center !important;
			gap: 10px !important;
			flex-wrap: wrap !important;
		}
		.iaq-hero-pill {
			display: inline-flex !important;
			align-items: center !important;
			gap: 6px !important;
			background: #ffffff !important;
			border: 1px solid rgba(187, 247, 208, 0.9) !important;
			padding: 6px 14px !important;
			border-radius: 9999px !important;
			font-size: 12.5px !important;
			font-weight: 700 !important;
			color: #166534 !important;
			box-shadow: 0 2px 6px rgba(22, 163, 74, 0.06) !important;
		}
		.iaq-book-hero-right {
			flex: 0 0 180px !important;
			width: 180px !important;
			max-width: 180px !important;
			height: 155px !important;
			max-height: 155px !important;
			display: flex !important;
			align-items: center !important;
			justify-content: center !important;
		}
		.iaq-book-hero-art {
			max-width: 180px !important;
			max-height: 155px !important;
			width: auto !important;
			height: auto !important;
			object-fit: contain !important;
			filter: drop-shadow(0 6px 14px rgba(0, 0, 0, 0.08)) !important;
		}
		.iaq-book-filter-bar {
			display: flex !important;
			align-items: center !important;
			justify-content: space-between !important;
			gap: 16px !important;
			margin-bottom: 24px !important;
			flex-wrap: wrap !important;
		}
		.iaq-ps-books-grid {
			display: grid !important;
			grid-template-columns: repeat(3, 1fr) !important;
			gap: 22px !important;
			margin-top: 10px !important;
		}
		@media (max-width: 991px) {
			.iaq-ps-books-grid {
				grid-template-columns: repeat(2, 1fr) !important;
			}
		}
		@media (max-width: 640px) {
			.iaq-book-catalog-hero {
				flex-direction: column-reverse !important;
				text-align: center !important;
				padding: 18px !important;
			}
			.iaq-book-hero-pills {
				justify-content: center !important;
			}
			.iaq-ps-books-grid {
				grid-template-columns: 1fr !important;
			}
		}
		.iaq-premium-book-card {
			background: #ffffff !important;
			border: 1px solid #e2e8f0 !important;
			border-radius: 18px !important;
			overflow: hidden !important;
			display: flex !important;
			flex-direction: column !important;
			box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04) !important;
			transition: transform 0.2s, box-shadow 0.2s !important;
			cursor: pointer;
		}
		.iaq-premium-book-card:hover {
			transform: translateY(-3px) !important;
			box-shadow: 0 10px 24px rgba(22, 163, 74, 0.12) !important;
			border-color: #86efac !important;
		}
		.iaq-pbc-cover-wrap {
			position: relative !important;
			width: 100% !important;
			height: 190px !important;
			background: #f8fafc !important;
			overflow: hidden !important;
		}
		.iaq-pbc-cover-img {
			width: 100% !important;
			height: 100% !important;
			object-fit: cover !important;
			display: block !important;
		}
		.iaq-pbc-body {
			padding: 18px 20px 20px !important;
			display: flex !important;
			flex-direction: column !important;
			flex: 1 1 auto !important;
		}
		.iaq-pbc-title {
			font-size: 18px !important;
			font-weight: 800 !important;
			color: #0f172a !important;
			line-height: 1.3 !important;
			margin: 0 0 6px 0 !important;
		}
		.iaq-pbc-title a {
			color: #0f172a !important;
			text-decoration: none !important;
		}
		.iaq-pbc-author {
			display: flex !important;
			align-items: center !important;
			gap: 6px !important;
			font-size: 13px !important;
			font-weight: 600 !important;
			color: #475569 !important;
			margin-bottom: 8px !important;
		}
		.iaq-pbc-desc {
			font-size: 13px !important;
			line-height: 1.55 !important;
			color: #64748b !important;
			margin: 0 0 16px 0 !important;
			flex: 1 1 auto !important;
		}
		.iaq-pbc-meta-row {
			display: flex !important;
			align-items: center !important;
			justify-content: space-between !important;
			gap: 6px !important;
			padding: 12px 0 !important;
			border-top: 1px solid #f1f5f9 !important;
			margin-bottom: 16px !important;
			flex-wrap: wrap !important;
		}
		.iaq-pbc-meta-item {
			display: inline-flex !important;
			align-items: center !important;
			gap: 6px !important;
			font-size: 11.5px !important;
			font-weight: 650 !important;
			color: #334155 !important;
			white-space: nowrap !important;
		}
		.iaq-pbc-btn {
			width: 100% !important;
			display: flex !important;
			align-items: center !important;
			justify-content: space-between !important;
			padding: 10px 10px 10px 22px !important;
			border-radius: 9999px !important;
			text-decoration: none !important;
			font-size: 14.5px !important;
			font-weight: 700 !important;
			color: #ffffff !important;
			border: none !important;
			background: linear-gradient(135deg, #065f46 0%, #047857 100%) !important;
			box-shadow: 0 4px 14px rgba(6, 95, 70, 0.28) !important;
			box-sizing: border-box !important;
		}
		.iaq-pbc-btn:hover {
			background: linear-gradient(135deg, #047857 0%, #065f46 100%) !important;
		}
		.iaq-pbc-btn-arrow {
			width: 32px !important;
			height: 32px !important;
			border-radius: 50% !important;
			background: rgba(255, 255, 255, 0.9) !important;
			color: #065f46 !important;
			display: inline-flex !important;
			align-items: center !important;
			justify-content: center !important;
		}
		</style>
		<div class="iaq-book-catalog-hero">
			<div class="iaq-book-hero-left">
				<div class="iaq-book-hero-breadcrumb">
					<a href="<?php echo esc_url( $page_url() ); ?>" class="iaq-bh-home-icon" title="<?php esc_attr_e( 'Back to Practice Sets', 'indianagri-quiz-engine' ); ?>">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
					</a>
					<a href="<?php echo esc_url( $page_url() ); ?>" style="color:#15803d;font-weight:600;text-decoration:none;"><?php esc_html_e( 'Practice Sets', 'indianagri-quiz-engine' ); ?></a>
					<span class="iaq-bh-sep" style="color:#94a3b8;">&rsaquo;</span>
					<span class="iaq-bh-current" style="color:#0f172a;font-weight:600;"><?php esc_html_e( 'Practice by Book', 'indianagri-quiz-engine' ); ?></span>
				</div>
				<h1 class="iaq-book-hero-title"><?php esc_html_e( 'Practice by Book — Standard Reference Literature', 'indianagri-quiz-engine' ); ?></h1>
				<p class="iaq-book-hero-sub"><?php esc_html_e( 'Solve chapter-wise practice tests based on authoritative agricultural textbooks and competitive handbooks.', 'indianagri-quiz-engine' ); ?></p>
				<div class="iaq-book-hero-pills">
					<span class="iaq-hero-pill">
						<span class="iaq-hp-icon-wrap" style="color:#16a34a;display:inline-flex;">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
						</span>
						<?php esc_html_e( 'Authoritative Books', 'indianagri-quiz-engine' ); ?>
					</span>
					<span class="iaq-hero-pill">
						<span class="iaq-hp-icon-wrap" style="color:#16a34a;display:inline-flex;">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
						</span>
						<?php esc_html_e( 'Chapter-wise Practice Sets', 'indianagri-quiz-engine' ); ?>
					</span>
					<span class="iaq-hero-pill">
						<span class="iaq-hp-icon-wrap" style="color:#16a34a;display:inline-flex;">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
						</span>
						<?php esc_html_e( 'Exam-oriented MCQs', 'indianagri-quiz-engine' ); ?>
					</span>
					<span class="iaq-hero-pill">
						<span class="iaq-hp-icon-wrap" style="color:#16a34a;display:inline-flex;">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
						</span>
						<?php esc_html_e( 'Build Strong Concepts', 'indianagri-quiz-engine' ); ?>
					</span>
				</div>
			</div>
			<div class="iaq-book-hero-right">
				<img src="<?php echo esc_url( $iaq_books_img ); ?>" alt="<?php esc_attr_e( 'Standard Agricultural Reference Books', 'indianagri-quiz-engine' ); ?>" class="iaq-book-hero-art" />
			</div>
		</div>

		<div class="iaq-ps-filter-bar iaq-book-filter-bar">
			<form method="get" action="<?php echo esc_url( $page_url( 'books' ) ); ?>" class="iaq-ps-search-form">
				<input type="hidden" name="iaq_ps_view" value="books">
				<?php if ( isset( $_GET['page'] ) ) : ?>
					<input type="hidden" name="page" value="<?php echo esc_attr( sanitize_key( $_GET['page'] ) ); ?>">
				<?php endif; ?>
				<div class="iaq-ps-search-input-wrap">
					<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="iaq-input-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
					<input type="text" name="ps_search" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search by book title or author name...', 'indianagri-quiz-engine' ); ?>" class="iaq-input iaq-book-search-input">
				</div>
				<button type="submit" class="iaq-btn iaq-btn-book-search"><?php esc_html_e( 'Search', 'indianagri-quiz-engine' ); ?></button>
				<?php if ( ! empty( $search_term ) ) : ?>
					<a href="<?php echo esc_url( $page_url( 'books' ) ); ?>" class="iaq-btn iaq-btn-outline"><?php esc_html_e( 'Clear', 'indianagri-quiz-engine' ); ?></a>
				<?php endif; ?>
			</form>
			<div class="iaq-book-sort-wrap">
				<div class="iaq-book-sort-btn">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
					<span><?php esc_html_e( 'Recent First', 'indianagri-quiz-engine' ); ?></span>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
				</div>
			</div>
		</div>

		<?php
		// Ensure standard 3 books are always present even if database table is unseeded
		if ( empty( $books ) ) {
			$books = array(
				(object) array(
					'id'                  => 1,
					'title'               => 'A Competitive Book of Agriculture',
					'slug'                => 'competitive-book-of-agriculture',
					'author'              => 'Nem Raj Sundha',
					'description'         => 'Comprehensive handbook covering General Agriculture, Agronomy, Soil Science, Genetics and allied subjects for ICAR, JRF, and State PSCs.',
					'cover_image_url'     => '',
					'subject_id'          => 0,
					'examination_id'      => 0,
					'access_type'         => 'paid',
					'display_order'       => 1,
					'status'              => 'published',
					'subject_name'        => null,
					'examination_name'    => null,
					'practice_sets_count' => 69,
				),
				(object) array(
					'id'                  => 4,
					'title'               => 'Instant Horticulture',
					'slug'                => 'instant-horticulture',
					'author'              => 'S N Gupta',
					'description'         => 'Chapter-wise practice sets on fruits, vegetables, floriculture, landscaping and allied areas for competitive examinations.',
					'cover_image_url'     => '',
					'subject_id'          => 4,
					'examination_id'      => 0,
					'access_type'         => 'paid',
					'display_order'       => 2,
					'status'              => 'published',
					'subject_name'        => 'Horticulture',
					'examination_name'    => null,
					'practice_sets_count' => 1,
				),
				(object) array(
					'id'                  => 5,
					'title'               => 'Objective Agriculture',
					'slug'                => 'objective-agriculture',
					'author'              => 'S R Kantwa',
					'description'         => 'Topic-wise objective questions covering major agricultural subjects for ICAR, ASRB NET, CUET PG and IBPS AFO.',
					'cover_image_url'     => '',
					'subject_id'          => 0,
					'examination_id'      => 0,
					'access_type'         => 'paid',
					'display_order'       => 3,
					'status'              => 'published',
					'subject_name'        => 'Mixed Subjects',
					'examination_name'    => null,
					'practice_sets_count' => 67,
				),
			);
		}
		?>

		<div class="iaq-ps-cards-grid iaq-ps-books-grid">
			<?php foreach ( $books as $b ) :
				$title_lower = strtolower( $b->title );
				if ( false !== strpos( $title_lower, 'competitive' ) ) {
					$cover_img = $iaq_img_base . '/assets/images/book_competitive_agri.jpg';
				} elseif ( false !== strpos( $title_lower, 'horticulture' ) ) {
					$cover_img = $iaq_img_base . '/assets/images/book_instant_horticulture.jpg';
				} elseif ( false !== strpos( $title_lower, 'objective' ) ) {
					$cover_img = $iaq_img_base . '/assets/images/book_objective_agri.jpg';
				} elseif ( ! empty( $b->cover_image_url ) ) {
					$cover_img = $b->cover_image_url;
				} else {
					$cover_img = $iaq_img_base . '/assets/images/book_general_agri.jpg';
				}

				$raw_author = ! empty( $b->author ) ? trim( $b->author ) : '';
				$clean_author = preg_replace( '/^(by\s+)?ref[e|r]+nce\s*:\s*/i', '', $raw_author );

				$b_desc = ! empty( $b->description ) ? trim( $b->description ) : '';
				if ( empty( $b_desc ) ) {
					if ( false !== strpos( $title_lower, 'competitive' ) ) {
						$b_desc = 'Comprehensive handbook covering General Agriculture, Agronomy, Soil Science, Genetics and allied subjects for ICAR, JRF, and State PSCs.';
					} elseif ( false !== strpos( $title_lower, 'horticulture' ) ) {
						$b_desc = 'Chapter-wise practice sets on fruits, vegetables, floriculture, landscaping and allied areas for competitive examinations.';
					} elseif ( false !== strpos( $title_lower, 'objective' ) ) {
						$b_desc = 'Topic-wise objective questions covering major agricultural subjects for ICAR, ASRB NET, CUET PG and IBPS AFO.';
					} else {
						$b_desc = 'Comprehensive handbook covering General Agriculture, Agronomy, Soil Science, Genetics and allied subjects.';
					}
				}

				$sets_num = (int) $b->practice_sets_count;
				if ( false !== strpos( $title_lower, 'competitive' ) ) {
					$q_label    = '5,000+ Questions';
					$subj_label = '10+ Subjects';
				} elseif ( false !== strpos( $title_lower, 'horticulture' ) ) {
					$q_label    = '1,000+ Questions';
					$subj_label = ! empty( $b->subject_name ) ? $b->subject_name : 'Horticulture';
				} elseif ( false !== strpos( $title_lower, 'objective' ) ) {
					$q_label    = '3,000+ Questions';
					$subj_label = ! empty( $b->subject_name ) ? $b->subject_name : 'Mixed Subjects';
				} else {
					$q_label    = $sets_num >= 50 ? '5,000+ Questions' : ( $sets_num >= 20 ? '3,000+ Questions' : '1,000+ Questions' );
					$subj_label = ! empty( $b->subject_name ) ? $b->subject_name : ( $sets_num > 10 ? '10+ Subjects' : 'General Agriculture' );
				}

				$book_link = $page_url( 'book', array( 'book_id' => $b->id ) );
			?>
				<div class="iaq-ps-book-card iaq-premium-book-card" onclick="window.location.href='<?php echo esc_url( $book_link ); ?>';">
					<div class="iaq-pbc-cover-wrap">
						<img src="<?php echo esc_url( $cover_img ); ?>" alt="<?php echo esc_attr( $b->title ); ?>" class="iaq-pbc-cover-img" loading="lazy">
						<div class="iaq-pbc-badge-top-left">
							<?php if ( 'free' === $b->access_type ) : ?>
								<span class="iaq-pbc-badge-free">
									<span>⭐</span>
									<?php esc_html_e( 'FREE ACCESS', 'indianagri-quiz-engine' ); ?>
								</span>
							<?php else : ?>
								<span class="iaq-pbc-badge-premium">
									<span class="iaq-badge-crown">👑</span>
									<?php esc_html_e( 'PREMIUM ACCESS', 'indianagri-quiz-engine' ); ?>
								</span>
							<?php endif; ?>
						</div>
						<button type="button" class="iaq-pbc-favorite-btn" onclick="event.stopPropagation(); this.classList.toggle('is-favorited');" title="<?php esc_attr_e( 'Bookmark', 'indianagri-quiz-engine' ); ?>">
							<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
						</button>
					</div>

					<div class="iaq-pbc-body">
						<h3 class="iaq-pbc-title">
							<a href="<?php echo esc_url( $book_link ); ?>" onclick="event.stopPropagation();">
								<?php echo esc_html( $b->title ); ?>
							</a>
						</h3>

						<?php if ( ! empty( $clean_author ) ) : ?>
							<div class="iaq-pbc-author">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
								<span><?php printf( esc_html__( 'By Reference: %s', 'indianagri-quiz-engine' ), esc_html( $clean_author ) ); ?></span>
							</div>
						<?php endif; ?>

						<p class="iaq-pbc-desc">
							<?php echo esc_html( $b_desc ); ?>
						</p>

						<div class="iaq-pbc-meta-row">
							<div class="iaq-pbc-meta-item">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
								<span><strong><?php echo $sets_num; ?></strong> <?php esc_html_e( 'Practice Sets', 'indianagri-quiz-engine' ); ?></span>
							</div>
							<div class="iaq-pbc-meta-item">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
								<span><?php echo esc_html( $subj_label ); ?></span>
							</div>
							<div class="iaq-pbc-meta-item">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
								<span><strong><?php echo esc_html( $q_label ); ?></strong></span>
							</div>
						</div>

						<a href="<?php echo esc_url( $book_link ); ?>" class="iaq-pbc-btn" onclick="event.stopPropagation();">
							<span class="iaq-pbc-btn-label"><?php esc_html_e( 'Explore Practice Sets &rarr;', 'indianagri-quiz-engine' ); ?></span>
							<span class="iaq-pbc-btn-arrow">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
							</span>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

	<?php
	// =========================================================================
	// VIEW 3: PRACTICE BY BOOK — INDIVIDUAL BOOK DETAILS & SETS LIST
	// =========================================================================
	elseif ( 'book' === $view && ! empty( $book ) ) :
		?>
		<!-- Book Header Card -->
		<div class="iaq-ps-detail-hero">
			<div class="iaq-ps-detail-cover">
				<?php if ( ! empty( $book->cover_image_url ) ) : ?>
					<img src="<?php echo esc_url( $book->cover_image_url ); ?>" alt="<?php echo esc_attr( $book->title ); ?>">
				<?php else : ?>
					<div class="iaq-ps-detail-cover-ph">
						<svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
					</div>
				<?php endif; ?>
			</div>
			<div class="iaq-ps-detail-info">
				<div class="iaq-badges-line">
					<span class="iaq-pill iaq-pill-category"><?php esc_html_e( 'Practice Book', 'indianagri-quiz-engine' ); ?></span>
					<?php if ( 'paid' === $book->access_type ) : ?>
						<span class="iaq-pill iaq-pill-paid"><?php esc_html_e( 'Premium Book', 'indianagri-quiz-engine' ); ?></span>
					<?php else : ?>
						<span class="iaq-pill iaq-pill-free"><?php esc_html_e( 'Free Practice Sets', 'indianagri-quiz-engine' ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $book->subject_name ) ) : ?>
						<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $book->subject_name ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $book->examination_name ) ) : ?>
						<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $book->examination_name ); ?></span>
					<?php endif; ?>
				</div>

				<h2 class="iaq-ps-detail-title"><?php echo esc_html( $book->title ); ?></h2>

				<?php if ( ! empty( $book->author ) ) : ?>
					<div class="iaq-ps-detail-author">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/></svg> <?php printf( esc_html__( 'Author / Source: %s', 'indianagri-quiz-engine' ), esc_html( $book->author ) ); ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $book->description ) ) : ?>
					<div class="iaq-ps-detail-desc"><?php echo wp_kses_post( wpautop( $book->description ) ); ?></div>
				<?php endif; ?>

				<div class="iaq-ps-detail-stats">
					<div class="iaq-ps-stat-box">
						<span class="iaq-ps-stat-num"><?php echo count( $quizzes ); ?></span>
						<span class="iaq-ps-stat-lbl"><?php esc_html_e( 'Practice Sets', 'indianagri-quiz-engine' ); ?></span>
					</div>
					<div class="iaq-ps-stat-box">
						<span class="iaq-ps-stat-num">
							<?php
							$total_book_qs = array_sum( array_map( function( $q ) { return (int) $q->total_questions; }, $quizzes ) );
							echo esc_html( $total_book_qs );
							?>
						</span>
						<span class="iaq-ps-stat-lbl"><?php esc_html_e( 'Total Questions', 'indianagri-quiz-engine' ); ?></span>
					</div>
				</div>
			</div>
		</div>

		<!-- Practice Sets List -->
		<div class="iaq-ps-section-header">
			<h3><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg> <?php esc_html_e( 'Available Practice Sets', 'indianagri-quiz-engine' ); ?></h3>
			<span class="iaq-card-help"><?php esc_html_e( 'Numbered in sequence according to chapter progression.', 'indianagri-quiz-engine' ); ?></span>
		</div>

		<?php if ( empty( $quizzes ) ) : ?>
			<div class="iaq-ps-empty-state">
				<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
				<h3><?php esc_html_e( 'No Practice Sets Available for this Book', 'indianagri-quiz-engine' ); ?></h3>
				<p><?php esc_html_e( 'No practice sets have been added to this book yet.', 'indianagri-quiz-engine' ); ?></p>
				<a href="<?php echo esc_url( $page_url( 'books' ) ); ?>" class="iaq-btn iaq-btn-outline" style="margin-top:12px;">
					&larr; <?php esc_html_e( 'Back to Books Catalog', 'indianagri-quiz-engine' ); ?>
				</a>
			</div>
		<?php else : ?>
			<div class="iaq-ps-sets-list">
				<?php foreach ( $quizzes as $pq ) : ?>
					<?php include IAQ_PLUGIN_DIR . 'templates/frontend/practice-set-card.php'; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	<?php
	// =========================================================================
	// VIEW 4: PRACTICE BY SUBJECTS — SUBJECTS CATALOG
	// =========================================================================
	elseif ( 'subjects' === $view ) :
		?>
		<div class="iaq-ps-filter-bar">
			<form method="get" action="<?php echo esc_url( $page_url( 'subjects' ) ); ?>" class="iaq-ps-search-form">
				<input type="hidden" name="iaq_ps_view" value="subjects">
				<?php if ( isset( $_GET['page'] ) ) : ?>
					<input type="hidden" name="page" value="<?php echo esc_attr( sanitize_key( $_GET['page'] ) ); ?>">
				<?php endif; ?>
				<div class="iaq-ps-search-input-wrap">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="iaq-input-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
					<input type="text" name="ps_search" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search by subject name...', 'indianagri-quiz-engine' ); ?>" class="iaq-input">
				</div>
				<button type="submit" class="iaq-btn iaq-btn-primary iaq-btn-subject"><?php esc_html_e( 'Search', 'indianagri-quiz-engine' ); ?></button>
				<?php if ( ! empty( $search_term ) ) : ?>
					<a href="<?php echo esc_url( $page_url( 'subjects' ) ); ?>" class="iaq-btn iaq-btn-outline"><?php esc_html_e( 'Clear', 'indianagri-quiz-engine' ); ?></a>
				<?php endif; ?>
			</form>
		</div>

		<?php if ( empty( $subjects ) ) : ?>
			<div class="iaq-ps-empty-state">
				<svg width="54" height="54" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><rect width="7" height="7" x="3" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="14" rx="1.5"/><rect width="7" height="7" x="3" y="14" rx="1.5"/></svg>
				<h3><?php esc_html_e( 'No Subjects Found', 'indianagri-quiz-engine' ); ?></h3>
				<p><?php esc_html_e( 'No subjects currently match your search criteria.', 'indianagri-quiz-engine' ); ?></p>
			</div>
		<?php else : ?>
			<div class="iaq-ps-cards-grid iaq-ps-subjects-grid">
				<?php foreach ( $subjects as $subj ) : ?>
					<div class="iaq-ps-subject-card">
						<div class="iaq-ps-subject-icon-bar">
							<div class="iaq-ps-subject-avatar">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
							</div>
							<span class="iaq-pill iaq-pill-category">
								<?php printf( esc_html__( '%d Practice Sets', 'indianagri-quiz-engine' ), (int) $subj->practice_sets_count ); ?>
							</span>
						</div>

						<h3 class="iaq-ps-subject-title">
							<a href="<?php echo esc_url( $page_url( 'subject', array( 'subject_id' => $subj->id ) ) ); ?>">
								<?php echo esc_html( $subj->name ); ?>
							</a>
						</h3>

						<?php if ( ! empty( $subj->description ) ) : ?>
							<p class="iaq-ps-subject-desc">
								<?php echo esc_html( wp_trim_words( $subj->description, 16 ) ); ?>
							</p>
						<?php endif; ?>

						<div class="iaq-ps-subject-meta">
							<span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg> <?php printf( esc_html__( '%d Topics', 'indianagri-quiz-engine' ), (int) $subj->topics_count ); ?></span>
						</div>

						<div class="iaq-ps-subject-action">
							<a href="<?php echo esc_url( $page_url( 'subject', array( 'subject_id' => $subj->id ) ) ); ?>" class="iaq-btn iaq-btn-primary iaq-btn-subject iaq-full-width">
								<?php esc_html_e( 'Explore Subject', 'indianagri-quiz-engine' ); ?> &rarr;
							</a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	<?php
	// =========================================================================
	// VIEW 5: PRACTICE BY SUBJECTS — INDIVIDUAL SUBJECT SETS LIST
	// =========================================================================
	elseif ( 'subject' === $view && ! empty( $subject ) ) :
		?>
		<div class="iaq-ps-detail-hero iaq-ps-subject-hero">
			<div class="iaq-ps-detail-info" style="width:100%;">
				<div class="iaq-badges-line">
					<span class="iaq-pill iaq-pill-category"><?php esc_html_e( 'Practice by Subject', 'indianagri-quiz-engine' ); ?></span>
					<span class="iaq-pill" style="background:#e8f5e9;color:#117E3A;font-weight:700;">
						<?php printf( esc_html__( '%d Practice Sets', 'indianagri-quiz-engine' ), count( $quizzes ) ); ?>
					</span>
				</div>

				<h2 class="iaq-ps-detail-title"><?php echo esc_html( $subject->name ); ?></h2>

				<?php if ( ! empty( $subject->description ) ) : ?>
					<div class="iaq-ps-detail-desc"><?php echo wp_kses_post( wpautop( $subject->description ) ); ?></div>
				<?php endif; ?>
			</div>
		</div>

		<div class="iaq-ps-section-header">
			<h3><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg> <?php esc_html_e( 'Subject Practice Sets (Sequential Drills)', 'indianagri-quiz-engine' ); ?></h3>
			<span class="iaq-card-help"><?php esc_html_e( 'Sequential drills arranged by topic.', 'indianagri-quiz-engine' ); ?></span>
		</div>

		<?php if ( empty( $quizzes ) ) : ?>
			<div class="iaq-ps-empty-state">
				<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
				<h3><?php esc_html_e( 'No Practice Sets Available for this Subject', 'indianagri-quiz-engine' ); ?></h3>
				<p><?php esc_html_e( 'No practice sets have been created for this subject yet.', 'indianagri-quiz-engine' ); ?></p>
				<a href="<?php echo esc_url( $page_url( 'subjects' ) ); ?>" class="iaq-btn iaq-btn-outline" style="margin-top:12px;">
					&larr; <?php esc_html_e( 'Back to All Subjects', 'indianagri-quiz-engine' ); ?>
				</a>
			</div>
		<?php else : ?>
			<div class="iaq-ps-sets-list">
				<?php foreach ( $quizzes as $pq ) : ?>
					<?php include IAQ_PLUGIN_DIR . 'templates/frontend/practice-set-card.php'; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	<?php
	// =========================================================================
	// VIEW 6: PRACTICE BY EXAMS — EXAMINATIONS CATALOG
	// =========================================================================
	elseif ( 'exams' === $view ) :
		?>
		<div class="iaq-ps-filter-bar">
			<form method="get" action="<?php echo esc_url( $page_url( 'exams' ) ); ?>" class="iaq-ps-search-form">
				<input type="hidden" name="iaq_ps_view" value="exams">
				<?php if ( isset( $_GET['page'] ) ) : ?>
					<input type="hidden" name="page" value="<?php echo esc_attr( sanitize_key( $_GET['page'] ) ); ?>">
				<?php endif; ?>
				<div class="iaq-ps-search-input-wrap">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="iaq-input-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
					<input type="text" name="ps_search" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search by exam name or code (e.g., ICAR, NET, UPCATET)...', 'indianagri-quiz-engine' ); ?>" class="iaq-input">
				</div>
				<button type="submit" class="iaq-btn iaq-btn-primary iaq-btn-exam"><?php esc_html_e( 'Search', 'indianagri-quiz-engine' ); ?></button>
				<?php if ( ! empty( $search_term ) ) : ?>
					<a href="<?php echo esc_url( $page_url( 'exams' ) ); ?>" class="iaq-btn iaq-btn-outline"><?php esc_html_e( 'Clear', 'indianagri-quiz-engine' ); ?></a>
				<?php endif; ?>
			</form>
		</div>

		<?php if ( empty( $exams ) ) : ?>
			<div class="iaq-ps-empty-state">
				<svg width="54" height="54" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
				<h3><?php esc_html_e( 'No Examinations Found', 'indianagri-quiz-engine' ); ?></h3>
				<p><?php esc_html_e( 'No competitive examinations match your search criteria.', 'indianagri-quiz-engine' ); ?></p>
			</div>
		<?php else : ?>
			<div class="iaq-ps-cards-grid iaq-ps-exams-grid">
				<?php foreach ( $exams as $ex ) : ?>
					<div class="iaq-ps-exam-card">
						<div class="iaq-ps-exam-top">
							<span class="iaq-pill iaq-pill-category" style="background:#fef3c7;color:#b45309;font-weight:700;">
								<?php echo esc_html( $ex->code ); ?>
							</span>
							<?php if ( ! empty( $ex->category ) ) : ?>
								<span class="iaq-ps-exam-group"><?php echo esc_html( $ex->category ); ?></span>
							<?php endif; ?>
						</div>

						<h3 class="iaq-ps-exam-title">
							<a href="<?php echo esc_url( $page_url( 'exam', array( 'exam_id' => $ex->id ) ) ); ?>">
								<?php echo esc_html( $ex->name ); ?>
							</a>
						</h3>

						<?php if ( ! empty( $ex->description ) ) : ?>
							<p class="iaq-ps-exam-desc">
								<?php echo esc_html( wp_trim_words( $ex->description, 16 ) ); ?>
							</p>
						<?php endif; ?>

						<div class="iaq-ps-exam-meta">
							<span class="iaq-ps-meta-stat">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><rect width="7" height="7" x="3" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="14" rx="1.5"/><rect width="7" height="7" x="3" y="14" rx="1.5"/></svg>
								<strong><?php echo (int) $ex->assigned_subjects_count; ?></strong> <?php echo esc_html( _n( 'Subject', 'Subjects', (int) $ex->assigned_subjects_count, 'indianagri-quiz-engine' ) ); ?>
							</span>
							<span class="iaq-ps-meta-stat">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
								<strong><?php echo (int) $ex->practice_sets_count; ?></strong> <?php echo esc_html( _n( 'Set', 'Sets', (int) $ex->practice_sets_count, 'indianagri-quiz-engine' ) ); ?>
							</span>
						</div>

						<div class="iaq-ps-exam-action">
							<a href="<?php echo esc_url( $page_url( 'exam', array( 'exam_id' => $ex->id ) ) ); ?>" class="iaq-btn iaq-btn-primary iaq-btn-exam iaq-full-width">
								<?php esc_html_e( 'Explore Examination', 'indianagri-quiz-engine' ); ?> &rarr;
							</a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	<?php
	// =========================================================================
	// VIEW 7: PRACTICE BY EXAMS — EXAMINATION SUBJECT SELECTION
	// =========================================================================
	elseif ( 'exam' === $view && ! empty( $exam ) ) :
		?>
		<div class="iaq-ps-detail-hero iaq-ps-exam-hero">
			<div class="iaq-ps-detail-info" style="width:100%;">
				<div class="iaq-badges-line">
					<span class="iaq-pill iaq-pill-category" style="background:#fef3c7;color:#b45309;"><?php esc_html_e( 'Competitive Examination', 'indianagri-quiz-engine' ); ?></span>
					<?php if ( ! empty( $exam->code ) ) : ?>
						<span class="iaq-pill" style="background:#e0f2fe;color:#0369a1;font-weight:700;"><?php echo esc_html( $exam->code ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $exam->category ) ) : ?>
						<span class="iaq-pill" style="background:#f0fdf4;color:#117E3A;"><?php echo esc_html( $exam->category ); ?></span>
					<?php endif; ?>
				</div>

				<h2 class="iaq-ps-detail-title"><?php echo esc_html( $exam->name ); ?></h2>

				<?php if ( ! empty( $exam->description ) ) : ?>
					<div class="iaq-ps-detail-desc"><?php echo wp_kses_post( wpautop( $exam->description ) ); ?></div>
				<?php endif; ?>
			</div>
		</div>

		<div class="iaq-ps-section-header">
			<h3><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><rect width="7" height="7" x="3" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="14" rx="1.5"/><rect width="7" height="7" x="3" y="14" rx="1.5"/></svg> <?php esc_html_e( 'Select a Subject under this Examination', 'indianagri-quiz-engine' ); ?></h3>
			<span class="iaq-card-help"><?php esc_html_e( 'Choose a subject to view available practice sets.', 'indianagri-quiz-engine' ); ?></span>
		</div>

		<?php if ( empty( $exam_subjects ) ) : ?>
			<div class="iaq-ps-empty-state">
				<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><rect width="7" height="7" x="3" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="14" rx="1.5"/><rect width="7" height="7" x="3" y="14" rx="1.5"/></svg>
				<h3><?php esc_html_e( 'No Subjects Assigned to this Examination', 'indianagri-quiz-engine' ); ?></h3>
				<p><?php esc_html_e( 'No subjects have been configured for this examination yet.', 'indianagri-quiz-engine' ); ?></p>
				<a href="<?php echo esc_url( $page_url( 'exams' ) ); ?>" class="iaq-btn iaq-btn-outline" style="margin-top:12px;">
					&larr; <?php esc_html_e( 'Back to All Examinations', 'indianagri-quiz-engine' ); ?>
				</a>
			</div>
		<?php else : ?>
			<div class="iaq-ps-cards-grid iaq-ps-subjects-grid">
				<?php foreach ( $exam_subjects as $esub ) : ?>
					<div class="iaq-ps-subject-card">
						<div class="iaq-ps-subject-icon-bar">
							<div class="iaq-ps-subject-avatar" style="background:#fef3c7;color:#b45309;">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
							</div>
							<span class="iaq-pill" style="background:#f0fdf4;color:#117E3A;font-weight:700;">
								<?php
								$scount = (int) $esub->practice_sets_count;
								printf(
									esc_html( _n( '%d Set Available', '%d Sets Available', $scount, 'indianagri-quiz-engine' ) ),
									$scount
								);
								?>
							</span>
						</div>

						<h3 class="iaq-ps-subject-title">
							<a href="<?php echo esc_url( $page_url( 'exam_subject', array( 'exam_id' => $exam->id, 'subject_id' => $esub->id ) ) ); ?>">
								<?php echo esc_html( $esub->name ); ?>
							</a>
						</h3>

						<?php if ( ! empty( $esub->description ) ) : ?>
							<p class="iaq-ps-subject-desc">
								<?php echo esc_html( wp_trim_words( $esub->description, 16 ) ); ?>
							</p>
						<?php endif; ?>

						<div class="iaq-ps-subject-action">
							<a href="<?php echo esc_url( $page_url( 'exam_subject', array( 'exam_id' => $exam->id, 'subject_id' => $esub->id ) ) ); ?>" class="iaq-btn iaq-btn-primary iaq-btn-exam iaq-full-width">
								<?php esc_html_e( 'View Practice Sets', 'indianagri-quiz-engine' ); ?> &rarr;
							</a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	<?php
	// =========================================================================
	// VIEW 8: PRACTICE BY EXAMS — EXAMINATION + SUBJECT PRACTICE SETS LIST
	// =========================================================================
	elseif ( 'exam_subject' === $view && ! empty( $exam ) && ! empty( $subject ) ) :
		?>
		<div class="iaq-ps-detail-hero iaq-ps-exam-hero">
			<div class="iaq-ps-detail-info" style="width:100%;">
				<div class="iaq-badges-line">
					<span class="iaq-pill iaq-pill-category" style="background:#fef3c7;color:#b45309;"><?php echo esc_html( $exam->code ); ?></span>
					<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $subject->name ); ?></span>
					<span class="iaq-pill" style="background:#f0fdf4;color:#117E3A;font-weight:700;">
						<?php
						$qcount = count( $quizzes );
						printf(
							esc_html( _n( '%d Practice Set', '%d Practice Sets', $qcount, 'indianagri-quiz-engine' ) ),
							$qcount
						);
						?>
					</span>
				</div>

				<h2 class="iaq-ps-detail-title"><?php printf( esc_html__( '%1$s — %2$s Practice Sets', 'indianagri-quiz-engine' ), esc_html( $exam->name ), esc_html( $subject->name ) ); ?></h2>
				<p class="iaq-ps-subtitle" style="margin:4px 0 0 0;font-size:13px;color:#64748b;">
					<?php esc_html_e( 'Sequential practice drills and question sets for this subject.', 'indianagri-quiz-engine' ); ?>
				</p>
			</div>
		</div>

		<div class="iaq-ps-section-header">
			<h3><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg> <?php esc_html_e( 'Examination Subject Practice Sets', 'indianagri-quiz-engine' ); ?></h3>
			<span class="iaq-card-help"><?php esc_html_e( 'Sequential drills with detailed explanations.', 'indianagri-quiz-engine' ); ?></span>
		</div>

		<?php if ( empty( $quizzes ) ) : ?>
			<div class="iaq-ps-empty-state">
				<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
				<h3><?php esc_html_e( 'No Practice Sets Available for this Combination', 'indianagri-quiz-engine' ); ?></h3>
				<p><?php esc_html_e( 'No practice sets have been added to this examination and subject yet.', 'indianagri-quiz-engine' ); ?></p>
				<a href="<?php echo esc_url( $page_url( 'exam', array( 'exam_id' => $exam->id ) ) ); ?>" class="iaq-btn iaq-btn-outline" style="margin-top:12px;">
					&larr; <?php printf( esc_html__( 'Back to %s Subjects', 'indianagri-quiz-engine' ), esc_html( $exam->code ) ); ?>
				</a>
			</div>
		<?php else : ?>
			<div class="iaq-ps-sets-list">
				<?php foreach ( $quizzes as $pq ) : ?>
					<?php include IAQ_PLUGIN_DIR . 'templates/frontend/practice-set-card.php'; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	<?php endif; ?>

</div>
