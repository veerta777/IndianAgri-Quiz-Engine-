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
				if ( 'landing' === $view ) {
					esc_html_e( 'Agricultural Practice Sets & Question Drills', 'indianagri-quiz-engine' );
				} elseif ( 'books' === $view ) {
					esc_html_e( 'Practice by Book — Standard Reference Literature', 'indianagri-quiz-engine' );
				} elseif ( 'book' === $view && ! empty( $book ) ) {
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
				if ( 'landing' === $view ) {
					esc_html_e( 'Choose your preferred learning pathway: solve chapter-wise questions from famous agricultural books, practice individual subjects, or target your specific competitive examination.', 'indianagri-quiz-engine' );
				} elseif ( 'books' === $view ) {
					esc_html_e( 'Solve chapter-wise practice tests based on authoritative agricultural textbooks and competitive handbooks.', 'indianagri-quiz-engine' );
				} elseif ( 'book' === $view && ! empty( $book ) ) {
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

	<?php
	// =========================================================================
	// VIEW 1: ROOT LANDING VIEW (3 HERO CATEGORIES)
	// =========================================================================
	if ( 'landing' === $view ) :
		?>
		<div class="iaq-ps-landing-grid iaq-quick-actions-row">
			<!-- Card 1: Practice by Books -->
			<div class="iaq-qa-pill-card is-green" id="card-practice-by-book" style="cursor:pointer;" onclick="window.location.href='<?php echo esc_url( $page_url( 'books' ) ); ?>';">
				<div class="iaq-qa-badge-icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
						<path d="M6 6h10"/>
						<path d="M6 10h10"/>
					</svg>
				</div>
				<h4 class="iaq-qa-card-name"><?php esc_html_e( 'Practice by Books', 'indianagri-quiz-engine' ); ?></h4>
				<p class="iaq-qa-card-desc"><?php esc_html_e( 'Chapter-wise MCQs from standard agricultural reference books & textbooks.', 'indianagri-quiz-engine' ); ?></p>
				<a href="<?php echo esc_url( $page_url( 'books' ) ); ?>" class="iaq-qa-action-pill is-green" onclick="event.stopPropagation();">
					<?php esc_html_e( 'Browse Practice Books', 'indianagri-quiz-engine' ); ?> &rarr;
				</a>
			</div>

			<!-- Card 2: Practice by Subjects -->
			<div class="iaq-qa-pill-card is-blue" id="card-practice-by-subjects" style="cursor:pointer;" onclick="window.location.href='<?php echo esc_url( $page_url( 'subjects' ) ); ?>';">
				<div class="iaq-qa-badge-icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<rect width="7" height="7" x="3" y="3" rx="1.5"/>
						<rect width="7" height="7" x="14" y="3" rx="1.5"/>
						<rect width="7" height="7" x="14" y="14" rx="1.5"/>
						<rect width="7" height="7" x="3" y="14" rx="1.5"/>
					</svg>
				</div>
				<h4 class="iaq-qa-card-name"><?php esc_html_e( 'Practice by Subjects', 'indianagri-quiz-engine' ); ?></h4>
				<p class="iaq-qa-card-desc"><?php esc_html_e( 'Topic-wise chapter drills arranged sequentially (Set 1, 2, 3...) across core disciplines.', 'indianagri-quiz-engine' ); ?></p>
				<a href="<?php echo esc_url( $page_url( 'subjects' ) ); ?>" class="iaq-qa-action-pill is-blue" onclick="event.stopPropagation();">
					<?php esc_html_e( 'Browse by Subject', 'indianagri-quiz-engine' ); ?> &rarr;
				</a>
			</div>

			<!-- Card 3: Browse by Examination -->
			<div class="iaq-qa-pill-card is-teal" id="card-practice-by-exams" style="cursor:pointer;" onclick="window.location.href='<?php echo esc_url( $page_url( 'exams' ) ); ?>';">
				<div class="iaq-qa-badge-icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="12" cy="8" r="6"/>
						<path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
					</svg>
				</div>
				<h4 class="iaq-qa-card-name"><?php esc_html_e( 'Browse by Examination', 'indianagri-quiz-engine' ); ?></h4>
				<p class="iaq-qa-card-desc"><?php esc_html_e( 'Practice by Exams: Target competitive entrance tests like ICAR JRF, ASRB NET, CUET PG & IBPS AFO.', 'indianagri-quiz-engine' ); ?></p>
				<a href="<?php echo esc_url( $page_url( 'exams' ) ); ?>" class="iaq-qa-action-pill is-teal" onclick="event.stopPropagation();">
					<?php esc_html_e( 'Browse by Examination', 'indianagri-quiz-engine' ); ?> &rarr;
				</a>
			</div>
		</div>

	<?php
	// =========================================================================
	// VIEW 2: PRACTICE BY BOOK — BOOKS CATALOG
	// =========================================================================
	elseif ( 'books' === $view ) :
		?>
		<div class="iaq-ps-filter-bar">
			<form method="get" action="<?php echo esc_url( $page_url( 'books' ) ); ?>" class="iaq-ps-search-form">
				<input type="hidden" name="iaq_ps_view" value="books">
				<?php if ( isset( $_GET['page'] ) ) : ?>
					<input type="hidden" name="page" value="<?php echo esc_attr( sanitize_key( $_GET['page'] ) ); ?>">
				<?php endif; ?>
				<div class="iaq-ps-search-input-wrap">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="iaq-input-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
					<input type="text" name="ps_search" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search by book title or author name...', 'indianagri-quiz-engine' ); ?>" class="iaq-input">
				</div>
				<button type="submit" class="iaq-btn iaq-btn-primary iaq-btn-book"><?php esc_html_e( 'Search', 'indianagri-quiz-engine' ); ?></button>
				<?php if ( ! empty( $search_term ) ) : ?>
					<a href="<?php echo esc_url( $page_url( 'books' ) ); ?>" class="iaq-btn iaq-btn-outline"><?php esc_html_e( 'Clear', 'indianagri-quiz-engine' ); ?></a>
				<?php endif; ?>
			</form>
		</div>

		<?php if ( empty( $books ) ) : ?>
			<div class="iaq-ps-empty-state">
				<svg width="54" height="54" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
				<h3><?php esc_html_e( 'No Books Have Been Published Yet', 'indianagri-quiz-engine' ); ?></h3>
				<p><?php esc_html_e( 'Check back soon as our team publishes new reference books and practice sets.', 'indianagri-quiz-engine' ); ?></p>
				<a href="<?php echo esc_url( $page_url() ); ?>" class="iaq-btn iaq-btn-outline" style="margin-top:14px;">
					&larr; <?php esc_html_e( 'Back to Practice Sets', 'indianagri-quiz-engine' ); ?>
				</a>
			</div>
		<?php else : ?>
			<div class="iaq-ps-cards-grid iaq-ps-books-grid">
				<?php foreach ( $books as $b ) : ?>
					<div class="iaq-ps-book-card">
						<div class="iaq-ps-book-cover-wrap">
							<?php if ( ! empty( $b->cover_image_url ) ) : ?>
								<img src="<?php echo esc_url( $b->cover_image_url ); ?>" alt="<?php echo esc_attr( $b->title ); ?>" class="iaq-ps-book-cover-img">
							<?php else : ?>
								<div class="iaq-ps-book-cover-placeholder">
									<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/></svg>
									<span class="iaq-ps-cover-title"><?php echo esc_html( wp_trim_words( $b->title, 4, '...' ) ); ?></span>
								</div>
							<?php endif; ?>
						</div>

						<div class="iaq-ps-book-info">
							<div class="iaq-badges-line" style="margin-bottom:6px;">
								<?php if ( 'paid' === $b->access_type ) : ?>
									<span class="iaq-pill iaq-pill-paid"><?php esc_html_e( 'Premium Access', 'indianagri-quiz-engine' ); ?></span>
								<?php else : ?>
									<span class="iaq-pill iaq-pill-free"><?php esc_html_e( 'Free Access', 'indianagri-quiz-engine' ); ?></span>
								<?php endif; ?>

								<?php if ( ! empty( $b->examination_name ) ) : ?>
									<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $b->examination_name ); ?></span>
								<?php endif; ?>
							</div>

							<h3 class="iaq-ps-book-title">
								<a href="<?php echo esc_url( $page_url( 'book', array( 'book_id' => $b->id ) ) ); ?>">
									<?php echo esc_html( $b->title ); ?>
								</a>
							</h3>

							<?php if ( ! empty( $b->author ) ) : ?>
								<div class="iaq-ps-book-author">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/></svg>
									<span><?php printf( esc_html__( 'By %s', 'indianagri-quiz-engine' ), esc_html( $b->author ) ); ?></span>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $b->description ) ) : ?>
								<p class="iaq-ps-book-desc">
									<?php echo esc_html( wp_trim_words( $b->description, 18 ) ); ?>
								</p>
							<?php endif; ?>

							<div class="iaq-ps-book-meta">
								<span class="iaq-ps-meta-stat">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
									<strong><?php echo (int) $b->practice_sets_count; ?></strong> <?php esc_html_e( 'Practice Sets', 'indianagri-quiz-engine' ); ?>
								</span>
								<?php if ( ! empty( $b->subject_name ) ) : ?>
									<span class="iaq-ps-meta-stat">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
										<?php echo esc_html( $b->subject_name ); ?>
									</span>
								<?php endif; ?>
							</div>

							<div class="iaq-ps-book-footer">
								<a href="<?php echo esc_url( $page_url( 'book', array( 'book_id' => $b->id ) ) ); ?>" class="iaq-btn iaq-btn-primary iaq-btn-book iaq-full-width">
									<?php esc_html_e( 'Explore Practice Sets', 'indianagri-quiz-engine' ); ?> &rarr;
								</a>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

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
