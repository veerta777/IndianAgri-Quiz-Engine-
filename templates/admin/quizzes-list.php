<?php
/**
 * Quizzes list admin template.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$search        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$quiz_type     = isset( $_GET['quiz_type'] ) ? sanitize_key( $_GET['quiz_type'] ) : '';
$exam_category = isset( $_GET['exam_category'] ) ? sanitize_text_field( wp_unslash( $_GET['exam_category'] ) ) : '';
$status        = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
$current_page  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page      = 20;

$filter_args = array(
	'search'        => $search,
	'quiz_type'     => $quiz_type,
	'exam_category' => $exam_category,
	'status'        => $status,
	'page'          => $current_page,
	'per_page'      => $per_page,
	'orderby'       => 'id',
	'order'         => 'DESC',
);

$quizzes       = IAQ_Quiz_Builder::get_quizzes( $filter_args );
$total_quizzes = IAQ_Quiz_Builder::get_quiz_count( $filter_args );
$total_pages   = ceil( $total_quizzes / $per_page );

$quiz_types      = IAQ_Quiz_Builder::get_quiz_types();
$exam_categories = IAQ_Question_Bank::get_exam_categories();

// Statistics counts.
$count_all      = IAQ_Quiz_Builder::get_quiz_count();
$count_practice = IAQ_Quiz_Builder::get_quiz_count( array( 'quiz_type' => 'practice' ) );
$count_mock     = IAQ_Quiz_Builder::get_quiz_count( array( 'quiz_type' => 'mock' ) );
?>

<div class="wrap iaq-admin-wrap">
	<!-- Header Bar -->
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php esc_html_e( 'Quiz & Exam Builder', 'indianagri-quiz-engine' ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Configure agricultural mock examinations, practice sets, and previous year question papers with custom rules and shortcodes.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
		<div class="iaq-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-add-quiz' ) ); ?>" class="iaq-btn iaq-btn-primary">
				<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Create New Quiz', 'indianagri-quiz-engine' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-question-bank' ) ); ?>" class="iaq-btn iaq-btn-outline">
				<span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'Question Bank', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>
	</div>

	<!-- Statistics Cards -->
	<div class="iaq-stats-grid">
		<div class="iaq-stat-card">
			<div class="iaq-stat-icon dashicons dashicons-clipboard"></div>
			<div class="iaq-stat-content">
				<span class="iaq-stat-label"><?php esc_html_e( 'All Quizzes', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-value"><?php echo number_format_i18n( $count_all ); ?></span>
			</div>
		</div>
		<div class="iaq-stat-card">
			<div class="iaq-stat-icon dashicons dashicons-welcome-learn-more" style="color: #0284c7; background: #e0f2fe;"></div>
			<div class="iaq-stat-content">
				<span class="iaq-stat-label"><?php esc_html_e( 'Practice Sets', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-value"><?php echo number_format_i18n( $count_practice ); ?></span>
			</div>
		</div>
		<div class="iaq-stat-card">
			<div class="iaq-stat-icon dashicons dashicons-clock" style="color: #117E3A; background: #dcfce7;"></div>
			<div class="iaq-stat-content">
				<span class="iaq-stat-label"><?php esc_html_e( 'Mock Tests', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-value"><?php echo number_format_i18n( $count_mock ); ?></span>
			</div>
		</div>
	</div>

	<!-- Filter Bar -->
	<div class="iaq-card iaq-filter-card">
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="iaq-filter-form">
			<input type="hidden" name="page" value="iaq-quizzes">

			<div class="iaq-filter-row">
				<div class="iaq-filter-field iaq-filter-search">
					<label for="iaq-quiz-search"><?php esc_html_e( 'Search Quiz', 'indianagri-quiz-engine' ); ?></label>
					<div class="iaq-search-box">
						<span class="dashicons dashicons-search"></span>
						<input type="text" id="iaq-quiz-search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search by quiz title or topic...', 'indianagri-quiz-engine' ); ?>">
					</div>
				</div>

				<div class="iaq-filter-field">
					<label for="iaq-filter-type"><?php esc_html_e( 'Quiz Type', 'indianagri-quiz-engine' ); ?></label>
					<select name="quiz_type" id="iaq-filter-type" class="iaq-select">
						<option value=""><?php esc_html_e( 'All Types', 'indianagri-quiz-engine' ); ?></option>
						<?php foreach ( $quiz_types as $k => $meta ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $quiz_type, $k ); ?>>
								<?php echo esc_html( $meta['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="iaq-filter-field">
					<label for="iaq-filter-exam"><?php esc_html_e( 'Exam Category', 'indianagri-quiz-engine' ); ?></label>
					<select name="exam_category" id="iaq-filter-exam" class="iaq-select">
						<option value=""><?php esc_html_e( 'All Categories', 'indianagri-quiz-engine' ); ?></option>
						<?php foreach ( $exam_categories as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $exam_category, $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="iaq-filter-field">
					<label for="iaq-filter-status"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></label>
					<select name="status" id="iaq-filter-status" class="iaq-select">
						<option value=""><?php esc_html_e( 'All Statuses', 'indianagri-quiz-engine' ); ?></option>
						<option value="published" <?php selected( $status, 'published' ); ?>><?php esc_html_e( 'Published', 'indianagri-quiz-engine' ); ?></option>
						<option value="draft" <?php selected( $status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'indianagri-quiz-engine' ); ?></option>
						<option value="archived" <?php selected( $status, 'archived' ); ?>><?php esc_html_e( 'Archived', 'indianagri-quiz-engine' ); ?></option>
					</select>
				</div>

				<div class="iaq-filter-actions">
					<button type="submit" class="iaq-btn iaq-btn-primary">
						<span class="dashicons dashicons-filter"></span> <?php esc_html_e( 'Filter', 'indianagri-quiz-engine' ); ?>
					</button>
					<?php if ( $search || $quiz_type || $exam_category || $status ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes' ) ); ?>" class="iaq-btn iaq-btn-outline">
							<?php esc_html_e( 'Reset Filters', 'indianagri-quiz-engine' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</form>
	</div>

	<!-- Table Section -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes' ) ); ?>" id="iaq-bulk-quizzes-form">
		<?php wp_nonce_field( 'iaq_bulk_quizzes_nonce' ); ?>

		<div class="iaq-table-controls">
			<div class="iaq-bulk-wrap">
				<select name="bulk_action" id="iaq-bulk-quiz-action" class="iaq-select">
					<option value=""><?php esc_html_e( 'Bulk Actions', 'indianagri-quiz-engine' ); ?></option>
					<option value="published"><?php esc_html_e( 'Mark as Published', 'indianagri-quiz-engine' ); ?></option>
					<option value="draft"><?php esc_html_e( 'Mark as Draft', 'indianagri-quiz-engine' ); ?></option>
					<option value="archived"><?php esc_html_e( 'Mark as Archived', 'indianagri-quiz-engine' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete Selected', 'indianagri-quiz-engine' ); ?></option>
				</select>
				<button type="submit" name="iaq_bulk_quizzes_submit" class="iaq-btn iaq-btn-secondary">
					<?php esc_html_e( 'Apply', 'indianagri-quiz-engine' ); ?>
				</button>
			</div>
			<div class="iaq-count-info">
				<?php
				printf(
					/* translators: 1: filtered total count */
					esc_html__( 'Found %1$s quizzes', 'indianagri-quiz-engine' ),
					'<strong>' . esc_html( number_format_i18n( $total_quizzes ) ) . '</strong>'
				);
				?>
			</div>
		</div>

		<div class="iaq-card iaq-table-card">
			<table class="iaq-table">
				<thead>
					<tr>
						<th class="iaq-col-cb"><input type="checkbox" id="iaq-check-all-quizzes"></th>
						<th class="iaq-col-id"><?php esc_html_e( 'ID', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Quiz Title & Category', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Type', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Duration', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Total Marks', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Shortcode (Elementor / WP)', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $quizzes ) ) : ?>
						<tr>
							<td colspan="10" class="iaq-no-records">
								<div class="iaq-empty-state">
									<span class="dashicons dashicons-clipboard iaq-empty-icon"></span>
									<h3><?php esc_html_e( 'No Quizzes Found', 'indianagri-quiz-engine' ); ?></h3>
									<p><?php esc_html_e( 'Create your first mock exam or practice set to assemble questions from your question bank.', 'indianagri-quiz-engine' ); ?></p>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-add-quiz' ) ); ?>" class="iaq-btn iaq-btn-primary">
										<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Create New Quiz', 'indianagri-quiz-engine' ); ?>
									</a>
								</div>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $quizzes as $q ) : ?>
							<?php
							$type_meta = isset( $quiz_types[ $q->quiz_type ] ) ? $quiz_types[ $q->quiz_type ] : array( 'label' => ucfirst( $q->quiz_type ), 'badge_color' => '#64748b' );
							$edit_url  = admin_url( 'admin.php?page=iaq-quizzes&action=edit&id=' . $q->id );
							$assign_url = admin_url( 'admin.php?page=iaq-quizzes&action=manage_questions&id=' . $q->id );
							$delete_url = wp_nonce_url(
								admin_url( 'admin.php?page=iaq-quizzes&action=delete&id=' . $q->id ),
								'iaq_delete_quiz_' . $q->id
							);
							$duplicate_url = wp_nonce_url(
								admin_url( 'admin.php?page=iaq-quizzes&action=duplicate&id=' . $q->id ),
								'iaq_duplicate_quiz_' . $q->id
							);
							?>
							<tr>
								<td class="iaq-col-cb">
									<input type="checkbox" name="quiz_ids[]" value="<?php echo esc_attr( $q->id ); ?>" class="iaq-quiz-checkbox">
								</td>
								<td class="iaq-col-id">
									<span class="iaq-id-pill">#<?php echo esc_html( $q->id ); ?></span>
								</td>
								<td>
									<strong><a href="<?php echo esc_url( $edit_url ); ?>" style="color:#0f172a;text-decoration:none;font-size:14px;"><?php echo esc_html( $q->title ); ?></a></strong>
									<div style="margin-top:4px;display:flex;gap:6px;align-items:center;">
										<span class="iaq-badge iaq-badge-exam"><?php echo esc_html( $q->exam_category ); ?></span>
										<?php if ( $q->subject_name ) : ?>
											<span class="iaq-topic-badge"><?php echo esc_html( $q->subject_name ); ?></span>
										<?php endif; ?>
										<?php if ( 'paid' === $q->access_type ) : ?>
											<span class="iaq-badge" style="background:#fef3c7;color:#b45309;"><?php esc_html_e( 'Paid', 'indianagri-quiz-engine' ); ?></span>
										<?php else : ?>
											<span class="iaq-badge" style="background:#f1f5f9;color:#475569;"><?php esc_html_e( 'Free', 'indianagri-quiz-engine' ); ?></span>
										<?php endif; ?>
									</div>
								</td>
								<td>
									<span class="iaq-badge" style="background:<?php echo esc_attr( $type_meta['badge_color'] ); ?>15;color:<?php echo esc_attr( $type_meta['badge_color'] ); ?>;border-color:<?php echo esc_attr( $type_meta['badge_color'] ); ?>30;">
										<?php echo esc_html( $type_meta['label'] ); ?>
									</span>
								</td>
								<td>
									<a href="<?php echo esc_url( $assign_url ); ?>" class="iaq-btn iaq-btn-outline" style="padding:4px 8px;font-size:12px;">
										<strong><?php echo esc_html( $q->assigned_questions_count ); ?></strong> / <?php echo esc_html( $q->total_questions ); ?> <?php esc_html_e( 'MCQs', 'indianagri-quiz-engine' ); ?>
									</a>
								</td>
								<td>
									<?php echo ( $q->duration_minutes > 0 ) ? esc_html( $q->duration_minutes ) . ' ' . esc_html__( 'mins', 'indianagri-quiz-engine' ) : esc_html__( 'Untimed', 'indianagri-quiz-engine' ); ?>
								</td>
								<td>
									<strong><?php echo esc_html( $q->total_marks ); ?></strong>
									<div class="iaq-marks-indicator" style="font-size:11px;margin-top:2px;">
										<span class="iaq-pos-mark">+<?php echo esc_html( $q->correct_marks ); ?></span>
										<?php if ( floatval( $q->negative_marks ) > 0 ) : ?>
											<span class="iaq-neg-mark">-<?php echo esc_html( $q->negative_marks ); ?></span>
										<?php endif; ?>
									</div>
								</td>
								<td>
									<code style="cursor:pointer;background:#f8fafc;padding:3px 6px;border:1px solid #cbd5e1;border-radius:4px;" class="iaq-copy-shortcode" title="<?php esc_attr_e( 'Click to copy shortcode', 'indianagri-quiz-engine' ); ?>">[iaq_quiz id="<?php echo esc_attr( $q->id ); ?>"]</code>
								</td>
								<td>
									<span class="iaq-badge iaq-status-<?php echo esc_attr( $q->status ); ?>">
										<?php echo esc_html( ucfirst( $q->status ) ); ?>
									</span>
								</td>
								<td>
									<div class="iaq-row-action-btns">
										<a href="<?php echo esc_url( $assign_url ); ?>" class="iaq-action-btn" title="<?php esc_attr_e( 'Manage Questions', 'indianagri-quiz-engine' ); ?>" style="color:#117E3A;">
											<span class="dashicons dashicons-list-view"></span>
										</a>
										<a href="<?php echo esc_url( $edit_url ); ?>" class="iaq-action-btn iaq-btn-edit" title="<?php esc_attr_e( 'Edit Settings', 'indianagri-quiz-engine' ); ?>">
											<span class="dashicons dashicons-edit"></span>
										</a>
										<a href="<?php echo esc_url( $duplicate_url ); ?>" class="iaq-action-btn" title="<?php esc_attr_e( 'Duplicate Quiz', 'indianagri-quiz-engine' ); ?>" style="color:#0284c7;">
											<span class="dashicons dashicons-admin-page"></span>
										</a>
										<a href="<?php echo esc_url( $delete_url ); ?>" class="iaq-action-btn iaq-btn-delete iaq-confirm-delete" title="<?php esc_attr_e( 'Delete Quiz', 'indianagri-quiz-engine' ); ?>">
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

		<!-- Pagination Footer -->
		<?php if ( $total_pages > 1 ) : ?>
			<div class="iaq-pagination-bar">
				<div class="iaq-page-numbers">
					<?php
					$page_links = paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => '&laquo; ' . __( 'Previous', 'indianagri-quiz-engine' ),
							'next_text' => __( 'Next', 'indianagri-quiz-engine' ) . ' &raquo;',
							'total'     => $total_pages,
							'current'   => $current_page,
							'type'      => 'plain',
						)
					);
					echo wp_kses_post( $page_links );
					?>
				</div>
				<div class="iaq-page-summary">
					<?php
					printf(
						/* translators: 1: current page, 2: total pages, 3: total items */
						esc_html__( 'Page %1$d of %2$d (%3$s total quizzes)', 'indianagri-quiz-engine' ),
						esc_html( $current_page ),
						esc_html( $total_pages ),
						esc_html( number_format_i18n( $total_quizzes ) )
					);
					?>
				</div>
			</div>
		<?php endif; ?>
	</form>
</div>
