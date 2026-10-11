<?php
/**
 * Manage Quiz Questions template: Assign, reorder, auto-select, and remove questions.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $quiz ) || ! is_object( $quiz ) ) {
	wp_die( esc_html__( 'Invalid Quiz ID.', 'indianagri-quiz-engine' ) );
}

$assigned_questions = IAQ_Quiz_Builder::get_quiz_questions( $quiz->id );
$assigned_ids       = wp_list_pluck( $assigned_questions, 'id' );

$subjects        = IAQ_Question_Bank::get_subjects();
$exam_categories = IAQ_Question_Bank::get_exam_categories();
$difficulties    = IAQ_Question_Bank::get_difficulty_levels();

// Available questions filter.
$filter_subject = isset( $_GET['filter_subject'] ) ? absint( $_GET['filter_subject'] ) : 0;
$filter_diff    = isset( $_GET['filter_diff'] ) ? sanitize_key( $_GET['filter_diff'] ) : '';
$filter_exam    = isset( $_GET['filter_exam'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_exam'] ) ) : '';
$filter_search  = isset( $_GET['filter_search'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_search'] ) ) : '';

$available_questions = IAQ_Question_Bank::get_questions(
	array(
		'subject_id'    => $filter_subject,
		'difficulty'    => $filter_diff,
		'exam_category' => $filter_exam,
		'search'        => $filter_search,
		'status'        => 'published',
		'per_page'      => 50,
		'page'          => 1,
	)
);
?>

<div class="wrap iaq-admin-wrap">
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php printf( esc_html__( 'Manage Questions for: %s', 'indianagri-quiz-engine' ), esc_html( $quiz->title ) ); ?></h1>
			<p class="iaq-header-desc">
				<?php printf( esc_html__( 'Total Assigned: %1$d MCQs | Aggregate Marks: %2$s | Duration: %3$d mins', 'indianagri-quiz-engine' ), count( $assigned_questions ), esc_html( $quiz->total_marks ), esc_html( $quiz->duration_minutes ) ); ?>
			</p>
		</div>
		<div class="iaq-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes&action=edit&id=' . $quiz->id ) ); ?>" class="iaq-btn iaq-btn-outline">
				<span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Edit Quiz Settings', 'indianagri-quiz-engine' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes' ) ); ?>" class="iaq-btn iaq-btn-outline">
				<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'All Quizzes', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>
	</div>

	<div class="iaq-grid-two-col">
		<!-- LEFT COLUMN: CURRENTLY ASSIGNED QUESTIONS -->
		<div class="iaq-col-panel">
			<div class="iaq-card">
				<div class="iaq-card-header" style="display:flex;justify-content:space-between;align-items:center;flex-direction:row;">
					<h2>
						<span class="dashicons dashicons-saved"></span> 
						<?php printf( esc_html__( 'Assigned Questions (%d)', 'indianagri-quiz-engine' ), count( $assigned_questions ) ); ?>
					</h2>
				</div>
				<div class="iaq-card-body iaq-p0">
					<?php if ( empty( $assigned_questions ) ) : ?>
						<div class="iaq-empty-state" style="padding:32px 16px;">
							<span class="dashicons dashicons-editor-help iaq-empty-icon"></span>
							<p style="margin:0;"><?php esc_html_e( 'No questions assigned yet. Select questions from the right panel or use the Auto-Pick tool.', 'indianagri-quiz-engine' ); ?></p>
						</div>
					<?php else : ?>
						<table class="iaq-table">
							<thead>
								<tr>
									<th style="width:36px;">#</th>
									<th><?php esc_html_e( 'Question', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Subject', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Marks', 'indianagri-quiz-engine' ); ?></th>
									<th style="width:40px;"><?php esc_html_e( 'Remove', 'indianagri-quiz-engine' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$seq = 0;
								foreach ( $assigned_questions as $aq ) :
									$seq++;
									$remove_url = wp_nonce_url(
										admin_url( 'admin.php?page=iaq-quizzes&action=remove_question&id=' . $quiz->id . '&question_id=' . $aq->id ),
										'iaq_remove_q_' . $quiz->id . '_' . $aq->id
									);
									?>
									<tr>
										<td><strong><?php echo esc_html( $seq ); ?></strong></td>
										<td>
											<div style="font-size:13px;line-height:1.4;">
												<?php echo wp_kses_post( wp_trim_words( $aq->question_text, 14, '...' ) ); ?>
											</div>
											<div style="font-size:11px;color:#15803d;font-weight:600;margin-top:2px;">
												✓ <?php printf( esc_html__( 'Option %s is correct', 'indianagri-quiz-engine' ), esc_html( $aq->correct_option ) ); ?>
											</div>
										</td>
										<td>
											<span class="iaq-subject-badge" style="font-size:11px;"><?php echo esc_html( $aq->subject_name ? $aq->subject_name : 'General' ); ?></span>
										</td>
										<td>
											<span class="iaq-pos-mark">+<?php echo esc_html( $aq->marks ); ?></span>
										</td>
										<td>
											<a href="<?php echo esc_url( $remove_url ); ?>" class="iaq-action-btn iaq-btn-delete" title="<?php esc_attr_e( 'Remove from Quiz', 'indianagri-quiz-engine' ); ?>">
												<span class="dashicons dashicons-no"></span>
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

		<!-- RIGHT COLUMN: QUESTION PICKER & AUTO-SELECT -->
		<div class="iaq-col-panel">
			<!-- Auto-Pick Tool Card -->
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2><span class="dashicons dashicons-randomize"></span> <?php esc_html_e( 'Auto-Pick Questions from Bank', 'indianagri-quiz-engine' ); ?></h2>
					<span class="iaq-card-help"><?php esc_html_e( 'Quickly populate the examination by randomly selecting published MCQs matching specific criteria.', 'indianagri-quiz-engine' ); ?></span>
				</div>
				<div class="iaq-card-body">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes&action=manage_questions&id=' . $quiz->id ) ); ?>">
						<?php wp_nonce_field( 'iaq_auto_assign_nonce' ); ?>
						<input type="hidden" name="quiz_id" value="<?php echo esc_attr( $quiz->id ); ?>">

						<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
							<div class="iaq-form-group">
								<label for="auto-count"><?php esc_html_e( 'Number of MCQs', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" name="auto_count" id="auto-count" class="iaq-input iaq-full-width" min="1" max="200" value="20" required>
							</div>

							<div class="iaq-form-group">
								<label for="auto-diff"><?php esc_html_e( 'Difficulty', 'indianagri-quiz-engine' ); ?></label>
								<select name="auto_difficulty" id="auto-diff" class="iaq-select iaq-full-width">
									<option value=""><?php esc_html_e( 'Any Difficulty', 'indianagri-quiz-engine' ); ?></option>
									<option value="easy"><?php esc_html_e( 'Easy', 'indianagri-quiz-engine' ); ?></option>
									<option value="medium"><?php esc_html_e( 'Medium', 'indianagri-quiz-engine' ); ?></option>
									<option value="hard"><?php esc_html_e( 'Hard', 'indianagri-quiz-engine' ); ?></option>
								</select>
							</div>
						</div>

						<div class="iaq-form-group">
							<label for="auto-subject"><?php esc_html_e( 'Filter by Subject', 'indianagri-quiz-engine' ); ?></label>
							<select name="auto_subject_id" id="auto-subject" class="iaq-select iaq-full-width">
								<option value="0"><?php esc_html_e( 'All Subjects', 'indianagri-quiz-engine' ); ?></option>
								<?php foreach ( $subjects as $subj ) : ?>
									<option value="<?php echo esc_attr( $subj->id ); ?>">
										<?php echo esc_html( $subj->name ); ?> (<?php echo esc_html( $subj->question_count ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<button type="submit" name="iaq_do_auto_assign" class="iaq-btn iaq-btn-secondary iaq-full-width">
							<span class="dashicons dashicons-randomize"></span> <?php esc_html_e( 'Auto-Assign Questions Now', 'indianagri-quiz-engine' ); ?>
						</button>
					</form>
				</div>
			</div>

			<!-- Manual Question Selector -->
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2><span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Browse & Add Questions Manually', 'indianagri-quiz-engine' ); ?></h2>
				</div>
				<div class="iaq-card-body">
					<!-- Filter Controls -->
					<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin-bottom:16px;">
						<input type="hidden" name="page" value="iaq-quizzes">
						<input type="hidden" name="action" value="manage_questions">
						<input type="hidden" name="id" value="<?php echo esc_attr( $quiz->id ); ?>">

						<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
							<input type="text" name="filter_search" value="<?php echo esc_attr( $filter_search ); ?>" placeholder="<?php esc_attr_e( 'Search questions...', 'indianagri-quiz-engine' ); ?>" class="iaq-input" style="flex:1;">
							<select name="filter_subject" class="iaq-select">
								<option value="0"><?php esc_html_e( 'All Subjects', 'indianagri-quiz-engine' ); ?></option>
								<?php foreach ( $subjects as $subj ) : ?>
									<option value="<?php echo esc_attr( $subj->id ); ?>" <?php selected( $filter_subject, $subj->id ); ?>>
										<?php echo esc_html( $subj->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<button type="submit" class="iaq-btn iaq-btn-secondary">
								<span class="dashicons dashicons-filter"></span>
							</button>
						</div>
					</form>

					<!-- Add Selected Form -->
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes&action=manage_questions&id=' . $quiz->id ) ); ?>">
						<?php wp_nonce_field( 'iaq_assign_questions_nonce' ); ?>
						<input type="hidden" name="quiz_id" value="<?php echo esc_attr( $quiz->id ); ?>">

						<div class="iaq-scrollable-table" style="max-height:360px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:14px;">
							<table class="iaq-table">
								<thead>
									<tr>
										<th style="width:30px;"><input type="checkbox" id="iaq-check-all-add"></th>
										<th><?php esc_html_e( 'Question Stem', 'indianagri-quiz-engine' ); ?></th>
										<th><?php esc_html_e( 'Diff', 'indianagri-quiz-engine' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php if ( empty( $available_questions ) ) : ?>
										<tr><td colspan="3" class="iaq-no-records"><?php esc_html_e( 'No published questions found.', 'indianagri-quiz-engine' ); ?></td></tr>
									<?php else : ?>
										<?php foreach ( $available_questions as $avail_q ) : ?>
											<?php $is_already = in_array( $avail_q->id, $assigned_ids, true ); ?>
											<tr style="<?php echo $is_already ? 'background:#f1f5f9;opacity:0.6;' : ''; ?>">
												<td>
													<input type="checkbox" name="selected_question_ids[]" value="<?php echo esc_attr( $avail_q->id ); ?>" class="iaq-add-checkbox" <?php disabled( $is_already ); ?>>
												</td>
												<td>
													<div style="font-size:12px;">
														<?php echo wp_kses_post( wp_trim_words( $avail_q->question_text, 14, '...' ) ); ?>
													</div>
													<?php if ( $is_already ) : ?>
														<span style="font-size:10px;color:#15803d;font-weight:700;">✓ <?php esc_html_e( 'Already Added', 'indianagri-quiz-engine' ); ?></span>
													<?php endif; ?>
												</td>
												<td>
													<span class="iaq-badge" style="font-size:10px;padding:2px 6px;">
														<?php echo esc_html( ucfirst( $avail_q->difficulty ) ); ?>
													</span>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>

						<button type="submit" name="iaq_add_selected_questions" class="iaq-btn iaq-btn-primary iaq-full-width">
							<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Selected Questions to Quiz', 'indianagri-quiz-engine' ); ?>
						</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
