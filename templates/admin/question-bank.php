<?php
/**
 * Question Bank management view.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extract filter parameters from GET request.
$search        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$subject_id    = isset( $_GET['subject_id'] ) ? absint( $_GET['subject_id'] ) : 0;
$topic_id      = isset( $_GET['topic_id'] ) ? absint( $_GET['topic_id'] ) : 0;
$exam_category = isset( $_GET['exam_category'] ) ? sanitize_text_field( wp_unslash( $_GET['exam_category'] ) ) : '';
$difficulty    = isset( $_GET['difficulty'] ) ? sanitize_key( $_GET['difficulty'] ) : '';
$status        = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
$current_page  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page      = 20;

$filter_args = array(
	'search'        => $search,
	'subject_id'    => $subject_id,
	'topic_id'      => $topic_id,
	'exam_category' => $exam_category,
	'difficulty'    => $difficulty,
	'status'        => $status,
	'page'          => $current_page,
	'per_page'      => $per_page,
	'orderby'       => 'id',
	'order'         => 'DESC',
);

$questions       = IAQ_Question_Bank::get_questions( $filter_args );
$total_questions = IAQ_Question_Bank::get_question_count( $filter_args );
$total_pages     = ceil( $total_questions / $per_page );

$subjects        = IAQ_Question_Bank::get_subjects();
$topics          = $subject_id > 0 ? IAQ_Question_Bank::get_topics( $subject_id ) : array();
$exam_categories = IAQ_Question_Bank::get_exam_categories();
$difficulties    = IAQ_Question_Bank::get_difficulty_levels();

// Overall stats.
$total_all_count = IAQ_Question_Bank::get_question_count();
$easy_count      = IAQ_Question_Bank::get_question_count( array( 'difficulty' => 'easy' ) );
$medium_count    = IAQ_Question_Bank::get_question_count( array( 'difficulty' => 'medium' ) );
$hard_count      = IAQ_Question_Bank::get_question_count( array( 'difficulty' => 'hard' ) );
?>

<div class="wrap iaq-admin-wrap">
	<!-- Header Bar -->
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php esc_html_e( 'Question Bank', 'indianagri-quiz-engine' ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Create, filter, organize, and manage agricultural MCQ repositories for mock exams and competitive tests.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
		<div class="iaq-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-add-question' ) ); ?>" class="iaq-btn iaq-btn-primary">
				<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Question', 'indianagri-quiz-engine' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-import-export' ) ); ?>" class="iaq-btn iaq-btn-secondary">
				<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Import / Export', 'indianagri-quiz-engine' ); ?>
			</a>
			<?php
			$export_url = wp_nonce_url(
				add_query_arg(
					array_merge(
						array(
							'iaq_action' => 'export_csv',
						),
						$filter_args
					),
					admin_url( 'admin.php' )
				),
				'iaq_export_csv'
			);
			?>
			<a href="<?php echo esc_url( $export_url ); ?>" class="iaq-btn iaq-btn-outline">
				<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>
	</div>

	<!-- Statistics Cards -->
	<div class="iaq-stats-grid">
		<div class="iaq-stat-card">
			<div class="iaq-stat-icon dashicons dashicons-list-view"></div>
			<div class="iaq-stat-content">
				<span class="iaq-stat-label"><?php esc_html_e( 'Total Questions', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-value"><?php echo number_format_i18n( $total_all_count ); ?></span>
			</div>
		</div>
		<div class="iaq-stat-card">
			<div class="iaq-stat-icon dashicons dashicons-yes" style="color: #16a34a; background: #dcfce7;"></div>
			<div class="iaq-stat-content">
				<span class="iaq-stat-label"><?php esc_html_e( 'Easy MCQs', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-value"><?php echo number_format_i18n( $easy_count ); ?></span>
			</div>
		</div>
		<div class="iaq-stat-card">
			<div class="iaq-stat-icon dashicons dashicons-marker" style="color: #d97706; background: #fef3c7;"></div>
			<div class="iaq-stat-content">
				<span class="iaq-stat-label"><?php esc_html_e( 'Medium MCQs', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-value"><?php echo number_format_i18n( $medium_count ); ?></span>
			</div>
		</div>
		<div class="iaq-stat-card">
			<div class="iaq-stat-icon dashicons dashicons-warning" style="color: #dc2626; background: #fee2e2;"></div>
			<div class="iaq-stat-content">
				<span class="iaq-stat-label"><?php esc_html_e( 'Hard MCQs', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-value"><?php echo number_format_i18n( $hard_count ); ?></span>
			</div>
		</div>
	</div>

	<!-- Filter Form -->
	<div class="iaq-card iaq-filter-card">
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="iaq-filter-form">
			<input type="hidden" name="page" value="iaq-question-bank">

			<div class="iaq-filter-row">
				<div class="iaq-filter-field iaq-filter-search">
					<label for="iaq-search-input"><?php esc_html_e( 'Search Keyword', 'indianagri-quiz-engine' ); ?></label>
					<div class="iaq-search-box">
						<span class="dashicons dashicons-search"></span>
						<input type="text" id="iaq-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search questions, options, explanation (English / हिंदी)...', 'indianagri-quiz-engine' ); ?>">
					</div>
				</div>

				<div class="iaq-filter-field">
					<label for="iaq-filter-subject"><?php esc_html_e( 'Subject', 'indianagri-quiz-engine' ); ?></label>
					<select name="subject_id" id="iaq-filter-subject" class="iaq-select">
						<option value="0"><?php esc_html_e( 'All Subjects', 'indianagri-quiz-engine' ); ?></option>
						<?php foreach ( $subjects as $subj ) : ?>
							<option value="<?php echo esc_attr( $subj->id ); ?>" <?php selected( $subject_id, $subj->id ); ?>>
								<?php echo esc_html( $subj->name ); ?> (<?php echo esc_html( $subj->question_count ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="iaq-filter-field">
					<label for="iaq-filter-topic"><?php esc_html_e( 'Topic', 'indianagri-quiz-engine' ); ?></label>
					<select name="topic_id" id="iaq-filter-topic" class="iaq-select">
						<option value="0"><?php esc_html_e( 'All Topics', 'indianagri-quiz-engine' ); ?></option>
						<?php foreach ( $topics as $top ) : ?>
							<option value="<?php echo esc_attr( $top->id ); ?>" <?php selected( $topic_id, $top->id ); ?>>
								<?php echo esc_html( $top->name ); ?>
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
					<label for="iaq-filter-diff"><?php esc_html_e( 'Difficulty', 'indianagri-quiz-engine' ); ?></label>
					<select name="difficulty" id="iaq-filter-diff" class="iaq-select">
						<option value=""><?php esc_html_e( 'All Difficulties', 'indianagri-quiz-engine' ); ?></option>
						<?php foreach ( $difficulties as $d_key => $d_meta ) : ?>
							<option value="<?php echo esc_attr( $d_key ); ?>" <?php selected( $difficulty, $d_key ); ?>>
								<?php echo esc_html( $d_meta['label'] ); ?>
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
					<?php if ( $search || $subject_id || $topic_id || $exam_category || $difficulty || $status ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-question-bank' ) ); ?>" class="iaq-btn iaq-btn-outline">
							<?php esc_html_e( 'Reset Filters', 'indianagri-quiz-engine' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</form>
	</div>

	<!-- Question Table Section -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-question-bank' ) ); ?>" id="iaq-bulk-form">
		<?php wp_nonce_field( 'iaq_bulk_questions_nonce' ); ?>

		<div class="iaq-table-controls">
			<div class="iaq-bulk-wrap">
				<select name="bulk_action" id="iaq-bulk-action" class="iaq-select">
					<option value=""><?php esc_html_e( 'Bulk Actions', 'indianagri-quiz-engine' ); ?></option>
					<option value="published"><?php esc_html_e( 'Mark as Published', 'indianagri-quiz-engine' ); ?></option>
					<option value="draft"><?php esc_html_e( 'Mark as Draft', 'indianagri-quiz-engine' ); ?></option>
					<option value="archived"><?php esc_html_e( 'Mark as Archived', 'indianagri-quiz-engine' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete Selected', 'indianagri-quiz-engine' ); ?></option>
				</select>
				<button type="submit" name="iaq_bulk_action_submit" class="iaq-btn iaq-btn-secondary" id="iaq-apply-bulk">
					<?php esc_html_e( 'Apply', 'indianagri-quiz-engine' ); ?>
				</button>
			</div>
			<div class="iaq-count-info">
				<?php
				printf(
					/* translators: 1: filtered total count */
					esc_html__( 'Found %1$s questions', 'indianagri-quiz-engine' ),
					'<strong>' . esc_html( number_format_i18n( $total_questions ) ) . '</strong>'
				);
				?>
			</div>
		</div>

		<div class="iaq-card iaq-table-card">
			<table class="iaq-table" id="iaq-questions-table">
				<thead>
					<tr>
						<th class="iaq-col-cb"><input type="checkbox" id="iaq-check-all"></th>
						<th class="iaq-col-id"><?php esc_html_e( 'ID', 'indianagri-quiz-engine' ); ?></th>
						<th class="iaq-col-question"><?php esc_html_e( 'Question & Options', 'indianagri-quiz-engine' ); ?></th>
						<th class="iaq-col-tax"><?php esc_html_e( 'Subject & Topic', 'indianagri-quiz-engine' ); ?></th>
						<th class="iaq-col-exam"><?php esc_html_e( 'Category', 'indianagri-quiz-engine' ); ?></th>
						<th class="iaq-col-diff"><?php esc_html_e( 'Difficulty', 'indianagri-quiz-engine' ); ?></th>
						<th class="iaq-col-marks"><?php esc_html_e( 'Marks', 'indianagri-quiz-engine' ); ?></th>
						<th class="iaq-col-status"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
						<th class="iaq-col-actions"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $questions ) ) : ?>
						<tr>
							<td colspan="9" class="iaq-no-records">
								<div class="iaq-empty-state">
									<span class="dashicons dashicons-search iaq-empty-icon"></span>
									<h3><?php esc_html_e( 'No Questions Found', 'indianagri-quiz-engine' ); ?></h3>
									<p><?php esc_html_e( 'There are no questions matching your search filters or the database is currently empty.', 'indianagri-quiz-engine' ); ?></p>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-add-question' ) ); ?>" class="iaq-btn iaq-btn-primary">
										<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Your First Question', 'indianagri-quiz-engine' ); ?>
									</a>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-import-export' ) ); ?>" class="iaq-btn iaq-btn-outline">
										<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Bulk Import CSV', 'indianagri-quiz-engine' ); ?>
									</a>
								</div>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $questions as $q ) : ?>
							<?php
							$diff_info  = isset( $difficulties[ $q->difficulty ] ) ? $difficulties[ $q->difficulty ] : array( 'label' => ucfirst( $q->difficulty ), 'color' => '#64748b' );
							$delete_url = wp_nonce_url(
								admin_url( 'admin.php?page=iaq-question-bank&action=delete&id=' . $q->id ),
								'iaq_delete_question_' . $q->id
							);
							$edit_url   = admin_url( 'admin.php?page=iaq-question-bank&action=edit&id=' . $q->id );
							?>
							<tr class="iaq-row-item" data-id="<?php echo esc_attr( $q->id ); ?>">
								<td class="iaq-col-cb">
									<input type="checkbox" name="question_ids[]" value="<?php echo esc_attr( $q->id ); ?>" class="iaq-row-checkbox">
								</td>
								<td class="iaq-col-id">
									<span class="iaq-id-pill">#<?php echo esc_html( $q->id ); ?></span>
								</td>
								<td class="iaq-col-question">
									<div class="iaq-question-title">
										<strong><?php echo wp_kses_post( wp_trim_words( $q->question_text, 22, '...' ) ); ?></strong>
									</div>
									<div class="iaq-options-mini-summary">
										<span class="iaq-opt-pill <?php echo ( 'A' === $q->correct_option ) ? 'is-correct' : ''; ?>">A: <?php echo esc_html( wp_trim_words( $q->option_a, 4 ) ); ?></span>
										<span class="iaq-opt-pill <?php echo ( 'B' === $q->correct_option ) ? 'is-correct' : ''; ?>">B: <?php echo esc_html( wp_trim_words( $q->option_b, 4 ) ); ?></span>
										<span class="iaq-opt-pill <?php echo ( 'C' === $q->correct_option ) ? 'is-correct' : ''; ?>">C: <?php echo esc_html( wp_trim_words( $q->option_c, 4 ) ); ?></span>
										<span class="iaq-opt-pill <?php echo ( 'D' === $q->correct_option ) ? 'is-correct' : ''; ?>">D: <?php echo esc_html( wp_trim_words( $q->option_d, 4 ) ); ?></span>
									</div>
								</td>
								<td class="iaq-col-tax">
									<div class="iaq-subject-badge"><?php echo esc_html( $q->subject_name ? $q->subject_name : __( 'Uncategorized', 'indianagri-quiz-engine' ) ); ?></div>
									<?php if ( ! empty( $q->topic_name ) ) : ?>
										<div class="iaq-topic-badge"><?php echo esc_html( $q->topic_name ); ?></div>
									<?php endif; ?>
								</td>
								<td class="iaq-col-exam">
									<span class="iaq-badge iaq-badge-exam"><?php echo esc_html( $q->exam_category ); ?></span>
								</td>
								<td class="iaq-col-diff">
									<span class="iaq-badge iaq-badge-diff" style="background: <?php echo esc_attr( $diff_info['color'] ); ?>15; color: <?php echo esc_attr( $diff_info['color'] ); ?>; border-color: <?php echo esc_attr( $diff_info['color'] ); ?>30;">
										<?php echo esc_html( $diff_info['label'] ); ?>
									</span>
								</td>
								<td class="iaq-col-marks">
									<div class="iaq-marks-indicator">
										<span class="iaq-pos-mark">+<?php echo esc_html( $q->marks ); ?></span>
										<?php if ( floatval( $q->negative_marks ) > 0 ) : ?>
											<span class="iaq-neg-mark">-<?php echo esc_html( $q->negative_marks ); ?></span>
										<?php endif; ?>
									</div>
								</td>
								<td class="iaq-col-status">
									<span class="iaq-badge iaq-status-<?php echo esc_attr( $q->status ); ?>">
										<?php echo esc_html( ucfirst( $q->status ) ); ?>
									</span>
								</td>
								<td class="iaq-col-actions">
									<div class="iaq-row-action-btns">
										<button type="button" class="iaq-action-btn iaq-btn-preview" title="<?php esc_attr_e( 'Preview Question', 'indianagri-quiz-engine' ); ?>" data-question="<?php echo esc_attr( wp_json_encode( $q ) ); ?>">
											<span class="dashicons dashicons-visibility"></span>
										</button>
										<a href="<?php echo esc_url( $edit_url ); ?>" class="iaq-action-btn iaq-btn-edit" title="<?php esc_attr_e( 'Edit Question', 'indianagri-quiz-engine' ); ?>">
											<span class="dashicons dashicons-edit"></span>
										</a>
										<a href="<?php echo esc_url( $delete_url ); ?>" class="iaq-action-btn iaq-btn-delete iaq-confirm-delete" title="<?php esc_attr_e( 'Delete Question', 'indianagri-quiz-engine' ); ?>">
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
						esc_html__( 'Page %1$d of %2$d (%3$s total questions)', 'indianagri-quiz-engine' ),
						esc_html( $current_page ),
						esc_html( $total_pages ),
						esc_html( number_format_i18n( $total_questions ) )
					);
					?>
				</div>
			</div>
		<?php endif; ?>
	</form>
</div>

<!-- Modal: Question Preview -->
<div id="iaq-preview-modal" class="iaq-modal-overlay" style="display:none;">
	<div class="iaq-modal-dialog">
		<div class="iaq-modal-header">
			<div class="iaq-modal-title">
				<span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Question Preview', 'indianagri-quiz-engine' ); ?>
			</div>
			<button type="button" class="iaq-modal-close" id="iaq-close-preview">&times;</button>
		</div>
		<div class="iaq-modal-body" id="iaq-preview-content">
			<!-- Populated dynamically via JS -->
		</div>
		<div class="iaq-modal-footer">
			<button type="button" class="iaq-btn iaq-btn-secondary" id="iaq-close-preview-btn"><?php esc_html_e( 'Close Preview', 'indianagri-quiz-engine' ); ?></button>
		</div>
	</div>
</div>
