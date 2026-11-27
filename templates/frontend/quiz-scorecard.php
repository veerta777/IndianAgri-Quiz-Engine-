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
				$mistakes_count     = (int) $scorecard['incorrect_answers'] + (int) $scorecard['unattempted_questions'];
				$quiz_id_for_retake = ! empty( $scorecard['quiz_id'] ) ? (int) $scorecard['quiz_id'] : ( isset( $_GET['iaq_quiz_id'] ) ? absint( $_GET['iaq_quiz_id'] ) : 0 );
				if ( $quiz_id_for_retake > 0 ) :
					$retake_url = add_query_arg(
						array(
							'iaq_quiz_id' => $quiz_id_for_retake,
							'iaq_action'  => 'retake',
						),
						remove_query_arg( array( 'attempt_id' ), ( function_exists( 'get_permalink' ) && get_permalink() ? get_permalink() : '/' ) )
					);
					if ( $mistakes_count > 0 && ! empty( $scorecard['attempt_id'] ) ) :
						$retake_mistakes_url = add_query_arg(
							array(
								'iaq_quiz_id' => $quiz_id_for_retake,
								'iaq_action'  => 'retake_mistakes',
								'attempt_id'  => (int) $scorecard['attempt_id'],
							),
							remove_query_arg( array( 'attempt_id' ), ( function_exists( 'get_permalink' ) && get_permalink() ? get_permalink() : '/' ) )
						);
						?>
						<a href="<?php echo esc_url( $retake_mistakes_url ); ?>" class="iaq-btn-retake is-mistakes" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; background:#dc2626; color:#fff; padding:6px 14px; border-radius:6px; font-size:13px; font-weight:600;">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
							<?php printf( esc_html__( 'Re-attempt Mistakes Only (%d)', 'indianagri-quiz-engine' ), $mistakes_count ); ?>
						</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( $retake_url ); ?>" class="iaq-btn-retake" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; background:#117E3A; color:#fff; padding:6px 14px; border-radius:6px; font-size:13px; font-weight:600;">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
						<?php esc_html_e( 'Retake Full Test', 'indianagri-quiz-engine' ); ?>
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

		<?php
		$share_quiz_title = ! empty( $scorecard['quiz_title'] ) ? $scorecard['quiz_title'] : __( 'Agricultural Examination', 'indianagri-quiz-engine' );
		$share_score_text = sprintf(
			"🌾 IndianAgriExam CBT Mock Test 🎯\n📝 Exam: %s\n📊 Score: %s / %s (%s%%)\n⭐ Accuracy: %s / %s Correct%s\n\nCan you beat my score? Test your preparation here:\n🔗 %s",
			$share_quiz_title,
			$scorecard['score'],
			$scorecard['total_marks'],
			$scorecard['percentage'],
			$scorecard['correct_answers'],
			$scorecard['total_questions'],
			( isset( $scorecard['percentile'] ) && null !== $scorecard['percentile'] ) ? "\n🏆 Percentile: " . $scorecard['percentile'] . 'th %ile' : '',
			esc_url( ( function_exists( 'get_permalink' ) && get_permalink() ) ? get_permalink() : home_url( '/' ) )
		);
		$wa_url          = 'https://api.whatsapp.com/send?text=' . rawurlencode( $share_score_text );
		$quiz_target_url = ( function_exists( 'get_permalink' ) && get_permalink() ) ? get_permalink() : home_url( '/' );
		$tg_url          = 'https://t.me/share/url?url=' . rawurlencode( $quiz_target_url ) . '&text=' . rawurlencode( $share_score_text );
		?>
		<!-- Social Share Bar (WhatsApp & Telegram) -->
		<div class="iaq-share-bar" style="margin: 22px 0 16px 0; padding: 14px 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
			<div style="display:flex; align-items:center; gap:8px;">
				<span style="font-size:18px;">🎯</span>
				<strong style="font-size:14px; color:#1e293b;"><?php esc_html_e( 'Proud of your score? Challenge your friends & study groups!', 'indianagri-quiz-engine' ); ?></strong>
			</div>
			<div style="display:flex; gap:10px; flex-wrap:wrap;">
				<a href="<?php echo esc_url( $wa_url ); ?>" class="iaq-share-btn is-whatsapp" target="_blank" rel="noopener noreferrer">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="#ffffff" style="vertical-align:-2px;margin-right:6px;"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
					<span><?php esc_html_e( 'Share on WhatsApp', 'indianagri-quiz-engine' ); ?></span>
				</a>
				<a href="<?php echo esc_url( $tg_url ); ?>" class="iaq-share-btn is-telegram" target="_blank" rel="noopener noreferrer">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="#ffffff" style="vertical-align:-2px;margin-right:6px;"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.828.916z"/></svg>
					<span><?php esc_html_e( 'Share on Telegram', 'indianagri-quiz-engine' ); ?></span>
				</a>
			</div>
		</div>

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
					<?php
					$current_uid     = get_current_user_id();
					$bookmarked_qids = ( $current_uid > 0 && class_exists( 'IAQ_Bookmarks' ) ) ? IAQ_Bookmarks::get_bookmarked_question_ids( $current_uid ) : array();
					?>
					<?php foreach ( $questions as $q ) : ?>
						<?php
						$status = 'unattempted';
						if ( 1 === $q['is_correct'] ) {
							$status = 'correct';
						} elseif ( 0 === $q['is_correct'] ) {
							$status = 'incorrect';
						}
						$is_bookmarked = in_array( (int) $q['id'], $bookmarked_qids, true );
						?>
						<div class="iaq-solution-card is-status-<?php echo esc_attr( $status ); ?>">
							<div class="iaq-sol-card-header">
								<div class="iaq-sol-badges">
									<span class="iaq-sol-qnum"><?php printf( esc_html__( 'Question %d', 'indianagri-quiz-engine' ), $q['index'] ); ?></span>
									<?php if ( ! empty( $q['subject_name'] ) ) : ?>
										<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $q['subject_name'] ); ?></span>
									<?php endif; ?>
								</div>
								<div class="iaq-sol-header-right" style="display: flex; align-items: center; gap: 8px;">
									<button type="button" class="iaq-sol-bookmark-btn <?php echo $is_bookmarked ? 'is-bookmarked' : ''; ?>" data-question-id="<?php echo (int) $q['id']; ?>" title="<?php echo $is_bookmarked ? esc_attr__( 'Bookmarked - Click to remove', 'indianagri-quiz-engine' ) : esc_attr__( 'Bookmark this question for revision', 'indianagri-quiz-engine' ); ?>">
										<svg class="iaq-bm-icon" width="13" height="13" viewBox="0 0 24 24" fill="<?php echo $is_bookmarked ? '#f59e0b' : 'none'; ?>" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
										<span class="iaq-bm-text"><?php echo $is_bookmarked ? esc_html__( 'Bookmarked', 'indianagri-quiz-engine' ) : esc_html__( 'Bookmark', 'indianagri-quiz-engine' ); ?></span>
									</button>
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
			$mistakes_count     = (int) $scorecard['incorrect_answers'] + (int) $scorecard['unattempted_questions'];
			$quiz_id_for_retake = ! empty( $scorecard['quiz_id'] ) ? (int) $scorecard['quiz_id'] : ( isset( $_GET['iaq_quiz_id'] ) ? absint( $_GET['iaq_quiz_id'] ) : 0 );
			if ( $quiz_id_for_retake > 0 ) :
				$retake_url = add_query_arg(
					array(
						'iaq_quiz_id' => $quiz_id_for_retake,
						'iaq_action'  => 'retake',
					),
					remove_query_arg( array( 'attempt_id' ), ( function_exists( 'get_permalink' ) && get_permalink() ? get_permalink() : '/' ) )
				);
				if ( $mistakes_count > 0 && ! empty( $scorecard['attempt_id'] ) ) :
					$retake_mistakes_url = add_query_arg(
						array(
							'iaq_quiz_id' => $quiz_id_for_retake,
							'iaq_action'  => 'retake_mistakes',
							'attempt_id'  => (int) $scorecard['attempt_id'],
						),
						remove_query_arg( array( 'attempt_id' ), ( function_exists( 'get_permalink' ) && get_permalink() ? get_permalink() : '/' ) )
					);
					?>
					<a href="<?php echo esc_url( $retake_mistakes_url ); ?>" class="iaq-btn-retake is-mistakes" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; background:#dc2626; color:#fff; padding:10px 20px; border-radius:6px; font-weight:600;">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
						<?php printf( esc_html__( 'Re-attempt Mistakes Only (%d)', 'indianagri-quiz-engine' ), $mistakes_count ); ?>
					</a>
				<?php endif; ?>
				<a href="<?php echo esc_url( $retake_url ); ?>" class="iaq-btn-retake" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; background:#117E3A; color:#fff; padding:10px 20px; border-radius:6px; font-weight:600;">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
					<?php esc_html_e( 'Retake Full Test', 'indianagri-quiz-engine' ); ?>
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
