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
						<span class="dashicons dashicons-yes-alt"></span>
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
						<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'In Progress', 'indianagri-quiz-engine' ); ?>
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
					<span class="dashicons dashicons-list-view"></span>
					<strong><?php echo esc_html( $pq->total_questions ); ?></strong> <?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?>
				</span>
				<span class="iaq-ps-meta-chip">
					<span class="dashicons dashicons-calculator"></span>
					<strong><?php echo esc_html( $pq->total_marks ); ?></strong> <?php esc_html_e( 'Marks', 'indianagri-quiz-engine' ); ?>
				</span>
				<span class="iaq-ps-meta-chip">
					<span class="dashicons dashicons-clock"></span>
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
				<span class="dashicons dashicons-lock"></span> <?php esc_html_e( 'Unlock Premium', 'indianagri-quiz-engine' ); ?>
			</a>
		<?php elseif ( 'in_progress' === $attempt_status ) : ?>
			<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-btn iaq-btn-primary" style="background:#d97706;">
				<span class="dashicons dashicons-controls-play"></span> <?php esc_html_e( 'Resume Practice', 'indianagri-quiz-engine' ); ?>
			</a>
		<?php elseif ( 'completed' === $attempt_status ) : ?>
			<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-btn iaq-btn-primary">
				<span class="dashicons dashicons-redo"></span> <?php esc_html_e( 'Practice Again', 'indianagri-quiz-engine' ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-btn iaq-btn-primary">
				<?php esc_html_e( 'Start Practice', 'indianagri-quiz-engine' ); ?> &rarr;
			</a>
		<?php endif; ?>
	</div>
</div>
