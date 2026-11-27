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

			<div class="iaq-scorecard-header-actions" style="margin-top:12px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
				<?php
				$quiz_id_for_retake = ! empty( $scorecard['quiz_id'] ) ? (int) $scorecard['quiz_id'] : ( isset( $_GET['iaq_quiz_id'] ) ? absint( $_GET['iaq_quiz_id'] ) : 0 );
				if ( $quiz_id_for_retake > 0 ) :
					$retake_url = add_query_arg(
						array(
							'iaq_quiz_id' => $quiz_id_for_retake,
							'iaq_action'  => 'retake',
						),
						remove_query_arg( array( 'attempt_id' ), ( function_exists( 'get_permalink' ) && get_permalink() ? get_permalink() : '/' ) )
					);
					?>
					<a href="<?php echo esc_url( $retake_url ); ?>" class="iaq-btn-retake" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; background:#117E3A; color:#fff; padding:6px 14px; border-radius:6px; font-size:13px; font-weight:600;">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
						<?php esc_html_e( 'Retake Test', 'indianagri-quiz-engine' ); ?>
					</a>
				<?php endif; ?>
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
			<div class="iaq-breakdown-item is-correct iaq-filter-trigger" data-filter="correct" role="button" tabindex="0" style="cursor:pointer;" title="<?php esc_attr_e( 'Click to filter correct questions', 'indianagri-quiz-engine' ); ?>">
				<span class="iaq-bd-count"><?php echo esc_html( $scorecard['correct_answers'] ); ?></span>
				<span class="iaq-bd-label"><?php esc_html_e( 'Correct Answers', 'indianagri-quiz-engine' ); ?></span>
			</div>
			<div class="iaq-breakdown-item is-incorrect iaq-filter-trigger" data-filter="incorrect" role="button" tabindex="0" style="cursor:pointer;" title="<?php esc_attr_e( 'Click to filter incorrect questions', 'indianagri-quiz-engine' ); ?>">
				<span class="iaq-bd-count"><?php echo esc_html( $scorecard['incorrect_answers'] ); ?></span>
				<span class="iaq-bd-label"><?php esc_html_e( 'Incorrect Answers', 'indianagri-quiz-engine' ); ?></span>
			</div>
			<div class="iaq-breakdown-item is-unattempted iaq-filter-trigger" data-filter="unattempted" role="button" tabindex="0" style="cursor:pointer;" title="<?php esc_attr_e( 'Click to filter unattempted questions', 'indianagri-quiz-engine' ); ?>">
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
				<div style="border-bottom: 2px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 20px;">
					<h3 style="margin: 0 0 6px 0; font-size: 20px; font-weight: 800; color: #0f172a;">
						<?php esc_html_e( 'Question-by-Question Solutions & Explanations', 'indianagri-quiz-engine' ); ?>
					</h3>
					<span style="font-size: 13px; color: #64748b;">
						<?php esc_html_e( 'Review your responses alongside verified answer keys and detailed agricultural notes.', 'indianagri-quiz-engine' ); ?>
					</span>
				</div>

				<!-- Interactive Filter Tabs: All, Correct, Incorrect, Unattempted -->
				<div class="iaq-filter-tabs-bar" style="margin-bottom: 24px; display: flex; gap: 8px; flex-wrap: wrap;">
					<button type="button" class="iaq-filter-tab is-active" data-filter="all">
						<?php esc_html_e( 'All Questions', 'indianagri-quiz-engine' ); ?> (<?php echo count( $questions ); ?>)
					</button>
					<button type="button" class="iaq-filter-tab is-correct-tab" data-filter="correct">
						<span class="iaq-tab-dot is-correct-dot"></span> <?php esc_html_e( 'Correct', 'indianagri-quiz-engine' ); ?> (<?php echo (int) $scorecard['correct_answers']; ?>)
					</button>
					<button type="button" class="iaq-filter-tab is-incorrect-tab" data-filter="incorrect">
						<span class="iaq-tab-dot is-incorrect-dot"></span> <?php esc_html_e( 'Incorrect (Wrong)', 'indianagri-quiz-engine' ); ?> (<?php echo (int) $scorecard['incorrect_answers']; ?>)
					</button>
					<button type="button" class="iaq-filter-tab is-unattempted-tab" data-filter="unattempted">
						<span class="iaq-tab-dot is-unattempted-dot"></span> <?php esc_html_e( 'Unattempted', 'indianagri-quiz-engine' ); ?> (<?php echo (int) $scorecard['unattempted_questions']; ?>)
					</button>
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
								<?php echo wp_kses_post( class_exists( 'IAQ_Quiz_Attempt' ) ? IAQ_Quiz_Attempt::clean_content( $q['question_text'] ) : $q['question_text'] ); ?>
								<?php if ( ! empty( $q['question_text_hi'] ) && $q['question_text_hi'] !== $q['question_text'] ) : ?>
									<div class="iaq-qtext-hi" style="margin-top:6px; color:#334155; font-weight:500;">
										<?php echo wp_kses_post( class_exists( 'IAQ_Quiz_Attempt' ) ? IAQ_Quiz_Attempt::clean_content( $q['question_text_hi'] ) : $q['question_text_hi'] ); ?>
									</div>
								<?php endif; ?>
							</div>

							<!-- Options -->
							<div class="iaq-sol-options-grid">
								<?php
								$options = array(
									'A' => class_exists( 'IAQ_Quiz_Attempt' ) ? IAQ_Quiz_Attempt::clean_content( $q['option_a'] ) : $q['option_a'],
									'B' => class_exists( 'IAQ_Quiz_Attempt' ) ? IAQ_Quiz_Attempt::clean_content( $q['option_b'] ) : $q['option_b'],
									'C' => class_exists( 'IAQ_Quiz_Attempt' ) ? IAQ_Quiz_Attempt::clean_content( $q['option_c'] ) : $q['option_c'],
									'D' => class_exists( 'IAQ_Quiz_Attempt' ) ? IAQ_Quiz_Attempt::clean_content( $q['option_d'] ) : $q['option_d'],
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
									$opt_hi = ! empty( $q[ 'option_' . strtolower( $k ) . '_hi' ] ) ? ( class_exists( 'IAQ_Quiz_Attempt' ) ? IAQ_Quiz_Attempt::clean_content( $q[ 'option_' . strtolower( $k ) . '_hi' ] ) : $q[ 'option_' . strtolower( $k ) . '_hi' ] ) : '';
									?>
									<div class="iaq-sol-option-item <?php echo esc_attr( $opt_cls ); ?>">
										<span class="iaq-sol-opt-key"><?php echo esc_html( $k ); ?></span>
										<span class="iaq-sol-opt-label">
											<?php echo wp_kses_post( $opt_text ); ?>
											<?php if ( ! empty( $opt_hi ) && $opt_hi !== $opt_text ) : ?>
												<span class="iaq-opt-hi" style="color:#64748b;font-weight:normal;">(<?php echo wp_kses_post( $opt_hi ); ?>)</span>
											<?php endif; ?>
										</span>
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
			<?php
			$quiz_id_for_retake = ! empty( $scorecard['quiz_id'] ) ? (int) $scorecard['quiz_id'] : ( isset( $_GET['iaq_quiz_id'] ) ? absint( $_GET['iaq_quiz_id'] ) : 0 );
			if ( $quiz_id_for_retake > 0 ) :
				$retake_url = add_query_arg(
					array(
						'iaq_quiz_id' => $quiz_id_for_retake,
						'iaq_action'  => 'retake',
					),
					remove_query_arg( array( 'attempt_id' ), ( function_exists( 'get_permalink' ) && get_permalink() ? get_permalink() : '/' ) )
				);
				?>
				<a href="<?php echo esc_url( $retake_url ); ?>" class="iaq-btn-retake" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; background:#117E3A; color:#fff; padding:10px 20px; border-radius:6px; font-weight:600;">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
					<?php esc_html_e( 'Retake Test', 'indianagri-quiz-engine' ); ?>
				</a>
			<?php endif; ?>
			<button type="button" class="iaq-btn-print" onclick="window.print();">
				<span class="dashicons dashicons-printer"></span> <?php esc_html_e( 'Print Scorecard / Download PDF', 'indianagri-quiz-engine' ); ?>
			</button>
			<a href="<?php echo esc_url( remove_query_arg( array( 'iaq_quiz_id', 'iaq_action', 'attempt_id' ), ( function_exists( 'get_permalink' ) && get_permalink() ? get_permalink() : '/' ) ) ); ?>" class="iaq-btn-action-primary" style="text-decoration:none;">
				&larr; <?php esc_html_e( 'Back to Quiz List', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>

	</div>
</div>
