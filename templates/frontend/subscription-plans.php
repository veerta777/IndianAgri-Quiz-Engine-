<?php
/**
 * Frontend: Subscription Plans Catalog & Monetization Pricing Table.
 * Rendered via shortcode [iaq_subscription_plans].
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plans        = IAQ_Access_Control::get_plans( array( 'status' => 'active' ) );
$user_id      = get_current_user_id();
$active_subs  = $user_id ? IAQ_Access_Control::get_user_subscriptions( $user_id, 'active' ) : array();
$active_plan_ids = ! empty( $active_subs ) ? wp_list_pluck( $active_subs, 'plan_id' ) : array();
?>

<div class="iaq-pricing-wrapper">
	<div class="iaq-pricing-header" style="text-align: center; margin-bottom: 36px;">
		<span style="background: #e8f5e9; color: #117E3A; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
			<?php esc_html_e( 'IndianAgri Premium Membership', 'indianagri-quiz-engine' ); ?>
		</span>
		<h2 style="font-size: 30px; font-weight: 800; color: #1e293b; margin: 12px 0 8px 0;">
			<?php esc_html_e( 'Choose Your Exam Preparation Plan', 'indianagri-quiz-engine' ); ?>
		</h2>
		<p style="font-size: 16px; color: #64748b; max-width: 650px; margin: 0 auto;">
			<?php esc_html_e( 'Unlock unlimited agricultural practice sets, authentic timed mock tests, previous-year question papers, and bilingual Hindi/English solutions.', 'indianagri-quiz-engine' ); ?>
		</p>
	</div>

	<?php if ( empty( $plans ) ) : ?>
		<div class="iaq-notice-box iaq-notice-info" style="text-align: center; padding: 24px;">
			<p><?php esc_html_e( 'No subscription plans are currently active. Please check back soon.', 'indianagri-quiz-engine' ); ?></p>
		</div>
	<?php else : ?>
		<div class="iaq-plans-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; align-items: stretch;">
			<?php
			foreach ( $plans as $plan ) :
				$is_featured = ( 1 === (int) $plan->is_featured );
				$is_owned    = in_array( (int) $plan->id, $active_plan_ids, true );
				$card_border = $is_featured ? '2px solid #117E3A' : '1px solid #e2e8f0';
				$card_shadow = $is_featured ? '0 10px 25px -5px rgba(17, 126, 58, 0.15)' : '0 4px 6px -1px rgba(0, 0, 0, 0.05)';
				?>
				<div class="iaq-plan-card" style="position: relative; background: #fff; border: <?php echo esc_attr( $card_border ); ?>; border-radius: 12px; padding: 28px 24px; box-shadow: <?php echo esc_attr( $card_shadow ); ?>; display: flex; flex-direction: column;">
					<?php if ( $is_featured ) : ?>
						<div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #117E3A; color: #fff; padding: 4px 14px; border-radius: 12px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
							<?php esc_html_e( 'Most Popular', 'indianagri-quiz-engine' ); ?>
						</div>
					<?php endif; ?>

					<div class="iaq-plan-top" style="margin-bottom: 20px;">
						<h3 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0;">
							<?php echo esc_html( $plan->name ); ?>
						</h3>
						<?php if ( ! empty( $plan->description ) ) : ?>
							<p style="font-size: 13px; color: #64748b; margin: 0 0 16px 0; min-height: 38px;">
								<?php echo esc_html( $plan->description ); ?>
							</p>
						<?php endif; ?>

						<div class="iaq-plan-price" style="display: flex; align-items: baseline; gap: 4px; margin-bottom: 8px;">
							<span style="font-size: 34px; font-weight: 800; color: #117E3A;">
								&#8377;<?php echo esc_html( number_format( (float) $plan->price, 0 ) ); ?>
							</span>
							<span style="font-size: 14px; color: #64748b; font-weight: 500;">
								/ <?php echo esc_html( $plan->duration_value . ' ' . ucfirst( $plan->duration_unit ) ); ?>
							</span>
						</div>

						<div style="display: inline-block; padding: 3px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 600; color: #475569;">
							<?php if ( 'all_access' === $plan->access_scope ) : ?>
								&#10004; <?php esc_html_e( 'All Agricultural Examinations', 'indianagri-quiz-engine' ); ?>
							<?php else : ?>
								&#9679; <?php echo esc_html( $plan->included_categories ); ?>
							<?php endif; ?>
						</div>
					</div>

					<div class="iaq-plan-features" style="flex: 1; border-top: 1px solid #f1f5f9; padding-top: 20px; margin-bottom: 24px;">
						<ul style="list-style: none; padding: 0; margin: 0; font-size: 14px; color: #334155; line-height: 1.8;">
							<li style="display: flex; align-items: center; gap: 8px;">
								<span style="color: #117E3A; font-weight: bold;">&#10003;</span>
								<?php esc_html_e( 'Full Timed CBT Exam Engine', 'indianagri-quiz-engine' ); ?>
							</li>
							<li style="display: flex; align-items: center; gap: 8px;">
								<span style="color: #117E3A; font-weight: bold;">&#10003;</span>
								<?php esc_html_e( 'Bilingual (English & Hindi) Content', 'indianagri-quiz-engine' ); ?>
							</li>
							<li style="display: flex; align-items: center; gap: 8px;">
								<span style="color: #117E3A; font-weight: bold;">&#10003;</span>
								<?php esc_html_e( 'Detailed Scientific Explanations', 'indianagri-quiz-engine' ); ?>
							</li>
							<li style="display: flex; align-items: center; gap: 8px;">
								<span style="color: #117E3A; font-weight: bold;">&#10003;</span>
								<?php esc_html_e( 'Subject & Topic Mastery Scorecards', 'indianagri-quiz-engine' ); ?>
							</li>
							<li style="display: flex; align-items: center; gap: 8px;">
								<span style="color: #117E3A; font-weight: bold;">&#10003;</span>
								<?php esc_html_e( 'Unlimited Attempt Resumptions', 'indianagri-quiz-engine' ); ?>
							</li>
						</ul>
					</div>

					<div class="iaq-plan-action">
						<?php if ( $is_owned ) : ?>
							<button type="button" disabled style="width: 100%; padding: 12px; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; font-weight: 700; cursor: not-allowed; text-align: center;">
								&#10004; <?php esc_html_e( 'Current Active Plan', 'indianagri-quiz-engine' ); ?>
							</button>
						<?php else : ?>
							<?php
							// Construct checkout or cart URL.
							$checkout_url = '#';
							if ( ! empty( $plan->wc_product_id ) && function_exists( 'wc_get_checkout_url' ) ) {
								$checkout_url = add_query_arg( 'add-to-cart', $plan->wc_product_id, wc_get_checkout_url() );
							} elseif ( ! empty( $plan->wc_product_id ) ) {
								$checkout_url = add_query_arg( 'add-to-cart', $plan->wc_product_id, home_url( '/' ) );
							} else {
								$checkout_url = apply_filters( 'iaq_subscription_checkout_url', '#', $plan );
							}
							?>
							<a href="<?php echo esc_url( $checkout_url ); ?>" class="iaq-btn-plan-buy" style="display: block; width: 100%; box-sizing: border-box; text-align: center; padding: 12px; background: <?php echo $is_featured ? '#117E3A' : '#0f172a'; ?>; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 700; transition: background 0.2s ease;">
								<?php esc_html_e( 'Subscribe Now', 'indianagri-quiz-engine' ); ?> &rarr;
							</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
