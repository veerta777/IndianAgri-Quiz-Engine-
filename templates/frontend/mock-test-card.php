<?php
/**
 * Frontend: Individual Mock Test Card Component.
 * Styled matching the clean accordion list row design.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $quiz ) ) {
	return;
}

$test_num      = isset( $quiz->mock_test_number ) ? (int) $quiz->mock_test_number : 1;
$is_paid       = ( 'paid' === $quiz->access_type );
$is_accessible = isset( $quiz->is_accessible ) ? (bool) $quiz->is_accessible : true;
$attempt_st    = isset( $quiz->attempt_status ) ? $quiz->attempt_status : 'not_attempted';
$score_pct     = isset( $quiz->latest_percentage ) ? (float) $quiz->latest_percentage : null;
$score_val     = isset( $quiz->latest_score ) ? (float) $quiz->latest_score : null;

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

$display_title = ! empty( $quiz->formatted_title ) ? $quiz->formatted_title : ( ! empty( $quiz->title ) ? $quiz->title : sprintf( __( 'Mock Test %d', 'indianagri-quiz-engine' ), $test_num ) );
?>

<div class="iaq-item-row iaq-mock-card <?php echo $is_paid ? 'is-paid' : 'is-free'; ?> <?php echo ! $is_accessible ? 'is-locked' : ''; ?>" id="mock-test-card-<?php echo esc_attr( $quiz->id ); ?>">
	<div class="iaq-item-left">
		<div class="iaq-item-doc-icon" title="<?php esc_attr_e( 'Mock Test', 'indianagri-quiz-engine' ); ?>">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
				<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
				<polyline points="14 2 14 8 20 8"/>
				<line x1="16" y1="13" x2="8" y2="13"/>
				<line x1="16" y1="17" x2="8" y2="17"/>
			</svg>
		</div>
		<div class="iaq-item-title-wrap">
			<h4 class="iaq-item-title">
				<a href="<?php echo esc_url( $attempt_url ); ?>" title="<?php echo esc_attr( $display_title ); ?>">
					<?php echo esc_html( $display_title ); ?>
				</a>
			</h4>
		</div>
	</div>

	<div class="iaq-item-center">
		<?php if ( 'completed' === $attempt_st ) : ?>
			<span class="iaq-status-badge is-completed">
				<?php esc_html_e( 'Completed', 'indianagri-quiz-engine' ); ?>
			</span>
		<?php elseif ( 'in_progress' === $attempt_st ) : ?>
			<span class="iaq-status-badge is-in-progress">
				<?php esc_html_e( 'In Progress', 'indianagri-quiz-engine' ); ?>
			</span>
		<?php else : ?>
			<span class="iaq-status-badge is-not-started">
				<?php esc_html_e( 'Not Started', 'indianagri-quiz-engine' ); ?>
			</span>
		<?php endif; ?>
	</div>

	<div class="iaq-item-right">
		<?php if ( ! $is_accessible ) : ?>
			<a href="<?php echo esc_url( $sub_plans_url ); ?>" class="iaq-item-btn is-unlock" title="<?php esc_attr_e( 'Premium Mock Test — Subscription Required', 'indianagri-quiz-engine' ); ?>">
				<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
				<span><?php esc_html_e( 'Unlock', 'indianagri-quiz-engine' ); ?></span>
			</a>
		<?php elseif ( 'completed' === $attempt_st ) : ?>
			<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-item-btn is-view">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
				<span><?php esc_html_e( 'View', 'indianagri-quiz-engine' ); ?></span>
			</a>
		<?php elseif ( 'in_progress' === $attempt_st ) : ?>
			<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-item-btn is-continue">
				<svg width="11" height="11" viewBox="0 0 24 24" fill="#ffffff" stroke="#ffffff" stroke-width="1"><polygon points="5 3 19 12 5 21 5 3"/></svg>
				<span><?php esc_html_e( 'Continue', 'indianagri-quiz-engine' ); ?></span>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-item-btn is-start">
				<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
				<span><?php esc_html_e( 'Start', 'indianagri-quiz-engine' ); ?></span>
			</a>
		<?php endif; ?>
	</div>
</div>
