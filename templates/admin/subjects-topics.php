<?php
/**
 * Subjects & Topics taxonomy management template.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$subjects = IAQ_Question_Bank::get_subjects();
$topics   = IAQ_Question_Bank::get_topics();

// Selected Subject for Practice Sets & Bulk Upload
$selected_subject_id = isset( $_GET['subject_id'] ) ? absint( $_GET['subject_id'] ) : 0;
$selected_subject    = $selected_subject_id > 0 ? IAQ_Question_Bank::get_subject( $selected_subject_id ) : null;
$subject_quizzes     = $selected_subject_id > 0 ? IAQ_Practice_Sets::get_subject_quizzes( $selected_subject_id, '' ) : array();
$subject_topics      = $selected_subject_id > 0 ? IAQ_Question_Bank::get_topics( $selected_subject_id ) : array();
$next_set_number     = count( $subject_quizzes ) + 1;

// Check if editing a subject.
$edit_subject = null;
if ( isset( $_GET['edit_subject'] ) ) {
	$edit_subject = IAQ_Question_Bank::get_subject( absint( $_GET['edit_subject'] ) );
}

// Check if editing a topic.
$edit_topic = null;
if ( isset( $_GET['edit_topic'] ) ) {
	$edit_topic = IAQ_Question_Bank::get_topic( absint( $_GET['edit_topic'] ) );
}
?>

<div class="wrap iaq-admin-wrap">
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php esc_html_e( 'Subjects & Topics Taxonomy', 'indianagri-quiz-engine' ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Structure agricultural domains into core subjects, specialized topics, and assign practice sets with 1-click bulk upload.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
		<div class="iaq-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-question-bank' ) ); ?>" class="iaq-btn iaq-btn-outline">
				<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to Question Bank', 'indianagri-quiz-engine' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-import-export' ) ); ?>" class="iaq-btn iaq-btn-secondary">
				<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Import / Export', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>
	</div>

	<!-- =========================================================================
	     SUBJECT PRACTICE SETS & BULK UPLOAD MODULE
	     ========================================================================= -->
	<div class="iaq-card" id="practice-sets" style="margin-bottom: 28px; border: 1px solid #cbd5e1; border-top: 4px solid #117E3A; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border-radius: 8px; overflow: hidden;">
		<div class="iaq-card-header" style="background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
			<div>
				<div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #117E3A; letter-spacing: 0.5px; margin-bottom: 4px;">
					<?php esc_html_e( 'Practice by Subject & MCQ Bulk Import', 'indianagri-quiz-engine' ); ?>
				</div>
				<h2 style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
					<span class="dashicons dashicons-portfolio" style="color: #117E3A; font-size: 24px; width: 24px; height: 24px;"></span>
					<?php
					if ( $selected_subject ) {
						printf( esc_html__( 'Practice Sets for %s', 'indianagri-quiz-engine' ), esc_html( $selected_subject->name ) );
					} else {
						esc_html_e( 'Select Subject to View Practice Sets & Bulk Upload', 'indianagri-quiz-engine' );
					}
					?>
				</h2>
			</div>

			<!-- Dynamic Subject Selector Dropdown -->
			<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
				<label for="iaq-ps-subject-picker" style="font-size: 13px; font-weight: 700; color: #334155;">
					<?php esc_html_e( 'Choose Subject:', 'indianagri-quiz-engine' ); ?>
				</label>
				<select id="iaq-ps-subject-picker" class="iaq-select" style="min-width: 280px; font-size: 14px; padding: 6px 12px; font-weight: 600; border: 2px solid #117E3A; border-radius: 6px;">
					<option value="0"><?php esc_html_e( '— Select a Subject —', 'indianagri-quiz-engine' ); ?></option>
					<?php foreach ( $subjects as $subj ) : ?>
						<option value="<?php echo esc_attr( $subj->id ); ?>" <?php selected( $selected_subject_id, $subj->id ); ?>>
							<?php echo esc_html( $subj->name ); ?> (<?php echo (int) $subj->practice_sets_count; ?> <?php esc_html_e( 'Sets', 'indianagri-quiz-engine' ); ?> &bull; <?php echo (int) $subj->question_count; ?> <?php esc_html_e( 'MCQs', 'indianagri-quiz-engine' ); ?>)
						</option>
					<?php endforeach; ?>
				</select>
				<?php if ( $selected_subject_id > 0 ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subjects-topics' ) ); ?>" class="iaq-btn iaq-btn-outline" style="padding: 6px 12px; font-size: 12px;">
						<span class="dashicons dashicons-no-alt"></span> <?php esc_html_e( 'Clear Selection', 'indianagri-quiz-engine' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="iaq-card-body" style="padding: 24px;">
			<?php if ( ! $selected_subject ) : ?>
				<!-- Unselected State Prompt -->
				<div style="text-align: center; padding: 36px 20px; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px;">
					<span class="dashicons dashicons-category" style="font-size: 44px; width: 44px; height: 44px; color: #94a3b8; margin-bottom: 12px;"></span>
					<h3 style="margin: 0 0 8px 0; color: #1e293b; font-size: 17px; font-weight: 700;">
						<?php esc_html_e( 'Please select a Subject to view its Practice Sets & Bulk Upload', 'indianagri-quiz-engine' ); ?>
					</h3>
					<p style="margin: 0 auto; max-width: 580px; color: #64748b; font-size: 14px; line-height: 1.5;">
						<?php esc_html_e( 'Select any subject from the dropdown above, or click on a subject name in the "All Subjects" list below to view practice set titles, question counts, and immediately bulk upload questions.', 'indianagri-quiz-engine' ); ?>
					</p>
				</div>
			<?php else : ?>
				<!-- Selected Subject View -->
				<div style="display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: start;">
					
					<!-- Left Side: Practice Sets Table -->
					<div>
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
							<h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">
								<span class="dashicons dashicons-list-view" style="color: #117E3A;"></span>
								<?php printf( esc_html__( 'Practice Sets for %s (%d)', 'indianagri-quiz-engine' ), esc_html( $selected_subject->name ), count( $subject_quizzes ) ); ?>
							</h3>
							<span class="iaq-badge" style="background: #f1f5f9; color: #475569; font-size: 12px; font-weight: 600;">
								<?php echo esc_html( $selected_subject->name ); ?>
							</span>
						</div>

						<?php if ( empty( $subject_quizzes ) ) : ?>
							<div style="padding: 24px; text-align: center; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; margin-bottom: 16px;">
								<span class="dashicons dashicons-info" style="font-size: 32px; width: 32px; height: 32px; color: #94a3b8; margin-bottom: 8px;"></span>
								<h4 style="margin: 0 0 6px 0; color: #334155; font-size: 15px; font-weight: 700;">
									<?php printf( esc_html__( 'No practice sets created yet for %s', 'indianagri-quiz-engine' ), esc_html( $selected_subject->name ) ); ?>
								</h4>
								<p style="margin: 0; font-size: 13px; color: #64748b;">
									<?php esc_html_e( 'Use the form on the right to create Practice Set 1 and bulk upload questions in one step.', 'indianagri-quiz-engine' ); ?>
								</p>
							</div>
						<?php else : ?>
							<div class="iaq-table-responsive" style="border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; margin-bottom: 16px;">
								<table class="iaq-table" style="margin: 0;">
									<thead>
										<tr style="background: #f8fafc;">
											<th style="padding: 10px 14px;"><?php esc_html_e( 'Practice Set Name', 'indianagri-quiz-engine' ); ?></th>
											<th style="padding: 10px 14px;"><?php esc_html_e( 'Topic', 'indianagri-quiz-engine' ); ?></th>
											<th style="padding: 10px 14px;"><?php esc_html_e( 'MCQs', 'indianagri-quiz-engine' ); ?></th>
											<th style="padding: 10px 14px;"><?php esc_html_e( 'Timing & Marks', 'indianagri-quiz-engine' ); ?></th>
											<th style="padding: 10px 14px;"><?php esc_html_e( 'Access', 'indianagri-quiz-engine' ); ?></th>
											<th style="padding: 10px 14px; text-align: right;"><?php esc_html_e( 'Actions / Bulk Upload', 'indianagri-quiz-engine' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $subject_quizzes as $pq ) : ?>
											<?php
											$pq_edit_url  = admin_url( 'admin.php?page=iaq-edit-quiz&id=' . $pq->id );
											$pq_qs_url    = admin_url( 'admin.php?page=iaq-quiz-questions&quiz_id=' . $pq->id );
											$pq_del_url   = wp_nonce_url(
												admin_url( 'admin.php?page=iaq-subjects-topics&iaq_action=delete_subject_practice_set&quiz_id=' . $pq->id . '&subject_id=' . $selected_subject_id ),
												'iaq_delete_subject_quiz_' . $pq->id
											);
											?>
											<tr>
												<td style="padding: 12px 14px;">
													<strong style="font-size: 14px; color: #0f172a;"><?php echo esc_html( $pq->title ); ?></strong>
													<div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
														ID: #<?php echo (int) $pq->id; ?> &bull; <?php echo esc_html( $pq->slug ); ?>
													</div>
												</td>
												<td style="padding: 12px 14px;">
													<?php if ( ! empty( $pq->topic_name ) ) : ?>
														<span class="iaq-badge" style="background: #f1f5f9; color: #334155; font-size: 12px; font-weight: 600;">
															<?php echo esc_html( $pq->topic_name ); ?>
														</span>
													<?php else : ?>
														<span style="color: #94a3b8; font-size: 12px;"><?php esc_html_e( 'All Topics', 'indianagri-quiz-engine' ); ?></span>
													<?php endif; ?>
												</td>
												<td style="padding: 12px 14px;">
													<span class="iaq-count-badge iaq-badge-green" style="font-size: 12px; font-weight: 700; padding: 3px 8px;">
														<?php echo (int) $pq->total_questions; ?> <?php esc_html_e( 'MCQs', 'indianagri-quiz-engine' ); ?>
													</span>
												</td>
												<td style="padding: 12px 14px; font-size: 12px; color: #475569;">
													<?php echo (int) $pq->duration_minutes > 0 ? sprintf( __( '%d mins', 'indianagri-quiz-engine' ), $pq->duration_minutes ) : __( 'Untimed', 'indianagri-quiz-engine' ); ?>
													<div style="color: #16a34a; font-weight: 600; font-size: 11px; margin-top: 2px;">
														+<?php echo number_format( (float) $pq->correct_marks, 2 ); ?> / -<?php echo number_format( (float) $pq->negative_marks, 2 ); ?>
													</div>
												</td>
												<td style="padding: 12px 14px;">
													<?php if ( 'free' === $pq->access_type ) : ?>
														<span class="iaq-pill iaq-pill-free"><?php esc_html_e( 'Free', 'indianagri-quiz-engine' ); ?></span>
													<?php else : ?>
														<span class="iaq-pill iaq-pill-paid"><?php esc_html_e( 'Paid', 'indianagri-quiz-engine' ); ?></span>
													<?php endif; ?>
												</td>
												<td style="padding: 12px 14px; text-align: right;">
													<div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
														<!-- Bulk Upload Toggle Button -->
														<button type="button" 
														        class="iaq-btn iaq-btn-primary iaq-toggle-bulk-upload-btn" 
														        data-target="iaq-bulk-box-<?php echo (int) $pq->id; ?>" 
														        style="padding: 4px 10px; font-size: 12px; background: #117E3A;" 
														        title="<?php esc_attr_e( 'Bulk upload MCQs into this practice set', 'indianagri-quiz-engine' ); ?>">
															<span class="dashicons dashicons-upload" style="font-size: 14px; vertical-align: middle;"></span> <?php esc_html_e( 'Bulk Upload', 'indianagri-quiz-engine' ); ?>
														</button>

														<a href="<?php echo esc_url( $pq_qs_url ); ?>" class="iaq-action-btn" title="<?php esc_attr_e( 'Manage Questions', 'indianagri-quiz-engine' ); ?>" style="padding: 4px 6px;">
															<span class="dashicons dashicons-list-view"></span>
														</a>
														<a href="<?php echo esc_url( $pq_edit_url ); ?>" class="iaq-action-btn iaq-btn-edit" title="<?php esc_attr_e( 'Edit Settings', 'indianagri-quiz-engine' ); ?>" style="padding: 4px 6px;">
															<span class="dashicons dashicons-edit"></span>
														</a>
														<a href="<?php echo esc_url( $pq_del_url ); ?>" class="iaq-action-btn iaq-btn-delete iaq-confirm-delete" title="<?php esc_attr_e( 'Delete Practice Set', 'indianagri-quiz-engine' ); ?>" style="padding: 4px 6px;">
															<span class="dashicons dashicons-trash"></span>
														</a>
													</div>

													<!-- Inline Quick Bulk Upload Form Container (Hidden by default) -->
													<div id="iaq-bulk-box-<?php echo (int) $pq->id; ?>" class="iaq-inline-bulk-box" style="display: none; margin-top: 10px; padding: 12px; background: #f0fdf4; border: 1px dashed #117E3A; border-radius: 6px; text-align: left;">
														<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subjects-topics&subject_id=' . $selected_subject_id ) ); ?>">
															<?php wp_nonce_field( 'iaq_bulk_import_to_quiz_nonce' ); ?>
															<input type="hidden" name="quiz_id" value="<?php echo (int) $pq->id; ?>">
															<input type="hidden" name="subject_id" value="<?php echo (int) $selected_subject_id; ?>">
															<input type="hidden" name="from_subjects_topics" value="1">

															<div style="font-size: 12px; font-weight: 700; color: #117E3A; margin-bottom: 6px;">
																<span class="dashicons dashicons-media-spreadsheet" style="vertical-align: middle;"></span>
																<?php printf( esc_html__( 'Upload MCQs directly into "%s"', 'indianagri-quiz-engine' ), esc_html( $pq->title ) ); ?>
															</div>
															<div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
																<input type="file" name="csv_file" accept=".json,.csv" required style="font-size: 12px; background: #fff; padding: 4px; border: 1px solid #cbd5e1; border-radius: 4px;">
																<button type="submit" name="iaq_bulk_import_to_quiz" class="iaq-btn iaq-btn-primary" style="padding: 5px 12px; font-size: 12px;">
																	<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Import MCQs', 'indianagri-quiz-engine' ); ?>
																</button>
																<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-import-export' ) ); ?>" style="font-size: 11px; color: #117E3A; text-decoration: underline; margin-left: auto;">
																	<?php esc_html_e( 'Templates', 'indianagri-quiz-engine' ); ?>
																</a>
															</div>
														</form>
													</div>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>
					</div>

					<!-- Right Side: 1-Click Create Practice Set & Bulk Upload Form -->
					<div>
						<div class="iaq-card" style="border: 2px solid #117E3A; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
							<div class="iaq-card-header" style="background: #f0fdf4; border-bottom: 1px solid #dcfce7; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center;">
								<h3 style="margin: 0; font-size: 14px; font-weight: 700; color: #117E3A;">
									<span class="dashicons dashicons-upload"></span>
									<?php printf( esc_html__( 'Create Practice Set %d & Import', 'indianagri-quiz-engine' ), $next_set_number ); ?>
								</h3>
								<span class="iaq-pill" style="background: #117E3A; color: #fff; font-size: 10px; font-weight: 700; text-transform: uppercase;">
									<?php esc_html_e( '1-Step Setup', 'indianagri-quiz-engine' ); ?>
								</span>
							</div>
							<div class="iaq-card-body" style="padding: 16px;">
								<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subjects-topics&subject_id=' . $selected_subject_id ) ); ?>">
									<?php wp_nonce_field( 'iaq_create_subject_practice_set_nonce' ); ?>
									<input type="hidden" name="subject_id" value="<?php echo (int) $selected_subject_id; ?>">

									<div class="iaq-form-group" style="margin-bottom: 12px;">
										<label for="new-subj-set-title" style="font-size: 12px; font-weight: 700; color: #0f172a;">
											<?php esc_html_e( 'Practice Set Title', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span>
										</label>
										<input type="text" name="set_title" id="new-subj-set-title" class="iaq-input iaq-full-width" value="<?php echo esc_attr( sprintf( __( '%s - Practice Set %d', 'indianagri-quiz-engine' ), $selected_subject->name, $next_set_number ) ); ?>" required style="font-size: 13px;">
									</div>

									<div class="iaq-form-group" style="margin-bottom: 12px;">
										<label for="new-subj-set-topic" style="font-size: 12px; font-weight: 700; color: #0f172a;">
											<?php esc_html_e( 'Topic (Optional)', 'indianagri-quiz-engine' ); ?>
										</label>
										<select name="topic_id" id="new-subj-set-topic" class="iaq-select iaq-full-width" style="font-size: 13px;">
											<option value="0"><?php printf( esc_html__( '— All Topics in %s —', 'indianagri-quiz-engine' ), esc_html( $selected_subject->name ) ); ?></option>
											<?php foreach ( $subject_topics as $st ) : ?>
												<option value="<?php echo (int) $st->id; ?>">
													<?php echo esc_html( $st->name ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</div>

									<div class="iaq-form-group" style="margin-bottom: 12px;">
										<label for="new-subj-set-access" style="font-size: 12px; font-weight: 700; color: #0f172a;">
											<?php esc_html_e( 'Access Control / Paywall', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span>
										</label>
										<select name="access_type" id="new-subj-set-access" class="iaq-select iaq-full-width" style="font-size: 13px;">
											<option value="paid" selected><?php esc_html_e( 'Paid (Subscription Required)', 'indianagri-quiz-engine' ); ?></option>
											<option value="free"><?php esc_html_e( 'Free (Open Demo / Free Sample)', 'indianagri-quiz-engine' ); ?></option>
										</select>
									</div>

									<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
										<div class="iaq-form-group" style="margin: 0;">
											<label for="new-subj-set-order" style="font-size: 11px; font-weight: 700;"><?php esc_html_e( 'Position', 'indianagri-quiz-engine' ); ?></label>
											<input type="number" min="1" name="sort_order" id="new-subj-set-order" class="iaq-input iaq-full-width" value="<?php echo (int) $next_set_number; ?>" style="font-size: 13px;">
										</div>
										<div class="iaq-form-group" style="margin: 0;">
											<label for="new-subj-set-duration" style="font-size: 11px; font-weight: 700;"><?php esc_html_e( 'Duration (Mins)', 'indianagri-quiz-engine' ); ?></label>
											<input type="number" min="0" name="duration_minutes" id="new-subj-set-duration" class="iaq-input iaq-full-width" value="20" style="font-size: 13px;">
										</div>
									</div>

									<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
										<div class="iaq-form-group" style="margin: 0;">
											<label for="new-subj-set-marks" style="font-size: 11px; font-weight: 700;"><?php esc_html_e( 'Marks (+)', 'indianagri-quiz-engine' ); ?></label>
											<input type="number" step="0.01" min="0.01" name="correct_marks" id="new-subj-set-marks" class="iaq-input iaq-full-width" value="1.00" style="font-size: 13px;">
										</div>
										<div class="iaq-form-group" style="margin: 0;">
											<label for="new-subj-set-neg" style="font-size: 11px; font-weight: 700;"><?php esc_html_e( 'Negative (-)', 'indianagri-quiz-engine' ); ?></label>
											<input type="number" step="0.01" min="0.00" name="negative_marks" id="new-subj-set-neg" class="iaq-input iaq-full-width" value="0.25" style="font-size: 13px;">
										</div>
									</div>

									<!-- Bulk Import Dropzone / File Picker -->
									<div class="iaq-form-group" style="background: #f8fafc; padding: 12px; border: 1px dashed #cbd5e1; border-radius: 6px; margin-top: 8px; margin-bottom: 14px;">
										<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
											<label for="new-subj-set-csv" style="font-weight: 700; color: #0f172a; margin: 0; font-size: 12px;">
												<span class="dashicons dashicons-media-spreadsheet" style="color: #117E3A; vertical-align: middle;"></span>
												<?php esc_html_e( 'Bulk Import Questions (JSON / CSV)', 'indianagri-quiz-engine' ); ?>
											</label>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-import-export' ) ); ?>" style="font-size: 11px; color: #117E3A; font-weight: 600; text-decoration: none;" title="<?php esc_attr_e( 'Download question templates', 'indianagri-quiz-engine' ); ?>">
												<span class="dashicons dashicons-download" style="font-size: 12px; vertical-align: middle;"></span> Templates
											</a>
										</div>
										<input type="file" name="csv_file" id="new-subj-set-csv" accept=".json,.csv" class="iaq-full-width" style="font-size: 12px;">
										<span class="iaq-card-help" style="display: block; margin-top: 4px; font-size: 11px;">
											<?php printf( esc_html__( 'Upload JSON or CSV to immediately attach questions to Practice Set %d.', 'indianagri-quiz-engine' ), $next_set_number ); ?>
										</span>
									</div>

									<button type="submit" name="iaq_create_subject_practice_set" class="iaq-btn iaq-btn-primary iaq-full-width" style="padding: 10px 16px; font-size: 13px; font-weight: 700; justify-content: center;">
										<span class="dashicons dashicons-upload"></span>
										<?php printf( esc_html__( 'Create Practice Set %d & Import', 'indianagri-quiz-engine' ), $next_set_number ); ?>
									</button>
								</form>
							</div>
						</div>
					</div>

				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="iaq-grid-two-col">
		<!-- LEFT COLUMN: SUBJECTS -->
		<div class="iaq-col-panel">
			<!-- Add/Edit Subject Form -->
			<div class="iaq-card" id="iaq-subject-form-card">
				<div class="iaq-card-header">
					<h2 id="iaq-subject-form-title">
						<span class="dashicons dashicons-category"></span> 
						<span class="iaq-form-title-text"><?php echo $edit_subject ? esc_html__( 'Edit Subject', 'indianagri-quiz-engine' ) : esc_html__( 'Add New Subject', 'indianagri-quiz-engine' ); ?></span>
					</h2>
				</div>
				<div class="iaq-card-body">
					<form method="post" id="iaq-subject-form" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subjects-topics' ) ); ?>">
						<?php wp_nonce_field( 'iaq_save_subject_nonce' ); ?>
						<input type="hidden" name="id" id="subj-id" value="<?php echo esc_attr( $edit_subject ? $edit_subject->id : '' ); ?>">

						<div class="iaq-form-group">
							<label for="subj-name"><?php esc_html_e( 'Subject Name', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<input type="text" name="name" id="subj-name" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_subject ? $edit_subject->name : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., Agronomy, Soil Science', 'indianagri-quiz-engine' ); ?>" required>
						</div>

						<div class="iaq-form-group">
							<label for="subj-slug"><?php esc_html_e( 'Slug (Optional)', 'indianagri-quiz-engine' ); ?></label>
							<input type="text" name="slug" id="subj-slug" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_subject ? $edit_subject->slug : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., agronomy', 'indianagri-quiz-engine' ); ?>">
						</div>

						<div class="iaq-form-group">
							<label for="subj-desc"><?php esc_html_e( 'Description', 'indianagri-quiz-engine' ); ?></label>
							<textarea name="description" id="subj-desc" rows="3" class="iaq-textarea iaq-full-width" placeholder="<?php esc_attr_e( 'Short summary of the agricultural discipline...', 'indianagri-quiz-engine' ); ?>"><?php echo esc_textarea( $edit_subject ? $edit_subject->description : '' ); ?></textarea>
						</div>

						<div class="iaq-form-actions">
							<button type="submit" name="iaq_save_subject" id="iaq-subject-submit-btn" class="iaq-btn iaq-btn-primary">
								<span class="dashicons dashicons-saved"></span> <span class="iaq-btn-text"><?php echo $edit_subject ? esc_html__( 'Update Subject', 'indianagri-quiz-engine' ) : esc_html__( 'Create Subject', 'indianagri-quiz-engine' ); ?></span>
							</button>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subjects-topics' ) ); ?>" id="iaq-subject-cancel-btn" class="iaq-btn iaq-btn-outline" style="<?php echo $edit_subject ? '' : 'display:none;'; ?>">
								<?php esc_html_e( 'Cancel', 'indianagri-quiz-engine' ); ?>
							</a>
						</div>
					</form>
				</div>
			</div>

			<!-- Subjects List -->
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'All Subjects', 'indianagri-quiz-engine' ); ?> (<?php echo count( $subjects ); ?>)</h2>
				</div>
				<div class="iaq-card-body iaq-p0">
					<table class="iaq-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Subject', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Topics', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Practice Sets', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $subjects ) ) : ?>
								<tr><td colspan="5" class="iaq-no-records"><?php esc_html_e( 'No subjects registered yet.', 'indianagri-quiz-engine' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ( $subjects as $s ) : ?>
									<?php
									$del_url = wp_nonce_url(
										admin_url( 'admin.php?page=iaq-subjects-topics&iaq_action=delete_subject&id=' . $s->id ),
										'iaq_delete_subject_' . $s->id
									);
									$edit_url     = admin_url( 'admin.php?page=iaq-subjects-topics&edit_subject=' . $s->id );
									$select_url   = admin_url( 'admin.php?page=iaq-subjects-topics&subject_id=' . $s->id . '#practice-sets' );
									$is_active    = ( (int) $selected_subject_id === (int) $s->id );
									$tr_style     = $is_active ? 'style="background: #f0fdf4; border-left: 3px solid #117E3A;"' : '';
									?>
									<tr <?php echo $tr_style; ?>>
										<td>
											<strong>
												<a href="<?php echo esc_url( $select_url ); ?>" style="text-decoration: none; color: #0f172a; display: inline-flex; align-items: center; gap: 4px;" title="<?php esc_attr_e( 'Click to view Practice Sets & Bulk Upload', 'indianagri-quiz-engine' ); ?>">
													<?php echo esc_html( $s->name ); ?>
													<span class="dashicons dashicons-arrow-right-alt2" style="font-size: 14px; width: 14px; height: 14px; color: #117E3A;"></span>
												</a>
											</strong>
											<div class="iaq-slug-subtext"><?php echo esc_html( $s->slug ); ?></div>
										</td>
										<td>
											<span class="iaq-count-badge"><?php echo esc_html( $s->topic_count ); ?></span>
										</td>
										<td>
											<span class="iaq-count-badge iaq-badge-green"><?php echo esc_html( $s->question_count ); ?></span>
										</td>
										<td>
											<a href="<?php echo esc_url( $select_url ); ?>" class="iaq-count-badge iaq-badge-blue" style="text-decoration: none; font-weight: 700;" title="<?php esc_attr_e( 'View Practice Sets & Bulk Upload', 'indianagri-quiz-engine' ); ?>">
												<span class="dashicons dashicons-portfolio" style="font-size: 13px; width: 13px; height: 13px; vertical-align: middle;"></span>
												<?php echo (int) $s->practice_sets_count; ?> <?php esc_html_e( 'Sets', 'indianagri-quiz-engine' ); ?>
											</a>
										</td>
										<td>
											<div class="iaq-row-action-btns">
												<a href="<?php echo esc_url( $select_url ); ?>" 
												   class="iaq-action-btn" 
												   style="color: #117E3A; background: #e8f5e9;" 
												   title="<?php esc_attr_e( 'View Practice Sets & Bulk Upload', 'indianagri-quiz-engine' ); ?>">
													<span class="dashicons dashicons-upload"></span>
												</a>
												<a href="<?php echo esc_url( $edit_url ); ?>" 
												   class="iaq-action-btn iaq-btn-edit iaq-edit-subject-btn" 
												   data-id="<?php echo esc_attr( $s->id ); ?>" 
												   data-name="<?php echo esc_attr( $s->name ); ?>" 
												   data-slug="<?php echo esc_attr( $s->slug ); ?>" 
												   data-desc="<?php echo esc_attr( $s->description ); ?>" 
												   title="<?php esc_attr_e( 'Edit Subject', 'indianagri-quiz-engine' ); ?>">
													<span class="dashicons dashicons-edit"></span>
												</a>
												<a href="<?php echo esc_url( $del_url ); ?>" class="iaq-action-btn iaq-btn-delete iaq-confirm-delete" title="<?php esc_attr_e( 'Delete Subject', 'indianagri-quiz-engine' ); ?>">
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

		<!-- RIGHT COLUMN: TOPICS -->
		<div class="iaq-col-panel">
			<!-- Add/Edit Topic Form -->
			<div class="iaq-card" id="iaq-topic-form-card">
				<div class="iaq-card-header">
					<h2 id="iaq-topic-form-title">
						<span class="dashicons dashicons-tag"></span> 
						<span class="iaq-form-title-text"><?php echo $edit_topic ? esc_html__( 'Edit Topic', 'indianagri-quiz-engine' ) : esc_html__( 'Add New Topic', 'indianagri-quiz-engine' ); ?></span>
					</h2>
				</div>
				<div class="iaq-card-body">
					<form method="post" id="iaq-topic-form" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subjects-topics' ) ); ?>">
						<?php wp_nonce_field( 'iaq_save_topic_nonce' ); ?>
						<input type="hidden" name="id" id="topic-id" value="<?php echo esc_attr( $edit_topic ? $edit_topic->id : '' ); ?>">

						<div class="iaq-form-group">
							<label for="topic-subject"><?php esc_html_e( 'Parent Subject', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<select name="subject_id" id="topic-subject" class="iaq-select iaq-full-width" required>
								<option value=""><?php esc_html_e( '— Select Parent Subject —', 'indianagri-quiz-engine' ); ?></option>
								<?php
								$selected_parent = $edit_topic ? $edit_topic->subject_id : 0;
								foreach ( $subjects as $subj ) :
									?>
									<option value="<?php echo esc_attr( $subj->id ); ?>" <?php selected( $selected_parent, $subj->id ); ?>>
										<?php echo esc_html( $subj->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="iaq-form-group">
							<label for="topic-name"><?php esc_html_e( 'Topic Name', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<input type="text" name="name" id="topic-name" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_topic ? $edit_topic->name : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., Weed Science & Management', 'indianagri-quiz-engine' ); ?>" required>
						</div>

						<div class="iaq-form-group">
							<label for="topic-slug"><?php esc_html_e( 'Slug (Optional)', 'indianagri-quiz-engine' ); ?></label>
							<input type="text" name="slug" id="topic-slug" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_topic ? $edit_topic->slug : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., weed-science', 'indianagri-quiz-engine' ); ?>">
						</div>

						<div class="iaq-form-actions">
							<button type="submit" name="iaq_save_topic" id="iaq-topic-submit-btn" class="iaq-btn iaq-btn-primary">
								<span class="dashicons dashicons-saved"></span> <span class="iaq-btn-text"><?php echo $edit_topic ? esc_html__( 'Update Topic', 'indianagri-quiz-engine' ) : esc_html__( 'Create Topic', 'indianagri-quiz-engine' ); ?></span>
							</button>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subjects-topics' ) ); ?>" id="iaq-topic-cancel-btn" class="iaq-btn iaq-btn-outline" style="<?php echo $edit_topic ? '' : 'display:none;'; ?>">
								<?php esc_html_e( 'Cancel', 'indianagri-quiz-engine' ); ?>
							</a>
						</div>
					</form>
				</div>
			</div>

			<!-- Topics List -->
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2><span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'All Topics', 'indianagri-quiz-engine' ); ?> (<?php echo count( $topics ); ?>)</h2>
				</div>
				<div class="iaq-card-body iaq-p0">
					<div class="iaq-scrollable-table">
						<table class="iaq-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Topic', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Subject', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'MCQs', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $topics ) ) : ?>
									<tr><td colspan="4" class="iaq-no-records"><?php esc_html_e( 'No topics registered yet.', 'indianagri-quiz-engine' ); ?></td></tr>
								<?php else : ?>
									<?php
									// Map subjects by ID for easy display.
									$subj_name_map = array();
									foreach ( $subjects as $s ) {
										$subj_name_map[ $s->id ] = $s->name;
									}
									foreach ( $topics as $t ) :
										?>
										<?php
										$del_topic_url = wp_nonce_url(
											admin_url( 'admin.php?page=iaq-subjects-topics&iaq_action=delete_topic&id=' . $t->id ),
											'iaq_delete_topic_' . $t->id
										);
										$edit_topic_url = admin_url( 'admin.php?page=iaq-subjects-topics&edit_topic=' . $t->id );
										$parent_name    = isset( $subj_name_map[ $t->subject_id ] ) ? $subj_name_map[ $t->subject_id ] : __( 'Unknown', 'indianagri-quiz-engine' );
										?>
										<tr>
											<td>
												<strong><?php echo esc_html( $t->name ); ?></strong>
											</td>
											<td>
												<span class="iaq-subject-badge"><?php echo esc_html( $parent_name ); ?></span>
											</td>
											<td>
												<span class="iaq-count-badge"><?php echo esc_html( $t->question_count ); ?></span>
											</td>
											<td>
												<div class="iaq-row-action-btns">
													<a href="<?php echo esc_url( $edit_topic_url ); ?>" 
													   class="iaq-action-btn iaq-btn-edit iaq-edit-topic-btn" 
													   data-id="<?php echo esc_attr( $t->id ); ?>" 
													   data-subject-id="<?php echo esc_attr( $t->subject_id ); ?>" 
													   data-name="<?php echo esc_attr( $t->name ); ?>" 
													   data-slug="<?php echo esc_attr( $t->slug ); ?>" 
													   title="<?php esc_attr_e( 'Edit', 'indianagri-quiz-engine' ); ?>">
														<span class="dashicons dashicons-edit"></span>
													</a>
													<a href="<?php echo esc_url( $del_topic_url ); ?>" class="iaq-action-btn iaq-btn-delete iaq-confirm-delete" title="<?php esc_attr_e( 'Delete', 'indianagri-quiz-engine' ); ?>">
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
</div>
