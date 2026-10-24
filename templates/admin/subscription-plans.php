<?php
/**
 * Subscription Plans Catalog admin template.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edit_id   = isset( $_GET['edit_id'] ) ? absint( $_GET['edit_id'] ) : 0;
$edit_plan = $edit_id ? IAQ_Access_Control::get_plan( $edit_id ) : null;
$plans     = IAQ_Access_Control::get_plans();
$all_exams = class_exists( 'IAQ_Examinations' ) ? IAQ_Examinations::get_examinations() : array();
?>

<div class="wrap iaq-admin-wrap">
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php esc_html_e( 'Subscription Plans & Monetization Catalog', 'indianagri-quiz-engine' ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Define and price recurring or fixed-term subscription plans in INR (₹), configure all-access vs. examination-specific packages, and link WooCommerce products.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
	</div>

	<div class="iaq-two-col-layout" style="display:grid;grid-template-columns:380px 1fr;gap:24px;margin-top:20px;">
		<!-- Add / Edit Plan Column -->
		<div class="iaq-form-column">
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2>
						<span class="dashicons dashicons-<?php echo $edit_plan ? 'edit' : 'cart'; ?>"></span>
						<?php echo $edit_plan ? esc_html__( 'Edit Subscription Plan', 'indianagri-quiz-engine' ) : esc_html__( 'Create New Subscription Plan', 'indianagri-quiz-engine' ); ?>
					</h2>
				</div>
				<div class="iaq-card-body">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subscription-plans' ) ); ?>">
						<?php wp_nonce_field( 'iaq_save_plan_nonce' ); ?>
						<?php if ( $edit_plan ) : ?>
							<input type="hidden" name="id" value="<?php echo esc_attr( $edit_plan->id ); ?>">
						<?php endif; ?>

						<div class="iaq-form-group">
							<label for="plan-name"><?php esc_html_e( 'Plan Name', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<input type="text" name="name" id="plan-name" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_plan ? $edit_plan->name : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., Monthly All Access, UPCATET Special', 'indianagri-quiz-engine' ); ?>" required>
						</div>

						<div class="iaq-form-group">
							<label for="plan-code"><?php esc_html_e( 'Plan Code / Identifier', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<input type="text" name="code" id="plan-code" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_plan ? $edit_plan->code : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., monthly-all-access, yearly-pass', 'indianagri-quiz-engine' ); ?>" required>
						</div>

						<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
							<div class="iaq-form-group">
								<label for="plan-price"><?php esc_html_e( 'Price (₹ INR)', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<input type="number" step="1" min="0" name="price" id="plan-price" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_plan ? $edit_plan->price : '29' ); ?>" required>
							</div>
							<div class="iaq-form-group">
								<label for="plan-wc-id"><?php esc_html_e( 'WooCommerce Product', 'indianagri-quiz-engine' ); ?></label>
								<?php
								$wc_prods = function_exists( 'wc_get_products' ) ? wc_get_products( array( 'limit' => 150, 'status' => 'publish', 'orderby' => 'name', 'order' => 'ASC' ) ) : array();
								$selected_wc_id = $edit_plan && ! empty( $edit_plan->wc_product_id ) ? (int) $edit_plan->wc_product_id : 0;
								?>
								<?php if ( ! empty( $wc_prods ) ) : ?>
									<select name="wc_product_id" id="plan-wc-id" class="iaq-select iaq-full-width">
										<option value=""><?php esc_html_e( '— Select Product —', 'indianagri-quiz-engine' ); ?></option>
										<?php foreach ( $wc_prods as $wp_p ) : ?>
											<option value="<?php echo esc_attr( $wp_p->get_id() ); ?>" <?php selected( $selected_wc_id, $wp_p->get_id() ); ?>>
												#<?php echo esc_html( $wp_p->get_id() ); ?> - <?php echo esc_html( $wp_p->get_name() ); ?> (₹<?php echo esc_html( $wp_p->get_price() ); ?>)
											</option>
										<?php endforeach; ?>
									</select>
								<?php else : ?>
									<input type="number" name="wc_product_id" id="plan-wc-id" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $selected_wc_id ? $selected_wc_id : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. 101', 'indianagri-quiz-engine' ); ?>">
								<?php endif; ?>
							</div>
						</div>

						<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
							<div class="iaq-form-group">
								<label for="plan-duration-val"><?php esc_html_e( 'Duration Length', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<input type="number" min="1" max="365" name="duration_value" id="plan-duration-val" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_plan ? $edit_plan->duration_value : '1' ); ?>" required>
							</div>
							<div class="iaq-form-group">
								<label for="plan-duration-unit"><?php esc_html_e( 'Duration Unit', 'indianagri-quiz-engine' ); ?></label>
								<select name="duration_unit" id="plan-duration-unit" class="iaq-select iaq-full-width">
									<option value="months" <?php selected( $edit_plan ? $edit_plan->duration_unit : 'months', 'months' ); ?>><?php esc_html_e( 'Calendar Month(s)', 'indianagri-quiz-engine' ); ?></option>
									<option value="days" <?php selected( $edit_plan ? $edit_plan->duration_unit : '', 'days' ); ?>><?php esc_html_e( 'Days', 'indianagri-quiz-engine' ); ?></option>
									<option value="years" <?php selected( $edit_plan ? $edit_plan->duration_unit : '', 'years' ); ?>><?php esc_html_e( 'Calendar Year(s)', 'indianagri-quiz-engine' ); ?></option>
								</select>
							</div>
						</div>

						<?php
						$current_scope = 'all_access';
						if ( $edit_plan ) {
							$current_scope = ! empty( $edit_plan->access_type ) ? ( 'exam_specific' === $edit_plan->access_type ? 'specific_exams' : $edit_plan->access_type ) : ( ! empty( $edit_plan->access_scope ) ? $edit_plan->access_scope : 'all_access' );
						}
						$current_included = '';
						if ( $edit_plan ) {
							$current_included = ! empty( $edit_plan->included_exam_ids ) ? $edit_plan->included_exam_ids : ( ! empty( $edit_plan->included_categories ) ? $edit_plan->included_categories : '' );
						}
						?>
						<div class="iaq-form-group">
							<label for="plan-scope"><?php esc_html_e( 'Access Entitlement Scope', 'indianagri-quiz-engine' ); ?></label>
							<select name="access_scope" id="plan-scope" class="iaq-select iaq-full-width">
								<option value="all_access" <?php selected( $current_scope, 'all_access' ); ?>>
									<?php esc_html_e( 'All-Access (Unlocks ALL Agricultural Exams & Quizzes)', 'indianagri-quiz-engine' ); ?>
								</option>
								<option value="specific_exams" <?php selected( $current_scope, 'specific_exams' ); ?>>
									<?php esc_html_e( 'Examination-Specific (Only selected exam categories)', 'indianagri-quiz-engine' ); ?>
								</option>
							</select>
						</div>

						<div class="iaq-form-group" id="iaq-included-exams-group">
							<label for="plan-included"><?php esc_html_e( 'Included Examination Categories', 'indianagri-quiz-engine' ); ?></label>
							<textarea name="included_categories" id="plan-included" rows="2" class="iaq-textarea iaq-full-width" placeholder="<?php esc_attr_e( 'Comma-separated (e.g. ICAR JRF/SRF, UPCATET PG, CUET PG Agriculture)', 'indianagri-quiz-engine' ); ?>"><?php echo esc_textarea( $current_included ); ?></textarea>
							<span class="iaq-card-help"><?php esc_html_e( 'Required when Access Scope is Examination-Specific.', 'indianagri-quiz-engine' ); ?></span>
						</div>

						<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
							<div class="iaq-form-group">
								<label for="plan-order"><?php esc_html_e( 'Display Order', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" name="display_order" id="plan-order" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_plan ? $edit_plan->display_order : 0 ); ?>">
							</div>
							<div class="iaq-form-group">
								<label for="plan-status"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></label>
								<select name="status" id="plan-status" class="iaq-select iaq-full-width">
									<option value="active" <?php selected( $edit_plan ? $edit_plan->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active (Purchasable)', 'indianagri-quiz-engine' ); ?></option>
									<option value="inactive" <?php selected( $edit_plan ? $edit_plan->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inactive (Hidden)', 'indianagri-quiz-engine' ); ?></option>
									<option value="archived" <?php selected( $edit_plan ? $edit_plan->status : '', 'archived' ); ?>><?php esc_html_e( 'Archived', 'indianagri-quiz-engine' ); ?></option>
								</select>
							</div>
						</div>

						<div class="iaq-form-group">
							<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
								<input type="checkbox" name="is_featured" value="1" <?php checked( $edit_plan && 1 === (int) $edit_plan->is_featured ); ?>>
								<strong><?php esc_html_e( 'Mark as Featured / "Most Popular" Plan', 'indianagri-quiz-engine' ); ?></strong>
							</label>
						</div>

						<div class="iaq-form-group">
							<label for="plan-desc"><?php esc_html_e( 'Short Description & Features', 'indianagri-quiz-engine' ); ?></label>
							<textarea name="description" id="plan-desc" rows="3" class="iaq-textarea iaq-full-width" placeholder="<?php esc_attr_e( 'Plan benefits displayed on the pricing card...', 'indianagri-quiz-engine' ); ?>"><?php echo esc_textarea( $edit_plan ? $edit_plan->description : '' ); ?></textarea>
						</div>

						<div style="margin-top:20px;display:flex;gap:10px;">
							<button type="submit" name="iaq_save_plan" class="iaq-btn iaq-btn-primary" style="flex:1;">
								<span class="dashicons dashicons-saved"></span> <?php echo $edit_plan ? esc_html__( 'Update Plan', 'indianagri-quiz-engine' ) : esc_html__( 'Save Plan', 'indianagri-quiz-engine' ); ?>
							</button>
							<?php if ( $edit_plan ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subscription-plans' ) ); ?>" class="iaq-btn iaq-btn-outline">
									<?php esc_html_e( 'Cancel', 'indianagri-quiz-engine' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Plans Table Column -->
		<div class="iaq-table-column">
			<div class="iaq-card">
				<div class="iaq-card-header" style="display:flex;justify-content:space-between;align-items:center;">
					<h2><span class="dashicons dashicons-tickets-alt"></span> <?php esc_html_e( 'Subscription Catalog', 'indianagri-quiz-engine' ); ?></h2>
					<div style="display:flex;gap:8px;align-items:center;">
						<span class="iaq-card-help"><?php printf( esc_html__( '%d Plans defined', 'indianagri-quiz-engine' ), count( $plans ) ); ?></span>
					</div>
				</div>
				<div class="iaq-card-body" style="padding:0;">
					<table class="iaq-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Plan Name', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Price', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Duration', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Scope', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'WC Prod ID', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
								<th style="text-align:right;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $plans ) ) : ?>
								<tr>
									<td colspan="7" class="iaq-no-records" style="text-align:center;padding:30px;">
										<?php esc_html_e( 'No subscription plans found. Create your first plan on the left.', 'indianagri-quiz-engine' ); ?>
									</td>
								</tr>
							<?php else : ?>
								<?php foreach ( $plans as $p ) : ?>
									<?php
									$edit_url = admin_url( 'admin.php?page=iaq-subscription-plans&edit_id=' . $p->id );
									$delete_url = wp_nonce_url(
										admin_url( 'admin.php?page=iaq-subscription-plans&action=delete&id=' . $p->id ),
										'iaq_delete_plan_' . $p->id
									);
									?>
									<tr>
										<td>
											<strong><a href="<?php echo esc_url( $edit_url ); ?>" style="color:#0f172a;text-decoration:none;"><?php echo esc_html( $p->name ); ?></a></strong>
											<?php if ( 1 === (int) $p->is_featured ) : ?>
												<span style="background:#fef3c7;color:#b45309;font-size:10px;font-weight:800;padding:2px 6px;border-radius:4px;margin-left:4px;">★ <?php esc_html_e( 'FEATURED', 'indianagri-quiz-engine' ); ?></span>
											<?php endif; ?>
											<div style="font-size:11px;color:#64748b;font-family:monospace;"><?php echo esc_html( ! empty( $p->code ) ? $p->code : ( ! empty( $p->slug ) ? $p->slug : '' ) ); ?></div>
										</td>
										<td>
											<strong style="color:#117E3A;font-size:15px;">&#8377;<?php echo esc_html( number_format( (float) $p->price, 0 ) ); ?></strong>
										</td>
										<td>
											<?php echo esc_html( $p->duration_value . ' ' . ucfirst( $p->duration_unit ) ); ?>
										</td>
										<td>
											<?php
											$p_scope = ! empty( $p->access_scope ) ? $p->access_scope : ( ! empty( $p->access_type ) ? $p->access_type : 'all_access' );
											$p_cats  = ! empty( $p->included_categories ) ? $p->included_categories : ( ! empty( $p->included_exam_ids ) ? $p->included_exam_ids : '' );
											?>
											<?php if ( 'all_access' === $p_scope ) : ?>
												<span class="iaq-badge" style="background:#e8f5e9;color:#117E3A;font-weight:700;"><?php esc_html_e( 'All Access', 'indianagri-quiz-engine' ); ?></span>
											<?php else : ?>
												<span class="iaq-badge" style="background:#f1f5f9;color:#334155;"><?php esc_html_e( 'Category Specific', 'indianagri-quiz-engine' ); ?></span>
												<?php if ( ! empty( $p_cats ) ) : ?>
													<div style="font-size:11px;color:#64748b;margin-top:2px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
														<?php echo esc_html( $p_cats ); ?>
													</div>
												<?php endif; ?>
											<?php endif; ?>
										</td>
										<td>
											<?php if ( ! empty( $p->wc_product_id ) ) : ?>
												<code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;">#<?php echo esc_html( $p->wc_product_id ); ?></code>
											<?php else : ?>
												<span style="color:#94a3b8;font-size:12px;">&mdash;</span>
											<?php endif; ?>
										</td>
										<td>
											<span class="iaq-badge iaq-status-<?php echo esc_attr( 'active' === $p->status ? 'published' : 'draft' ); ?>">
												<?php echo esc_html( ucfirst( $p->status ) ); ?>
											</span>
										</td>
										<td style="text-align:right;">
											<div class="iaq-row-action-btns" style="justify-content:flex-end;">
												<a href="<?php echo esc_url( $edit_url ); ?>" class="iaq-action-btn iaq-btn-edit" title="<?php esc_attr_e( 'Edit Plan', 'indianagri-quiz-engine' ); ?>">
													<span class="dashicons dashicons-edit"></span>
												</a>
												<a href="<?php echo esc_url( $delete_url ); ?>" class="iaq-action-btn iaq-btn-delete iaq-confirm-delete" title="<?php esc_attr_e( 'Delete Plan', 'indianagri-quiz-engine' ); ?>">
													<span class="dashicons dashicons-trash"></span>
												</a>
											</div>
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
