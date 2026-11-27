<?php
/**
 * Frontend: Single Practice Set Card component.
 * Rendered within Book, Subject, and Exam-Subject practice set listings.
 * Styled matching the clean accordion list row design.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $pq ) || ! is_object( $pq ) ) {
	return;
}

$user_id   = is_user_logged_in() ? get_current_user_id() : 0;
$can_solve = class_exists( 'IAQ_Access_Control' ) ? IAQ_Access_Control::can_attempt_quiz( $user_id, $pq->id ) : true;
$attempt_status = isset( $pq->attempt_status ) ? $pq->attempt_status : 'not_attempted';
$set_num   = isset( $pq->practice_set_number ) ? (int) $pq->practice_set_number : 1;

// Target URL for attempting test
$attempt_url = add_query_arg( array( 'iaq_quiz_id' => (int) $pq->id, 'iaq_mode' => 'practice' ), get_permalink() );
$result_args = array(
	'iaq_quiz_id' => (int) $pq->id,
	'iaq_action'  => 'view_result',
);
if ( ! empty( $pq->latest_attempt_id ) ) {
	$result_args['attempt_id'] = (int) $pq->latest_attempt_id;
}
$result_url = add_query_arg( $result_args, get_permalink() );

// Clean title
$display_title = ! empty( $pq->title ) ? $pq->title : sprintf( __( 'Practice Set %d', 'indianagri-quiz-engine' ), $set_num );
$action_url    = ( 'completed' === $attempt_status ) ? $result_url : $attempt_url;
?>

<div class="iaq-item-row iaq-ps-set-card" id="practice-set-card-<?php echo esc_attr( $pq->id ); ?>">
	<div class="iaq-item-left">
		<div class="iaq-item-doc-icon" title="<?php esc_attr_e( 'Practice Set', 'indianagri-quiz-engine' ); ?>">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
				<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
				<polyline points="14 2 14 8 20 8"/>
				<line x1="16" y1="13" x2="8" y2="13"/>
				<line x1="16" y1="17" x2="8" y2="17"/>
			</svg>
		</div>
		<div class="iaq-item-title-wrap">
			<h4 class="iaq-item-title">
				<a href="<?php echo esc_url( $action_url ); ?>" title="<?php echo esc_attr( $display_title ); ?>">
					<?php echo esc_html( $display_title ); ?>
				</a>
			</h4>
		</div>
	</div>

	<div class="iaq-item-center">
		<?php if ( 'completed' === $attempt_status ) : ?>
			<span class="iaq-status-badge is-completed">
				<?php esc_html_e( 'Completed', 'indianagri-quiz-engine' ); ?>
			</span>
		<?php elseif ( 'in_progress' === $attempt_status ) : ?>
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
		<?php if ( ! $can_solve ) : ?>
			<a href="<?php echo esc_url( function_exists( 'get_permalink' ) ? get_permalink() : '/subscription-plans' ); ?>" class="iaq-item-btn is-unlock" title="<?php esc_attr_e( 'Premium Practice Set — Subscription Required', 'indianagri-quiz-engine' ); ?>">
				<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
				<span><?php esc_html_e( 'Unlock', 'indianagri-quiz-engine' ); ?></span>
			</a>
		<?php elseif ( 'completed' === $attempt_status ) : ?>
			<a href="<?php echo esc_url( $result_url ); ?>" class="iaq-item-btn is-view" title="<?php esc_attr_e( 'View Result & Explanations', 'indianagri-quiz-engine' ); ?>">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
				<span><?php esc_html_e( 'View Result', 'indianagri-quiz-engine' ); ?></span>
			</a>
		<?php elseif ( 'in_progress' === $attempt_status ) : ?>
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
