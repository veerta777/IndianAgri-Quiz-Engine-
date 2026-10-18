<?php
/**
 * Examinations Management admin template.
 * Supports adding, editing, archiving examinations, assigning subjects to exams,
 * and managing practice sets per exam-subject combination without editing code.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$action     = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
$exam_id    = isset( $_GET['exam_id'] ) ? absint( $_GET['exam_id'] ) : ( isset( $_GET['edit_id'] ) ? absint( $_GET['edit_id'] ) : 0 );
$subject_id = isset( $_GET['subject_id'] ) ? absint( $_GET['subject_id'] ) : 0;

// =========================================================================
// SUBVIEW 1: Manage Practice Sets for Examination + Subject
// =========================================================================
if ( 'manage_subject_quizzes' === $action && $exam_id && $subject_id ) :
	$exam            = IAQ_Examinations::get_examination( $exam_id );
	$subject         = IAQ_Question_Bank::get_subject( $subject_id );
	$assigned_sets   = IAQ_Practice_Sets::get_exam_subject_quizzes( $exam_id, $subject_id, '' );
	$all_quizzes     = IAQ_Quiz_Builder::get_quizzes( array( 'per_page' => 200, 'subject_id' => $subject_id, 'orderby' => 'title', 'order' => 'ASC' ) );
	$assigned_q_ids  = array_map( function( $q ) { return (int) $q->id; }, $assigned_sets );
	$next_set_number = count( $assigned_sets ) + 1;
	?>
	<div class="wrap iaq-admin-wrap">
		<div class="iaq-header-banner">
			<div class="iaq-header-title">
				<div class="iaq-header-badge"><?php esc_html_e( 'Practice by Exams', 'indianagri-quiz-engine' ); ?></div>
				<h1><?php printf( esc_html__( '%1$s → %2$s Practice Sets', 'indianagri-quiz-engine' ), esc_html( $exam ? $exam->name : '' ), esc_html( $subject ? $subject->name : '' ) ); ?></h1>
				<p class="iaq-header-desc">
					<?php esc_html_e( 'Manage the practice sets that appear when students choose this examination and subject. Numbering dynamically starts at 1.', 'indianagri-quiz-engine' ); ?>
				</p>
			</div>
			<div class="iaq-header-actions" style="margin-top:12px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subjects&exam_id=' . $exam_id ) ); ?>" class="iaq-btn iaq-btn-outline">
					<span class="dashicons dashicons-arrow-left-alt"></span> <?php printf( esc_html__( 'Back to %s Subjects', 'indianagri-quiz-engine' ), esc_html( $exam ? $exam->code : 'Exam' ) ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-add-quiz&quiz_type=practice&exam_category=' . urlencode( $exam ? $exam->name : '' ) . '&subject_id=' . $subject_id ) ); ?>" class="iaq-btn iaq-btn-primary">
					<span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'Create New Quiz for this Category', 'indianagri-quiz-engine' ); ?>
				</a>
			</div>
		</div>

		<div class="iaq-two-col-layout" style="display:grid;grid-template-columns:380px 1fr;gap:24px;margin-top:20px;">
			<div class="iaq-form-column">
				<!-- Card 1: 1-Click Create & Bulk Import -->
				<div class="iaq-card" style="border: 2px solid #117E3A;">
					<div class="iaq-card-header" style="background:#f0fdf4;display:flex;justify-content:space-between;align-items:center;">
						<h2 style="color:#117E3A;font-size:15px;margin:0;">
							<span class="dashicons dashicons-upload"></span> <?php printf( esc_html__( 'Create Practice Set %d & Import', 'indianagri-quiz-engine' ), $next_set_number ); ?>
						</h2>
						<span class="iaq-pill" style="background:#117E3A;color:#fff;font-size:10px;font-weight:700;text-transform:uppercase;">1-Step Setup</span>
					</div>
					<div class="iaq-card-body">
						<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subject_quizzes&exam_id=' . $exam_id . '&subject_id=' . $subject_id ) ); ?>">
							<?php wp_nonce_field( 'iaq_create_esq_practice_set_nonce' ); ?>
							<input type="hidden" name="exam_id" value="<?php echo esc_attr( $exam_id ); ?>">
							<input type="hidden" name="subject_id" value="<?php echo esc_attr( $subject_id ); ?>">

							<div class="iaq-form-group">
								<label for="new-esq-title"><?php esc_html_e( 'Practice Set Title', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<input type="text" name="set_title" id="new-esq-title" class="iaq-input iaq-full-width" value="<?php echo esc_attr( sprintf( __( 'Practice Set %d', 'indianagri-quiz-engine' ), $next_set_number ) ); ?>" required>
							</div>

							<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
								<div class="iaq-form-group">
									<label for="new-esq-order"><?php esc_html_e( 'Display Position', 'indianagri-quiz-engine' ); ?></label>
									<input type="number" min="1" name="sort_order" id="new-esq-order" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $next_set_number ); ?>">
									<span class="iaq-card-help"><?php esc_html_e( 'Set # in sequence', 'indianagri-quiz-engine' ); ?></span>
								</div>
								<div class="iaq-form-group">
									<label for="new-esq-duration"><?php esc_html_e( 'Duration (Mins)', 'indianagri-quiz-engine' ); ?></label>
									<input type="number" min="0" name="duration_minutes" id="new-esq-duration" class="iaq-input iaq-full-width" value="20">
									<span class="iaq-card-help"><?php esc_html_e( '0 for untimed', 'indianagri-quiz-engine' ); ?></span>
								</div>
							</div>

							<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
								<div class="iaq-form-group">
									<label for="new-esq-marks"><?php esc_html_e( 'Marks (+)', 'indianagri-quiz-engine' ); ?></label>
									<input type="number" step="0.01" min="0.01" name="correct_marks" id="new-esq-marks" class="iaq-input iaq-full-width" value="1.00">
								</div>
								<div class="iaq-form-group">
									<label for="new-esq-neg"><?php esc_html_e( 'Negative (-)', 'indianagri-quiz-engine' ); ?></label>
									<input type="number" step="0.01" min="0.00" name="negative_marks" id="new-esq-neg" class="iaq-input iaq-full-width" value="0.25">
								</div>
							</div>

							<div class="iaq-form-group" style="background:#f8fafc;padding:12px;border:1px dashed #cbd5e1;border-radius:6px;margin-top:8px;">
								<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
									<label for="new-esq-csv" style="font-weight:700;color:#0f172a;margin:0;">
										<span class="dashicons dashicons-media-spreadsheet" style="color:#117E3A;vertical-align:middle;"></span>
										<?php esc_html_e( 'Bulk Import Questions (JSON / CSV)', 'indianagri-quiz-engine' ); ?>
									</label>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-import-export' ) ); ?>" class="iaq-link" style="font-size:11px;color:#117E3A;font-weight:600;text-decoration:none;" title="<?php esc_attr_e( 'Download JSON or CSV templates with Hindi & English agriculture questions', 'indianagri-quiz-engine' ); ?>">
										<span class="dashicons dashicons-download" style="font-size:13px;vertical-align:middle;"></span> Templates
									</a>
								</div>
								<input type="file" name="csv_file" id="new-esq-csv" accept=".json,.csv" class="iaq-full-width" style="font-size:12px;">
								<span class="iaq-card-help" style="display:block;margin-top:4px;">
									<?php printf( esc_html__( 'Upload JSON or CSV (up to 200 MCQs at a time) to immediately complete Practice Set %d with questions attached.', 'indianagri-quiz-engine' ), $next_set_number ); ?>
								</span>
							</div>

							<div style="margin-top:16px;">
								<button type="submit" name="iaq_create_exam_subject_practice_set" class="iaq-btn iaq-btn-primary iaq-full-width" style="padding:10px 16px;font-size:14px;font-weight:700;">
									<span class="dashicons dashicons-plus-alt"></span> <?php printf( esc_html__( 'Create Practice Set %d & Import', 'indianagri-quiz-engine' ), $next_set_number ); ?>
								</button>
							</div>
						</form>
					</div>
				</div>

				<!-- Card 2: Attach Existing Practice Set -->
				<div class="iaq-card" style="margin-top:20px;">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-paperclip"></span> <?php esc_html_e( 'Or Attach Existing Quiz', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subject_quizzes&exam_id=' . $exam_id . '&subject_id=' . $subject_id ) ); ?>">
							<?php wp_nonce_field( 'iaq_assign_esq_nonce' ); ?>
							<input type="hidden" name="exam_id" value="<?php echo esc_attr( $exam_id ); ?>">
							<input type="hidden" name="subject_id" value="<?php echo esc_attr( $subject_id ); ?>">

							<div class="iaq-form-group">
								<label for="esq-quiz-id"><?php esc_html_e( 'Select Practice Set', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<select name="quiz_id" id="esq-quiz-id" class="iaq-select iaq-full-width" required>
									<option value=""><?php esc_html_e( '— Select a Practice Set —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $all_quizzes as $q ) : ?>
										<?php if ( ! in_array( (int) $q->id, $assigned_q_ids, true ) ) : ?>
											<option value="<?php echo esc_attr( $q->id ); ?>">
												#<?php echo esc_html( $q->id ); ?>: <?php echo esc_html( $q->title ); ?> (<?php echo esc_html( $q->total_questions ); ?> Qs)
											</option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
								<span class="iaq-card-help"><?php esc_html_e( 'Assigns the existing quiz to this examination-subject filter without duplicating questions.', 'indianagri-quiz-engine' ); ?></span>
							</div>

							<div class="iaq-form-group">
								<label for="esq-sort-order"><?php esc_html_e( 'Display Position / Sort Order', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" min="1" name="sort_order" id="esq-sort-order" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $next_set_number ); ?>">
								<span class="iaq-card-help"><?php esc_html_e( 'Determines the sequence (Practice Set 1, Set 2, Set 3...).', 'indianagri-quiz-engine' ); ?></span>
							</div>

							<div style="margin-top:16px;">
								<button type="submit" name="iaq_assign_exam_subject_quiz" class="iaq-btn iaq-btn-outline iaq-full-width">
									<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Attach Existing Quiz', 'indianagri-quiz-engine' ); ?>
								</button>
							</div>
						</form>
					</div>
				</div>

				<!-- Exam & Subject Context Summary -->
				<div class="iaq-card" style="margin-top:20px;">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Category Details', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body" style="font-size:13px;line-height:1.6;color:#475569;">
						<div style="margin-bottom:8px;">
							<strong><?php esc_html_e( 'Examination:', 'indianagri-quiz-engine' ); ?></strong>
							<?php echo esc_html( $exam ? $exam->name . ' (' . $exam->code . ')' : '—' ); ?>
						</div>
						<div style="margin-bottom:8px;">
							<strong><?php esc_html_e( 'Subject:', 'indianagri-quiz-engine' ); ?></strong>
							<?php echo esc_html( $subject ? $subject->name : '—' ); ?>
						</div>
						<div>
							<strong><?php esc_html_e( 'Practice Sets:', 'indianagri-quiz-engine' ); ?></strong>
							<span class="iaq-count-badge iaq-badge-green"><?php echo count( $assigned_sets ); ?></span>
						</div>
					</div>
				</div>
			</div>

			<div class="iaq-list-column">
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2>
							<span class="dashicons dashicons-list-view"></span>
							<?php printf( esc_html__( 'Assigned Practice Sets (%d)', 'indianagri-quiz-engine' ), count( $assigned_sets ) ); ?>
						</h2>
					</div>
					<div class="iaq-card-body" style="padding:0;">
						<?php if ( empty( $assigned_sets ) ) : ?>
							<div style="padding:40px;text-align:center;color:#64748b;">
								<span class="dashicons dashicons-welcome-learn-more" style="font-size:48px;width:48px;height:48px;color:#cbd5e1;margin-bottom:12px;"></span>
								<h3 style="margin:0 0 8px 0;color:#334155;"><?php esc_html_e( 'No Practice Sets Attached', 'indianagri-quiz-engine' ); ?></h3>
								<p style="margin:0;font-size:13px;"><?php esc_html_e( 'Use the form on the left to create and bulk import your first practice set in 1 step.', 'indianagri-quiz-engine' ); ?></p>
							</div>
						<?php else : ?>
							<table class="iaq-admin-table" style="width:100%;border-collapse:collapse;">
								<thead>
									<tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;text-align:left;">
										<th style="padding:12px 16px;width:70px;"><?php esc_html_e( 'Display #', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;"><?php esc_html_e( 'Practice Set Title', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;width:90px;"><?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;width:80px;"><?php esc_html_e( 'Marks', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;width:90px;"><?php esc_html_e( 'Duration', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;width:80px;"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;text-align:right;width:180px;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $assigned_sets as $s_quiz ) : ?>
										<tr style="border-bottom:1px solid #f1f5f9;">
											<td style="padding:12px 16px;font-weight:700;color:#117E3A;">
												<?php printf( esc_html__( 'Set %d', 'indianagri-quiz-engine' ), esc_html( $s_quiz->practice_set_number ) ); ?>
											</td>
											<td style="padding:12px 16px;">
												<strong><?php echo esc_html( $s_quiz->title ); ?></strong>
												<div style="font-size:11px;color:#64748b;margin-top:2px;">
													Quiz ID: #<?php echo esc_html( $s_quiz->id ); ?> &bull;
													<?php echo 'paid' === $s_quiz->access_type ? '<span style="color:#d97706;">Paid</span>' : '<span style="color:#117E3A;">Free</span>'; ?>
												</div>
											</td>
											<td style="padding:12px 16px;color:#475569;font-weight:600;"><?php echo esc_html( $s_quiz->total_questions ); ?> Qs</td>
											<td style="padding:12px 16px;color:#475569;"><?php echo esc_html( $s_quiz->total_marks ); ?></td>
											<td style="padding:12px 16px;color:#475569;">
												<?php echo $s_quiz->duration_minutes > 0 ? esc_html( $s_quiz->duration_minutes ) . 'm' : esc_html__( 'Untimed', 'indianagri-quiz-engine' ); ?>
											</td>
											<td style="padding:12px 16px;">
												<span class="iaq-status-badge iaq-status-<?php echo esc_attr( $s_quiz->status ); ?>">
													<?php echo esc_html( ucfirst( $s_quiz->status ) ); ?>
												</span>
											</td>
											<td style="padding:12px 16px;text-align:right;">
												<button type="button" class="iaq-table-action iaq-btn-trigger-modal" data-quiz-id="<?php echo esc_attr( $s_quiz->id ); ?>" data-quiz-title="<?php echo esc_attr( $s_quiz->title ); ?>" title="<?php esc_attr_e( 'Bulk Import Questions into this Set', 'indianagri-quiz-engine' ); ?>" style="color:#117E3A;background:none;border:none;cursor:pointer;">
													<span class="dashicons dashicons-upload"></span>
												</button>
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes&action=manage_questions&id=' . $s_quiz->id ) ); ?>" class="iaq-table-action" title="<?php esc_attr_e( 'Manage & View Questions', 'indianagri-quiz-engine' ); ?>">
													<span class="dashicons dashicons-list-view"></span>
												</a>
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes&action=edit&id=' . $s_quiz->id ) ); ?>" class="iaq-table-action" title="<?php esc_attr_e( 'Edit Quiz Settings', 'indianagri-quiz-engine' ); ?>">
													<span class="dashicons dashicons-edit"></span>
												</a>
												<?php if ( ! empty( $s_quiz->exam_quiz_rel_id ) ) : ?>
													<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subject_quizzes&exam_id=' . $exam_id . '&subject_id=' . $subject_id . '&iaq_action=remove_exam_subject_quiz&quiz_id=' . $s_quiz->id ), 'iaq_remove_esq_' . $exam_id . '_' . $subject_id . '_' . $s_quiz->id ) ); ?>" class="iaq-table-action iaq-action-delete" title="<?php esc_attr_e( 'Detach from this combination', 'indianagri-quiz-engine' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Detach this practice set from this examination subject? The quiz itself will not be deleted.', 'indianagri-quiz-engine' ); ?>');">
														<span class="dashicons dashicons-no-alt"></span>
													</a>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Quick Bulk Import Modal for Existing Practice Set -->
		<div id="iaq-quiz-import-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:99999;align-items:center;justify-content:center;">
			<div style="background:#fff;border-radius:8px;max-width:500px;width:90%;box-shadow:0 10px 25px rgba(0,0,0,0.2);overflow:hidden;">
				<div style="padding:16px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
					<h3 style="margin:0;font-size:16px;color:#0f172a;display:flex;align-items:center;gap:8px;">
						<span class="dashicons dashicons-upload" style="color:#117E3A;"></span>
						<span id="iaq-modal-title"><?php esc_html_e( 'Bulk Import Questions', 'indianagri-quiz-engine' ); ?></span>
					</h3>
					<button type="button" onclick="document.getElementById('iaq-quiz-import-modal').style.display='none';" style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748b;">&times;</button>
				</div>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subject_quizzes&exam_id=' . $exam_id . '&subject_id=' . $subject_id ) ); ?>" style="padding:20px;">
					<?php wp_nonce_field( 'iaq_bulk_import_to_quiz_nonce' ); ?>
					<input type="hidden" name="exam_id" value="<?php echo esc_attr( $exam_id ); ?>">
					<input type="hidden" name="subject_id" value="<?php echo esc_attr( $subject_id ); ?>">
					<input type="hidden" name="quiz_id" id="iaq-modal-quiz-id" value="0">

					<p style="margin:0 0 16px 0;font-size:13px;color:#475569;">
						<?php esc_html_e( 'Select a JSON or CSV file containing questions (up to 200 MCQs at a time). All valid questions will be imported into the Question Bank and automatically assigned to this practice set.', 'indianagri-quiz-engine' ); ?>
					</p>

					<div class="iaq-form-group">
						<label for="iaq-modal-csv" style="font-weight:600;display:block;margin-bottom:6px;"><?php esc_html_e( 'Select Questions File (JSON or CSV)', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
						<input type="file" name="csv_file" id="iaq-modal-csv" accept=".json,.csv" class="iaq-full-width" required>
					</div>

					<div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
						<button type="button" class="iaq-btn iaq-btn-outline" onclick="document.getElementById('iaq-quiz-import-modal').style.display='none';"><?php esc_html_e( 'Cancel', 'indianagri-quiz-engine' ); ?></button>
						<button type="submit" name="iaq_bulk_import_to_quiz" class="iaq-btn iaq-btn-primary">
							<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload & Assign to Set', 'indianagri-quiz-engine' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var triggers = document.querySelectorAll('.iaq-btn-trigger-modal');
			var modal    = document.getElementById('iaq-quiz-import-modal');
			var modalTitle = document.getElementById('iaq-modal-title');
			var modalQuizId = document.getElementById('iaq-modal-quiz-id');

			triggers.forEach(function(btn) {
				btn.addEventListener('click', function() {
					var qid   = this.getAttribute('data-quiz-id');
					var title = this.getAttribute('data-quiz-title');
					modalQuizId.value = qid;
					modalTitle.textContent = 'Import Questions to: ' + title;
					modal.style.display = 'flex';
				});
			});
		});
		</script>
	</div>
	<?php
	return;
endif;

// =========================================================================
// SUBVIEW 2: Manage Assigned Subjects for Examination
// =========================================================================
if ( 'manage_subjects' === $action && $exam_id ) :
	$exam              = IAQ_Examinations::get_examination( $exam_id );
	$assigned_subjects = IAQ_Practice_Sets::get_exam_subjects( $exam_id );
	$all_subjects      = IAQ_Question_Bank::get_subjects();
	$assigned_sub_ids  = array_map( function( $s ) { return (int) $s->id; }, $assigned_subjects );
	?>
	<div class="wrap iaq-admin-wrap">
		<div class="iaq-header-banner">
			<div class="iaq-header-title">
				<div class="iaq-header-badge"><?php esc_html_e( 'Practice by Exams', 'indianagri-quiz-engine' ); ?></div>
				<h1><?php printf( esc_html__( 'Assigned Subjects: %s (%s)', 'indianagri-quiz-engine' ), esc_html( $exam ? $exam->name : '' ), esc_html( $exam ? $exam->code : '' ) ); ?></h1>
				<p class="iaq-header-desc">
					<?php esc_html_e( 'Configure which agricultural subjects belong to this competitive examination. Students selecting this exam will only see these configured subjects.', 'indianagri-quiz-engine' ); ?>
				</p>
			</div>
			<div class="iaq-header-actions" style="margin-top:12px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations' ) ); ?>" class="iaq-btn iaq-btn-outline">
					<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to All Examinations', 'indianagri-quiz-engine' ); ?>
				</a>
			</div>
		</div>

		<div class="iaq-two-col-layout" style="display:grid;grid-template-columns:360px 1fr;gap:24px;margin-top:20px;">
			<div class="iaq-form-column">
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'Assign Subject to Exam', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subjects&exam_id=' . $exam_id ) ); ?>">
							<?php wp_nonce_field( 'iaq_assign_exam_subject_nonce' ); ?>
							<input type="hidden" name="exam_id" value="<?php echo esc_attr( $exam_id ); ?>">

							<div class="iaq-form-group">
								<label for="exam-assign-subject"><?php esc_html_e( 'Select Subject', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<select name="subject_id" id="exam-assign-subject" class="iaq-select iaq-full-width" required>
									<option value=""><?php esc_html_e( '— Select a Subject —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $all_subjects as $sub ) : ?>
										<?php if ( ! in_array( (int) $sub->id, $assigned_sub_ids, true ) ) : ?>
											<option value="<?php echo esc_attr( $sub->id ); ?>">
												<?php echo esc_html( $sub->name ); ?>
											</option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="exam-assign-order"><?php esc_html_e( 'Display Order', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" min="1" name="display_order" id="exam-assign-order" class="iaq-input iaq-full-width" value="<?php echo esc_attr( count( $assigned_subjects) + 1 ); ?>">
								<span class="iaq-card-help"><?php esc_html_e( 'Controls the order in which subjects appear under this examination.', 'indianagri-quiz-engine' ); ?></span>
							</div>

							<div style="margin-top:16px;">
								<button type="submit" name="iaq_assign_exam_subject" class="iaq-btn iaq-btn-primary iaq-full-width">
									<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Assign Subject', 'indianagri-quiz-engine' ); ?>
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>

			<div class="iaq-list-column">
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2>
							<span class="dashicons dashicons-category"></span>
							<?php printf( esc_html__( 'Assigned Subjects (%d)', 'indianagri-quiz-engine' ), count( $assigned_subjects ) ); ?>
						</h2>
					</div>
					<div class="iaq-card-body" style="padding:0;">
						<?php if ( empty( $assigned_subjects ) ) : ?>
							<div style="padding:40px;text-align:center;color:#64748b;">
								<span class="dashicons dashicons-category" style="font-size:48px;width:48px;height:48px;color:#cbd5e1;margin-bottom:12px;"></span>
								<h3 style="margin:0 0 8px 0;color:#334155;"><?php esc_html_e( 'No Subjects Assigned Yet', 'indianagri-quiz-engine' ); ?></h3>
								<p style="margin:0;font-size:13px;"><?php esc_html_e( 'Use the form on the left to assign subjects to this examination.', 'indianagri-quiz-engine' ); ?></p>
							</div>
						<?php else : ?>
							<table class="iaq-admin-table" style="width:100%;border-collapse:collapse;">
								<thead>
									<tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;text-align:left;">
										<th style="padding:12px 16px;width:60px;"><?php esc_html_e( 'Order', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;"><?php esc_html_e( 'Subject Name', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;width:160px;"><?php esc_html_e( 'Practice Sets Available', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;text-align:right;width:200px;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $assigned_subjects as $asub ) : ?>
										<tr style="border-bottom:1px solid #f1f5f9;">
											<td style="padding:12px 16px;font-weight:700;color:#64748b;">
												<?php echo esc_html( isset( $asub->exam_subject_order ) ? $asub->exam_subject_order : '—' ); ?>
											</td>
											<td style="padding:12px 16px;">
												<strong style="font-size:14px;color:#0f172a;"><?php echo esc_html( $asub->name ); ?></strong>
												<?php if ( ! empty( $asub->description ) ) : ?>
													<div style="font-size:12px;color:#64748b;margin-top:2px;">
														<?php echo esc_html( wp_trim_words( $asub->description, 12 ) ); ?>
													</div>
												<?php endif; ?>
											</td>
											<td style="padding:12px 16px;">
												<span class="iaq-pill" style="background:#f0fdf4;color:#117E3A;font-weight:700;">
													<?php printf( esc_html__( '%d Sets', 'indianagri-quiz-engine' ), isset( $asub->practice_sets_count ) ? (int) $asub->practice_sets_count : 0 ); ?>
												</span>
											</td>
											<td style="padding:12px 16px;text-align:right;">
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subject_quizzes&exam_id=' . $exam_id . '&subject_id=' . $asub->id ) ); ?>" class="iaq-btn iaq-btn-outline" style="padding:4px 8px;font-size:12px;margin-right:4px;">
													<?php esc_html_e( 'Manage Sets', 'indianagri-quiz-engine' ); ?>
												</a>
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subjects&exam_id=' . $exam_id . '&iaq_action=remove_exam_subject&subject_id=' . $asub->id ), 'iaq_remove_exam_sub_' . $exam_id . '_' . $asub->id ) ); ?>" class="iaq-table-action iaq-action-delete" title="<?php esc_attr_e( 'Remove Subject from Exam', 'indianagri-quiz-engine' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Remove this subject from this examination? The subject itself and its questions will remain in the question bank.', 'indianagri-quiz-engine' ); ?>');">
													<span class="dashicons dashicons-no-alt"></span>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	return;
endif;

// =========================================================================
// DEFAULT VIEW: Examinations List & Add/Edit Form
// =========================================================================
$edit_exam = $exam_id ? IAQ_Examinations::get_examination( $exam_id ) : null;
$exams     = IAQ_Examinations::get_examinations();
?>

<div class="wrap iaq-admin-wrap">
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php esc_html_e( 'Examinations & Categories Management', 'indianagri-quiz-engine' ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Manage agricultural competitive examinations, eligibility streams, display order, and categorization without editing PHP code.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
	</div>

	<div class="iaq-two-col-layout" style="display:grid;grid-template-columns:350px 1fr;gap:24px;margin-top:20px;">
		<!-- Add / Edit Form Column -->
		<div class="iaq-form-column">
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2>
						<span class="dashicons dashicons-<?php echo $edit_exam ? 'edit' : 'plus-alt'; ?>"></span>
						<?php echo $edit_exam ? esc_html__( 'Edit Examination', 'indianagri-quiz-engine' ) : esc_html__( 'Add New Examination', 'indianagri-quiz-engine' ); ?>
					</h2>
				</div>
				<div class="iaq-card-body">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations' ) ); ?>">
						<?php wp_nonce_field( 'iaq_save_examination_nonce' ); ?>
						<?php if ( $edit_exam ) : ?>
							<input type="hidden" name="id" value="<?php echo esc_attr( $edit_exam->id ); ?>">
						<?php endif; ?>

						<div class="iaq-form-group">
							<label for="exam-name"><?php esc_html_e( 'Examination Name', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<input type="text" name="name" id="exam-name" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_exam ? $edit_exam->name : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., ICAR JRF/SRF, UPCATET PG', 'indianagri-quiz-engine' ); ?>" required>
						</div>

						<div class="iaq-form-group">
							<label for="exam-code"><?php esc_html_e( 'Examination Code / Acronym', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<input type="text" name="code" id="exam-code" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_exam ? $edit_exam->code : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., ICAR-JRF, UPCATET-PG', 'indianagri-quiz-engine' ); ?>" required>
							<span class="iaq-card-help"><?php esc_html_e( 'Unique identifier used for filters and subscription linking.', 'indianagri-quiz-engine' ); ?></span>
						</div>

						<div class="iaq-form-group">
							<label for="exam-slug"><?php esc_html_e( 'URL Slug', 'indianagri-quiz-engine' ); ?></label>
							<input type="text" name="slug" id="exam-slug" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_exam ? $edit_exam->slug : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., icar-jrf-srf', 'indianagri-quiz-engine' ); ?>">
						</div>

						<div class="iaq-form-group">
							<label for="exam-category"><?php esc_html_e( 'Broad Group / Category', 'indianagri-quiz-engine' ); ?></label>
							<input type="text" name="category" id="exam-category" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_exam ? $edit_exam->category : 'Agricultural Entrance' ); ?>" placeholder="<?php esc_attr_e( 'e.g., PG Entrance, NET / ARS, Job Recruitment', 'indianagri-quiz-engine' ); ?>">
						</div>

						<div class="iaq-form-group">
							<label for="exam-order"><?php esc_html_e( 'Display Order', 'indianagri-quiz-engine' ); ?></label>
							<input type="number" name="display_order" id="exam-order" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_exam ? $edit_exam->display_order : 0 ); ?>" min="0">
						</div>

						<div class="iaq-form-group">
							<label for="exam-status"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></label>
							<select name="status" id="exam-status" class="iaq-select iaq-full-width">
								<option value="published" <?php selected( $edit_exam ? $edit_exam->status : 'published', 'published' ); ?>><?php esc_html_e( 'Published (Live)', 'indianagri-quiz-engine' ); ?></option>
								<option value="draft" <?php selected( $edit_exam ? $edit_exam->status : '', 'draft' ); ?>><?php esc_html_e( 'Draft (Hidden)', 'indianagri-quiz-engine' ); ?></option>
								<option value="archived" <?php selected( $edit_exam ? $edit_exam->status : '', 'archived' ); ?>><?php esc_html_e( 'Archived', 'indianagri-quiz-engine' ); ?></option>
							</select>
						</div>

						<div class="iaq-form-group">
							<label for="exam-desc"><?php esc_html_e( 'Description & Instructions', 'indianagri-quiz-engine' ); ?></label>
							<textarea name="description" id="exam-desc" rows="3" class="iaq-textarea iaq-full-width" placeholder="<?php esc_attr_e( 'Eligibility guidelines, exam syllabus overview...', 'indianagri-quiz-engine' ); ?>"><?php echo esc_textarea( $edit_exam ? $edit_exam->description : '' ); ?></textarea>
						</div>

						<div style="margin-top:20px;display:flex;gap:10px;">
							<button type="submit" name="iaq_save_examination" class="iaq-btn iaq-btn-primary" style="flex:1;">
								<span class="dashicons dashicons-saved"></span> <?php echo $edit_exam ? esc_html__( 'Update Examination', 'indianagri-quiz-engine' ) : esc_html__( 'Save Examination', 'indianagri-quiz-engine' ); ?>
							</button>
							<?php if ( $edit_exam ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations' ) ); ?>" class="iaq-btn iaq-btn-outline">
									<?php esc_html_e( 'Cancel', 'indianagri-quiz-engine' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Examination Table Column -->
		<div class="iaq-table-column">
			<div class="iaq-card">
				<div class="iaq-card-header" style="display:flex;justify-content:space-between;align-items:center;">
					<h2><span class="dashicons dashicons-welcome-learn-more"></span> <?php esc_html_e( 'Configured Examinations', 'indianagri-quiz-engine' ); ?></h2>
					<span class="iaq-card-help"><?php printf( esc_html__( '%d Examinations active', 'indianagri-quiz-engine' ), count( $exams ) ); ?></span>
				</div>
				<div class="iaq-card-body" style="padding:0;">
					<table class="iaq-table">
						<thead>
							<tr>
								<th style="width:50px;"><?php esc_html_e( 'Order', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Examination Name', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Code', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Group', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
								<th style="text-align:right;width:180px;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $exams ) ) : ?>
								<tr>
									<td colspan="6" class="iaq-no-records" style="text-align:center;padding:30px;">
										<?php esc_html_e( 'No examinations configured. Add your first exam on the left.', 'indianagri-quiz-engine' ); ?>
									</td>
								</tr>
							<?php else : ?>
								<?php foreach ( $exams as $exam ) : ?>
									<?php
									$edit_url = admin_url( 'admin.php?page=iaq-examinations&edit_id=' . $exam->id );
									$duplicate_url = wp_nonce_url(
										admin_url( 'admin.php?page=iaq-examinations&action=duplicate&id=' . $exam->id ),
										'iaq_duplicate_exam_' . $exam->id
									);
									$delete_url = wp_nonce_url(
										admin_url( 'admin.php?page=iaq-examinations&action=delete&id=' . $exam->id ),
										'iaq_delete_exam_' . $exam->id
									);
									$manage_subjects_url = admin_url( 'admin.php?page=iaq-examinations&action=manage_subjects&exam_id=' . $exam->id );
									?>
									<tr>
										<td>
											<span class="iaq-id-pill"><?php echo esc_html( $exam->display_order ); ?></span>
										</td>
										<td>
											<strong><a href="<?php echo esc_url( $manage_subjects_url ); ?>" style="color:#0f172a;text-decoration:none;"><?php echo esc_html( $exam->name ); ?></a></strong>
											<?php if ( ! empty( $exam->description ) ) : ?>
												<div style="font-size:12px;color:#64748b;margin-top:2px;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
													<?php echo esc_html( $exam->description ); ?>
												</div>
											<?php endif; ?>
										</td>
										<td>
											<span class="iaq-badge" style="background:#e0f2fe;color:#0369a1;font-weight:700;"><?php echo esc_html( $exam->code ); ?></span>
										</td>
										<td>
											<span style="font-size:12px;color:#475569;"><?php echo esc_html( ! empty( $exam->category ) ? $exam->category : '—' ); ?></span>
										</td>
										<td>
											<span class="iaq-badge iaq-status-<?php echo esc_attr( $exam->status ); ?>">
												<?php echo esc_html( ucfirst( $exam->status ) ); ?>
											</span>
										</td>
										<td style="text-align:right;">
											<div class="iaq-row-action-btns" style="justify-content:flex-end;">
												<a href="<?php echo esc_url( $manage_subjects_url ); ?>" class="iaq-btn iaq-btn-outline" style="padding:4px 8px;font-size:12px;margin-right:4px;" title="<?php esc_attr_e( 'Manage Subjects & Practice Sets', 'indianagri-quiz-engine' ); ?>">
													<?php esc_html_e( 'Subjects', 'indianagri-quiz-engine' ); ?>
												</a>
												<a href="<?php echo esc_url( $edit_url ); ?>" class="iaq-action-btn iaq-btn-edit" title="<?php esc_attr_e( 'Edit Examination', 'indianagri-quiz-engine' ); ?>">
													<span class="dashicons dashicons-edit"></span>
												</a>
												<a href="<?php echo esc_url( $duplicate_url ); ?>" class="iaq-action-btn" title="<?php esc_attr_e( 'Duplicate Examination', 'indianagri-quiz-engine' ); ?>" style="color:#0284c7;">
													<span class="dashicons dashicons-admin-page"></span>
												</a>
												<a href="<?php echo esc_url( $delete_url ); ?>" class="iaq-action-btn iaq-btn-delete iaq-confirm-delete" title="<?php esc_attr_e( 'Delete Examination', 'indianagri-quiz-engine' ); ?>">
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
