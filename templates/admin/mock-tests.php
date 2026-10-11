<?php
/**
 * Admin: Mock Tests Hierarchy & Multi-Level Structure Management.
 * Manages the 10 Main Mock Categories, States, Universities, Vacancies,
 * Series, and Context-Aware Mock Test Assignments.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab    = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'categories';
$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';

$edit_id = isset( $_GET['edit_id'] ) ? absint( $_GET['edit_id'] ) : 0;

$all_categories = IAQ_Mock_Tests::get_categories( array( 'status' => '' ) );
$all_exams      = IAQ_Examinations::get_examinations();
$all_subjects   = IAQ_Question_Bank::get_subjects();
?>

<div class="wrap iaq-admin-wrap">

	<!-- Header Banner -->
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'Multi-Level Hierarchy Architecture', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php esc_html_e( 'Mock Test Module Hierarchy & Management', 'indianagri-quiz-engine' ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Dynamically configure and organize full-length Mock Tests across 10 National Categories, Examinations, Disciplines, Posts, and States without writing code.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
		<div class="iaq-header-actions" style="margin-top:12px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-add-quiz&quiz_type=mock' ) ); ?>" class="iaq-btn iaq-btn-primary">
				<span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'Create New Mock Test', 'indianagri-quiz-engine' ); ?>
			</a>
			<a href="<?php echo esc_url( home_url( '/?page=iaq-preview-mock-tests' ) ); ?>" target="_blank" class="iaq-btn iaq-btn-outline">
				<span class="dashicons dashicons-external"></span> <?php esc_html_e( 'Live Student Portal Preview', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>
	</div>

	<!-- Navigation Tabs -->
	<nav class="nav-tab-wrapper iaq-nav-tab-wrapper" style="margin:20px 0;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=categories' ) ); ?>" class="nav-tab <?php echo 'categories' === $tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-category"></span> <?php esc_html_e( '1. Categories (10 Streams)', 'indianagri-quiz-engine' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=states_univs' ) ); ?>" class="nav-tab <?php echo 'states_univs' === $tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e( '2. States & Universities', 'indianagri-quiz-engine' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=vacancies' ) ); ?>" class="nav-tab <?php echo 'vacancies' === $tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-groups"></span> <?php esc_html_e( '3. Vacancies & Posts', 'indianagri-quiz-engine' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=series' ) ); ?>" class="nav-tab <?php echo 'series' === $tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-portfolio"></span> <?php esc_html_e( '4. Mock Test Series', 'indianagri-quiz-engine' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=quizzes' ) ); ?>" class="nav-tab <?php echo 'quizzes' === $tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-paperclip"></span> <?php esc_html_e( '5. Context Attachments & Reorder', 'indianagri-quiz-engine' ); ?>
		</a>
	</nav>

	<!-- TAB 1: CATEGORIES MANAGEMENT -->
	<?php if ( 'categories' === $tab ) : ?>
		<?php
		$edit_cat = ( 'edit' === $action && $edit_id ) ? IAQ_Mock_Tests::get_category( $edit_id ) : null;
		?>
		<div class="iaq-two-col-layout" style="display:grid;grid-template-columns:360px 1fr;gap:24px;">
			<!-- Form Column -->
			<div class="iaq-form-column">
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons <?php echo $edit_cat ? 'dashicons-edit' : 'dashicons-plus-alt'; ?>"></span> <?php echo $edit_cat ? esc_html__( 'Edit Category', 'indianagri-quiz-engine' ) : esc_html__( 'Add New Category', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=categories' ) ); ?>">
							<?php wp_nonce_field( 'iaq_save_mock_category_nonce' ); ?>
							<?php if ( $edit_cat ) : ?>
								<input type="hidden" name="id" value="<?php echo esc_attr( $edit_cat->id ); ?>">
							<?php endif; ?>

							<div class="iaq-form-group">
								<label for="cat-name"><?php esc_html_e( 'Category Title', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<input type="text" name="name" id="cat-name" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_cat ? $edit_cat->name : '' ); ?>" required placeholder="e.g. ICAR Exams">
							</div>

							<div class="iaq-form-group">
								<label for="cat-slug"><?php esc_html_e( 'Slug / Identifier', 'indianagri-quiz-engine' ); ?></label>
								<input type="text" name="slug" id="cat-slug" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_cat ? $edit_cat->slug : '' ); ?>" placeholder="e.g. icar-exams">
								<span class="iaq-card-help"><?php esc_html_e( 'Leave blank to generate automatically from title.', 'indianagri-quiz-engine' ); ?></span>
							</div>

							<div class="iaq-form-group">
								<label for="cat-code"><?php esc_html_e( 'Short Code / Acronym', 'indianagri-quiz-engine' ); ?></label>
								<input type="text" name="code" id="cat-code" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_cat ? $edit_cat->code : '' ); ?>" placeholder="e.g. ICAR">
							</div>

							<div class="iaq-form-group">
								<label for="cat-icon"><?php esc_html_e( 'Dashicon Class', 'indianagri-quiz-engine' ); ?></label>
								<input type="text" name="icon" id="cat-icon" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_cat ? $edit_cat->icon : 'dashicons-awards' ); ?>" placeholder="dashicons-awards">
							</div>

							<div class="iaq-form-group">
								<label for="cat-badge"><?php esc_html_e( 'Badge Text', 'indianagri-quiz-engine' ); ?></label>
								<input type="text" name="badge_text" id="cat-badge" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_cat ? $edit_cat->badge_text : '' ); ?>" placeholder="e.g. National PG & SRF">
							</div>

							<div class="iaq-form-group">
								<label for="cat-desc"><?php esc_html_e( 'Description', 'indianagri-quiz-engine' ); ?></label>
								<textarea name="description" id="cat-desc" rows="3" class="iaq-textarea iaq-full-width" placeholder="Summary for candidates..."><?php echo esc_textarea( $edit_cat ? $edit_cat->description : '' ); ?></textarea>
							</div>

							<div class="iaq-form-group">
								<label for="cat-order"><?php esc_html_e( 'Display Position / Sort Order', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" min="1" name="display_order" id="cat-order" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_cat ? $edit_cat->display_order : count( $all_categories ) + 1 ); ?>">
							</div>

							<div class="iaq-form-group">
								<label for="cat-status"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></label>
								<select name="status" id="cat-status" class="iaq-select iaq-full-width">
									<option value="active" <?php selected( $edit_cat ? $edit_cat->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active (Visible on Portal)', 'indianagri-quiz-engine' ); ?></option>
									<option value="inactive" <?php selected( $edit_cat ? $edit_cat->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inactive (Disabled)', 'indianagri-quiz-engine' ); ?></option>
								</select>
							</div>

							<div style="margin-top:16px;display:flex;gap:10px;">
								<button type="submit" name="iaq_save_mock_category" class="iaq-btn iaq-btn-primary iaq-full-width">
									<span class="dashicons dashicons-saved"></span> <?php echo $edit_cat ? esc_html__( 'Update Category', 'indianagri-quiz-engine' ) : esc_html__( 'Save Category', 'indianagri-quiz-engine' ); ?>
								</button>
								<?php if ( $edit_cat ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=categories' ) ); ?>" class="iaq-btn iaq-btn-outline">
										<?php esc_html_e( 'Cancel', 'indianagri-quiz-engine' ); ?>
									</a>
								<?php endif; ?>
							</div>
						</form>
					</div>
				</div>
			</div>

			<!-- Table Column -->
			<div class="iaq-table-column">
				<div class="iaq-card">
					<div class="iaq-card-header" style="display:flex;justify-content:space-between;align-items:center;">
						<h2><span class="dashicons dashicons-category"></span> <?php esc_html_e( 'Mock Test Categories', 'indianagri-quiz-engine' ); ?> (<?php echo count( $all_categories ); ?>)</h2>
						<span style="font-size:12px;color:#64748b;"><?php esc_html_e( '10 Main Streams + Custom Exams', 'indianagri-quiz-engine' ); ?></span>
					</div>
					<div class="iaq-card-body" style="padding:0;">
						<table class="wp-list-table widefat striped" style="border:none;">
							<thead>
								<tr>
									<th style="width:50px;"><?php esc_html_e( 'Order', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Category Title', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Code / Slug', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Badge', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
									<th style="width:130px;text-align:right;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( ! empty( $all_categories ) ) : ?>
									<?php foreach ( $all_categories as $cat ) : ?>
										<tr>
											<td><span class="iaq-order-badge"><?php echo (int) $cat->display_order; ?></span></td>
											<td>
												<strong>
													<span class="dashicons <?php echo esc_attr( ! empty( $cat->icon ) ? $cat->icon : 'dashicons-awards' ); ?>" style="color:#117E3A;font-size:16px;vertical-align:middle;margin-right:4px;"></span>
													<?php echo esc_html( $cat->name ); ?>
												</strong>
												<div style="font-size:11px;color:#64748b;margin-top:2px;"><?php echo esc_html( wp_trim_words( $cat->description, 8 ) ); ?></div>
											</td>
											<td>
												<code><?php echo esc_html( $cat->slug ); ?></code>
												<?php if ( ! empty( $cat->code ) ) : ?>
													<span style="font-size:11px;color:#64748b;">(<?php echo esc_html( $cat->code ); ?>)</span>
												<?php endif; ?>
											</td>
											<td>
												<?php if ( ! empty( $cat->badge_text ) ) : ?>
													<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $cat->badge_text ); ?></span>
												<?php else : ?>
													<span style="color:#94a3b8;">&mdash;</span>
												<?php endif; ?>
											</td>
											<td>
												<span class="iaq-badge iaq-badge-<?php echo 'active' === $cat->status ? 'published' : 'draft'; ?>">
													<?php echo 'active' === $cat->status ? esc_html__( 'Active', 'indianagri-quiz-engine' ) : esc_html__( 'Disabled', 'indianagri-quiz-engine' ); ?>
												</span>
											</td>
											<td style="text-align:right;">
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=categories&action=edit&edit_id=' . $cat->id ) ); ?>" class="button button-small">
													<?php esc_html_e( 'Edit', 'indianagri-quiz-engine' ); ?>
												</a>
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=categories&iaq_action=delete_mock_category&id=' . $cat->id ), 'iaq_delete_mock_cat_' . $cat->id ) ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this category?', 'indianagri-quiz-engine' ); ?>');">
													<?php esc_html_e( 'Delete', 'indianagri-quiz-engine' ); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr><td colspan="6"><?php esc_html_e( 'No categories found. Default seeds will be created automatically.', 'indianagri-quiz-engine' ); ?></td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

	<!-- TAB 2: STATES & UNIVERSITIES -->
	<?php elseif ( 'states_univs' === $tab ) : ?>
		<?php
		$edit_item = ( 'edit' === $action && $edit_id ) ? IAQ_Mock_Tests::get_state_university( $edit_id ) : null;
		$states    = IAQ_Mock_Tests::get_states( array( 'status' => '' ) );
		$univs     = IAQ_Mock_Tests::get_universities( array( 'status' => '' ) );
		?>
		<div class="iaq-two-col-layout" style="display:grid;grid-template-columns:360px 1fr;gap:24px;">
			<!-- Form Column -->
			<div class="iaq-form-column">
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-admin-site-alt3"></span> <?php echo $edit_item ? esc_html__( 'Edit Entry', 'indianagri-quiz-engine' ) : esc_html__( 'Add State or University', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=states_univs' ) ); ?>">
							<?php wp_nonce_field( 'iaq_save_state_university_nonce' ); ?>
							<?php if ( $edit_item ) : ?>
								<input type="hidden" name="id" value="<?php echo esc_attr( $edit_item->id ); ?>">
							<?php endif; ?>

							<div class="iaq-form-group">
								<label for="item-type"><?php esc_html_e( 'Entry Type', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<select name="type" id="item-type" class="iaq-select iaq-full-width" required>
									<option value="state" <?php selected( $edit_item ? $edit_item->type : 'state', 'state' ); ?>><?php esc_html_e( 'State (State Agriculture Exams)', 'indianagri-quiz-engine' ); ?></option>
									<option value="university" <?php selected( $edit_item ? $edit_item->type : '', 'university' ); ?>><?php esc_html_e( 'University / Entrance Group', 'indianagri-quiz-engine' ); ?></option>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="item-name"><?php esc_html_e( 'Name', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<input type="text" name="name" id="item-name" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_item ? $edit_item->name : '' ); ?>" required placeholder="e.g. Rajasthan or State Agricultural Universities">
							</div>

							<div class="iaq-form-group">
								<label for="item-code"><?php esc_html_e( 'Short Code / State Abbreviation', 'indianagri-quiz-engine' ); ?></label>
								<input type="text" name="code" id="item-code" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_item ? $edit_item->code : '' ); ?>" placeholder="e.g. RJ, MP, SAU">
							</div>

							<div class="iaq-form-group">
								<label for="item-slug"><?php esc_html_e( 'Slug', 'indianagri-quiz-engine' ); ?></label>
								<input type="text" name="slug" id="item-slug" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_item ? $edit_item->slug : '' ); ?>" placeholder="e.g. rajasthan">
							</div>

							<div class="iaq-form-group">
								<label for="item-order"><?php esc_html_e( 'Display Position / Sort Order', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" min="1" name="display_order" id="item-order" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_item ? $edit_item->display_order : 1 ); ?>">
							</div>

							<div class="iaq-form-group">
								<label for="item-status"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></label>
								<select name="status" id="item-status" class="iaq-select iaq-full-width">
									<option value="active" <?php selected( $edit_item ? $edit_item->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'indianagri-quiz-engine' ); ?></option>
									<option value="inactive" <?php selected( $edit_item ? $edit_item->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'indianagri-quiz-engine' ); ?></option>
								</select>
							</div>

							<div style="margin-top:16px;display:flex;gap:10px;">
								<button type="submit" name="iaq_save_state_university" class="iaq-btn iaq-btn-primary iaq-full-width">
									<span class="dashicons dashicons-saved"></span> <?php echo $edit_item ? esc_html__( 'Update Entry', 'indianagri-quiz-engine' ) : esc_html__( 'Save Entry', 'indianagri-quiz-engine' ); ?>
								</button>
								<?php if ( $edit_item ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=states_univs' ) ); ?>" class="iaq-btn iaq-btn-outline">
										<?php esc_html_e( 'Cancel', 'indianagri-quiz-engine' ); ?>
									</a>
								<?php endif; ?>
							</div>
						</form>
					</div>
				</div>
			</div>

			<!-- Tables Column -->
			<div class="iaq-table-column">
				<!-- States Table -->
				<div class="iaq-card" style="margin-bottom:24px;">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e( 'Configured States (State Agriculture Exams)', 'indianagri-quiz-engine' ); ?> (<?php echo count( $states ); ?>)</h2>
					</div>
					<div class="iaq-card-body" style="padding:0;">
						<table class="wp-list-table widefat striped" style="border:none;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Code', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'State Name', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Slug', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
									<th style="width:130px;text-align:right;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( ! empty( $states ) ) : ?>
									<?php foreach ( $states as $st ) : ?>
										<tr>
											<td><strong><?php echo esc_html( $st->code ); ?></strong></td>
											<td><?php echo esc_html( $st->name ); ?></td>
											<td><code><?php echo esc_html( $st->slug ); ?></code></td>
											<td>
												<span class="iaq-badge iaq-badge-<?php echo 'active' === $st->status ? 'published' : 'draft'; ?>">
													<?php echo esc_html( ucfirst( $st->status ) ); ?>
												</span>
											</td>
											<td style="text-align:right;">
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=states_univs&action=edit&edit_id=' . $st->id ) ); ?>" class="button button-small">
													<?php esc_html_e( 'Edit', 'indianagri-quiz-engine' ); ?>
												</a>
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=states_univs&iaq_action=delete_state_university&id=' . $st->id ), 'iaq_delete_state_univ_' . $st->id ) ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this state?', 'indianagri-quiz-engine' ); ?>');">
													<?php esc_html_e( 'Delete', 'indianagri-quiz-engine' ); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr><td colspan="5"><?php esc_html_e( 'No states configured yet.', 'indianagri-quiz-engine' ); ?></td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Universities Table -->
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-welcome-learn-more"></span> <?php esc_html_e( 'Configured University Entrance Types', 'indianagri-quiz-engine' ); ?> (<?php echo count( $univs ); ?>)</h2>
					</div>
					<div class="iaq-card-body" style="padding:0;">
						<table class="wp-list-table widefat striped" style="border:none;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Code', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Entrance Group / University Name', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Slug', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
									<th style="width:130px;text-align:right;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( ! empty( $univs ) ) : ?>
									<?php foreach ( $univs as $un ) : ?>
										<tr>
											<td><strong><?php echo esc_html( $un->code ); ?></strong></td>
											<td><?php echo esc_html( $un->name ); ?></td>
											<td><code><?php echo esc_html( $un->slug ); ?></code></td>
											<td>
												<span class="iaq-badge iaq-badge-<?php echo 'active' === $un->status ? 'published' : 'draft'; ?>">
													<?php echo esc_html( ucfirst( $un->status ) ); ?>
												</span>
											</td>
											<td style="text-align:right;">
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=states_univs&action=edit&edit_id=' . $un->id ) ); ?>" class="button button-small">
													<?php esc_html_e( 'Edit', 'indianagri-quiz-engine' ); ?>
												</a>
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=states_univs&iaq_action=delete_state_university&id=' . $un->id ), 'iaq_delete_state_univ_' . $un->id ) ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this university entry?', 'indianagri-quiz-engine' ); ?>');">
													<?php esc_html_e( 'Delete', 'indianagri-quiz-engine' ); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr><td colspan="5"><?php esc_html_e( 'No university groups configured yet.', 'indianagri-quiz-engine' ); ?></td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

	<!-- TAB 3: VACANCIES & POSTS -->
	<?php elseif ( 'vacancies' === $tab ) : ?>
		<?php
		$vac_exam_id  = isset( $_GET['filter_exam_id'] ) ? absint( $_GET['filter_exam_id'] ) : 0;
		$all_vacancies= IAQ_Mock_Tests::get_vacancies( $vac_exam_id );
		$edit_vac     = ( 'edit' === $action && $edit_id ) ? IAQ_Mock_Tests::get_vacancy( $edit_id ) : null;
		?>
		<div class="iaq-two-col-layout" style="display:grid;grid-template-columns:360px 1fr;gap:24px;">
			<!-- Form Column -->
			<div class="iaq-form-column">
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-groups"></span> <?php echo $edit_vac ? esc_html__( 'Edit Vacancy / Post', 'indianagri-quiz-engine' ) : esc_html__( 'Add New Vacancy / Post', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=vacancies' ) ); ?>">
							<?php wp_nonce_field( 'iaq_save_mock_vacancy_nonce' ); ?>
							<?php if ( $edit_vac ) : ?>
								<input type="hidden" name="id" value="<?php echo esc_attr( $edit_vac->id ); ?>">
							<?php endif; ?>

							<div class="iaq-form-group">
								<label for="vac-exam"><?php esc_html_e( 'Recruiting Examination', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<select name="examination_id" id="vac-exam" class="iaq-select iaq-full-width" required>
									<option value=""><?php esc_html_e( '— Select Examination —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $all_exams as $ex ) : ?>
										<option value="<?php echo esc_attr( $ex->id ); ?>" <?php selected( $edit_vac ? $edit_vac->examination_id : $vac_exam_id, $ex->id ); ?>>
											<?php echo esc_html( $ex->code ); ?> &mdash; <?php echo esc_html( $ex->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="vac-title"><?php esc_html_e( 'Post / Vacancy Designation', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<input type="text" name="title" id="vac-title" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_vac ? $edit_vac->title : '' ); ?>" required placeholder="e.g. Technical Assistant (AGTA)">
							</div>

							<div class="iaq-form-group">
								<label for="vac-slug"><?php esc_html_e( 'Slug', 'indianagri-quiz-engine' ); ?></label>
								<input type="text" name="slug" id="vac-slug" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_vac ? $edit_vac->slug : '' ); ?>" placeholder="e.g. technical-assistant">
							</div>

							<div class="iaq-form-group">
								<label for="vac-posts"><?php esc_html_e( 'Number of Advertised Posts', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" min="0" name="posts_count" id="vac-posts" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_vac ? $edit_vac->posts_count : 0 ); ?>">
							</div>

							<div class="iaq-form-group">
								<label for="vac-advt"><?php esc_html_e( 'Advertisement / Notification Ref No.', 'indianagri-quiz-engine' ); ?></label>
								<input type="text" name="advertisement_no" id="vac-advt" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_vac ? $edit_vac->advertisement_no : '' ); ?>" placeholder="e.g. Advt. 07-Exam/2024">
							</div>

							<div class="iaq-form-group">
								<label for="vac-desc"><?php esc_html_e( 'Details / Eligibility Summary', 'indianagri-quiz-engine' ); ?></label>
								<textarea name="description" id="vac-desc" rows="3" class="iaq-textarea iaq-full-width"><?php echo esc_textarea( $edit_vac ? $edit_vac->description : '' ); ?></textarea>
							</div>

							<div class="iaq-form-group">
								<label for="vac-status"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></label>
								<select name="status" id="vac-status" class="iaq-select iaq-full-width">
									<option value="active" <?php selected( $edit_vac ? $edit_vac->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active (Accepting Tests)', 'indianagri-quiz-engine' ); ?></option>
									<option value="inactive" <?php selected( $edit_vac ? $edit_vac->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'indianagri-quiz-engine' ); ?></option>
								</select>
							</div>

							<div style="margin-top:16px;display:flex;gap:10px;">
								<button type="submit" name="iaq_save_mock_vacancy" class="iaq-btn iaq-btn-primary iaq-full-width">
									<span class="dashicons dashicons-saved"></span> <?php echo $edit_vac ? esc_html__( 'Update Vacancy', 'indianagri-quiz-engine' ) : esc_html__( 'Save Vacancy', 'indianagri-quiz-engine' ); ?>
								</button>
								<?php if ( $edit_vac ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=vacancies' ) ); ?>" class="iaq-btn iaq-btn-outline">
										<?php esc_html_e( 'Cancel', 'indianagri-quiz-engine' ); ?>
									</a>
								<?php endif; ?>
							</div>
						</form>
					</div>
				</div>
			</div>

			<!-- Table Column -->
			<div class="iaq-table-column">
				<div class="iaq-card">
					<div class="iaq-card-header" style="display:flex;justify-content:space-between;align-items:center;">
						<h2><span class="dashicons dashicons-groups"></span> <?php esc_html_e( 'Recruitment Vacancies', 'indianagri-quiz-engine' ); ?> (<?php echo count( $all_vacancies ); ?>)</h2>
						<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
							<input type="hidden" name="page" value="iaq-mock-tests">
							<input type="hidden" name="tab" value="vacancies">
							<select name="filter_exam_id" onchange="this.form.submit();" style="max-width:220px;font-size:12px;">
								<option value=""><?php esc_html_e( '— All Examinations —', 'indianagri-quiz-engine' ); ?></option>
								<?php foreach ( $all_exams as $ex ) : ?>
									<option value="<?php echo esc_attr( $ex->id ); ?>" <?php selected( $vac_exam_id, $ex->id ); ?>>
										<?php echo esc_html( $ex->code ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</form>
					</div>
					<div class="iaq-card-body" style="padding:0;">
						<table class="wp-list-table widefat striped" style="border:none;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Vacancy Title', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Examination', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Posts', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Advt Ref', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
									<th style="width:130px;text-align:right;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( ! empty( $all_vacancies ) ) : ?>
									<?php foreach ( $all_vacancies as $vac ) : ?>
										<tr>
											<td>
												<strong><?php echo esc_html( $vac->title ); ?></strong>
												<div style="font-size:11px;color:#64748b;"><code><?php echo esc_html( $vac->slug ); ?></code></div>
											</td>
											<td>
												<span class="iaq-pill iaq-pill-category"><?php echo esc_html( ! empty( $vac->exam_name ) ? $vac->exam_name : 'Exam #' . $vac->examination_id ); ?></span>
											</td>
											<td>
												<?php if ( ! empty( $vac->posts_count ) ) : ?>
													<span class="iaq-order-badge"><?php echo (int) $vac->posts_count; ?></span>
												<?php else : ?>
													<span style="color:#94a3b8;">&mdash;</span>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( ! empty( $vac->advertisement_no ) ? $vac->advertisement_no : '&mdash;' ); ?></td>
											<td>
												<span class="iaq-badge iaq-badge-<?php echo 'active' === $vac->status ? 'published' : 'draft'; ?>">
													<?php echo esc_html( ucfirst( $vac->status ) ); ?>
												</span>
											</td>
											<td style="text-align:right;">
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=vacancies&action=edit&edit_id=' . $vac->id ) ); ?>" class="button button-small">
													<?php esc_html_e( 'Edit', 'indianagri-quiz-engine' ); ?>
												</a>
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=vacancies&iaq_action=delete_mock_vacancy&id=' . $vac->id ), 'iaq_delete_mock_vac_' . $vac->id ) ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this vacancy?', 'indianagri-quiz-engine' ); ?>');">
													<?php esc_html_e( 'Delete', 'indianagri-quiz-engine' ); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr><td colspan="6"><?php esc_html_e( 'No vacancies added yet.', 'indianagri-quiz-engine' ); ?></td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

	<!-- TAB 4: MOCK TEST SERIES -->
	<?php elseif ( 'series' === $tab ) : ?>
		<?php
		$all_series = IAQ_Mock_Tests::get_series_list();
		$edit_ser   = ( 'edit' === $action && $edit_id ) ? IAQ_Mock_Tests::get_series( $edit_id ) : null;
		?>
		<div class="iaq-two-col-layout" style="display:grid;grid-template-columns:360px 1fr;gap:24px;">
			<!-- Form Column -->
			<div class="iaq-form-column">
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-portfolio"></span> <?php echo $edit_ser ? esc_html__( 'Edit Mock Series', 'indianagri-quiz-engine' ) : esc_html__( 'Create Mock Series', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=series' ) ); ?>">
							<?php wp_nonce_field( 'iaq_save_mock_series_nonce' ); ?>
							<?php if ( $edit_ser ) : ?>
								<input type="hidden" name="id" value="<?php echo esc_attr( $edit_ser->id ); ?>">
							<?php endif; ?>

							<div class="iaq-form-group">
								<label for="ser-title"><?php esc_html_e( 'Series Title', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<input type="text" name="title" id="ser-title" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_ser ? $edit_ser->title : '' ); ?>" required placeholder="e.g. Agronomy Mastery Test Series">
							</div>

							<div class="iaq-form-group">
								<label for="ser-category"><?php esc_html_e( 'Target Category', 'indianagri-quiz-engine' ); ?></label>
								<select name="category_id" id="ser-category" class="iaq-select iaq-full-width">
									<option value=""><?php esc_html_e( '— Any / General —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $all_categories as $cat ) : ?>
										<option value="<?php echo esc_attr( $cat->id ); ?>" <?php selected( $edit_ser ? $edit_ser->category_id : 0, $cat->id ); ?>>
											<?php echo esc_html( $cat->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="ser-exam"><?php esc_html_e( 'Target Examination', 'indianagri-quiz-engine' ); ?></label>
								<select name="examination_id" id="ser-exam" class="iaq-select iaq-full-width">
									<option value=""><?php esc_html_e( '— Any Examination —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $all_exams as $ex ) : ?>
										<option value="<?php echo esc_attr( $ex->id ); ?>" <?php selected( $edit_ser ? $edit_ser->examination_id : 0, $ex->id ); ?>>
											<?php echo esc_html( $ex->code ); ?> &mdash; <?php echo esc_html( $ex->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="ser-subject"><?php esc_html_e( 'Target Subject / Discipline', 'indianagri-quiz-engine' ); ?></label>
								<select name="subject_id" id="ser-subject" class="iaq-select iaq-full-width">
									<option value=""><?php esc_html_e( '— Any Subject —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $all_subjects as $sub ) : ?>
										<option value="<?php echo esc_attr( $sub->id ); ?>" <?php selected( $edit_ser ? $edit_ser->subject_id : 0, $sub->id ); ?>>
											<?php echo esc_html( $sub->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="ser-tests"><?php esc_html_e( 'Total Mock Tests in Series', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" min="1" name="total_tests" id="ser-tests" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_ser ? $edit_ser->total_tests : 10 ); ?>">
							</div>

							<div class="iaq-form-group">
								<label for="ser-access"><?php esc_html_e( 'Access Control', 'indianagri-quiz-engine' ); ?></label>
								<select name="access_type" id="ser-access" class="iaq-select iaq-full-width">
									<option value="free" <?php selected( $edit_ser ? $edit_ser->access_type : 'free', 'free' ); ?>><?php esc_html_e( 'Free (All Visitors & Students)', 'indianagri-quiz-engine' ); ?></option>
									<option value="premium" <?php selected( $edit_ser ? $edit_ser->access_type : '', 'premium' ); ?>><?php esc_html_e( 'Premium (Subscription Required)', 'indianagri-quiz-engine' ); ?></option>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="ser-desc"><?php esc_html_e( 'Description & Instructions', 'indianagri-quiz-engine' ); ?></label>
								<textarea name="description" id="ser-desc" rows="3" class="iaq-textarea iaq-full-width"><?php echo esc_textarea( $edit_ser ? $edit_ser->description : '' ); ?></textarea>
							</div>

							<div class="iaq-form-group">
								<label for="ser-status"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></label>
								<select name="status" id="ser-status" class="iaq-select iaq-full-width">
									<option value="published" <?php selected( $edit_ser ? $edit_ser->status : 'published', 'published' ); ?>><?php esc_html_e( 'Published', 'indianagri-quiz-engine' ); ?></option>
									<option value="draft" <?php selected( $edit_ser ? $edit_ser->status : '', 'draft' ); ?>><?php esc_html_e( 'Draft', 'indianagri-quiz-engine' ); ?></option>
									<option value="archived" <?php selected( $edit_ser ? $edit_ser->status : '', 'archived' ); ?>><?php esc_html_e( 'Archived', 'indianagri-quiz-engine' ); ?></option>
								</select>
							</div>

							<div style="margin-top:16px;display:flex;gap:10px;">
								<button type="submit" name="iaq_save_mock_series" class="iaq-btn iaq-btn-primary iaq-full-width">
									<span class="dashicons dashicons-saved"></span> <?php echo $edit_ser ? esc_html__( 'Update Series', 'indianagri-quiz-engine' ) : esc_html__( 'Save Series', 'indianagri-quiz-engine' ); ?>
								</button>
								<?php if ( $edit_ser ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=series' ) ); ?>" class="iaq-btn iaq-btn-outline">
										<?php esc_html_e( 'Cancel', 'indianagri-quiz-engine' ); ?>
									</a>
								<?php endif; ?>
							</div>
						</form>
					</div>
				</div>
			</div>

			<!-- Table Column -->
			<div class="iaq-table-column">
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-portfolio"></span> <?php esc_html_e( 'Configured Mock Test Series', 'indianagri-quiz-engine' ); ?> (<?php echo count( $all_series ); ?>)</h2>
					</div>
					<div class="iaq-card-body" style="padding:0;">
						<table class="wp-list-table widefat striped" style="border:none;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Series Title', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Scope (Category/Exam)', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Tests Planned', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Access', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
									<th style="width:130px;text-align:right;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( ! empty( $all_series ) ) : ?>
									<?php foreach ( $all_series as $ser ) : ?>
										<tr>
											<td>
												<strong><?php echo esc_html( $ser->title ); ?></strong>
												<?php if ( ! empty( $ser->subject_name ) ) : ?>
													<div style="font-size:11px;color:#117E3A;"><?php echo esc_html( $ser->subject_name ); ?></div>
												<?php endif; ?>
											</td>
											<td>
												<?php if ( ! empty( $ser->category_name ) ) : ?>
													<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $ser->category_name ); ?></span>
												<?php endif; ?>
												<?php if ( ! empty( $ser->exam_name ) ) : ?>
													<span style="font-size:12px;color:#64748b;"><?php echo esc_html( $ser->exam_name ); ?></span>
												<?php endif; ?>
											</td>
											<td><span class="iaq-order-badge"><?php echo (int) $ser->total_tests; ?> Tests</span></td>
											<td>
												<span class="iaq-pill iaq-pill-<?php echo 'premium' === $ser->access_type ? 'premium' : 'free'; ?>">
													<?php echo 'premium' === $ser->access_type ? esc_html__( 'Premium', 'indianagri-quiz-engine' ) : esc_html__( 'Free', 'indianagri-quiz-engine' ); ?>
												</span>
											</td>
											<td>
												<span class="iaq-badge iaq-badge-<?php echo esc_attr( $ser->status ); ?>">
													<?php echo esc_html( ucfirst( $ser->status ) ); ?>
												</span>
											</td>
											<td style="text-align:right;">
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=series&action=edit&edit_id=' . $ser->id ) ); ?>" class="button button-small">
													<?php esc_html_e( 'Edit', 'indianagri-quiz-engine' ); ?>
												</a>
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=series&iaq_action=delete_mock_series&id=' . $ser->id ), 'iaq_delete_mock_ser_' . $ser->id ) ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this test series?', 'indianagri-quiz-engine' ); ?>');">
													<?php esc_html_e( 'Delete', 'indianagri-quiz-engine' ); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr><td colspan="6"><?php esc_html_e( 'No test series configured yet.', 'indianagri-quiz-engine' ); ?></td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

	<!-- TAB 5: CONTEXT ATTACHMENTS & REORDER -->
	<?php elseif ( 'quizzes' === $tab ) : ?>
		<?php
		$sel_cat_id   = isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] ) : 0;
		$sel_exam_id  = isset( $_GET['exam_id'] ) ? absint( $_GET['exam_id'] ) : 0;
		$sel_sub_id   = isset( $_GET['subject_id'] ) ? absint( $_GET['subject_id'] ) : 0;
		$sel_vac_id   = isset( $_GET['vacancy_id'] ) ? absint( $_GET['vacancy_id'] ) : 0;
		$sel_state_id = isset( $_GET['state_id'] ) ? absint( $_GET['state_id'] ) : 0;
		$sel_ser_id   = isset( $_GET['series_id'] ) ? absint( $_GET['series_id'] ) : 0;

		$context = array_filter(
			array(
				'category_id' => $sel_cat_id,
				'exam_id'     => $sel_exam_id,
				'subject_id'  => $sel_sub_id,
				'vacancy_id'  => $sel_vac_id,
				'state_id'    => $sel_state_id,
				'series_id'   => $sel_ser_id,
			)
		);

		$context_quizzes = IAQ_Mock_Tests::get_context_mock_tests( $context );
		$available_quizzes = IAQ_Quiz_Builder::get_quizzes( array( 'quiz_type' => 'mock', 'per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
		$all_vacancies   = IAQ_Mock_Tests::get_vacancies();
		$all_states      = IAQ_Mock_Tests::get_states();
		$all_series      = IAQ_Mock_Tests::get_series_list();
		?>
		<div class="iaq-two-col-layout" style="display:grid;grid-template-columns:360px 1fr;gap:24px;">
			<!-- Attach Quiz Form Column -->
			<div class="iaq-form-column">
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-paperclip"></span> <?php esc_html_e( 'Attach Mock Test', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=quizzes' ) ); ?>">
							<?php wp_nonce_field( 'iaq_attach_mock_quiz_nonce' ); ?>

							<div class="iaq-form-group">
								<label for="att-quiz"><?php esc_html_e( 'Select Master Mock Test (quiz_type = mock)', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<select name="quiz_id" id="att-quiz" class="iaq-select iaq-full-width" required>
									<option value=""><?php esc_html_e( '— Select a Mock Test —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $available_quizzes as $q ) : ?>
										<option value="<?php echo esc_attr( $q->id ); ?>">
											#<?php echo esc_html( $q->id ); ?>: <?php echo esc_html( $q->title ); ?> (<?php echo (int) $q->total_questions; ?> Qs &bull; <?php echo (float) $q->total_marks; ?> Marks)
										</option>
									<?php endforeach; ?>
								</select>
								<span class="iaq-card-help"><?php esc_html_e( 'Only quizzes configured as Mock Test are listed. Reuses questions with zero data duplication.', 'indianagri-quiz-engine' ); ?></span>
							</div>

							<div class="iaq-form-group">
								<label for="att-cat"><?php esc_html_e( 'Target Category (Hierarchy Level 1)', 'indianagri-quiz-engine' ); ?></label>
								<select name="category_id" id="att-cat" class="iaq-select iaq-full-width">
									<option value=""><?php esc_html_e( '— None / Global —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $all_categories as $cat ) : ?>
										<option value="<?php echo esc_attr( $cat->id ); ?>" <?php selected( $sel_cat_id, $cat->id ); ?>>
											<?php echo esc_html( $cat->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="att-exam"><?php esc_html_e( 'Target Examination (Hierarchy Level 2)', 'indianagri-quiz-engine' ); ?></label>
								<select name="exam_id" id="att-exam" class="iaq-select iaq-full-width">
									<option value=""><?php esc_html_e( '— None / Skip —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $all_exams as $ex ) : ?>
										<option value="<?php echo esc_attr( $ex->id ); ?>" <?php selected( $sel_exam_id, $ex->id ); ?>>
											<?php echo esc_html( $ex->code ); ?> &mdash; <?php echo esc_html( $ex->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="att-sub"><?php esc_html_e( 'Target Discipline / Subject', 'indianagri-quiz-engine' ); ?></label>
								<select name="subject_id" id="att-sub" class="iaq-select iaq-full-width">
									<option value=""><?php esc_html_e( '— None / All Subjects —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $all_subjects as $sub ) : ?>
										<option value="<?php echo esc_attr( $sub->id ); ?>" <?php selected( $sel_sub_id, $sub->id ); ?>>
											<?php echo esc_html( $sub->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="att-vac"><?php esc_html_e( 'Target Vacancy / Post (Optional)', 'indianagri-quiz-engine' ); ?></label>
								<select name="vacancy_id" id="att-vac" class="iaq-select iaq-full-width">
									<option value=""><?php esc_html_e( '— None / Not Vacancy Specific —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $all_vacancies as $vac ) : ?>
										<option value="<?php echo esc_attr( $vac->id ); ?>" <?php selected( $sel_vac_id, $vac->id ); ?>>
											<?php echo esc_html( $vac->title ); ?> (<?php echo esc_html( ! empty( $vac->exam_name ) ? $vac->exam_name : 'Exam #' . $vac->examination_id ); ?>)
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="att-state"><?php esc_html_e( 'Target State (Optional)', 'indianagri-quiz-engine' ); ?></label>
								<select name="state_id" id="att-state" class="iaq-select iaq-full-width">
									<option value=""><?php esc_html_e( '— None / National —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $all_states as $st ) : ?>
										<option value="<?php echo esc_attr( $st->id ); ?>" <?php selected( $sel_state_id, $st->id ); ?>>
											<?php echo esc_html( $st->name ); ?> (<?php echo esc_html( $st->code ); ?>)
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="att-series"><?php esc_html_e( 'Target Mock Series (Optional)', 'indianagri-quiz-engine' ); ?></label>
								<select name="series_id" id="att-series" class="iaq-select iaq-full-width">
									<option value=""><?php esc_html_e( '— None / Standalone —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $all_series as $ser ) : ?>
										<option value="<?php echo esc_attr( $ser->id ); ?>" <?php selected( $sel_ser_id, $ser->id ); ?>>
											<?php echo esc_html( $ser->title ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="att-order"><?php esc_html_e( 'Sequence Order (Determines Mock Test 1, 2, 3...)', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" min="1" name="sort_order" id="att-order" class="iaq-input iaq-full-width" value="<?php echo esc_attr( count( $context_quizzes ) + 1 ); ?>">
							</div>

							<div style="margin-top:16px;">
								<button type="submit" name="iaq_attach_mock_quiz" class="iaq-btn iaq-btn-primary iaq-full-width">
									<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Attach to Context', 'indianagri-quiz-engine' ); ?>
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>

			<!-- Context Quizzes Table Column -->
			<div class="iaq-table-column">
				<div class="iaq-card">
					<div class="iaq-card-header" style="display:flex;justify-content:space-between;align-items:center;">
						<h2><span class="dashicons dashicons-awards"></span> <?php esc_html_e( 'Mock Tests in Current Context', 'indianagri-quiz-engine' ); ?> (<?php echo count( $context_quizzes ); ?>)</h2>
						<span style="font-size:12px;color:#117E3A;font-weight:600;"><?php esc_html_e( 'Dynamic 1..N Numbering Active', 'indianagri-quiz-engine' ); ?></span>
					</div>

					<!-- Filter bar for Context Quizzes -->
					<div style="padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
						<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display:flex;gap:8px;flex-wrap:wrap;width:100%;">
							<input type="hidden" name="page" value="iaq-mock-tests">
							<input type="hidden" name="tab" value="quizzes">

							<select name="category_id" onchange="this.form.submit();" style="font-size:12px;">
								<option value=""><?php esc_html_e( 'All Categories', 'indianagri-quiz-engine' ); ?></option>
								<?php foreach ( $all_categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat->id ); ?>" <?php selected( $sel_cat_id, $cat->id ); ?>><?php echo esc_html( $cat->name ); ?></option>
								<?php endforeach; ?>
							</select>

							<select name="exam_id" onchange="this.form.submit();" style="font-size:12px;">
								<option value=""><?php esc_html_e( 'All Exams', 'indianagri-quiz-engine' ); ?></option>
								<?php foreach ( $all_exams as $ex ) : ?>
									<option value="<?php echo esc_attr( $ex->id ); ?>" <?php selected( $sel_exam_id, $ex->id ); ?>><?php echo esc_html( $ex->code ); ?></option>
								<?php endforeach; ?>
							</select>

							<select name="subject_id" onchange="this.form.submit();" style="font-size:12px;">
								<option value=""><?php esc_html_e( 'All Subjects', 'indianagri-quiz-engine' ); ?></option>
								<?php foreach ( $all_subjects as $sub ) : ?>
									<option value="<?php echo esc_attr( $sub->id ); ?>" <?php selected( $sel_sub_id, $sub->id ); ?>><?php echo esc_html( $sub->name ); ?></option>
								<?php endforeach; ?>
							</select>

							<?php if ( $sel_cat_id || $sel_exam_id || $sel_sub_id ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=quizzes' ) ); ?>" class="button button-small" style="line-height:26px;">
									<?php esc_html_e( 'Clear Filters', 'indianagri-quiz-engine' ); ?>
								</a>
							<?php endif; ?>
						</form>
					</div>

					<div class="iaq-card-body" style="padding:0;">
						<table class="wp-list-table widefat striped" style="border:none;">
							<thead>
								<tr>
									<th style="width:70px;"><?php esc_html_e( 'Position', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Student-Facing Mock Test Title', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Questions / Marks', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Duration', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Access', 'indianagri-quiz-engine' ); ?></th>
									<th style="width:110px;text-align:right;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( ! empty( $context_quizzes ) ) : ?>
									<?php foreach ( $context_quizzes as $cq ) : ?>
										<tr>
											<td>
												<span class="iaq-order-badge">#<?php echo (int) $cq->mock_test_number; ?></span>
											</td>
											<td>
												<strong><?php echo esc_html( $cq->formatted_title ); ?></strong>
												<div style="font-size:11px;color:#64748b;">
													<?php esc_html_e( 'Master Quiz ID:', 'indianagri-quiz-engine' ); ?> #<?php echo (int) $cq->id; ?> &bull; 
													<?php echo esc_html( $cq->title ); ?>
												</div>
											</td>
											<td>
												<?php echo (int) $cq->total_questions; ?> Qs &bull; <?php echo (float) $cq->total_marks; ?> Marks
												<?php if ( (float) $cq->negative_marks > 0 ) : ?>
													<div style="font-size:11px;color:#dc2626;">-<?php echo (float) $cq->negative_marks; ?> <?php esc_html_e( 'Neg', 'indianagri-quiz-engine' ); ?></div>
												<?php endif; ?>
											</td>
											<td><?php echo (int) $cq->duration_minutes; ?> <?php esc_html_e( 'min', 'indianagri-quiz-engine' ); ?></td>
											<td>
												<span class="iaq-pill iaq-pill-<?php echo 'premium' === $cq->access_type ? 'premium' : 'free'; ?>">
													<?php echo 'premium' === $cq->access_type ? esc_html__( 'Premium', 'indianagri-quiz-engine' ) : esc_html__( 'Free', 'indianagri-quiz-engine' ); ?>
												</span>
											</td>
											<td style="text-align:right;">
												<?php if ( ! empty( $cq->mock_rel_id ) ) : ?>
													<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=iaq-mock-tests&tab=quizzes&iaq_action=detach_mock_quiz&rel_id=' . $cq->mock_rel_id ), 'iaq_detach_mock_rel_' . $cq->mock_rel_id ) ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Detach this quiz from this context? The master quiz record remains intact.', 'indianagri-quiz-engine' ); ?>');">
														<?php esc_html_e( 'Detach', 'indianagri-quiz-engine' ); ?>
													</a>
												<?php else : ?>
													<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes&action=edit&id=' . $cq->id ) ); ?>" class="button button-small">
														<?php esc_html_e( 'Edit Quiz', 'indianagri-quiz-engine' ); ?>
													</a>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr><td colspan="6"><?php esc_html_e( 'No mock tests attached in this context yet. Select or attach one on the left.', 'indianagri-quiz-engine' ); ?></td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

	<?php endif; ?>

</div>
