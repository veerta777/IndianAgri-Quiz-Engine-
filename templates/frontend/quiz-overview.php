<?php
/**
 * Frontend: Quiz Overview & Candidate Instructions Card.
 * Displayed via shortcode [iaq_quiz id="123"].
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $quiz ) || ! is_object( $quiz ) ) {
	return;
}

$types     = IAQ_Quiz_Builder::get_quiz_types();
$type_meta = isset( $types[ $quiz->quiz_type ] ) ? $types[ $quiz->quiz_type ] : array( 'label' => ucfirst( $quiz->quiz_type ), 'badge_color' => '#117E3A' );
?>

<div class="iaq-exam-card" id="iaq-quiz-overview-<?php echo esc_attr( $quiz->id ); ?>">
	<!-- Header -->
	<div class="iaq-exam-header">
		<div class="iaq-exam-badges">
			<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $quiz->exam_category ); ?></span>
			<span class="iaq-pill iaq-pill-type" style="background: <?php echo esc_attr( $type_meta['badge_color'] ); ?>15; color: <?php echo esc_attr( $type_meta['badge_color'] ); ?>;">
				<?php echo esc_html( $type_meta['label'] ); ?>
			</span>
			<?php if ( 'paid' === $quiz->access_type ) : ?>
				<span class="iaq-pill iaq-pill-paid"><?php esc_html_e( 'Premium Test', 'indianagri-quiz-engine' ); ?></span>
			<?php else : ?>
				<span class="iaq-pill iaq-pill-free"><?php esc_html_e( 'Free Test', 'indianagri-quiz-engine' ); ?></span>
			<?php endif; ?>
		</div>
		<h2 class="iaq-exam-title"><?php echo esc_html( $quiz->title ); ?></h2>
		<?php if ( ! empty( $quiz->description ) ) : ?>
			<div class="iaq-exam-description"><?php echo wp_kses_post( wpautop( $quiz->description ) ); ?></div>
		<?php endif; ?>
	</div>

	<!-- Parameters Matrix -->
	<div class="iaq-param-grid">
		<div class="iaq-param-box">
			<span class="iaq-param-label"><?php esc_html_e( 'Total Questions', 'indianagri-quiz-engine' ); ?></span>
			<span class="iaq-param-val"><?php echo esc_html( $quiz->total_questions ); ?></span>
		</div>
		<div class="iaq-param-box">
			<span class="iaq-param-label"><?php esc_html_e( 'Total Marks', 'indianagri-quiz-engine' ); ?></span>
			<span class="iaq-param-val"><?php echo esc_html( $quiz->total_marks ); ?></span>
		</div>
		<div class="iaq-param-box">
			<span class="iaq-param-label"><?php esc_html_e( 'Duration', 'indianagri-quiz-engine' ); ?></span>
			<span class="iaq-param-val">
				<?php echo ( $quiz->duration_minutes > 0 ) ? sprintf( esc_html__( '%d Mins', 'indianagri-quiz-engine' ), $quiz->duration_minutes ) : esc_html__( 'Untimed', 'indianagri-quiz-engine' ); ?>
			</span>
		</div>
		<div class="iaq-param-box">
			<span class="iaq-param-label"><?php esc_html_e( 'Marking Scheme', 'indianagri-quiz-engine' ); ?></span>
			<span class="iaq-param-val iaq-val-marking">
				+<?php echo esc_html( $quiz->correct_marks ); ?> / -<?php echo esc_html( $quiz->negative_marks ); ?>
			</span>
		</div>
		<div class="iaq-param-box">
			<span class="iaq-param-label"><?php esc_html_e( 'Pass Score', 'indianagri-quiz-engine' ); ?></span>
			<span class="iaq-param-val"><?php echo esc_html( $quiz->pass_percentage ); ?>%</span>
		</div>
		<div class="iaq-param-box">
			<span class="iaq-param-label"><?php esc_html_e( 'Attempts Allowed', 'indianagri-quiz-engine' ); ?></span>
			<span class="iaq-param-val">
				<?php echo ( $quiz->max_attempts > 0 ) ? esc_html( $quiz->max_attempts ) : esc_html__( 'Unlimited', 'indianagri-quiz-engine' ); ?>
			</span>
		</div>
	</div>

	<!-- Instructions -->
	<?php if ( ! empty( $quiz->instructions ) ) : ?>
		<div class="iaq-instructions-card">
			<h4 class="iaq-instructions-title">
				<span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Examination Instructions & Guidelines', 'indianagri-quiz-engine' ); ?>
			</h4>
			<div class="iaq-instructions-content">
				<?php echo wp_kses_post( wpautop( $quiz->instructions ) ); ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- CTA Bar -->
	<div class="iaq-start-bar">
		<?php if ( ! is_user_logged_in() ) : ?>
			<div class="iaq-login-alert">
				<?php
				printf(
					/* translators: %s: login url */
					wp_kses_post( __( 'Please <a href="%s">log in to your account</a> to attempt this examination.', 'indianagri-quiz-engine' ) ),
					esc_url( wp_login_url( get_permalink() ) )
				);
				?>
			</div>
		<?php else : ?>
			<?php
			$user_id     = get_current_user_id();
			$entitlement = IAQ_Access_Control::check_user_entitlement( $user_id, $quiz );
			?>
			<?php if ( ! $entitlement['allowed'] ) : ?>
				<div class="iaq-locked-card" style="padding: 24px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; text-align: center; width: 100%;">
					<div style="font-size: 32px; color: #dc2626; margin-bottom: 8px;">🔒</div>
					<h3 style="margin: 0 0 6px 0; color: #991b1b; font-size: 18px;"><?php esc_html_e( 'Premium Examination — Subscription Required', 'indianagri-quiz-engine' ); ?></h3>
					<p style="margin: 0 0 16px 0; color: #7f1d1d; font-size: 14px; max-width: 600px; margin-left: auto; margin-right: auto;">
						<?php echo esc_html( $entitlement['reason'] ); ?>
					</p>
					<a href="<?php echo esc_url( apply_filters( 'iaq_pricing_page_url', '?page=iaq-preview-subscription-plans' ) ); ?>" class="iaq-btn-start" style="display:inline-block; text-decoration:none; background:#117E3A; color:#fff;">
						<span class="dashicons dashicons-cart" style="vertical-align:middle;margin-right:4px;"></span>
						<?php esc_html_e( 'Explore Subscription Plans & Unlock', 'indianagri-quiz-engine' ); ?> &rarr;
					</a>
				</div>
			<?php else : ?>
				<div class="iaq-cta-actions">
					<?php if ( ! empty( $active_attempt ) ) : ?>
						<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
							<span style="background:#fef3c7;color:#b45309;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:700;">
								<span class="dashicons dashicons-clock" style="font-size:14px;vertical-align:middle;"></span>
								<?php esc_html_e( 'Attempt in progress', 'indianagri-quiz-engine' ); ?>
							</span>
							<button type="button" class="iaq-btn-start" id="iaq-start-quiz-btn" data-quiz-id="<?php echo esc_attr( $quiz->id ); ?>" style="background:#b45309;">
								<?php esc_html_e( 'Resume Examination Now', 'indianagri-quiz-engine' ); ?> &rarr;
							</button>
						</div>
					<?php else : ?>
						<button type="button" class="iaq-btn-start" id="iaq-start-quiz-btn" data-quiz-id="<?php echo esc_attr( $quiz->id ); ?>">
							<?php esc_html_e( 'Start Examination Now', 'indianagri-quiz-engine' ); ?> &rarr;
						</button>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
