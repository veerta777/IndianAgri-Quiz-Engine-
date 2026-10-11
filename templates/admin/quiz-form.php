<?php
/**
 * Add / Edit Quiz Form template.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_edit = ! empty( $quiz ) && is_object( $quiz );
$title   = $is_edit ? sprintf( __( 'Edit Quiz #%d: %s', 'indianagri-quiz-engine' ), $quiz->id, $quiz->title ) : __( 'Create New Examination / Quiz', 'indianagri-quiz-engine' );

$quiz_types      = IAQ_Quiz_Builder::get_quiz_types();
$review_modes    = IAQ_Quiz_Builder::get_review_modes();
$exam_categories = IAQ_Question_Bank::get_exam_categories();
if ( class_exists( 'IAQ_Examinations' ) ) {
	$dyn_exams = IAQ_Examinations::get_examinations( array( 'status' => 'published' ) );
	if ( ! empty( $dyn_exams ) ) {
		foreach ( $dyn_exams as $dex ) {
			$exam_categories[ $dex->name ] = $dex->name;
		}
	}
}
$subjects        = IAQ_Question_Bank::get_subjects();
$selected_subj   = $is_edit ? $quiz->subject_id : ( isset( $_GET['subject_id'] ) ? absint( $_GET['subject_id'] ) : 0 );
$topics          = $selected_subj ? IAQ_Question_Bank::get_topics( $selected_subj ) : array();

$all_books       = class_exists( 'IAQ_Practice_Sets' ) ? IAQ_Practice_Sets::get_books( array( 'status' => 'published' ) ) : array();
$selected_book   = isset( $_GET['book_id'] ) ? absint( $_GET['book_id'] ) : 0;
if ( $is_edit && ! $selected_book ) {
	global $wpdb;
	$t_bq = IAQ_Database::get_table_name( 'book_quizzes' );
	$selected_book = (int) $wpdb->get_var( $wpdb->prepare( "SELECT book_id FROM {$t_bq} WHERE quiz_id = %d LIMIT 1", $quiz->id ) );
}

$default_marks     = 1.00;
$default_neg_marks = 0.25;
$default_duration  = 60;
$settings          = get_option( 'iaq_settings', array() );
if ( ! empty( $settings['default_marks'] ) ) $default_marks = $settings['default_marks'];
if ( ! empty( $settings['default_negative_marks'] ) ) $default_neg_marks = $settings['default_negative_marks'];
if ( ! empty( $settings['default_duration'] ) ) $default_duration = $settings['default_duration'];
?>

<div class="wrap iaq-admin-wrap">
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php echo esc_html( $title ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Configure examination parameters, marking schemes, timers, and attempt restrictions.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
		<div class="iaq-header-actions">
			<?php if ( $is_edit ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes&action=manage_questions&id=' . $quiz->id ) ); ?>" class="iaq-btn iaq-btn-primary">
					<span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Manage & Assign Questions', 'indianagri-quiz-engine' ); ?>
				</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes' ) ); ?>" class="iaq-btn iaq-btn-outline">
				<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to All Quizzes', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes' ) ); ?>" class="iaq-form" id="iaq-quiz-builder-form">
		<?php wp_nonce_field( 'iaq_save_quiz_nonce' ); ?>
		<?php if ( $is_edit ) : ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $quiz->id ); ?>">
		<?php endif; ?>

		<div class="iaq-two-column-layout">
			<!-- MAIN COLUMN -->
			<div class="iaq-main-column">
				<!-- General Details Card -->
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Quiz Information & Type', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<div class="iaq-form-group">
							<label for="quiz-title"><?php esc_html_e( 'Quiz / Exam Title', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<input type="text" name="title" id="quiz-title" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $is_edit ? $quiz->title : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., ICAR JRF Agronomy Mock Test 01 (Full Syllabus)', 'indianagri-quiz-engine' ); ?>" required>
						</div>

						<div class="iaq-form-group">
							<label for="quiz-slug"><?php esc_html_e( 'Slug (URL Identifier)', 'indianagri-quiz-engine' ); ?></label>
							<input type="text" name="slug" id="quiz-slug" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $is_edit ? $quiz->slug : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., icar-jrf-agronomy-mock-01', 'indianagri-quiz-engine' ); ?>">
						</div>

						<div class="iaq-form-group">
							<label><?php esc_html_e( 'Select Quiz Mode', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:12px;margin-top:6px;">
								<?php
								$current_type = $is_edit ? $quiz->quiz_type : 'mock';
								foreach ( $quiz_types as $type_key => $type_info ) :
									$checked = ( $current_type === $type_key );
									?>
									<label style="border:1px solid <?php echo $checked ? '#117E3A' : '#cbd5e1'; ?>;background:<?php echo $checked ? '#f0fdf4' : '#ffffff'; ?>;padding:12px;border-radius:8px;cursor:pointer;display:flex;flex-direction:column;gap:4px;">
										<div style="display:flex;align-items:center;gap:8px;font-weight:700;color:#0f172a;">
											<input type="radio" name="quiz_type" value="<?php echo esc_attr( $type_key ); ?>" <?php checked( $checked ); ?>>
											<?php echo esc_html( $type_info['label'] ); ?>
										</div>
										<span style="font-size:12px;color:#64748b;margin-left:22px;"><?php echo esc_html( $type_info['description'] ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="iaq-form-group" style="margin-top:16px;">
							<label for="quiz-desc"><?php esc_html_e( 'Short Description / Overview', 'indianagri-quiz-engine' ); ?></label>
							<textarea name="description" id="quiz-desc" rows="3" class="iaq-textarea iaq-full-width" placeholder="<?php esc_attr_e( 'Brief summary of what this examination covers...', 'indianagri-quiz-engine' ); ?>"><?php echo esc_textarea( $is_edit ? $quiz->description : '' ); ?></textarea>
						</div>
					</div>
				</div>

				<!-- Exam Instructions Card -->
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-welcome-write-blog"></span> <?php esc_html_e( 'Candidate Instructions (Pre-Exam Screen)', 'indianagri-quiz-engine' ); ?></h2>
						<span class="iaq-card-help"><?php esc_html_e( 'Displayed to students before clicking the "Start Test" button. Supports Hindi & English.', 'indianagri-quiz-engine' ); ?></span>
					</div>
					<div class="iaq-card-body">
						<?php
						$default_instructions = "1. This examination contains multiple-choice questions with 4 alternatives.\n2. Mark your answers carefully. Negative marking applies for incorrect attempts.\n3. The test will auto-submit once the countdown timer expires.\n4. You can use the question palette to jump between questions and mark items for review.";
						$instr_content = $is_edit ? $quiz->instructions : $default_instructions;

						wp_editor(
							$instr_content,
							'instructions',
							array(
								'textarea_name' => 'instructions',
								'textarea_rows' => 6,
								'media_buttons' => false,
								'teeny'         => true,
								'quicktags'     => true,
							)
						);
						?>
					</div>
				</div>

				<!-- Rules & Anti-Cheating Behavior -->
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Anti-Cheating & Question Behavior Rules', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
							<div class="iaq-form-group">
								<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
									<input type="checkbox" name="shuffle_questions" value="1" <?php checked( $is_edit ? $quiz->shuffle_questions : 1, 1 ); ?>>
									<strong><?php esc_html_e( 'Randomize Question Order', 'indianagri-quiz-engine' ); ?></strong>
								</label>
								<span class="iaq-card-help" style="margin-left:26px;display:block;">
									<?php esc_html_e( 'Each student sees questions in a different scrambled sequence.', 'indianagri-quiz-engine' ); ?>
								</span>
							</div>

							<div class="iaq-form-group">
								<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
									<input type="checkbox" name="shuffle_options" value="1" <?php checked( $is_edit ? $quiz->shuffle_options : 1, 1 ); ?>>
									<strong><?php esc_html_e( 'Randomize Option Order (A/B/C/D)', 'indianagri-quiz-engine' ); ?></strong>
								</label>
								<span class="iaq-card-help" style="margin-left:26px;display:block;">
									<?php esc_html_e( 'Shuffles the order of choices A, B, C, D for every student attempt.', 'indianagri-quiz-engine' ); ?>
								</span>
							</div>
						</div>

						<div class="iaq-form-group" style="margin-top:14px;">
							<label for="quiz-review-mode"><?php esc_html_e( 'Answer Key & Explanation Visibility', 'indianagri-quiz-engine' ); ?></label>
							<select name="show_answers_mode" id="quiz-review-mode" class="iaq-select iaq-full-width">
								<?php
								$current_mode = $is_edit ? $quiz->show_answers_mode : 'after_submission';
								foreach ( $review_modes as $m_key => $m_label ) :
									?>
									<option value="<?php echo esc_attr( $m_key ); ?>" <?php selected( $current_mode, $m_key ); ?>>
										<?php echo esc_html( $m_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="iaq-form-group">
							<label for="quiz-max-attempts"><?php esc_html_e( 'Maximum Allowed Attempts per Student', 'indianagri-quiz-engine' ); ?></label>
							<input type="number" min="0" max="100" name="max_attempts" id="quiz-max-attempts" class="iaq-input" value="<?php echo esc_attr( $is_edit ? $quiz->max_attempts : '0' ); ?>">
							<span class="iaq-card-help"><?php esc_html_e( 'Set to 0 for unlimited re-attempts.', 'indianagri-quiz-engine' ); ?></span>
						</div>
					</div>
				</div>

				<!-- PYQ Details Card (Used when Quiz Type is Previous Year Paper) -->
				<div class="iaq-card" id="iaq-pyq-details-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-media-document"></span> <?php esc_html_e( 'Previous-Year Paper (PYQ) Specifications', 'indianagri-quiz-engine' ); ?></h2>
						<span class="iaq-card-help"><?php esc_html_e( 'Configure examination year, exam shift/session, and optional official paper PDF download.', 'indianagri-quiz-engine' ); ?></span>
					</div>
					<div class="iaq-card-body">
						<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
							<div class="iaq-form-group">
								<label for="pyq-year"><?php esc_html_e( 'Examination Year', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" min="1990" max="2035" name="pyq_year" id="pyq-year" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $is_edit && ! empty( $quiz->pyq_year ) ? $quiz->pyq_year : date( 'Y' ) ); ?>" placeholder="e.g. 2023">
								<span class="iaq-card-help"><?php esc_html_e( 'Year in which this official exam paper was conducted.', 'indianagri-quiz-engine' ); ?></span>
							</div>
							<div class="iaq-form-group">
								<label for="pyq-session"><?php esc_html_e( 'Exam Session / Shift / Subject Sub-Stream', 'indianagri-quiz-engine' ); ?></label>
								<input type="text" name="pyq_session" id="pyq-session" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $is_edit && ! empty( $quiz->pyq_session ) ? $quiz->pyq_session : '' ); ?>" placeholder="e.g. Shift 1 / Morning Session / Agronomy Special">
								<span class="iaq-card-help"><?php esc_html_e( 'Session, shift, or paper series code.', 'indianagri-quiz-engine' ); ?></span>
							</div>
						</div>
						<div class="iaq-form-group" style="margin-top:14px;">
							<label for="pyq-pdf-url"><?php esc_html_e( 'Official Question Paper PDF Attachment URL', 'indianagri-quiz-engine' ); ?></label>
							<input type="url" name="pdf_attachment_url" id="pyq-pdf-url" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $is_edit && ! empty( $quiz->pdf_attachment_url ) ? $quiz->pdf_attachment_url : '' ); ?>" placeholder="https://example.com/wp-content/uploads/pyq-2023.pdf">
							<span class="iaq-card-help"><?php esc_html_e( 'Optional link to downloadable question paper or scanned key PDF.', 'indianagri-quiz-engine' ); ?></span>
						</div>
					</div>
				</div>
			</div>

			<!-- SIDEBAR COLUMN -->
			<div class="iaq-side-column">
				<!-- Publish Settings -->
				<div class="iaq-card iaq-publish-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Publish Status', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<div class="iaq-form-group">
							<label for="quiz-status"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></label>
							<select name="status" id="quiz-status" class="iaq-select iaq-full-width">
								<?php $current_status = $is_edit ? $quiz->status : 'published'; ?>
								<option value="published" <?php selected( $current_status, 'published' ); ?>><?php esc_html_e( 'Published (Live)', 'indianagri-quiz-engine' ); ?></option>
								<option value="draft" <?php selected( $current_status, 'draft' ); ?>><?php esc_html_e( 'Draft (In Progress)', 'indianagri-quiz-engine' ); ?></option>
								<option value="archived" <?php selected( $current_status, 'archived' ); ?>><?php esc_html_e( 'Archived', 'indianagri-quiz-engine' ); ?></option>
							</select>
						</div>

						<?php if ( $is_edit ) : ?>
							<div class="iaq-form-group">
								<label><?php esc_html_e( 'Assigned Shortcode', 'indianagri-quiz-engine' ); ?></label>
								<input type="text" readonly class="iaq-input iaq-full-width" value='[iaq_quiz id="<?php echo esc_attr( $quiz->id ); ?>"]' style="background:#f1f5f9;font-family:monospace;font-size:12px;">
								<span class="iaq-card-help"><?php esc_html_e( 'Embed this shortcode on any WordPress or Elementor page.', 'indianagri-quiz-engine' ); ?></span>
							</div>
						<?php endif; ?>
					</div>
					<div class="iaq-card-footer">
						<button type="submit" name="iaq_save_quiz" class="iaq-btn iaq-btn-primary iaq-btn-large iaq-full-width">
							<span class="dashicons dashicons-saved"></span> <?php echo $is_edit ? esc_html__( 'Update Quiz Settings', 'indianagri-quiz-engine' ) : esc_html__( 'Save & Proceed to Questions', 'indianagri-quiz-engine' ); ?>
						</button>
					</div>
				</div>

				<!-- Exam Marking Scheme -->
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-calculator"></span> <?php esc_html_e( 'Marking & Timer Rules', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<div class="iaq-form-group">
							<label for="quiz-duration"><?php esc_html_e( 'Duration (Minutes)', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<input type="number" min="0" max="600" name="duration_minutes" id="quiz-duration" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $is_edit ? $quiz->duration_minutes : $default_duration ); ?>" required>
							<span class="iaq-card-help"><?php esc_html_e( 'Enter 0 for untimed test.', 'indianagri-quiz-engine' ); ?></span>
						</div>

						<div class="iaq-scoring-row">
							<div class="iaq-form-group">
								<label for="quiz-marks"><?php esc_html_e( 'Marks (+)', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<input type="number" step="0.25" min="0.25" max="100" name="correct_marks" id="quiz-marks" class="iaq-input" value="<?php echo esc_attr( $is_edit ? $quiz->correct_marks : $default_marks ); ?>" required>
							</div>
							<div class="iaq-form-group">
								<label for="quiz-neg-marks"><?php esc_html_e( 'Negative (-)', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" step="0.01" min="0" max="100" name="negative_marks" id="quiz-neg-marks" class="iaq-input" value="<?php echo esc_attr( $is_edit ? $quiz->negative_marks : $default_neg_marks ); ?>" required>
							</div>
						</div>

						<div class="iaq-form-group">
							<label for="quiz-pass-pct"><?php esc_html_e( 'Passing Percentage (%)', 'indianagri-quiz-engine' ); ?></label>
							<input type="number" step="1" min="0" max="100" name="pass_percentage" id="quiz-pass-pct" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $is_edit ? $quiz->pass_percentage : '40' ); ?>" required>
						</div>
					</div>
				</div>

				<!-- Taxonomy & Category -->
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-category"></span> <?php esc_html_e( 'Target Exam & Subject', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<div class="iaq-form-group">
							<label for="quiz-exam-cat"><?php esc_html_e( 'Examination Category', 'indianagri-quiz-engine' ); ?></label>
							<select name="exam_category" id="quiz-exam-cat" class="iaq-select iaq-full-width">
								<?php
								$current_exam = $is_edit ? $quiz->exam_category : 'General Agriculture';
								foreach ( $exam_categories as $key => $label ) :
									?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_exam, $key ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="iaq-form-group">
							<label for="quiz-subject"><?php esc_html_e( 'Subject Filter (Optional)', 'indianagri-quiz-engine' ); ?></label>
							<select name="subject_id" id="quiz-subject" class="iaq-select iaq-full-width">
								<option value=""><?php esc_html_e( '— All Subjects / General —', 'indianagri-quiz-engine' ); ?></option>
								<?php foreach ( $subjects as $subj ) : ?>
									<option value="<?php echo esc_attr( $subj->id ); ?>" <?php selected( $selected_subj, $subj->id ); ?>>
										<?php echo esc_html( $subj->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="iaq-form-group">
							<label for="quiz-book"><?php esc_html_e( 'Assign to Practice Book (Optional)', 'indianagri-quiz-engine' ); ?></label>
							<select name="book_id" id="quiz-book" class="iaq-select iaq-full-width">
								<option value="0"><?php esc_html_e( '— None / Standalone Practice Set —', 'indianagri-quiz-engine' ); ?></option>
								<?php foreach ( $all_books as $bk ) : ?>
									<option value="<?php echo esc_attr( $bk->id ); ?>" <?php selected( $selected_book, $bk->id ); ?>>
										<?php echo esc_html( $bk->title ); ?> (<?php echo esc_html( $bk->author ? $bk->author : 'Author' ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
							<span class="iaq-card-help"><?php esc_html_e( 'Allows students to find and solve this practice set under Practice by Book.', 'indianagri-quiz-engine' ); ?></span>
						</div>

						<div class="iaq-form-group">
							<label for="quiz-display-order"><?php esc_html_e( 'Display Order', 'indianagri-quiz-engine' ); ?></label>
							<input type="number" min="0" name="display_order" id="quiz-display-order" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $is_edit && isset( $quiz->display_order ) ? $quiz->display_order : 0 ); ?>">
							<span class="iaq-card-help"><?php esc_html_e( 'Controls the sequence in Subject & Exam-Subject practice set listings (Set 1, 2, 3...).', 'indianagri-quiz-engine' ); ?></span>
						</div>
					</div>
				</div>

				<!-- Access Control -->
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-lock"></span> <?php esc_html_e( 'Access & Pricing', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<div class="iaq-form-group">
							<label for="quiz-access"><?php esc_html_e( 'Access Permission', 'indianagri-quiz-engine' ); ?></label>
							<select name="access_type" id="quiz-access" class="iaq-select iaq-full-width">
								<?php $current_access = $is_edit ? $quiz->access_type : 'free'; ?>
								<option value="free" <?php selected( $current_access, 'free' ); ?>><?php esc_html_e( 'Free (Open to Registered Students)', 'indianagri-quiz-engine' ); ?></option>
								<option value="paid" <?php selected( $current_access, 'paid' ); ?>><?php esc_html_e( 'Paid (Requires WooCommerce Purchase)', 'indianagri-quiz-engine' ); ?></option>
							</select>
						</div>

						<div class="iaq-form-group" id="iaq-wc-product-wrap" style="<?php echo ( $is_edit && 'paid' === $quiz->access_type ) ? '' : 'display:none;'; ?>">
							<label for="quiz-wc-product"><?php esc_html_e( 'WooCommerce Product ID', 'indianagri-quiz-engine' ); ?></label>
							<input type="number" name="wc_product_id" id="quiz-wc-product" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $is_edit ? $quiz->wc_product_id : '' ); ?>" placeholder="<?php esc_attr_e( 'Enter WC Product ID...', 'indianagri-quiz-engine' ); ?>">
							<span class="iaq-card-help"><?php esc_html_e( 'Students must purchase this product through WooCommerce/Razorpay to unlock access.', 'indianagri-quiz-engine' ); ?></span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
