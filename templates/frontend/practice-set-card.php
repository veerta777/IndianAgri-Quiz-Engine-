<?php
/**
 * Frontend: Single Practice Set Card component.
 * Rendered within Book, Subject, and Exam-Subject practice set listings.
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
$attempt_url = add_query_arg( array( 'iaq_quiz_id' => (int) $pq->id ), get_permalink() );
?>

<div class="iaq-ps-set-card" id="practice-set-card-<?php echo esc_attr( $pq->id ); ?>">
	<div class="iaq-ps-set-left">
		<div class="iaq-ps-set-badge-col">
			<span class="iaq-ps-num-badge">
				<?php printf( esc_html__( 'Set %d', 'indianagri-quiz-engine' ), $set_num ); ?>
			</span>
		</div>
		<div class="iaq-ps-set-details">
			<div class="iaq-badges-line" style="margin-bottom:4px;">
				<?php if ( 'paid' === $pq->access_type ) : ?>
					<span class="iaq-pill iaq-pill-paid"><?php esc_html_e( 'Premium', 'indianagri-quiz-engine' ); ?></span>
				<?php else : ?>
					<span class="iaq-pill iaq-pill-free"><?php esc_html_e( 'Free', 'indianagri-quiz-engine' ); ?></span>
				<?php endif; ?>

				<?php if ( 'completed' === $attempt_status ) : ?>
					<span class="iaq-pill iaq-pill-completed">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><polyline points="20 6 9 17 4 12"/></svg>
						<?php
						if ( null !== $pq->latest_percentage ) {
							printf( esc_html__( 'Completed (%d%%)', 'indianagri-quiz-engine' ), round( $pq->latest_percentage ) );
						} else {
							esc_html_e( 'Completed', 'indianagri-quiz-engine' );
						}
						?>
					</span>
				<?php elseif ( 'in_progress' === $attempt_status ) : ?>
					<span class="iaq-pill iaq-pill-in-progress">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> <?php esc_html_e( 'In Progress', 'indianagri-quiz-engine' ); ?>
					</span>
				<?php else : ?>
					<span class="iaq-pill iaq-pill-not-attempted"><?php esc_html_e( 'Not Attempted', 'indianagri-quiz-engine' ); ?></span>
				<?php endif; ?>
			</div>

			<h4 class="iaq-ps-set-title">
				<?php printf( esc_html__( 'Practice Set %1$d: %2$s', 'indianagri-quiz-engine' ), $set_num, esc_html( $pq->title ) ); ?>
			</h4>

			<div class="iaq-ps-set-meta-line">
				<span class="iaq-ps-meta-chip">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
					<strong><?php echo esc_html( $pq->total_questions ); ?></strong> <?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?>
				</span>
				<span class="iaq-ps-meta-chip">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><rect width="16" height="20" x="4" y="2" rx="2"/><line x1="8" x2="16" y1="6" y2="6"/><line x1="16" x2="16" y1="14" y2="18"/><path d="M16 10h.01"/><path d="M12 10h.01"/><path d="M8 10h.01"/><path d="M12 14h.01"/><path d="M8 14h.01"/><path d="M12 18h.01"/><path d="M8 18h.01"/></svg>
					<strong><?php echo esc_html( $pq->total_marks ); ?></strong> <?php esc_html_e( 'Marks', 'indianagri-quiz-engine' ); ?>
				</span>
				<span class="iaq-ps-meta-chip">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
					<strong><?php echo $pq->duration_minutes > 0 ? esc_html( $pq->duration_minutes ) . 'm' : esc_html__( 'Untimed', 'indianagri-quiz-engine' ); ?></strong>
				</span>
				<?php if ( ! empty( $pq->negative_marks ) && floatval( $pq->negative_marks ) > 0 ) : ?>
					<span class="iaq-ps-meta-chip" style="color:#b91c1c;">
						-<?php echo esc_html( $pq->negative_marks ); ?> <?php esc_html_e( 'Negative', 'indianagri-quiz-engine' ); ?>
					</span>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="iaq-ps-set-right">
		<?php if ( ! $can_solve ) : ?>
			<a href="<?php echo esc_url( function_exists( 'get_permalink' ) ? get_permalink() : '/subscription-plans' ); ?>" class="iaq-btn iaq-btn-outline" style="border-color:#d97706;color:#d97706;">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> <?php esc_html_e( 'Unlock Premium', 'indianagri-quiz-engine' ); ?>
			</a>
		<?php elseif ( 'in_progress' === $attempt_status ) : ?>
			<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-btn iaq-btn-primary" style="background:#d97706;border-color:#d97706;">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><polygon points="5 3 19 12 5 21 5 3"/></svg> <?php esc_html_e( 'Resume Practice', 'indianagri-quiz-engine' ); ?>
			</a>
		<?php elseif ( 'completed' === $attempt_status ) : ?>
			<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-btn iaq-btn-primary">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> <?php esc_html_e( 'Practice Again', 'indianagri-quiz-engine' ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-btn iaq-btn-primary">
				<span><?php esc_html_e( 'Start Practice', 'indianagri-quiz-engine' ); ?></span> &rarr;
			</a>
		<?php endif; ?>
	</div>
</div>
