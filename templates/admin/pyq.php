<?php
/**
 * Admin: Previous Year Question Papers (PYQ) Management.
 * Dedicated administration section for PYQs reusing the existing Mock Test hierarchy.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab    = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'papers';
$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
$edit_id = isset( $_GET['edit_id'] ) ? absint( $_GET['edit_id'] ) : 0;

$filter_year     = isset( $_GET['filter_year'] ) ? absint( $_GET['filter_year'] ) : 0;
$filter_category = isset( $_GET['filter_category'] ) ? absint( $_GET['filter_category'] ) : 0;
$filter_status   = isset( $_GET['filter_status'] ) ? sanitize_key( $_GET['filter_status'] ) : 'all';
$search_query    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

$paged   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page = 20;
$offset  = ( $paged - 1 ) * $per_page;

$all_categories = IAQ_Mock_Tests::get_categories( array( 'status' => '' ) );
$all_exams      = IAQ_Examinations::get_examinations();
$all_subjects   = IAQ_Question_Bank::get_subjects();
$available_years = IAQ_PYQ::get_available_years();

// Default years to choose if empty
if ( empty( $available_years ) ) {
	$current_y = (int) date( 'Y' );
	for ( $y = $current_y; $y >= 2015; $y-- ) {
		$available_years[] = $y;
	}
}

$total_papers = IAQ_PYQ::get_admin_papers_count(
	array(
		'search'      => $search_query,
		'year'        => $filter_year,
		'category_id' => $filter_category,
		'status'      => $filter_status,
	)
);

$papers = IAQ_PYQ::get_admin_papers(
	array(
		'search'      => $search_query,
		'year'        => $filter_year,
		'category_id' => $filter_category,
		'status'      => $filter_status,
		'limit'       => $per_page,
		'offset'      => $offset,
	)
);

// If editing, load the paper and its current relational mapping
$edit_paper = null;
$edit_rel   = null;
if ( $edit_id > 0 ) {
	$edit_paper = IAQ_Quiz_Builder::get_quiz( $edit_id );
	if ( $edit_paper ) {
		global $wpdb;
		$table_rel = IAQ_Database::get_table_name( 'mock_test_relations' );
		$edit_rel  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_rel} WHERE quiz_id = %d LIMIT 1", $edit_id ) );
		$tab       = 'form';
	}
} elseif ( 'add' === $action ) {
	$tab = 'form';
}
?>

<div class="wrap iaq-admin-wrap" id="iaq-pyq-admin">

	<!-- Header Banner -->
	<div class="iaq-header-banner" style="background:linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);">
		<div class="iaq-header-title">
			<div class="iaq-header-badge" style="background:rgba(255,255,255,0.18);color:#e0e7ff;"><?php esc_html_e( 'Official Past Examination Archive', 'indianagri-quiz-engine' ); ?></div>
			<h1 style="color:#ffffff;"><?php esc_html_e( 'Previous Year Question Papers (PYQ) Management', 'indianagri-quiz-engine' ); ?></h1>
			<p class="iaq-header-desc" style="color:#c7d2fe;">
				<?php esc_html_e( 'Manage official historical agricultural examination papers by year and exam category. Reuses the existing Mock Test hierarchy with zero code duplication.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
		<div class="iaq-header-actions" style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-pyq&tab=form&action=add' ) ); ?>" class="iaq-btn iaq-btn-primary" style="background:#7c3aed;border-color:#6d28d9;">
				<span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'Add New PYQ Paper', 'indianagri-quiz-engine' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-pyq&tab=import' ) ); ?>" class="iaq-btn iaq-btn-outline" style="color:#ffffff;border-color:rgba(255,255,255,0.4);">
				<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Bulk Import Questions to PYQ', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>
	</div>

	<!-- Navigation Tabs -->
	<nav class="nav-tab-wrapper" style="margin-top:20px;border-bottom:1px solid #cbd5e1;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-pyq&tab=papers' ) ); ?>" class="nav-tab <?php echo 'papers' === $tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-media-document" style="vertical-align:middle;margin-right:4px;"></span>
			<?php esc_html_e( 'All PYQ Papers', 'indianagri-quiz-engine' ); ?>
			<span class="iaq-tab-count" style="background:#e0e7ff;color:#3730a3;padding:2px 8px;border-radius:10px;font-size:11px;margin-left:4px;font-weight:700;">
				<?php echo (int) $total_papers; ?>
			</span>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-pyq&tab=form' . ( $edit_id ? '&edit_id=' . $edit_id : '&action=add' ) ) ); ?>" class="nav-tab <?php echo 'form' === $tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-edit" style="vertical-align:middle;margin-right:4px;"></span>
			<?php echo $edit_id ? esc_html__( 'Edit PYQ Paper', 'indianagri-quiz-engine' ) : esc_html__( 'Add New PYQ Paper', 'indianagri-quiz-engine' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-pyq&tab=import' ) ); ?>" class="nav-tab <?php echo 'import' === $tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-upload" style="vertical-align:middle;margin-right:4px;"></span>
			<?php esc_html_e( 'CSV Question Import', 'indianagri-quiz-engine' ); ?>
		</a>
	</nav>

	<!-- TAB 1: ALL PYQ PAPERS LIST -->
	<?php if ( 'papers' === $tab ) : ?>

		<div class="iaq-admin-section" style="margin-top:20px;background:#ffffff;padding:20px;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,0.05);">

			<!-- Filter Bar -->
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:20px;">
				<input type="hidden" name="page" value="iaq-pyq">
				<input type="hidden" name="tab" value="papers">

				<!-- Search Input -->
				<div style="flex:1;min-width:220px;">
					<input type="search" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Search by paper title or description...', 'indianagri-quiz-engine' ); ?>" class="regular-text" style="width:100%;">
				</div>

				<!-- Year Filter -->
				<select name="filter_year" style="max-width:140px;">
					<option value="0"><?php esc_html_e( '— All Years —', 'indianagri-quiz-engine' ); ?></option>
					<?php foreach ( $available_years as $y ) : ?>
						<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $filter_year, $y ); ?>>
							<?php echo esc_html( $y ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<!-- Mock Hierarchy Category Filter -->
				<select name="filter_category" style="max-width:240px;">
					<option value="0"><?php esc_html_e( '— All Mock Categories —', 'indianagri-quiz-engine' ); ?></option>
					<?php foreach ( $all_categories as $cat ) : ?>
						<option value="<?php echo esc_attr( $cat->id ); ?>" <?php selected( $filter_category, $cat->id ); ?>>
							<?php echo esc_html( $cat->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<!-- Status Filter -->
				<select name="filter_status" style="max-width:130px;">
					<option value="all"><?php esc_html_e( 'All Statuses', 'indianagri-quiz-engine' ); ?></option>
					<option value="published" <?php selected( $filter_status, 'published' ); ?>><?php esc_html_e( 'Published', 'indianagri-quiz-engine' ); ?></option>
					<option value="draft" <?php selected( $filter_status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'indianagri-quiz-engine' ); ?></option>
				</select>

				<button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'indianagri-quiz-engine' ); ?></button>
				<?php if ( ! empty( $search_query ) || $filter_year > 0 || $filter_category > 0 || 'all' !== $filter_status ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-pyq' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Reset Filters', 'indianagri-quiz-engine' ); ?></a>
				<?php endif; ?>
			</form>

			<!-- Papers Table -->
			<?php if ( empty( $papers ) ) : ?>
				<div style="text-align:center;padding:50px 20px;">
					<span class="dashicons dashicons-media-document" style="font-size:48px;width:48px;height:48px;color:#94a3b8;margin-bottom:12px;"></span>
					<h3 style="font-size:18px;color:#1e293b;margin:0 0 8px;"><?php esc_html_e( 'No Previous Year Question Papers Found', 'indianagri-quiz-engine' ); ?></h3>
					<p style="color:#64748b;margin:0 0 16px;"><?php esc_html_e( 'Create your first authentic past exam paper to make it available to students.', 'indianagri-quiz-engine' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-pyq&tab=form&action=add' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Add Your First PYQ Paper', 'indianagri-quiz-engine' ); ?>
					</a>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width:50px;"><?php esc_html_e( 'Year', 'indianagri-quiz-engine' ); ?></th>
							<th><?php esc_html_e( 'Paper Title', 'indianagri-quiz-engine' ); ?></th>
							<th><?php esc_html_e( 'Mock Hierarchy Placement', 'indianagri-quiz-engine' ); ?></th>
							<th style="width:90px;"><?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?></th>
							<th style="width:110px;"><?php esc_html_e( 'Marks (+ / -)', 'indianagri-quiz-engine' ); ?></th>
							<th style="width:90px;"><?php esc_html_e( 'Duration', 'indianagri-quiz-engine' ); ?></th>
							<th style="width:80px;"><?php esc_html_e( 'Access', 'indianagri-quiz-engine' ); ?></th>
							<th style="width:90px;"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
							<th style="width:180px;text-align:right;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $papers as $p ) : ?>
							<tr>
								<td>
									<span style="display:inline-block;padding:3px 8px;background:#e0e7ff;color:#3730a3;border-radius:6px;font-weight:700;font-size:12px;">
										<?php echo $p->pyq_year ? esc_html( $p->pyq_year ) : '—'; ?>
									</span>
								</td>
								<td>
									<strong>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-pyq&tab=form&edit_id=' . $p->id ) ); ?>" style="color:#0f172a;text-decoration:none;">
											<?php echo esc_html( $p->title ); ?>
										</a>
									</strong>
									<?php if ( ! empty( $p->pyq_session ) ) : ?>
										<span style="font-size:11px;color:#64748b;margin-left:6px;">(<?php echo esc_html( $p->pyq_session ); ?>)</span>
									<?php endif; ?>
									<div class="row-actions" style="margin-top:4px;">
										<span class="edit">
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-pyq&tab=form&edit_id=' . $p->id ) ); ?>"><?php esc_html_e( 'Edit Settings', 'indianagri-quiz-engine' ); ?></a> |
										</span>
										<span class="manage">
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes&action=manage_questions&quiz_id=' . $p->id ) ); ?>" style="font-weight:600;color:#117E3A;">
												<?php esc_html_e( 'Manage MCQs', 'indianagri-quiz-engine' ); ?>
											</a> |
										</span>
										<span class="trash">
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=iaq-pyq&iaq_action=delete_pyq_paper&quiz_id=' . $p->id ), 'iaq_delete_pyq_' . $p->id ) ); ?>" style="color:#dc2626;" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this PYQ Paper?', 'indianagri-quiz-engine' ); ?>');">
												<?php esc_html_e( 'Delete', 'indianagri-quiz-engine' ); ?>
											</a>
										</span>
									</div>
								</td>
								<td>
									<?php if ( ! empty( $p->category_name ) ) : ?>
										<div style="font-size:12px;font-weight:600;color:#117E3A;">
											<span class="dashicons dashicons-category" style="font-size:13px;width:13px;height:13px;vertical-align:middle;"></span>
											<?php echo esc_html( $p->category_name ); ?>
										</div>
									<?php endif; ?>
									<?php if ( ! empty( $p->exam_name ) ) : ?>
										<div style="font-size:11px;color:#475569;margin-top:2px;">
											&bull; <?php echo esc_html( $p->exam_name ); ?>
										</div>
									<?php endif; ?>
									<?php if ( ! empty( $p->subject_name ) ) : ?>
										<div style="font-size:11px;color:#64748b;">
											&bull; <?php echo esc_html( $p->subject_name ); ?>
										</div>
									<?php endif; ?>
									<?php if ( empty( $p->category_name ) && empty( $p->exam_name ) ) : ?>
										<span style="color:#94a3b8;font-size:12px;"><?php esc_html_e( 'General Archive', 'indianagri-quiz-engine' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes&action=manage_questions&quiz_id=' . $p->id ) ); ?>" style="text-decoration:none;font-weight:700;color:#0284c7;">
										<?php echo (int) $p->total_questions; ?> <?php esc_html_e( 'MCQs', 'indianagri-quiz-engine' ); ?>
									</a>
								</td>
								<td>
									<span style="color:#117E3A;font-weight:600;">+<?php echo esc_html( $p->correct_marks ); ?></span>
									<span style="color:#64748b;"> / </span>
									<span style="color:#dc2626;font-weight:600;">-<?php echo esc_html( $p->negative_marks ); ?></span>
								</td>
								<td>
									<?php echo (int) $p->duration_minutes > 0 ? sprintf( esc_html__( '%d mins', 'indianagri-quiz-engine' ), (int) $p->duration_minutes ) : esc_html__( 'Untimed', 'indianagri-quiz-engine' ); ?>
								</td>
								<td>
									<?php if ( 'paid' === $p->access_type ) : ?>
										<span style="background:#fef3c7;color:#b45309;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">
											<?php esc_html_e( 'Premium', 'indianagri-quiz-engine' ); ?>
										</span>
									<?php else : ?>
										<span style="background:#ecfdf5;color:#047857;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">
											<?php esc_html_e( 'Free', 'indianagri-quiz-engine' ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( 'published' === $p->status ) : ?>
										<span style="background:#dcfce7;color:#166534;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">
											<?php esc_html_e( 'Published', 'indianagri-quiz-engine' ); ?>
										</span>
									<?php else : ?>
										<span style="background:#f1f5f9;color:#64748b;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">
											<?php esc_html_e( 'Draft', 'indianagri-quiz-engine' ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td style="text-align:right;">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes&action=manage_questions&quiz_id=' . $p->id ) ); ?>" class="button button-small" title="<?php esc_attr_e( 'Manage Questions', 'indianagri-quiz-engine' ); ?>">
										<span class="dashicons dashicons-editor-ol" style="font-size:13px;width:13px;height:13px;vertical-align:middle;"></span> <?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?>
									</a>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-pyq&tab=form&edit_id=' . $p->id ) ); ?>" class="button button-small">
										<span class="dashicons dashicons-edit" style="font-size:13px;width:13px;height:13px;vertical-align:middle;"></span> <?php esc_html_e( 'Edit', 'indianagri-quiz-engine' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<!-- Pagination -->
				<?php if ( $total_papers > $per_page ) : ?>
					<div class="tablenav" style="margin-top:16px;">
						<div class="tablenav-pages">
							<?php
							echo paginate_links(
								array(
									'base'    => add_query_arg( 'paged', '%#%' ),
									'format'  => '',
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
									'total'   => ceil( $total_papers / $per_page ),
									'current' => $paged,
								)
							);
							?>
						</div>
					</div>
				<?php endif; ?>

			<?php endif; ?>

		</div>

	<!-- TAB 2: ADD / EDIT PYQ PAPER FORM -->
	<?php elseif ( 'form' === $tab ) : ?>

		<div class="iaq-admin-section" style="margin-top:20px;background:#ffffff;padding:26px;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,0.05);max-width:900px;">
			<div style="margin-bottom:20px;border-bottom:1px solid #f1f5f9;padding-bottom:14px;">
				<h2 style="margin:0 0 4px;font-size:20px;color:#1e1b4b;">
					<?php echo $edit_paper ? esc_html__( 'Edit PYQ Paper Settings', 'indianagri-quiz-engine' ) : esc_html__( 'Add New Previous Year Question Paper', 'indianagri-quiz-engine' ); ?>
				</h2>
				<p style="margin:0;color:#64748b;font-size:13px;">
					<?php esc_html_e( 'Configure official examination details and assign to the existing Mock Test hierarchy.', 'indianagri-quiz-engine' ); ?>
				</p>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-pyq' ) ); ?>">
				<?php wp_nonce_field( 'iaq_save_pyq_paper_nonce' ); ?>
				<input type="hidden" name="iaq_action" value="save_pyq_paper">
				<?php if ( $edit_paper ) : ?>
					<input type="hidden" name="id" value="<?php echo esc_attr( $edit_paper->id ); ?>">
				<?php endif; ?>

				<table class="form-table" role="presentation">
					<tbody>

						<!-- Paper Title -->
						<tr>
							<th scope="row">
								<label for="pyq-title" style="font-weight:700;"><?php esc_html_e( 'Official Paper Title', 'indianagri-quiz-engine' ); ?> <span style="color:#dc2626;">*</span></label>
							</th>
							<td>
								<input type="text" name="title" id="pyq-title" value="<?php echo $edit_paper ? esc_attr( $edit_paper->title ) : ''; ?>" class="regular-text" style="width:100%;font-size:15px;padding:8px 12px;" required placeholder="<?php esc_attr_e( 'e.g. ICAR AIEEA PG 2024 Agronomy Official Question Paper', 'indianagri-quiz-engine' ); ?>">
								<p class="description"><?php esc_html_e( 'Include examination name, year, and subject/discipline in the title for student clarity.', 'indianagri-quiz-engine' ); ?></p>
							</td>
						</tr>

						<!-- Examination Year & Session -->
						<tr>
							<th scope="row">
								<label for="pyq-year" style="font-weight:700;"><?php esc_html_e( 'Examination Year & Session', 'indianagri-quiz-engine' ); ?> <span style="color:#dc2626;">*</span></label>
							</th>
							<td>
								<div style="display:flex;gap:14px;align-items:center;">
									<div>
										<input type="number" name="pyq_year" id="pyq-year" min="1990" max="<?php echo esc_attr( date( 'Y' ) + 1 ); ?>" value="<?php echo $edit_paper && $edit_paper->pyq_year ? esc_attr( $edit_paper->pyq_year ) : esc_attr( date( 'Y' ) ); ?>" style="width:120px;padding:6px 10px;font-weight:700;font-size:15px;" required>
										<span style="font-size:11px;color:#64748b;display:block;margin-top:2px;"><?php esc_html_e( 'Year (e.g. 2024)', 'indianagri-quiz-engine' ); ?></span>
									</div>
									<div style="flex:1;">
										<input type="text" name="pyq_session" id="pyq-session" value="<?php echo $edit_paper ? esc_attr( $edit_paper->pyq_session ) : ''; ?>" placeholder="<?php esc_attr_e( 'e.g. Shift 1 / Morning Session / Phase 2 (Optional)', 'indianagri-quiz-engine' ); ?>" style="width:100%;padding:6px 10px;">
										<span style="font-size:11px;color:#64748b;display:block;margin-top:2px;"><?php esc_html_e( 'Session / Shift / Tier', 'indianagri-quiz-engine' ); ?></span>
									</div>
								</div>
							</td>
						</tr>

						<!-- Mock Test Hierarchy Placement (Reused) -->
						<tr style="background:#f8fafc;">
							<th scope="row" style="padding:18px 10px;">
								<label style="font-weight:700;color:#1e1b4b;"><?php esc_html_e( 'Mock Test Hierarchy Placement', 'indianagri-quiz-engine' ); ?> <span style="color:#dc2626;">*</span></label>
								<p style="font-weight:normal;font-size:11px;color:#64748b;margin-top:4px;"><?php esc_html_e( 'Connects this PYQ paper to the existing Mock Test hierarchy navigation.', 'indianagri-quiz-engine' ); ?></p>
							</th>
							<td style="padding:18px 10px;">
								<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
									<!-- 1. Category -->
									<div>
										<label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( '1. Main Mock Category', 'indianagri-quiz-engine' ); ?></label>
										<select name="category_id" id="pyq-cat-select" style="width:100%;" required>
											<option value=""><?php esc_html_e( '— Select Category —', 'indianagri-quiz-engine' ); ?></option>
											<?php foreach ( $all_categories as $c ) : ?>
												<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $edit_rel ? $edit_rel->category_id : 0, $c->id ); ?>>
													<?php echo esc_html( $c->name ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</div>

									<!-- 2. Examination -->
									<div>
										<label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( '2. Specific Examination', 'indianagri-quiz-engine' ); ?></label>
										<select name="exam_id" id="pyq-exam-select" style="width:100%;">
											<option value="0"><?php esc_html_e( '— All / General —', 'indianagri-quiz-engine' ); ?></option>
											<?php foreach ( $all_exams as $ex ) : ?>
												<option value="<?php echo esc_attr( $ex->id ); ?>" <?php selected( $edit_rel ? $edit_rel->exam_id : 0, $ex->id ); ?>>
													<?php echo esc_html( $ex->name ); ?> (<?php echo esc_html( $ex->code ); ?>)
												</option>
											<?php endforeach; ?>
										</select>
									</div>

									<!-- 3. Subject / Discipline -->
									<div>
										<label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( '3. Subject / Discipline (Optional)', 'indianagri-quiz-engine' ); ?></label>
										<select name="subject_id" style="width:100%;">
											<option value="0"><?php esc_html_e( '— Select Subject (if applicable) —', 'indianagri-quiz-engine' ); ?></option>
											<?php foreach ( $all_subjects as $s ) : ?>
												<option value="<?php echo esc_attr( $s->id ); ?>" <?php selected( $edit_paper ? $edit_paper->subject_id : 0, $s->id ); ?>>
													<?php echo esc_html( $s->name ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</div>

									<!-- 4. Post / Vacancy / State -->
									<div>
										<label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;"><?php esc_html_e( '4. Examination Code / Tag', 'indianagri-quiz-engine' ); ?></label>
										<input type="text" name="exam_category" value="<?php echo $edit_paper ? esc_attr( $edit_paper->exam_category ) : 'ICAR'; ?>" placeholder="e.g. ICAR-PG / UPSSSC-TA / IBPS-AFO" style="width:100%;">
									</div>
								</div>
							</td>
						</tr>

						<!-- Examination Parameters: Duration, Marks, Negative Marking -->
						<tr>
							<th scope="row">
								<label style="font-weight:700;"><?php esc_html_e( 'Examination Blueprint', 'indianagri-quiz-engine' ); ?></label>
							</th>
							<td>
								<div style="display:flex;gap:14px;flex-wrap:wrap;">
									<div>
										<label style="font-size:12px;color:#64748b;display:block;margin-bottom:2px;"><?php esc_html_e( 'Duration (Minutes)', 'indianagri-quiz-engine' ); ?></label>
										<input type="number" name="duration_minutes" min="0" max="360" value="<?php echo $edit_paper ? esc_attr( $edit_paper->duration_minutes ) : 120; ?>" style="width:110px;">
									</div>
									<div>
										<label style="font-size:12px;color:#64748b;display:block;margin-bottom:2px;"><?php esc_html_e( 'Marks Per Question', 'indianagri-quiz-engine' ); ?></label>
										<input type="number" step="0.25" min="0.1" name="correct_marks" value="<?php echo $edit_paper ? esc_attr( $edit_paper->correct_marks ) : '1.00'; ?>" style="width:110px;">
									</div>
									<div>
										<label style="font-size:12px;color:#64748b;display:block;margin-bottom:2px;"><?php esc_html_e( 'Negative Deduction', 'indianagri-quiz-engine' ); ?></label>
										<input type="number" step="0.05" min="0" name="negative_marks" value="<?php echo $edit_paper ? esc_attr( $edit_paper->negative_marks ) : '0.25'; ?>" style="width:110px;">
									</div>
									<div>
										<label style="font-size:12px;color:#64748b;display:block;margin-bottom:2px;"><?php esc_html_e( 'Pass Percentage (%)', 'indianagri-quiz-engine' ); ?></label>
										<input type="number" step="1" min="0" max="100" name="pass_percentage" value="<?php echo $edit_paper ? esc_attr( $edit_paper->pass_percentage ) : '40'; ?>" style="width:100px;">
									</div>
								</div>
							</td>
						</tr>

						<!-- Attempt Mode: Practice vs Examination Mode -->
						<tr>
							<th scope="row">
								<label for="show-answers-mode" style="font-weight:700;"><?php esc_html_e( 'Candidate Attempt Mode', 'indianagri-quiz-engine' ); ?></label>
							</th>
							<td>
								<select name="show_answers_mode" id="show-answers-mode" style="max-width:400px;font-weight:600;">
									<option value="after_submission" <?php selected( $edit_paper ? $edit_paper->show_answers_mode : 'after_submission', 'after_submission' ); ?>>
										<?php esc_html_e( 'Examination Mode (Timed CBT, Answers & Explanations After Submission)', 'indianagri-quiz-engine' ); ?>
									</option>
									<option value="immediate" <?php selected( $edit_paper ? $edit_paper->show_answers_mode : 'after_submission', 'immediate' ); ?>>
										<?php esc_html_e( 'Practice Mode (Instant Feedback & Scientific Explanations)', 'indianagri-quiz-engine' ); ?>
									</option>
								</select>
								<p class="description">
									<?php esc_html_e( 'Select whether candidates solve under strict CBT exam conditions or with instant answer reveals for concept learning.', 'indianagri-quiz-engine' ); ?>
								</p>
							</td>
						</tr>

						<!-- Question Sequencing & Shuffle (Preserve Original Sequence) -->
						<tr>
							<th scope="row">
								<label style="font-weight:700;"><?php esc_html_e( 'Question Sequencing', 'indianagri-quiz-engine' ); ?></label>
							</th>
							<td>
								<label style="display:block;margin-bottom:6px;">
									<input type="checkbox" name="shuffle_questions" value="1" <?php checked( $edit_paper ? $edit_paper->shuffle_questions : 0, 1 ); ?>>
									<?php esc_html_e( 'Shuffle Question Order (Default is unchecked to preserve original paper sequence)', 'indianagri-quiz-engine' ); ?>
								</label>
								<label style="display:block;">
									<input type="checkbox" name="shuffle_options" value="1" <?php checked( $edit_paper ? $edit_paper->shuffle_options : 0, 1 ); ?>>
									<?php esc_html_e( 'Shuffle Option Sequence (A, B, C, D)', 'indianagri-quiz-engine' ); ?>
								</label>
							</td>
						</tr>

						<!-- Access Control: Free vs Premium -->
						<tr>
							<th scope="row">
								<label style="font-weight:700;"><?php esc_html_e( 'Access Control', 'indianagri-quiz-engine' ); ?></label>
							</th>
							<td>
								<div style="display:flex;gap:20px;align-items:center;">
									<label style="cursor:pointer;">
										<input type="radio" name="access_type" value="free" <?php checked( $edit_paper ? $edit_paper->access_type : 'free', 'free' ); ?>>
										<span style="font-weight:700;color:#047857;">🌟 <?php esc_html_e( 'Free Access (All Registered Students)', 'indianagri-quiz-engine' ); ?></span>
									</label>
									<label style="cursor:pointer;">
										<input type="radio" name="access_type" value="paid" <?php checked( $edit_paper ? $edit_paper->access_type : 'free', 'paid' ); ?>>
										<span style="font-weight:700;color:#b45309;">👑 <?php esc_html_e( 'Premium Access (Subscribers Only)', 'indianagri-quiz-engine' ); ?></span>
									</label>
								</div>
							</td>
						</tr>

						<!-- Status: Draft vs Published -->
						<tr>
							<th scope="row">
								<label for="pyq-status" style="font-weight:700;"><?php esc_html_e( 'Publishing Status', 'indianagri-quiz-engine' ); ?></label>
							</th>
							<td>
								<select name="status" id="pyq-status" style="width:160px;font-weight:700;">
									<option value="published" <?php selected( $edit_paper ? $edit_paper->status : 'published', 'published' ); ?>><?php esc_html_e( 'Published', 'indianagri-quiz-engine' ); ?></option>
									<option value="draft" <?php selected( $edit_paper ? $edit_paper->status : 'published', 'draft' ); ?>><?php esc_html_e( 'Draft', 'indianagri-quiz-engine' ); ?></option>
									<option value="archived" <?php selected( $edit_paper ? $edit_paper->status : 'published', 'archived' ); ?>><?php esc_html_e( 'Archived', 'indianagri-quiz-engine' ); ?></option>
								</select>
							</td>
						</tr>

						<!-- Paper Instructions & Description -->
						<tr>
							<th scope="row">
								<label for="pyq-instructions" style="font-weight:700;"><?php esc_html_e( 'Paper Instructions', 'indianagri-quiz-engine' ); ?></label>
							</th>
							<td>
								<textarea name="instructions" id="pyq-instructions" rows="4" style="width:100%;font-size:13px;"><?php echo $edit_paper ? esc_textarea( $edit_paper->instructions ) : "1. This paper contains official past examination questions in their authentic order.\n2. Total duration is configured as per original exam rules.\n3. Each correct answer awards the designated positive marks and incorrect responses attract negative marking.\n4. Scientific explanations are provided upon completion."; ?></textarea>
							</td>
						</tr>

					</tbody>
				</table>

				<div style="margin-top:24px;display:flex;gap:12px;">
					<button type="submit" class="button button-primary button-large" style="background:#7c3aed;border-color:#6d28d9;padding:6px 24px;height:auto;font-size:14px;font-weight:700;">
						<?php echo $edit_paper ? esc_html__( 'Update PYQ Paper', 'indianagri-quiz-engine' ) : esc_html__( 'Create PYQ Paper & Add Questions', 'indianagri-quiz-engine' ); ?>
					</button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-pyq' ) ); ?>" class="button button-secondary button-large">
						<?php esc_html_e( 'Cancel', 'indianagri-quiz-engine' ); ?>
					</a>
				</div>
			</form>
		</div>

	<!-- TAB 3: CSV QUESTION IMPORT DIRECTLY TO PYQ -->
	<?php elseif ( 'import' === $tab ) : ?>

		<div class="iaq-admin-section" style="margin-top:20px;background:#ffffff;padding:26px;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,0.05);max-width:800px;">
			<h2 style="margin:0 0 8px;font-size:18px;color:#1e1b4b;"><?php esc_html_e( 'Bulk Import Questions to PYQ Paper', 'indianagri-quiz-engine' ); ?></h2>
			<p style="color:#64748b;margin:0 0 20px;">
				<?php esc_html_e( 'Upload a CSV file of questions directly into any Previous Year Paper. Questions will be created in the Question Bank and sequentially mapped to this paper.', 'indianagri-quiz-engine' ); ?>
			</p>

			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-pyq' ) ); ?>">
				<?php wp_nonce_field( 'iaq_pyq_bulk_import_nonce' ); ?>
				<input type="hidden" name="iaq_action" value="bulk_import_pyq">

				<div style="margin-bottom:18px;">
					<label for="import-quiz-id" style="font-weight:700;display:block;margin-bottom:6px;"><?php esc_html_e( 'Select Target PYQ Paper:', 'indianagri-quiz-engine' ); ?> <span style="color:#dc2626;">*</span></label>
					<select name="quiz_id" id="import-quiz-id" style="width:100%;max-width:500px;" required>
						<option value=""><?php esc_html_e( '— Select PYQ Paper —', 'indianagri-quiz-engine' ); ?></option>
						<?php
						$all_pyqs = IAQ_PYQ::get_admin_papers( array( 'limit' => 200 ) );
						foreach ( $all_pyqs as $pq_opt ) :
						?>
							<option value="<?php echo esc_attr( $pq_opt->id ); ?>">
								[<?php echo esc_html( $pq_opt->pyq_year ); ?>] <?php echo esc_html( $pq_opt->title ); ?> (<?php echo (int) $pq_opt->total_questions; ?> Qs)
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div style="margin-bottom:18px;">
					<label for="import-csv" style="font-weight:700;display:block;margin-bottom:6px;"><?php esc_html_e( 'Select CSV File:', 'indianagri-quiz-engine' ); ?> <span style="color:#dc2626;">*</span></label>
					<input type="file" name="csv_file" id="import-csv" accept=".csv" required>
					<p class="description" style="margin-top:6px;">
						<?php esc_html_e( 'CSV format: question_text, option_a, option_b, option_c, option_d, correct_option (A/B/C/D), explanation, subject, topic, difficulty', 'indianagri-quiz-engine' ); ?>
					</p>
				</div>

				<div style="margin-top:20px;">
					<button type="submit" class="button button-primary">
						<span class="dashicons dashicons-upload" style="vertical-align:middle;"></span> <?php esc_html_e( 'Upload and Map Questions to Paper', 'indianagri-quiz-engine' ); ?>
					</button>
				</div>
			</form>
		</div>

	<?php endif; ?>

</div>
