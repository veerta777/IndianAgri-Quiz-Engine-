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
	$exam              = IAQ_Examinations::get_examination( $exam_id );
	$subject           = IAQ_Question_Bank::get_subject( $subject_id );
	$assigned_sets     = IAQ_Practice_Sets::get_exam_subject_quizzes( $exam_id, $subject_id, '' );
	$main_titles       = IAQ_Practice_Sets::get_exam_subject_main_titles( $exam_id, $subject_id, '' );
	$grouped_hierarchy = IAQ_Practice_Sets::get_exam_subject_grouped_hierarchy( $exam_id, $subject_id, '' );
	$all_quizzes       = IAQ_Quiz_Builder::get_quizzes( array( 'per_page' => 200, 'subject_id' => $subject_id, 'orderby' => 'title', 'order' => 'ASC' ) );
	$assigned_q_ids    = array_map( function( $q ) { return (int) $q->id; }, $assigned_sets );
	$target_title_id   = isset( $_GET['title_id'] ) ? absint( $_GET['title_id'] ) : 0;
	$edit_title_id     = isset( $_GET['edit_title_id'] ) ? absint( $_GET['edit_title_id'] ) : 0;
	$editing_title     = $edit_title_id ? IAQ_Practice_Sets::get_exam_subject_main_title( $edit_title_id ) : null;
	$next_set_number   = count( $assigned_sets ) + 1;
	$next_title_order  = count( $main_titles ) + 1;
	?>
	<div class="wrap iaq-admin-wrap">
		<div class="iaq-header-banner">
			<div class="iaq-header-title">
				<div class="iaq-header-badge"><?php esc_html_e( 'Practice by Exams Hierarchy', 'indianagri-quiz-engine' ); ?></div>
				<h1><?php printf( esc_html__( '%1$s → %2$s Practice Sets', 'indianagri-quiz-engine' ), esc_html( $exam ? $exam->name : '' ), esc_html( $subject ? $subject->name : '' ) ); ?></h1>
				<p class="iaq-header-desc">
					<?php esc_html_e( 'Manage the complete hierarchy: Examination → Subject → Main Title → Multiple Practice Sets → Questions. Create unlimited Main Titles and unlimited Practice Sets under each title.', 'indianagri-quiz-engine' ); ?>
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

				<!-- Card 0: Create or Edit Main Title -->
				<div class="iaq-card" style="border: 2px solid <?php echo $editing_title ? '#ea580c' : '#0284c7'; ?>;margin-bottom:20px;">
					<div class="iaq-card-header" style="background:<?php echo $editing_title ? '#fff7ed' : '#f0f9ff'; ?>;display:flex;justify-content:space-between;align-items:center;">
						<h2 style="color:<?php echo $editing_title ? '#c2410c' : '#0369a1'; ?>;font-size:15px;margin:0;">
							<span class="dashicons <?php echo $editing_title ? 'dashicons-edit' : 'dashicons-category'; ?>"></span>
							<?php echo $editing_title ? esc_html__( '1. Edit / Rename Main Title', 'indianagri-quiz-engine' ) : esc_html__( '1. Add Main Title', 'indianagri-quiz-engine' ); ?>
						</h2>
						<span class="iaq-pill" style="background:<?php echo $editing_title ? '#ea580c' : '#0284c7'; ?>;color:#fff;font-size:10px;font-weight:700;text-transform:uppercase;">
							<?php echo $editing_title ? esc_html__( 'Editing Title', 'indianagri-quiz-engine' ) : esc_html__( 'Step 1: Title', 'indianagri-quiz-engine' ); ?>
						</span>
					</div>
					<div class="iaq-card-body">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subject_quizzes&exam_id=' . $exam_id . '&subject_id=' . $subject_id ) ); ?>">
							<?php wp_nonce_field( 'iaq_save_main_title_nonce' ); ?>
							<input type="hidden" name="exam_id" value="<?php echo esc_attr( $exam_id ); ?>">
							<input type="hidden" name="subject_id" value="<?php echo esc_attr( $subject_id ); ?>">
							<?php if ( $editing_title ) : ?>
								<input type="hidden" name="id" value="<?php echo esc_attr( $editing_title->id ); ?>">
							<?php endif; ?>

							<div class="iaq-form-group">
								<label for="new-main-title"><?php esc_html_e( 'Main Title Name', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<input type="text" name="title" id="new-main-title" class="iaq-input iaq-full-width" placeholder="<?php esc_attr_e( 'e.g. 1. Principles of Agronomy', 'indianagri-quiz-engine' ); ?>" value="<?php echo esc_attr( $editing_title ? $editing_title->title : '' ); ?>" required>
								<span class="iaq-card-help">
									<?php echo $editing_title ? esc_html__( 'Renaming this title updates its name across the student portal. All assigned practice sets stay under this title automatically.', 'indianagri-quiz-engine' ) : esc_html__( 'Chapter or unit title under which practice sets will be organized.', 'indianagri-quiz-engine' ); ?>
								</span>
							</div>

							<div class="iaq-form-group">
								<label for="main-title-order"><?php esc_html_e( 'Display Position / Order', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" min="1" name="display_order" id="main-title-order" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $editing_title ? $editing_title->display_order : $next_title_order ); ?>">
							</div>

							<div style="margin-top:14px;">
								<button type="submit" name="iaq_save_main_title" class="iaq-btn iaq-full-width" style="background:<?php echo $editing_title ? '#ea580c' : '#0284c7'; ?>;color:#fff;font-weight:700;padding:8px 14px;">
									<span class="dashicons <?php echo $editing_title ? 'dashicons-saved' : 'dashicons-plus-alt'; ?>"></span>
									<?php echo $editing_title ? esc_html__( 'Save & Update Main Title', 'indianagri-quiz-engine' ) : esc_html__( '+ Create Main Title', 'indianagri-quiz-engine' ); ?>
								</button>
								<?php if ( $editing_title ) : ?>
									<div style="margin-top:8px;text-align:center;">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subject_quizzes&exam_id=' . $exam_id . '&subject_id=' . $subject_id ) ); ?>" class="iaq-link" style="font-size:12px;color:#64748b;text-decoration:none;">
											<?php esc_html_e( '← Cancel Editing / Create New Title', 'indianagri-quiz-engine' ); ?>
										</a>
									</div>
								<?php endif; ?>
							</div>
						</form>
					</div>
				</div>

				<!-- Card 1: 1-Click Create & Bulk Import -->
				<div class="iaq-card" style="border: 2px solid #117E3A;">
					<div class="iaq-card-header" style="background:#f0fdf4;display:flex;justify-content:space-between;align-items:center;">
						<h2 style="color:#117E3A;font-size:15px;margin:0;">
							<span class="dashicons dashicons-upload"></span> <?php printf( esc_html__( '2. Create Practice Set & Import', 'indianagri-quiz-engine' ), $next_set_number ); ?>
						</h2>
						<span class="iaq-pill" style="background:#117E3A;color:#fff;font-size:10px;font-weight:700;text-transform:uppercase;">Step 2: Sets</span>
					</div>
					<div class="iaq-card-body">
						<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subject_quizzes&exam_id=' . $exam_id . '&subject_id=' . $subject_id ) ); ?>">
							<?php wp_nonce_field( 'iaq_create_esq_practice_set_nonce' ); ?>
							<input type="hidden" name="exam_id" value="<?php echo esc_attr( $exam_id ); ?>">
							<input type="hidden" name="subject_id" value="<?php echo esc_attr( $subject_id ); ?>">

							<div class="iaq-form-group">
								<label for="new-esq-main-title"><?php esc_html_e( 'Select Main Title (Unit)', 'indianagri-quiz-engine' ); ?></label>
								<select name="main_title_id" id="new-esq-main-title" class="iaq-select iaq-full-width">
									<option value="0"><?php esc_html_e( '— General (No Main Title) —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $main_titles as $mt ) : ?>
										<option value="<?php echo esc_attr( $mt->id ); ?>" <?php selected( $target_title_id, $mt->id ); ?>>
											<?php echo esc_html( $mt->title ); ?> (<?php echo esc_html( $mt->practice_set_count ); ?> Sets)
										</option>
									<?php endforeach; ?>
								</select>
								<span class="iaq-card-help"><?php esc_html_e( 'Assign under an existing Main Title or create one below.', 'indianagri-quiz-engine' ); ?></span>
							</div>

							<div class="iaq-form-group" style="margin-top:-6px;">
								<input type="text" name="new_main_title_inline" placeholder="<?php esc_attr_e( 'Or type new Main Title directly here...', 'indianagri-quiz-engine' ); ?>" class="iaq-input iaq-full-width" style="font-size:12px;">
							</div>

							<div class="iaq-form-group">
								<label for="new-esq-title"><?php esc_html_e( 'Practice Set Title', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<input type="text" name="set_title" id="new-esq-title" class="iaq-input iaq-full-width" value="<?php echo esc_attr( sprintf( __( 'Practice Set %d', 'indianagri-quiz-engine' ), $next_set_number ) ); ?>" required>
							</div>

							<div class="iaq-form-group">
								<label for="new-esq-access"><?php esc_html_e( 'Access Control / Paywall', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<select name="access_type" id="new-esq-access" class="iaq-select iaq-full-width">
									<option value="paid" selected><?php esc_html_e( 'Paid (Subscription Required)', 'indianagri-quiz-engine' ); ?></option>
									<option value="free"><?php esc_html_e( 'Free (Open Demo / Free Sample)', 'indianagri-quiz-engine' ); ?></option>
								</select>
								<span class="iaq-card-help"><?php esc_html_e( 'Paid practice sets require an active student subscription to attempt.', 'indianagri-quiz-engine' ); ?></span>
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
								<label for="attach-main-title"><?php esc_html_e( 'Assign Under Main Title', 'indianagri-quiz-engine' ); ?></label>
								<select name="main_title_id" id="attach-main-title" class="iaq-select iaq-full-width">
									<option value="0"><?php esc_html_e( '— General (No Main Title) —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $main_titles as $mt ) : ?>
										<option value="<?php echo esc_attr( $mt->id ); ?>" <?php selected( $target_title_id, $mt->id ); ?>>
											<?php echo esc_html( $mt->title ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="esq-quiz-id"><?php esc_html_e( 'Select Practice Set', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<select name="quiz_id" id="esq-quiz-id" class="iaq-select iaq-full-width" required>
									<option value=""><?php esc_html_e( '— Select a Practice Set —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $all_quizzes as $q ) : ?>
										<option value="<?php echo esc_attr( $q->id ); ?>">
											#<?php echo esc_html( $q->id ); ?>: <?php echo esc_html( $q->title ); ?> (<?php echo esc_html( $q->total_questions ); ?> Qs)<?php echo in_array( (int) $q->id, $assigned_q_ids, true ) ? ' [' . esc_html__( 'Already Assigned - Reassign', 'indianagri-quiz-engine' ) . ']' : ''; ?>
										</option>
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
						<div style="margin-bottom:8px;">
							<strong><?php esc_html_e( 'Main Titles:', 'indianagri-quiz-engine' ); ?></strong>
							<span class="iaq-count-badge" style="background:#e0f2fe;color:#0369a1;"><?php echo count( $main_titles ); ?></span>
						</div>
						<div>
							<strong><?php esc_html_e( 'Total Practice Sets:', 'indianagri-quiz-engine' ); ?></strong>
							<span class="iaq-count-badge iaq-badge-green"><?php echo count( $assigned_sets ); ?></span>
						</div>
					</div>
				</div>
			</div>

			<div class="iaq-list-column">
				<div class="iaq-card">
					<div class="iaq-card-header" style="display:flex;justify-content:space-between;align-items:center;">
						<h2>
							<span class="dashicons dashicons-networking"></span>
							<?php printf( esc_html__( 'Main Titles & Practice Sets Hierarchy (%1$d Titles, %2$d Sets)', 'indianagri-quiz-engine' ), count( $main_titles ), count( $assigned_sets ) ); ?>
						</h2>
					</div>
					<div class="iaq-card-body" style="padding:16px;">
						<?php if ( empty( $main_titles ) && empty( $assigned_sets ) ) : ?>
							<div style="padding:40px;text-align:center;color:#64748b;">
								<span class="dashicons dashicons-category" style="font-size:48px;width:48px;height:48px;color:#cbd5e1;margin-bottom:12px;"></span>
								<h3 style="margin:0 0 8px 0;color:#334155;"><?php esc_html_e( 'No Main Titles or Practice Sets Yet', 'indianagri-quiz-engine' ); ?></h3>
								<p style="margin:0;font-size:13px;"><?php esc_html_e( 'Use "Step 1: Add Main Title" on the left to create your first unit (e.g. 1. Principles of Agronomy), then attach Practice Sets under it.', 'indianagri-quiz-engine' ); ?></p>
							</div>
						<?php else : ?>
							<?php foreach ( $grouped_hierarchy as $group ) : ?>
								<div class="iaq-main-title-block" style="border:1px solid #e2e8f0;border-radius:8px;margin-bottom:20px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
									<div class="iaq-main-title-header" style="background:#f8fafc;padding:12px 16px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
										<div style="display:flex;align-items:center;gap:10px;">
											<span class="dashicons dashicons-portfolio" style="color:#0284c7;font-size:20px;width:20px;height:20px;"></span>
											<h3 style="margin:0;font-size:15px;color:#0f172a;font-weight:700;">
												<?php echo esc_html( $group['title'] ); ?>
											</h3>
											<span class="iaq-pill" style="background:#e0f2fe;color:#0369a1;font-size:11px;font-weight:700;">
												<?php printf( esc_html__( '%d Practice Sets', 'indianagri-quiz-engine' ), count( $group['practice_sets'] ) ); ?>
											</span>
										</div>
										<div style="display:flex;align-items:center;gap:8px;">
											<?php if ( ! empty( $group['id'] ) ) : ?>
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subject_quizzes&exam_id=' . $exam_id . '&subject_id=' . $subject_id . '&edit_title_id=' . $group['id'] ) ); ?>" class="iaq-table-action" style="color:#0284c7;" title="<?php esc_attr_e( 'Edit / Rename this Main Title', 'indianagri-quiz-engine' ); ?>">
													<span class="dashicons dashicons-edit"></span>
												</a>
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subject_quizzes&exam_id=' . $exam_id . '&subject_id=' . $subject_id . '&title_id=' . $group['id'] ) ); ?>" class="iaq-btn iaq-btn-outline" style="padding:3px 8px;font-size:11px;" title="<?php esc_attr_e( 'Preselect this Main Title to add sets', 'indianagri-quiz-engine' ); ?>">
													<span class="dashicons dashicons-plus-alt" style="font-size:12px;vertical-align:middle;"></span> <?php esc_html_e( '+ Add Set', 'indianagri-quiz-engine' ); ?>
												</a>
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subject_quizzes&exam_id=' . $exam_id . '&subject_id=' . $subject_id . '&iaq_action=delete_main_title&title_id=' . $group['id'] ), 'iaq_delete_main_title_' . $group['id'] ) ); ?>" class="iaq-table-action iaq-action-delete" title="<?php esc_attr_e( 'Delete this Main Title (quizzes remain preserved)', 'indianagri-quiz-engine' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this Main Title? Any practice sets under it will remain in the system.', 'indianagri-quiz-engine' ); ?>');">
													<span class="dashicons dashicons-trash"></span>
												</a>
											<?php endif; ?>
										</div>
									</div>

									<?php if ( empty( $group['practice_sets'] ) ) : ?>
										<div style="padding:20px;text-align:center;color:#64748b;font-size:13px;">
											<?php esc_html_e( 'No practice sets attached to this Main Title yet. Use the form on the left to create Practice Set 1.', 'indianagri-quiz-engine' ); ?>
										</div>
									<?php else : ?>
										<table class="iaq-admin-table" style="width:100%;border-collapse:collapse;">
											<thead>
												<tr style="background:#ffffff;border-bottom:1px solid #e2e8f0;text-align:left;font-size:12px;color:#64748b;">
													<th style="padding:10px 14px;width:75px;"><?php esc_html_e( 'Set #', 'indianagri-quiz-engine' ); ?></th>
													<th style="padding:10px 14px;"><?php esc_html_e( 'Practice Set Title', 'indianagri-quiz-engine' ); ?></th>
													<th style="padding:10px 14px;width:80px;"><?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?></th>
													<th style="padding:10px 14px;width:70px;"><?php esc_html_e( 'Marks', 'indianagri-quiz-engine' ); ?></th>
													<th style="padding:10px 14px;width:80px;"><?php esc_html_e( 'Duration', 'indianagri-quiz-engine' ); ?></th>
													<th style="padding:10px 14px;width:75px;"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
													<th style="padding:10px 14px;text-align:right;width:170px;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ( $group['practice_sets'] as $s_quiz ) : ?>
													<tr style="border-bottom:1px solid #f1f5f9;">
														<td style="padding:10px 14px;font-weight:700;color:#117E3A;">
															<?php printf( esc_html__( 'Set %d', 'indianagri-quiz-engine' ), esc_html( $s_quiz->practice_set_number ) ); ?>
														</td>
														<td style="padding:10px 14px;">
															<strong><?php echo esc_html( $s_quiz->title ); ?></strong>
															<div style="font-size:11px;color:#64748b;margin-top:2px;">
																Quiz ID: #<?php echo esc_html( $s_quiz->id ); ?> &bull;
																<?php echo 'paid' === $s_quiz->access_type ? '<span style="color:#d97706;font-weight:600;">Paid</span>' : '<span style="color:#117E3A;font-weight:600;">Free</span>'; ?>
															</div>
														</td>
														<td style="padding:10px 14px;color:#475569;font-weight:600;"><?php echo esc_html( $s_quiz->total_questions ); ?> Qs</td>
														<td style="padding:10px 14px;color:#475569;"><?php echo esc_html( $s_quiz->total_marks ); ?></td>
														<td style="padding:10px 14px;color:#475569;">
															<?php echo $s_quiz->duration_minutes > 0 ? esc_html( $s_quiz->duration_minutes ) . 'm' : esc_html__( 'Untimed', 'indianagri-quiz-engine' ); ?>
														</td>
														<td style="padding:10px 14px;">
															<span class="iaq-status-badge iaq-status-<?php echo esc_attr( $s_quiz->status ); ?>">
																<?php echo esc_html( ucfirst( $s_quiz->status ) ); ?>
															</span>
														</td>
														<td style="padding:10px 14px;text-align:right;">
															<button type="button" class="iaq-table-action iaq-btn-trigger-move-modal" data-quiz-id="<?php echo esc_attr( $s_quiz->id ); ?>" data-quiz-title="<?php echo esc_attr( $s_quiz->title ); ?>" data-current-title-id="<?php echo esc_attr( $s_quiz->main_title_id ); ?>" data-sort-order="<?php echo esc_attr( $s_quiz->exam_sort_order ); ?>" title="<?php esc_attr_e( 'Change / Move to Another Main Title', 'indianagri-quiz-engine' ); ?>" style="color:#0284c7;background:none;border:none;cursor:pointer;">
																<span class="dashicons dashicons-randomize"></span>
															</button>
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
							<?php endforeach; ?>
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

		<!-- Modal: Move Practice Set to Another Main Title -->
		<div id="iaq-move-set-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:99999;align-items:center;justify-content:center;">
			<div style="background:#fff;border-radius:8px;max-width:500px;width:90%;box-shadow:0 10px 25px rgba(0,0,0,0.2);overflow:hidden;">
				<div style="padding:16px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
					<h3 style="margin:0;font-size:16px;color:#0f172a;display:flex;align-items:center;gap:8px;">
						<span class="dashicons dashicons-randomize" style="color:#0284c7;"></span>
						<span><?php esc_html_e( 'Move Practice Set to Another Main Title', 'indianagri-quiz-engine' ); ?></span>
					</h3>
					<button type="button" onclick="document.getElementById('iaq-move-set-modal').style.display='none';" style="background:none;border:none;cursor:pointer;color:#64748b;font-size:20px;">&times;</button>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations&action=manage_subject_quizzes&exam_id=' . $exam_id . '&subject_id=' . $subject_id ) ); ?>" style="padding:20px;">
					<?php wp_nonce_field( 'iaq_move_practice_set_main_title_nonce' ); ?>
					<input type="hidden" name="exam_id" value="<?php echo esc_attr( $exam_id ); ?>">
					<input type="hidden" name="subject_id" value="<?php echo esc_attr( $subject_id ); ?>">
					<input type="hidden" name="quiz_id" id="iaq-move-quiz-id" value="0">

					<div style="margin-bottom:16px;background:#f0f9ff;padding:12px;border:1px solid #bae6fd;border-radius:6px;">
						<div style="font-size:11px;color:#0369a1;font-weight:700;text-transform:uppercase;"><?php esc_html_e( 'Selected Practice Set', 'indianagri-quiz-engine' ); ?></div>
						<div id="iaq-move-quiz-title" style="font-size:14px;font-weight:700;color:#0f172a;margin-top:4px;"></div>
					</div>

					<div class="iaq-form-group" style="margin-bottom:14px;">
						<label for="iaq-move-target-title" style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;"><?php esc_html_e( 'Select Target Main Title (Unit)', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
						<select name="main_title_id" id="iaq-move-target-title" class="iaq-select iaq-full-width" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;">
							<option value="0"><?php esc_html_e( '— General (No Main Title) —', 'indianagri-quiz-engine' ); ?></option>
							<?php foreach ( $main_titles as $mt ) : ?>
								<option value="<?php echo esc_attr( $mt->id ); ?>">
									<?php echo esc_html( $mt->title ); ?> (<?php echo esc_html( $mt->practice_set_count ); ?> Sets)
								</option>
							<?php endforeach; ?>
						</select>
						<span class="iaq-card-help" style="display:block;font-size:11px;color:#64748b;margin-top:4px;"><?php esc_html_e( 'The practice set and all its questions will be immediately relocated under this Main Title.', 'indianagri-quiz-engine' ); ?></span>
					</div>

					<div class="iaq-form-group" style="margin-bottom:16px;">
						<label for="iaq-move-sort-order" style="display:block;font-weight:600;margin-bottom:6px;font-size:13px;"><?php esc_html_e( 'Display Position / Sort Order', 'indianagri-quiz-engine' ); ?></label>
						<input type="number" min="1" name="sort_order" id="iaq-move-sort-order" class="iaq-input iaq-full-width" style="width:100%;padding:8px 10px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;" value="1">
					</div>

					<div style="display:flex;justify-content:flex-end;gap:10px;">
						<button type="button" class="iaq-btn iaq-btn-outline" onclick="document.getElementById('iaq-move-set-modal').style.display='none';"><?php esc_html_e( 'Cancel', 'indianagri-quiz-engine' ); ?></button>
						<button type="submit" name="iaq_move_practice_set_main_title" class="iaq-btn iaq-btn-primary" style="background:#0284c7;">
							<span class="dashicons dashicons-randomize"></span> <?php esc_html_e( 'Move Practice Set', 'indianagri-quiz-engine' ); ?>
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

			var moveTriggers      = document.querySelectorAll('.iaq-btn-trigger-move-modal');
			var moveModal         = document.getElementById('iaq-move-set-modal');
			var moveQuizIdInput   = document.getElementById('iaq-move-quiz-id');
			var moveQuizTitleEl   = document.getElementById('iaq-move-quiz-title');
			var moveTargetSelect  = document.getElementById('iaq-move-target-title');
			var moveSortOrderInput = document.getElementById('iaq-move-sort-order');

			moveTriggers.forEach(function(btn) {
				btn.addEventListener('click', function() {
					var qid        = this.getAttribute('data-quiz-id');
					var title      = this.getAttribute('data-quiz-title');
					var curTitleId = this.getAttribute('data-current-title-id');
					var sortOrder  = this.getAttribute('data-sort-order');
					if (moveQuizIdInput) moveQuizIdInput.value = qid;
					if (moveQuizTitleEl) moveQuizTitleEl.textContent = title + ' (Quiz #' + qid + ')';
					if (moveTargetSelect && curTitleId) moveTargetSelect.value = curTitleId;
					if (moveSortOrderInput && sortOrder) moveSortOrderInput.value = sortOrder;
					if (moveModal) moveModal.style.display = 'flex';
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
