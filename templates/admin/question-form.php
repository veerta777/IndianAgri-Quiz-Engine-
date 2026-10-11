<?php
/**
 * Add / Edit Question form template.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_edit = ! empty( $question ) && is_object( $question );
$title   = $is_edit ? sprintf( __( 'Edit Question #%d', 'indianagri-quiz-engine' ), $question->id ) : __( 'Add New Question', 'indianagri-quiz-engine' );

$subjects        = IAQ_Question_Bank::get_subjects();
$selected_subj   = $is_edit ? $question->subject_id : ( ! empty( $subjects ) ? $subjects[0]->id : 0 );
$topics          = $selected_subj ? IAQ_Question_Bank::get_topics( $selected_subj ) : array();
$exam_categories = IAQ_Question_Bank::get_exam_categories();
$difficulties    = IAQ_Question_Bank::get_difficulty_levels();
?>

<div class="wrap iaq-admin-wrap">
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php echo esc_html( $title ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Compose agricultural MCQs in English or Hindi with complete four-option choices and detailed scientific rationale.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
		<div class="iaq-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-question-bank' ) ); ?>" class="iaq-btn iaq-btn-outline">
				<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to Question Bank', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-question-bank' ) ); ?>" class="iaq-form" id="iaq-question-editor-form">
		<?php wp_nonce_field( 'iaq_save_question_nonce' ); ?>
		<?php if ( $is_edit ) : ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $question->id ); ?>">
		<?php endif; ?>

		<div class="iaq-two-column-layout">
			<!-- Main Column: Question Content & Options -->
			<div class="iaq-main-column">
				<!-- Question Text Card -->
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'Question Stem (English / हिंदी)', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></h2>
						<span class="iaq-card-help"><?php esc_html_e( 'Supports Devanagari Hindi text, chemical formulas, and scientific notations.', 'indianagri-quiz-engine' ); ?></span>
					</div>
					<div class="iaq-card-body">
						<?php
						$question_content = $is_edit ? $question->question_text : '';
						wp_editor(
							$question_content,
							'question_text',
							array(
								'textarea_name' => 'question_text',
								'textarea_rows' => 7,
								'media_buttons' => true,
								'teeny'         => false,
								'quicktags'     => true,
							)
						);
						?>
					</div>
				</div>

				<!-- Answer Options Card -->
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-forms"></span> <?php esc_html_e( 'Multiple Choice Options & Correct Answer', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></h2>
						<span class="iaq-card-help"><?php esc_html_e( 'Provide all 4 options and mark the radio button next to the correct choice.', 'indianagri-quiz-engine' ); ?></span>
					</div>
					<div class="iaq-card-body">
						<?php
						$selected_correct = $is_edit ? $question->correct_option : 'A';
						$option_keys      = array( 'A', 'B', 'C', 'D' );
						?>
						<div class="iaq-options-builder">
							<?php foreach ( $option_keys as $opt ) : ?>
								<?php
								$field_name = 'option_' . strtolower( $opt );
								$opt_value  = $is_edit ? $question->$field_name : '';
								$is_checked = ( $selected_correct === $opt );
								?>
								<div class="iaq-option-row <?php echo $is_checked ? 'is-correct-row' : ''; ?>" id="iaq-opt-row-<?php echo esc_attr( $opt ); ?>">
									<div class="iaq-option-radio-box">
										<input type="radio" name="correct_option" id="iaq-radio-<?php echo esc_attr( $opt ); ?>" value="<?php echo esc_attr( $opt ); ?>" <?php checked( $is_checked ); ?> required>
										<label for="iaq-radio-<?php echo esc_attr( $opt ); ?>" class="iaq-option-letter"><?php echo esc_html( $opt ); ?></label>
									</div>
									<div class="iaq-option-input-wrap">
										<textarea name="<?php echo esc_attr( $field_name ); ?>" rows="2" class="iaq-textarea iaq-option-text" placeholder="<?php echo esc_attr( sprintf( __( 'Enter text for Option %s (English or हिंदी)...', 'indianagri-quiz-engine' ), $opt ) ); ?>" required><?php echo esc_textarea( $opt_value ); ?></textarea>
										<span class="iaq-correct-indicator"><?php esc_html_e( 'Correct Answer', 'indianagri-quiz-engine' ); ?></span>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- Detailed Explanation Card -->
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e( 'Answer Explanation & Scientific Reference', 'indianagri-quiz-engine' ); ?></h2>
						<span class="iaq-card-help"><?php esc_html_e( 'Displayed to students after submission or in practice mode to solidify concepts.', 'indianagri-quiz-engine' ); ?></span>
					</div>
					<div class="iaq-card-body">
						<?php
						$explanation_content = $is_edit ? $question->explanation : '';
						wp_editor(
							$explanation_content,
							'explanation',
							array(
								'textarea_name' => 'explanation',
								'textarea_rows' => 5,
								'media_buttons' => true,
								'teeny'         => true,
								'quicktags'     => true,
							)
						);
						?>
					</div>
				</div>
			</div>

			<!-- Sidebar Column: Taxonomy & Parameters -->
			<div class="iaq-side-column">
				<!-- Publish Card -->
				<div class="iaq-card iaq-publish-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Publish Settings', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<div class="iaq-form-group">
							<label for="iaq-status"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></label>
							<select name="status" id="iaq-status" class="iaq-select iaq-full-width">
								<?php $current_status = $is_edit ? $question->status : 'published'; ?>
								<option value="published" <?php selected( $current_status, 'published' ); ?>><?php esc_html_e( 'Published (Live)', 'indianagri-quiz-engine' ); ?></option>
								<option value="draft" <?php selected( $current_status, 'draft' ); ?>><?php esc_html_e( 'Draft (In Review)', 'indianagri-quiz-engine' ); ?></option>
								<option value="archived" <?php selected( $current_status, 'archived' ); ?>><?php esc_html_e( 'Archived', 'indianagri-quiz-engine' ); ?></option>
							</select>
						</div>

						<div class="iaq-form-group">
							<label for="iaq-difficulty"><?php esc_html_e( 'Difficulty Level', 'indianagri-quiz-engine' ); ?></label>
							<select name="difficulty" id="iaq-difficulty" class="iaq-select iaq-full-width">
								<?php
								$current_diff = $is_edit ? $question->difficulty : 'medium';
								foreach ( $difficulties as $d_key => $d_meta ) :
									?>
									<option value="<?php echo esc_attr( $d_key ); ?>" <?php selected( $current_diff, $d_key ); ?>>
										<?php echo esc_html( $d_meta['label'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="iaq-scoring-row">
							<div class="iaq-form-group">
								<label for="iaq-marks"><?php esc_html_e( 'Marks (+)', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" step="0.25" min="0.25" max="100" name="marks" id="iaq-marks" class="iaq-input" value="<?php echo esc_attr( $is_edit ? $question->marks : '1.00' ); ?>" required>
							</div>
							<div class="iaq-form-group">
								<label for="iaq-neg-marks"><?php esc_html_e( 'Negative (-)', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" step="0.01" min="0" max="100" name="negative_marks" id="iaq-neg-marks" class="iaq-input" value="<?php echo esc_attr( $is_edit ? $question->negative_marks : '0.25' ); ?>" required>
							</div>
						</div>
					</div>
					<div class="iaq-card-footer">
						<button type="submit" name="iaq_save_question" class="iaq-btn iaq-btn-primary iaq-btn-large iaq-full-width">
							<span class="dashicons dashicons-saved"></span> <?php echo $is_edit ? esc_html__( 'Update Question', 'indianagri-quiz-engine' ) : esc_html__( 'Save & Publish Question', 'indianagri-quiz-engine' ); ?>
						</button>
					</div>
				</div>

				<!-- Taxonomy Card -->
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-category"></span> <?php esc_html_e( 'Subject & Topic', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<div class="iaq-form-group">
							<label for="iaq-form-subject"><?php esc_html_e( 'Subject', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<select name="subject_id" id="iaq-form-subject" class="iaq-select iaq-full-width" required>
								<option value=""><?php esc_html_e( '— Select Subject —', 'indianagri-quiz-engine' ); ?></option>
								<?php foreach ( $subjects as $subj ) : ?>
									<option value="<?php echo esc_attr( $subj->id ); ?>" <?php selected( $selected_subj, $subj->id ); ?>>
										<?php echo esc_html( $subj->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="iaq-form-group">
							<label for="iaq-form-topic"><?php esc_html_e( 'Topic (Optional)', 'indianagri-quiz-engine' ); ?></label>
							<select name="topic_id" id="iaq-form-topic" class="iaq-select iaq-full-width">
								<option value=""><?php esc_html_e( '— Select Topic —', 'indianagri-quiz-engine' ); ?></option>
								<?php
								$selected_topic = $is_edit ? $question->topic_id : 0;
								foreach ( $topics as $t ) :
									?>
									<option value="<?php echo esc_attr( $t->id ); ?>" <?php selected( $selected_topic, $t->id ); ?>>
										<?php echo esc_html( $t->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="iaq-form-group">
							<label for="iaq-form-exam"><?php esc_html_e( 'Target Exam Category', 'indianagri-quiz-engine' ); ?></label>
							<select name="exam_category" id="iaq-form-exam" class="iaq-select iaq-full-width">
								<?php
								$current_exam = $is_edit ? $question->exam_category : 'General Agriculture';
								foreach ( $exam_categories as $key => $label ) :
									?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_exam, $key ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
