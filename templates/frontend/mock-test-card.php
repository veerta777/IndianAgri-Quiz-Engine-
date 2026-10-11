<?php
/**
 * Frontend: Individual Mock Test Card Component.
 * Displays contextual Mock Test #, metadata, free/premium badges, attempt state, and launch CTAs.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $quiz ) ) {
	return;
}

$test_num     = isset( $quiz->mock_test_number ) ? (int) $quiz->mock_test_number : 1;
$is_paid      = ( 'paid' === $quiz->access_type );
$is_accessible= isset( $quiz->is_accessible ) ? (bool) $quiz->is_accessible : true;
$attempt_st   = isset( $quiz->attempt_status ) ? $quiz->attempt_status : 'not_attempted';
$score_pct    = isset( $quiz->latest_percentage ) ? (float) $quiz->latest_percentage : null;
$score_val    = isset( $quiz->latest_score ) ? (float) $quiz->latest_score : null;

// Target attempt link
$attempt_url = add_query_arg(
	array( 'iaq_quiz_id' => $quiz->id ),
	function_exists( 'get_permalink' ) ? get_permalink() : '/'
);
if ( isset( $_GET['page'] ) && strpos( sanitize_key( $_GET['page'] ), 'iaq-preview' ) !== false ) {
	$attempt_url = add_query_arg(
		array(
			'page'        => sanitize_key( $_GET['page'] ),
			'iaq_quiz_id' => $quiz->id,
		),
		'/'
	);
}

$sub_plans_url = add_query_arg(
	array( 'page' => 'iaq-preview-subscription-plans' ),
	function_exists( 'get_permalink' ) ? get_permalink() : '/'
);
?>

<div class="iaq-mock-card <?php echo $is_paid ? 'is-paid' : 'is-free'; ?> <?php echo ! $is_accessible ? 'is-locked' : ''; ?>">
	
	<!-- Header Bar: Number Pill & Access Type -->
	<div class="iaq-mock-card-header">
		<div class="iaq-mock-number-badge">
			<span class="dashicons dashicons-welcome-learn-more"></span>
			<span><?php printf( esc_html__( 'Mock Test %d', 'indianagri-quiz-engine' ), $test_num ); ?></span>
		</div>

		<div class="iaq-mock-badges-wrap">
			<?php if ( $is_paid ) : ?>
				<span class="iaq-pill iaq-pill-paid">
					<span class="dashicons dashicons-lock" style="font-size:11px;line-height:1.2;"></span>
					<?php esc_html_e( 'Premium Test', 'indianagri-quiz-engine' ); ?>
				</span>
			<?php else : ?>
				<span class="iaq-pill iaq-pill-free">
					<?php esc_html_e( 'Free Test', 'indianagri-quiz-engine' ); ?>
				</span>
			<?php endif; ?>

			<?php if ( ! empty( $quiz->exam_category ) ) : ?>
				<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $quiz->exam_category ); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<!-- Title & Description -->
	<div class="iaq-mock-card-body">
		<h4 class="iaq-mock-title">
			<a href="<?php echo esc_url( $attempt_url ); ?>">
				<?php echo esc_html( $quiz->formatted_title ); ?>
			</a>
		</h4>

		<?php if ( ! empty( $quiz->description ) ) : ?>
			<p class="iaq-mock-desc">
				<?php echo esc_html( wp_trim_words( $quiz->description, 16 ) ); ?>
			</p>
		<?php endif; ?>

		<!-- Examination Specifications Grid -->
		<div class="iaq-mock-specs-grid">
			<div class="iaq-mock-spec-item" title="<?php esc_attr_e( 'Total Questions', 'indianagri-quiz-engine' ); ?>">
				<span class="dashicons dashicons-editor-help"></span>
				<strong><?php echo (int) $quiz->total_questions; ?></strong>
				<span><?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?></span>
			</div>

			<div class="iaq-mock-spec-item" title="<?php esc_attr_e( 'Total Marks', 'indianagri-quiz-engine' ); ?>">
				<span class="dashicons dashicons-chart-pie"></span>
				<strong><?php echo floatval( $quiz->total_marks ); ?></strong>
				<span><?php esc_html_e( 'Marks', 'indianagri-quiz-engine' ); ?></span>
			</div>

			<div class="iaq-mock-spec-item" title="<?php esc_attr_e( 'Test Duration', 'indianagri-quiz-engine' ); ?>">
				<span class="dashicons dashicons-clock"></span>
				<strong>
					<?php
					if ( $quiz->duration_minutes > 0 ) {
						printf( esc_html__( '%d min', 'indianagri-quiz-engine' ), (int) $quiz->duration_minutes );
					} else {
						esc_html_e( 'Untimed', 'indianagri-quiz-engine' );
					}
					?>
				</strong>
				<span><?php esc_html_e( 'Duration', 'indianagri-quiz-engine' ); ?></span>
			</div>

			<div class="iaq-mock-spec-item" title="<?php esc_attr_e( 'Negative Marking per incorrect question', 'indianagri-quiz-engine' ); ?>">
				<span class="dashicons dashicons-dismiss"></span>
				<strong>
					<?php
					if ( (float) $quiz->negative_marks > 0 ) {
						echo '-' . floatval( $quiz->negative_marks );
					} else {
						echo '0';
					}
					?>
				</strong>
				<span><?php esc_html_e( 'Negative', 'indianagri-quiz-engine' ); ?></span>
			</div>
		</div>

		<!-- Student Attempt History Indicator -->
		<div class="iaq-mock-attempt-indicator">
			<?php if ( 'completed' === $attempt_st ) : ?>
				<div class="iaq-mock-score-banner is-completed">
					<span class="dashicons dashicons-yes-alt"></span>
					<span>
						<?php printf( esc_html__( 'Completed: Score %s / %s (%s%%)', 'indianagri-quiz-engine' ), floatval( $score_val ), floatval( $quiz->total_marks ), floatval( $score_pct ) ); ?>
					</span>
				</div>
			<?php elseif ( 'in_progress' === $attempt_st ) : ?>
				<div class="iaq-mock-score-banner is-in-progress">
					<span class="dashicons dashicons-backup"></span>
					<span><?php esc_html_e( 'Attempt in progress — Answers saved', 'indianagri-quiz-engine' ); ?></span>
				</div>
			<?php else : ?>
				<div class="iaq-mock-score-banner is-not-attempted">
					<span class="dashicons dashicons-marker"></span>
					<span><?php esc_html_e( 'Fresh Mock Test — Ready to attempt', 'indianagri-quiz-engine' ); ?></span>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Action Footer -->
	<div class="iaq-mock-card-footer">
		<?php if ( ! $is_accessible ) : ?>
			<a href="<?php echo esc_url( $sub_plans_url ); ?>" class="iaq-btn iaq-btn-lock">
				<span class="dashicons dashicons-lock"></span>
				<?php esc_html_e( 'Unlock Premium Test', 'indianagri-quiz-engine' ); ?>
			</a>
		<?php elseif ( 'in_progress' === $attempt_st ) : ?>
			<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-btn iaq-btn-primary iaq-btn-resume">
				<span class="dashicons dashicons-controls-play"></span>
				<?php esc_html_e( 'Resume Mock Test', 'indianagri-quiz-engine' ); ?>
			</a>
		<?php elseif ( 'completed' === $attempt_st ) : ?>
			<div class="iaq-btn-group" style="display:flex;gap:6px;width:100%;">
				<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-btn iaq-btn-primary" style="flex:1;">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Re-attempt Test', 'indianagri-quiz-engine' ); ?>
				</a>
			</div>
		<?php else : ?>
			<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-btn iaq-btn-primary">
				<span class="dashicons dashicons-arrow-right-alt"></span>
				<?php esc_html_e( 'Start Mock Test', 'indianagri-quiz-engine' ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
