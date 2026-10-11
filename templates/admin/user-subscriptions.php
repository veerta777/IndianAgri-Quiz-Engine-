<?php
/**
 * Student Subscriptions Audit & Entitlements admin template.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_subs = IAQ_Database::get_table_name( 'user_subscriptions' );

$status_filter = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
$where = '1=1';
$params = array();

if ( ! empty( $status_filter ) ) {
	$where .= ' AND status = %s';
	$params[] = $status_filter;
}

$query = "SELECT * FROM {$table_subs} WHERE {$where} ORDER BY id DESC LIMIT 100";
if ( ! empty( $params ) ) {
	$subscriptions = $wpdb->get_results( $wpdb->prepare( $query, $params ) );
} else {
	$subscriptions = $wpdb->get_results( $query );
}

$plans = IAQ_Access_Control::get_plans( array( 'status' => 'active' ) );
?>

<div class="wrap iaq-admin-wrap">
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php esc_html_e( 'Student Subscriptions & Entitlements Audit', 'indianagri-quiz-engine' ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Audit purchased memberships, monitor calendar-aware expiration dates, view WooCommerce order links, or manually grant/revoke student entitlements.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
	</div>

	<div class="iaq-two-col-layout" style="display:grid;grid-template-columns:350px 1fr;gap:24px;margin-top:20px;">
		<!-- Manual Grant Column -->
		<div class="iaq-form-column">
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'Manual Entitlement Grant', 'indianagri-quiz-engine' ); ?></h2>
					<span class="iaq-card-help"><?php esc_html_e( 'Authorize an account for complimentary or offline payments.', 'indianagri-quiz-engine' ); ?></span>
				</div>
				<div class="iaq-card-body">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-user-subscriptions' ) ); ?>">
						<?php wp_nonce_field( 'iaq_manual_grant_subscription_nonce' ); ?>

						<div class="iaq-form-group">
							<label for="grant-user-id"><?php esc_html_e( 'WordPress User ID', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<input type="number" name="user_id" id="grant-user-id" class="iaq-input iaq-full-width" placeholder="e.g. 1" required>
							<span class="iaq-card-help"><?php esc_html_e( 'Enter student user ID from WP Users list.', 'indianagri-quiz-engine' ); ?></span>
						</div>

						<div class="iaq-form-group">
							<label for="grant-plan-id"><?php esc_html_e( 'Subscription Plan', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<select name="plan_id" id="grant-plan-id" class="iaq-select iaq-full-width" required>
								<option value=""><?php esc_html_e( '-- Select Plan --', 'indianagri-quiz-engine' ); ?></option>
								<?php foreach ( $plans as $pl ) : ?>
									<option value="<?php echo esc_attr( $pl->id ); ?>">
										<?php echo esc_html( $pl->name ); ?> (&#8377;<?php echo esc_html( $pl->price ); ?> / <?php echo esc_html( $pl->duration_value . ' ' . $pl->duration_unit ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="iaq-form-group">
							<label for="grant-order-ref"><?php esc_html_e( 'Order Ref / Reason', 'indianagri-quiz-engine' ); ?></label>
							<input type="text" name="order_ref" id="grant-order-ref" class="iaq-input iaq-full-width" value="MANUAL-ADMIN-GRANT" placeholder="e.g. WC-ORDER-1234 or MANUAL-OFFLINE">
						</div>

						<div style="margin-top:20px;">
							<button type="submit" name="iaq_manual_grant_subscription" class="iaq-btn iaq-btn-primary iaq-full-width">
								<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Grant Entitlement', 'indianagri-quiz-engine' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Subscriptions Audit Table Column -->
		<div class="iaq-table-column">
			<div class="iaq-card">
				<div class="iaq-card-header" style="display:flex;justify-content:space-between;align-items:center;">
					<h2><span class="dashicons dashicons-media-spreadsheet"></span> <?php esc_html_e( 'Student Subscriptions Records', 'indianagri-quiz-engine' ); ?></h2>
					<div class="iaq-filter-links" style="display:flex;gap:10px;font-size:13px;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-user-subscriptions' ) ); ?>" style="<?php echo empty( $status_filter ) ? 'font-weight:700;color:#117E3A;' : 'color:#64748b;'; ?>"><?php esc_html_e( 'All', 'indianagri-quiz-engine' ); ?></a>
						<span>|</span>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-user-subscriptions&status=active' ) ); ?>" style="<?php echo 'active' === $status_filter ? 'font-weight:700;color:#117E3A;' : 'color:#64748b;'; ?>"><?php esc_html_e( 'Active', 'indianagri-quiz-engine' ); ?></a>
						<span>|</span>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-user-subscriptions&status=expired' ) ); ?>" style="<?php echo 'expired' === $status_filter ? 'font-weight:700;color:#117E3A;' : 'color:#64748b;'; ?>"><?php esc_html_e( 'Expired', 'indianagri-quiz-engine' ); ?></a>
						<span>|</span>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-user-subscriptions&status=revoked' ) ); ?>" style="<?php echo 'revoked' === $status_filter ? 'font-weight:700;color:#117E3A;' : 'color:#64748b;'; ?>"><?php esc_html_e( 'Revoked', 'indianagri-quiz-engine' ); ?></a>
					</div>
				</div>
				<div class="iaq-card-body" style="padding:0;">
					<table class="iaq-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'ID', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Student / User', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Plan Name', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Scope', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Order Ref', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Valid Period', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
								<th style="text-align:right;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $subscriptions ) ) : ?>
								<tr>
									<td colspan="8" class="iaq-no-records" style="text-align:center;padding:30px;">
										<?php esc_html_e( 'No subscription records found.', 'indianagri-quiz-engine' ); ?>
									</td>
								</tr>
							<?php else : ?>
								<?php foreach ( $subscriptions as $sub ) : ?>
									<?php
									$user_obj = get_userdata( $sub->user_id );
									$user_label = $user_obj ? $user_obj->display_name . ' (' . $user_obj->user_email . ')' : 'User #' . $sub->user_id;
									$is_active = ( 'active' === $sub->status && strtotime( $sub->expiry_date ) > time() );
									$revoke_url = wp_nonce_url(
										admin_url( 'admin.php?page=iaq-user-subscriptions&action=revoke&id=' . $sub->id ),
										'iaq_revoke_sub_' . $sub->id
									);
									?>
									<tr>
										<td>
											<span class="iaq-id-pill">#<?php echo esc_html( $sub->id ); ?></span>
										</td>
										<td>
											<strong><?php echo esc_html( $user_label ); ?></strong>
											<div style="font-size:11px;color:#64748b;">UID: <?php echo esc_html( $sub->user_id ); ?></div>
										</td>
										<td>
											<strong><?php echo esc_html( $sub->plan_name ); ?></strong>
											<div style="font-size:11px;color:#117E3A;font-weight:600;">&#8377;<?php echo esc_html( $sub->amount_paid ); ?> <?php echo esc_html( $sub->currency ); ?></div>
										</td>
										<td>
											<?php if ( 'all_access' === $sub->access_scope ) : ?>
												<span class="iaq-badge" style="background:#e8f5e9;color:#117E3A;font-weight:700;"><?php esc_html_e( 'All Access', 'indianagri-quiz-engine' ); ?></span>
											<?php else : ?>
												<span class="iaq-badge" style="background:#f1f5f9;color:#334155;"><?php echo esc_html( $sub->included_categories ); ?></span>
											<?php endif; ?>
										</td>
										<td>
											<code style="background:#f8fafc;padding:2px 6px;border-radius:4px;font-size:11px;"><?php echo esc_html( $sub->order_id ? '#' . $sub->order_id : $sub->gateway_ref ); ?></code>
										</td>
										<td>
											<div style="font-size:12px;">
												<span><?php echo esc_html( date( 'M j, Y', strtotime( $sub->start_date ) ) ); ?></span>
												&rarr;
												<strong><?php echo esc_html( date( 'M j, Y', strtotime( $sub->expiry_date ) ) ); ?></strong>
											</div>
										</td>
										<td>
											<?php if ( $is_active ) : ?>
												<span class="iaq-badge iaq-status-published"><?php esc_html_e( 'Active', 'indianagri-quiz-engine' ); ?></span>
											<?php elseif ( 'revoked' === $sub->status ) : ?>
												<span class="iaq-badge" style="background:#fee2e2;color:#991b1b;"><?php esc_html_e( 'Revoked', 'indianagri-quiz-engine' ); ?></span>
											<?php else : ?>
												<span class="iaq-badge iaq-status-draft"><?php esc_html_e( 'Expired', 'indianagri-quiz-engine' ); ?></span>
											<?php endif; ?>
										</td>
										<td style="text-align:right;">
											<?php if ( 'active' === $sub->status ) : ?>
												<a href="<?php echo esc_url( $revoke_url ); ?>" class="iaq-action-btn iaq-btn-delete iaq-confirm-delete" title="<?php esc_attr_e( 'Revoke Subscription', 'indianagri-quiz-engine' ); ?>">
													<span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'Revoke', 'indianagri-quiz-engine' ); ?>
												</a>
											<?php else : ?>
												<span style="color:#94a3b8;font-size:12px;">&mdash;</span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
