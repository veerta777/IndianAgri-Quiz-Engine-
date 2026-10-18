<?php
/**
 * Frontend: Official Examination Scorecard, Weak-Area Analytics & Solutions View.
 * Displayed via shortcode [iaq_quiz_results id="123"].
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $scorecard ) || ! is_array( $scorecard ) ) {
	return;
}
?>

<div class="iaq-portal-wrapper iaq-standalone-scorecard">
	<div class="iaq-results-card">
		
		<!-- Scorecard Official Header -->
		<div class="iaq-results-header">
			<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $scorecard['exam_category'] ); ?></span>
			<h2 class="iaq-results-title"><?php echo esc_html( $scorecard['quiz_title'] ); ?></h2>
			
			<div class="iaq-verdict-badge <?php echo $scorecard['passed'] ? 'iaq-verdict-passed' : 'iaq-verdict-failed'; ?>">
				<?php echo $scorecard['passed'] ? esc_html__( 'PASSED', 'indianagri-quiz-engine' ) : esc_html__( 'NEEDS IMPROVEMENT', 'indianagri-quiz-engine' ); ?>
			</div>

			<div style="font-size:13px;color:#64748b;margin-top:8px;">
				<span><?php esc_html_e( 'Completed on', 'indianagri-quiz-engine' ); ?>: <?php echo esc_html( $scorecard['end_time'] ); ?></span>
			</div>

			<div class="iaq-scorecard-header-actions" style="margin-top:12px;">
				<button type="button" class="iaq-btn-print" onclick="window.print();">
					<span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print Scorecard / Download PDF', 'indianagri-quiz-engine' ); ?>
				</button>
			</div>
		</div>

		<!-- Performance Metrics Grid -->
		<div class="iaq-score-matrix">
			<div class="iaq-stat-tile is-score">
				<span class="iaq-stat-label"><?php esc_html_e( 'Final Score', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-big"><?php echo esc_html( $scorecard['score'] ); ?> <small>/ <?php echo esc_html( $scorecard['total_marks'] ); ?></small></span>
			</div>
			<div class="iaq-stat-tile is-percent">
				<span class="iaq-stat-label"><?php esc_html_e( 'Percentage', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-big"><?php echo esc_html( $scorecard['percentage'] ); ?>%</span>
			</div>
			<?php if ( isset( $scorecard['percentile'] ) && null !== $scorecard['percentile'] ) : ?>
			<div class="iaq-stat-tile is-percentile">
				<span class="iaq-stat-label"><?php esc_html_e( 'Percentile', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-big"><?php echo esc_html( $scorecard['percentile'] ); ?><small>th %ile</small></span>
			</div>
			<?php endif; ?>
			<div class="iaq-stat-tile is-accuracy">
				<span class="iaq-stat-label"><?php esc_html_e( 'Correct / Total', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-big"><?php echo esc_html( $scorecard['correct_answers'] ); ?> / <?php echo esc_html( $scorecard['total_questions'] ); ?></span>
			</div>
			<div class="iaq-stat-tile is-time">
				<span class="iaq-stat-label"><?php esc_html_e( 'Time Taken', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-big"><?php echo esc_html( $scorecard['time_spent_formatted'] ); ?></span>
			</div>
		</div>

		<!-- Accuracy & Negative Marking Breakdown -->
		<div class="iaq-breakdown-row">
			<div class="iaq-breakdown-item is-correct">
				<span class="iaq-bd-count"><?php echo esc_html( $scorecard['correct_answers'] ); ?></span>
				<span class="iaq-bd-label"><?php esc_html_e( 'Correct Answers', 'indianagri-quiz-engine' ); ?></span>
			</div>
			<div class="iaq-breakdown-item is-incorrect">
				<span class="iaq-bd-count"><?php echo esc_html( $scorecard['incorrect_answers'] ); ?></span>
				<span class="iaq-bd-label"><?php esc_html_e( 'Incorrect Answers', 'indianagri-quiz-engine' ); ?></span>
			</div>
			<div class="iaq-breakdown-item is-unattempted">
				<span class="iaq-bd-count"><?php echo esc_html( $scorecard['unattempted_questions'] ); ?></span>
				<span class="iaq-bd-label"><?php esc_html_e( 'Unattempted', 'indianagri-quiz-engine' ); ?></span>
			</div>
			<?php if ( ! empty( $scorecard['negative_deductions'] ) && (float) $scorecard['negative_deductions'] > 0 ) : ?>
			<div class="iaq-breakdown-item is-negative-deduction">
				<span class="iaq-bd-count">-<?php echo esc_html( $scorecard['negative_deductions'] ); ?></span>
				<span class="iaq-bd-label"><?php esc_html_e( 'Negative Deductions', 'indianagri-quiz-engine' ); ?></span>
			</div>
			<?php endif; ?>
		</div>

		<!-- Subject & Topic Mastery Breakdown -->
		<?php if ( ! empty( $breakdown ) ) : ?>
			<div class="iaq-subject-analytics-card">
				<div class="iaq-subject-analytics-header">
					<h3 class="iaq-analytics-title">
						<span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e( 'Domain & Subject-Wise Performance Breakdown', 'indianagri-quiz-engine' ); ?>
					</h3>
					<span class="iaq-analytics-subtitle"><?php esc_html_e( 'Evaluate topic strengths and identify areas for competitive exam revision', 'indianagri-quiz-engine' ); ?></span>
				</div>
				<div class="iaq-analytics-table-responsive">
					<table class="iaq-analytics-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Subject / Domain', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Attempted', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Correct', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Score', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Accuracy', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Mastery Rating', 'indianagri-quiz-engine' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $breakdown as $sb ) : ?>
								<?php
								$mastery_cls = 'is-mastery-weak';
								if ( 'strong' === $sb['mastery_level'] ) {
									$mastery_cls = 'is-mastery-strong';
								} elseif ( 'moderate' === $sb['mastery_level'] ) {
									$mastery_cls = 'is-mastery-moderate';
								}
								?>
								<tr>
									<td><strong><?php echo esc_html( $sb['subject_name'] ); ?></strong></td>
									<td><?php echo esc_html( $sb['total_questions'] ); ?></td>
									<td><?php echo esc_html( $sb['attempted'] ); ?></td>
									<td><span class="iaq-score-pos"><?php echo esc_html( $sb['correct'] ); ?></span></td>
									<td><strong><?php echo esc_html( $sb['score'] ); ?></strong></td>
									<td><?php echo esc_html( $sb['accuracy_percent'] ); ?>%</td>
									<td><span class="iaq-mastery-badge <?php echo esc_attr( $mastery_cls ); ?>"><?php echo esc_html( $sb['mastery_label'] ); ?></span></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		<?php endif; ?>

		<!-- Detailed Solutions & Question-by-Question Analysis -->
		<?php if ( ! empty( $questions ) && 'never' !== $scorecard['show_answers_mode'] ) : ?>
			<div class="iaq-solutions-section" style="margin-top: 36px;">
				<div style="border-bottom: 2px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 24px;">
					<h3 style="margin: 0 0 6px 0; font-size: 20px; font-weight: 800; color: #0f172a;">
						<?php esc_html_e( 'Question-by-Question Solutions & Explanations', 'indianagri-quiz-engine' ); ?>
					</h3>
					<span style="font-size: 13px; color: #64748b;">
						<?php esc_html_e( 'Review your responses alongside verified answer keys and detailed agricultural notes.', 'indianagri-quiz-engine' ); ?>
					</span>
				</div>

				<div class="iaq-solutions-list">
					<?php foreach ( $questions as $q ) : ?>
						<?php
						$status = 'unattempted';
						if ( 1 === $q['is_correct'] ) {
							$status = 'correct';
						} elseif ( 0 === $q['is_correct'] ) {
							$status = 'incorrect';
						}
						?>
						<div class="iaq-solution-card is-status-<?php echo esc_attr( $status ); ?>">
							<div class="iaq-sol-card-header">
								<div class="iaq-sol-badges">
									<span class="iaq-sol-qnum"><?php printf( esc_html__( 'Question %d', 'indianagri-quiz-engine' ), $q['index'] ); ?></span>
									<?php if ( ! empty( $q['subject_name'] ) ) : ?>
										<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $q['subject_name'] ); ?></span>
									<?php endif; ?>
								</div>
								<div>
									<?php if ( 'correct' === $status ) : ?>
										<span class="iaq-sol-status-badge is-correct">
											<span class="dashicons dashicons-yes"></span> <?php printf( esc_html__( 'Correct (+%s)', 'indianagri-quiz-engine' ), $q['marks_awarded'] ); ?>
										</span>
									<?php elseif ( 'incorrect' === $status ) : ?>
										<span class="iaq-sol-status-badge is-incorrect">
											<span class="dashicons dashicons-no"></span> <?php printf( esc_html__( 'Incorrect (%s)', 'indianagri-quiz-engine' ), $q['marks_awarded'] ); ?>
										</span>
									<?php else : ?>
										<span class="iaq-sol-status-badge is-unattempted">
											<span class="dashicons dashicons-minus"></span> <?php esc_html_e( 'Not Attempted (0.00)', 'indianagri-quiz-engine' ); ?>
										</span>
									<?php endif; ?>
								</div>
							</div>

							<div class="iaq-sol-qtext">
								<?php echo wp_kses_post( $q['question_text'] ); ?>
							</div>

							<!-- Options -->
							<div class="iaq-sol-options-grid">
								<?php
								$options = array(
									'A' => $q['option_a'],
									'B' => $q['option_b'],
									'C' => $q['option_c'],
									'D' => $q['option_d'],
								);
								foreach ( $options as $k => $opt_text ) :
									$is_correct_opt  = ( $k === $q['correct_option'] );
									$is_selected_opt = ( $k === $q['selected_option'] );
									$opt_cls = '';
									if ( $is_correct_opt ) {
										$opt_cls = 'is-correct-answer';
									} elseif ( $is_selected_opt ) {
										$opt_cls = 'is-user-wrong';
									}
									?>
									<div class="iaq-sol-option-item <?php echo esc_attr( $opt_cls ); ?>">
										<span class="iaq-sol-opt-key"><?php echo esc_html( $k ); ?></span>
										<span class="iaq-sol-opt-label"><?php echo wp_kses_post( $opt_text ); ?></span>
										<?php if ( $is_correct_opt && $is_selected_opt ) : ?>
											<span class="iaq-sol-opt-tag"><?php esc_html_e( '✓ Your Correct Choice', 'indianagri-quiz-engine' ); ?></span>
										<?php elseif ( $is_correct_opt ) : ?>
											<span class="iaq-sol-opt-tag"><?php esc_html_e( '✓ Correct Answer', 'indianagri-quiz-engine' ); ?></span>
										<?php elseif ( $is_selected_opt ) : ?>
											<span class="iaq-sol-opt-tag"><?php esc_html_e( '✗ Your Choice', 'indianagri-quiz-engine' ); ?></span>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>

							<!-- Explanation -->
							<?php if ( ! empty( $q['explanation'] ) ) : ?>
								<div class="iaq-sol-explanation-box">
									<h4 class="iaq-sol-expl-title">
										<span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e( 'Detailed Scientific Explanation & Reference:', 'indianagri-quiz-engine' ); ?>
									</h4>
									<div class="iaq-sol-expl-content">
										<?php echo wp_kses_post( wpautop( $q['explanation'] ) ); ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- Actions -->
		<div class="iaq-results-actions" style="margin-top: 30px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
			<button type="button" class="iaq-btn-print" onclick="window.print();">
				<span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print Scorecard / Download PDF', 'indianagri-quiz-engine' ); ?>
			</button>
			<a href="<?php echo esc_url( remove_query_arg( array( 'iaq_quiz_id', 'iaq_action', 'attempt_id' ), ( function_exists( 'get_permalink' ) && get_permalink() ? get_permalink() : '/' ) ) ); ?>" class="iaq-btn-action-primary" style="text-decoration:none;">
				&larr; <?php esc_html_e( 'Back to Student Dashboard', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>

	</div>
</div>
