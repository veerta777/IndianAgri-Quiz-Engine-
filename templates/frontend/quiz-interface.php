<?php
/**
 * Frontend: Student Interactive Examination Interface.
 * Real-time Timer, Question Palette, Auto-Save, and Submission Modals.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $quiz ) || ! is_object( $quiz ) ) {
	return;
}
$is_practice_mode = class_exists( 'IAQ_Quiz_Builder' ) ? IAQ_Quiz_Builder::is_practice_quiz( $quiz ) : ( 'mock' !== $quiz->quiz_type && 'previous_year' !== $quiz->quiz_type );
?>

<!-- Examination Portal (Hidden until started/resumed) -->
<div class="iaq-portal-wrapper" id="iaq-examination-portal" style="display:none;" data-quiz-id="<?php echo esc_attr( $quiz->id ); ?>" data-quiz-type="<?php echo esc_attr( $quiz->quiz_type ); ?>" data-is-immediate="<?php echo $is_practice_mode ? '1' : '0'; ?>" data-show-answers-mode="<?php echo esc_attr( $is_practice_mode ? 'immediate' : $quiz->show_answers_mode ); ?>">

	<!-- Top Bar -->
	<header class="iaq-exam-topbar">
		<div class="iaq-topbar-title-group">
			<span class="iaq-topbar-badge"><?php echo esc_html( $quiz->exam_category ); ?></span>
			<h2 class="iaq-topbar-heading" id="iaq-exam-heading"><?php echo esc_html( $quiz->title ); ?></h2>
		</div>

		<div class="iaq-topbar-controls">
			<!-- Return to Dashboard Control -->
			<button type="button" class="iaq-btn-exam-back" id="iaq-exam-back-dashboard-btn" title="<?php esc_attr_e( 'Pause & Return to Dashboard', 'indianagri-quiz-engine' ); ?>">
				<svg class="iaq-icon" viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" /></svg> <span class="iaq-btn-text"><?php esc_html_e( 'Dashboard', 'indianagri-quiz-engine' ); ?></span>
			</button>

			<!-- Timer Clock -->
			<div class="iaq-timer-clock-badge" id="iaq-timer-container">
				<svg class="iaq-icon iaq-timer-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>
				<div class="iaq-timer-meta">
					<span class="iaq-timer-label"><?php esc_html_e( 'Time Left', 'indianagri-quiz-engine' ); ?></span>
					<span class="iaq-timer-digits" id="iaq-timer-digits">--:--</span>
				</div>
			</div>

			<!-- Status indicator -->
			<div class="iaq-auto-save-tag" id="iaq-live-save-status">
				<span class="iaq-save-dot"></span>
				<span class="iaq-save-text"><?php esc_html_e( 'Synchronized', 'indianagri-quiz-engine' ); ?></span>
			</div>
		</div>
	</header>

	<!-- Celebration Toast Overlay (Practice Streaks) -->
	<div class="iaq-celebration-container" id="iaq-celebration-container" aria-live="assertive"></div>

	<!-- Examination Layout (Workspace + Palette) -->
	<div class="iaq-exam-body">
		<!-- Left: Question Workspace -->
		<section class="iaq-exam-workspace">
			
			<!-- Practice Mode Progress & Streak Dashboard (Practice Sets Only) -->
			<div class="iaq-practice-dashboard" id="iaq-practice-dashboard" style="<?php echo $is_practice_mode ? 'display:flex;' : 'display:none;'; ?>" role="region" aria-label="<?php esc_attr_e( 'Practice Session Statistics', 'indianagri-quiz-engine' ); ?>">
				<div class="iaq-dash-item">
					<span class="iaq-dash-label"><?php esc_html_e( 'Attempted', 'indianagri-quiz-engine' ); ?></span>
					<span class="iaq-dash-value" id="iaq-dash-attempted">0 / 0</span>
				</div>
				<div class="iaq-dash-item is-correct-stat">
					<span class="iaq-dash-label"><?php esc_html_e( 'Correct', 'indianagri-quiz-engine' ); ?></span>
					<span class="iaq-dash-value" id="iaq-dash-correct">0</span>
				</div>
				<div class="iaq-dash-item is-incorrect-stat">
					<span class="iaq-dash-label"><?php esc_html_e( 'Incorrect', 'indianagri-quiz-engine' ); ?></span>
					<span class="iaq-dash-value" id="iaq-dash-incorrect">0</span>
				</div>
				<div class="iaq-dash-item is-streak-stat">
					<span class="iaq-dash-label"><?php esc_html_e( 'Streak', 'indianagri-quiz-engine' ); ?></span>
					<span class="iaq-dash-value" id="iaq-dash-streak">🔥 0</span>
				</div>
				<div class="iaq-dash-item is-highest-streak-stat">
					<span class="iaq-dash-label"><?php esc_html_e( 'Best Streak', 'indianagri-quiz-engine' ); ?></span>
					<span class="iaq-dash-value" id="iaq-dash-highest-streak">🏆 0</span>
				</div>
				<div class="iaq-dash-item is-accuracy-stat">
					<span class="iaq-dash-label"><?php esc_html_e( 'Accuracy', 'indianagri-quiz-engine' ); ?></span>
					<span class="iaq-dash-value" id="iaq-dash-accuracy">0.0%</span>
				</div>
			</div>

			<!-- Question Navigation Info Bar -->
			<div class="iaq-question-meta-bar">
				<div class="iaq-q-tracker">
					<span class="iaq-q-num-label"><?php esc_html_e( 'Question', 'indianagri-quiz-engine' ); ?></span>
					<span class="iaq-q-current" id="iaq-current-q-index">1</span>
					<span class="iaq-q-divider">/</span>
					<span class="iaq-q-total" id="iaq-total-q-count"><?php echo esc_html( $quiz->total_questions ); ?></span>
				</div>

				<div class="iaq-q-marks-pill">
					<span class="iaq-marks-pos">+<span id="iaq-q-pos-marks"><?php echo esc_html( $quiz->correct_marks ); ?></span></span>
					<span class="iaq-marks-neg">-<span id="iaq-q-neg-marks"><?php echo esc_html( $quiz->negative_marks ); ?></span></span>
				</div>
			</div>

			<!-- Question Text Card -->
			<div class="iaq-question-card">
				<div class="iaq-question-content" id="iaq-question-text">
					<!-- Injected dynamically via JS -->
					<div class="iaq-skeleton-line"></div>
					<div class="iaq-skeleton-line" style="width: 80%;"></div>
				</div>
			</div>

			<!-- Options List -->
			<div class="iaq-options-container" id="iaq-options-list">
				<!-- Injected dynamically via JS -->
			</div>

			<!-- Immediate Feedback & Scientific Explanation (Practice Mode) -->
			<div class="iaq-immediate-feedback-box" id="iaq-immediate-feedback-box" style="display:none;" role="region" aria-live="polite">
				<!-- Injected dynamically via JS -->
			</div>

			<!-- Bottom Actions Bar -->
			<footer class="iaq-exam-actions-bar">
				<div class="iaq-actions-left">
					<button type="button" class="iaq-btn-action iaq-btn-prev" id="iaq-action-prev">
						&larr; <?php esc_html_e( 'Previous', 'indianagri-quiz-engine' ); ?>
					</button>
					<button type="button" class="iaq-btn-action iaq-btn-clear" id="iaq-action-clear">
						<?php esc_html_e( 'Clear Selection', 'indianagri-quiz-engine' ); ?>
					</button>
				</div>
				<div class="iaq-actions-right">
					<button type="button" class="iaq-btn-action iaq-btn-mark" id="iaq-action-mark">
						<svg class="iaq-icon iaq-icon-flag" viewBox="0 0 20 20" fill="currentColor" width="14" height="14" aria-hidden="true" style="vertical-align:-2px;margin-right:4px;"><path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd" /></svg> <?php esc_html_e( 'Mark for Review & Next', 'indianagri-quiz-engine' ); ?>
					</button>
					<button type="button" class="iaq-btn-action iaq-btn-save-next" id="iaq-action-next">
						<?php esc_html_e( 'Save & Next', 'indianagri-quiz-engine' ); ?> &rarr;
					</button>
				</div>
			</footer>

		</section>

		<!-- Right: Question Palette Sidebar -->
		<aside class="iaq-exam-palette-sidebar">
			<div class="iaq-palette-panel">
				
				<div class="iaq-palette-head">
					<h3 class="iaq-palette-title"><?php esc_html_e( 'Question Palette', 'indianagri-quiz-engine' ); ?></h3>
				</div>

				<!-- Status Legend & Counters -->
				<div class="iaq-palette-legend">
					<div class="iaq-legend-item">
						<span class="iaq-legend-dot is-answered" id="iaq-count-answered">0</span>
						<span class="iaq-legend-name"><?php esc_html_e( 'Answered', 'indianagri-quiz-engine' ); ?></span>
					</div>
					<div class="iaq-legend-item">
						<span class="iaq-legend-dot is-unanswered" id="iaq-count-unanswered">0</span>
						<span class="iaq-legend-name"><?php esc_html_e( 'Not Answered', 'indianagri-quiz-engine' ); ?></span>
					</div>
					<div class="iaq-legend-item">
						<span class="iaq-legend-dot is-not-visited" id="iaq-count-not-visited">0</span>
						<span class="iaq-legend-name"><?php esc_html_e( 'Not Visited', 'indianagri-quiz-engine' ); ?></span>
					</div>
					<div class="iaq-legend-item iaq-legend-marked" style="<?php echo $is_practice_mode ? 'display:none;' : ''; ?>">
						<span class="iaq-legend-dot is-marked" id="iaq-count-marked">0</span>
						<span class="iaq-legend-name"><?php esc_html_e( 'Marked Review', 'indianagri-quiz-engine' ); ?></span>
					</div>
					<div class="iaq-legend-item iaq-legend-ans-marked" style="<?php echo $is_practice_mode ? 'display:none;' : ''; ?>">
						<span class="iaq-legend-dot is-ans-marked" id="iaq-count-ans-marked">0</span>
						<span class="iaq-legend-name"><?php esc_html_e( 'Answered & Marked', 'indianagri-quiz-engine' ); ?></span>
					</div>
				</div>

				<!-- Question Numbers Grid -->
				<div class="iaq-palette-grid-wrapper">
					<div class="iaq-palette-grid" id="iaq-palette-grid">
						<!-- Injected dynamically via JS -->
					</div>
				</div>

				<!-- Final Submit Button -->
				<div class="iaq-palette-submit-bar">
					<button type="button" class="iaq-btn-finalize-submit" id="iaq-btn-open-submit">
						<svg class="iaq-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true" style="vertical-align:-2px;margin-right:6px;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg> <span id="iaq-submit-btn-label"><?php echo $is_practice_mode ? esc_html__( 'Finish Practice', 'indianagri-quiz-engine' ) : esc_html__( 'Submit Examination', 'indianagri-quiz-engine' ); ?></span>
					</button>
				</div>

			</div>
		</aside>
	</div>

	<!-- Submit Confirmation Modal -->
	<div class="iaq-modal-overlay" id="iaq-submit-modal" style="display:none;">
		<div class="iaq-modal-dialog">
			<div class="iaq-modal-header">
				<h3 class="iaq-modal-title"><?php esc_html_e( 'Submit Examination Confirmation', 'indianagri-quiz-engine' ); ?></h3>
				<button type="button" class="iaq-modal-close" id="iaq-modal-cancel-btn">&times;</button>
			</div>
			<div class="iaq-modal-body">
				<p class="iaq-modal-lead"><?php esc_html_e( 'Are you sure you want to finish and submit your test? Here is your summary:', 'indianagri-quiz-engine' ); ?></p>
				
				<table class="iaq-summary-table">
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'Total Questions', 'indianagri-quiz-engine' ); ?>:</strong></td>
							<td class="iaq-sum-val" id="iaq-modal-total-q">0</td>
						</tr>
						<tr>
							<td><span class="iaq-sum-color-answered">●</span> <?php esc_html_e( 'Answered', 'indianagri-quiz-engine' ); ?>:</td>
							<td class="iaq-sum-val" id="iaq-modal-answered-q">0</td>
						</tr>
						<tr>
							<td><span class="iaq-sum-color-unanswered">●</span> <?php esc_html_e( 'Not Answered', 'indianagri-quiz-engine' ); ?>:</td>
							<td class="iaq-sum-val" id="iaq-modal-unanswered-q">0</td>
						</tr>
						<tr>
							<td><span class="iaq-sum-color-not-visited">●</span> <?php esc_html_e( 'Not Visited', 'indianagri-quiz-engine' ); ?>:</td>
							<td class="iaq-sum-val" id="iaq-modal-not-visited-q">0</td>
						</tr>
						<tr>
							<td><span class="iaq-sum-color-marked">●</span> <?php esc_html_e( 'Marked for Review', 'indianagri-quiz-engine' ); ?>:</td>
							<td class="iaq-sum-val" id="iaq-modal-marked-q">0</td>
						</tr>
						<tr>
							<td><span class="iaq-sum-color-ans-marked">●</span> <?php esc_html_e( 'Answered & Marked for Review', 'indianagri-quiz-engine' ); ?>:</td>
							<td class="iaq-sum-val" id="iaq-modal-ans-marked-q">0</td>
						</tr>
					</tbody>
				</table>

				<div class="iaq-modal-alert" id="iaq-modal-warning" style="display:none;">
					<svg class="iaq-icon" viewBox="0 0 20 20" fill="currentColor" width="18" height="18" aria-hidden="true" style="vertical-align:-2px;margin-right:6px;"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
					<span><?php esc_html_e( 'Warning: You still have unattempted questions remaining!', 'indianagri-quiz-engine' ); ?></span>
				</div>
			</div>
			<div class="iaq-modal-footer">
				<button type="button" class="iaq-btn-cancel-modal" id="iaq-modal-return-btn">
					<?php esc_html_e( 'Return to Test', 'indianagri-quiz-engine' ); ?>
				</button>
				<button type="button" class="iaq-btn-confirm-submit" id="iaq-modal-confirm-submit">
					<?php esc_html_e( 'Yes, Submit Final Test', 'indianagri-quiz-engine' ); ?> &rarr;
				</button>
			</div>
		</div>
	</div>

	<!-- Time Expired Modal -->
	<div class="iaq-modal-overlay" id="iaq-timeout-modal" style="display:none;">
		<div class="iaq-modal-dialog">
			<div class="iaq-modal-header" style="background:#dc2626;color:#ffffff;">
				<h3 class="iaq-modal-title" style="color:#ffffff;">
					<svg class="iaq-icon" viewBox="0 0 20 20" fill="currentColor" width="20" height="20" aria-hidden="true" style="vertical-align:-3px;margin-right:6px;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg> <?php esc_html_e( 'Time Expired!', 'indianagri-quiz-engine' ); ?>
				</h3>
			</div>
			<div class="iaq-modal-body" style="text-align:center;padding:30px 20px;">
				<div style="font-size:42px;margin-bottom:12px;">⏳</div>
				<h4 style="margin:0 0 8px 0;font-size:18px;color:#0f172a;"><?php esc_html_e( 'Your allocated time has ended.', 'indianagri-quiz-engine' ); ?></h4>
				<p style="color:#64748b;margin:0;"><?php esc_html_e( 'Your responses are being submitted automatically to calculate your final score.', 'indianagri-quiz-engine' ); ?></p>
			</div>
		</div>
	</div>

</div>

<!-- Results & Scorecard Portal (Hidden until submission complete) -->
<div class="iaq-portal-wrapper" id="iaq-results-portal" style="display:none;">
	<div class="iaq-results-card">
		<!-- Results Header -->
		<div class="iaq-results-header">
			<span class="iaq-pill iaq-pill-category" id="iaq-res-category"></span>
			<h2 class="iaq-results-title" id="iaq-res-title"></h2>
			<div class="iaq-verdict-badge" id="iaq-res-verdict"></div>
		</div>

		<!-- Key Statistics Grid -->
		<div class="iaq-score-matrix">
			<div class="iaq-stat-tile is-score">
				<span class="iaq-stat-label"><?php esc_html_e( 'Score Earned', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-big"><span id="iaq-res-score">0</span> <small>/ <span id="iaq-res-total-marks">0</span></small></span>
			</div>
			<div class="iaq-stat-tile is-percent">
				<span class="iaq-stat-label"><?php esc_html_e( 'Percentage', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-big"><span id="iaq-res-percent">0</span>%</span>
			</div>
			<div class="iaq-stat-tile is-accuracy">
				<span class="iaq-stat-label"><?php esc_html_e( 'Correct / Total', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-big"><span id="iaq-res-correct">0</span> / <span id="iaq-res-total-q">0</span></span>
			</div>
			<div class="iaq-stat-tile is-time">
				<span class="iaq-stat-label"><?php esc_html_e( 'Time Taken', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-big" id="iaq-res-time">--</span>
			</div>
			<div class="iaq-stat-tile is-air" id="iaq-res-air-tile" style="display:none;">
				<span class="iaq-stat-label"><?php esc_html_e( 'All India Rank', 'indianagri-quiz-engine' ); ?></span>
				<span class="iaq-stat-big" style="color:#0284c7;"><small style="font-weight:700;">AIR #</small><span id="iaq-res-air-rank">1</span> <small>/ <span id="iaq-res-air-cohort">1</span></small></span>
			</div>
		</div>

		<!-- Breakdown Chart / Counters -->
		<div class="iaq-breakdown-row">
			<div class="iaq-breakdown-item is-correct">
				<span class="iaq-bd-count" id="iaq-bd-correct">0</span>
				<span class="iaq-bd-label"><?php esc_html_e( 'Correct Answers', 'indianagri-quiz-engine' ); ?></span>
			</div>
			<div class="iaq-breakdown-item is-incorrect">
				<span class="iaq-bd-count" id="iaq-bd-incorrect">0</span>
				<span class="iaq-bd-label"><?php esc_html_e( 'Incorrect Answers', 'indianagri-quiz-engine' ); ?></span>
			</div>
			<div class="iaq-breakdown-item is-unattempted">
				<span class="iaq-bd-count" id="iaq-bd-unattempted">0</span>
				<span class="iaq-bd-label"><?php esc_html_e( 'Unattempted', 'indianagri-quiz-engine' ); ?></span>
			</div>
		</div>

		<!-- Subject & Topic Mastery Breakdown (Weak-Area Analytics) -->
		<div class="iaq-subject-analytics-card" id="iaq-subject-analytics-section">
			<div class="iaq-subject-analytics-header">
				<h3 class="iaq-analytics-title">
					<svg class="iaq-icon" viewBox="0 0 20 20" fill="currentColor" width="18" height="18" aria-hidden="true" style="vertical-align:-3px;margin-right:6px;"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/></svg> <?php esc_html_e( 'Domain & Subject-Wise Performance Analysis', 'indianagri-quiz-engine' ); ?>
				</h3>
				<span class="iaq-analytics-subtitle"><?php esc_html_e( 'Identify strong areas and topics needing revision', 'indianagri-quiz-engine' ); ?></span>
			</div>
			<div class="iaq-analytics-table-responsive">
				<table class="iaq-analytics-table" id="iaq-subject-table">
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
					<tbody id="iaq-subject-tbody">
						<!-- Injected dynamically via JS -->
					</tbody>
				</table>
			</div>
		</div>

		<!-- All India Rank List (AIR Leaderboard Table) -->
		<div class="iaq-air-leaderboard-card" id="iaq-air-leaderboard-section" style="display:none; margin: 24px 0 16px 0; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
			<div class="iaq-air-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; border-bottom: 2px solid #e0f2fe; padding-bottom: 12px;">
				<div>
					<h3 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
						<span>🏆</span> <?php esc_html_e( 'All India Rank List (AIR Leaderboard)', 'indianagri-quiz-engine' ); ?>
					</h3>
					<span style="font-size: 13px; color: #64748b;" id="iaq-air-subtitle">
						<?php esc_html_e( 'Live competitive ranking across India for this examination', 'indianagri-quiz-engine' ); ?>
					</span>
				</div>
				<div style="background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 6px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;">
					<span style="font-size: 12px; font-weight: 600; color: #0369a1;"><?php esc_html_e( 'Your Rank:', 'indianagri-quiz-engine' ); ?></span>
					<strong style="font-size: 15px; color: #0284c7;" id="iaq-res-air-badge">AIR #1</strong>
				</div>
			</div>

			<div class="iaq-analytics-table-responsive">
				<table class="iaq-analytics-table iaq-air-table" id="iaq-air-table">
					<thead>
						<tr>
							<th style="width: 90px; text-align: center;"><?php esc_html_e( 'AIR Rank', 'indianagri-quiz-engine' ); ?></th>
							<th><?php esc_html_e( 'Candidate', 'indianagri-quiz-engine' ); ?></th>
							<th style="text-align: center;"><?php esc_html_e( 'Score', 'indianagri-quiz-engine' ); ?></th>
							<th style="text-align: center;"><?php esc_html_e( 'Percentage', 'indianagri-quiz-engine' ); ?></th>
							<th style="text-align: center;"><?php esc_html_e( 'Time Taken', 'indianagri-quiz-engine' ); ?></th>
							<th style="text-align: center;"><?php esc_html_e( 'Badge', 'indianagri-quiz-engine' ); ?></th>
						</tr>
					</thead>
					<tbody id="iaq-air-tbody">
						<!-- Injected dynamically via JS -->
					</tbody>
				</table>
			</div>
		</div>

		<!-- Social Share Bar (WhatsApp & Telegram) -->
		<div class="iaq-share-bar" style="margin: 20px 0 14px 0; padding: 14px 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
			<div style="display:flex; align-items:center; gap:8px;">
				<span style="font-size:18px;">🎯</span>
				<strong style="font-size:14px; color:#1e293b;"><?php esc_html_e( 'Proud of your score? Challenge your friends & study groups!', 'indianagri-quiz-engine' ); ?></strong>
			</div>
			<div style="display:flex; gap:10px; flex-wrap:wrap;">
				<a href="#" class="iaq-share-btn is-whatsapp" id="iaq-res-share-whatsapp" target="_blank" rel="noopener noreferrer">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="#ffffff" style="vertical-align:-2px;margin-right:6px;"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
					<span><?php esc_html_e( 'Share on WhatsApp', 'indianagri-quiz-engine' ); ?></span>
				</a>
				<a href="#" class="iaq-share-btn is-telegram" id="iaq-res-share-telegram" target="_blank" rel="noopener noreferrer">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="#ffffff" style="vertical-align:-2px;margin-right:6px;"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.828.916z"/></svg>
					<span><?php esc_html_e( 'Share on Telegram', 'indianagri-quiz-engine' ); ?></span>
				</a>
			</div>
		</div>

		<!-- Actions Bar -->
		<div class="iaq-results-actions">
			<button type="button" class="iaq-btn-action-primary" id="iaq-res-review-btn">
				<svg class="iaq-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true" style="vertical-align:-2px;margin-right:6px;"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg> <?php esc_html_e( 'Review Detailed Solutions & Explanations', 'indianagri-quiz-engine' ); ?>
			</button>
			<button type="button" class="iaq-btn-print" id="iaq-res-print-btn">
				<svg class="iaq-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true" style="vertical-align:-2px;margin-right:6px;"><path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a1 1 0 001 1h8a1 1 0 001-1v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a1 1 0 00-1-1H6a1 1 0 00-1 1zm2 1h6v2H7V5zm8 6a1 1 0 11-2 0 1 1 0 012 0zm-8 2h6v3H7v-3z" clip-rule="evenodd" /></svg> <?php esc_html_e( 'Print Scorecard / Download PDF', 'indianagri-quiz-engine' ); ?>
			</button>
			<button type="button" class="iaq-btn-retake is-mistakes-only" id="iaq-res-retake-mistakes-btn" style="display:none; background:#dc2626; border-color:#dc2626; color:#ffffff;">
				<svg class="iaq-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true" style="vertical-align:-2px;margin-right:6px;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v4a1 1 0 001 1h2a1 1 0 100-2h-1V7z" clip-rule="evenodd" /></svg> <?php esc_html_e( 'Re-attempt Mistakes Only', 'indianagri-quiz-engine' ); ?> (<span id="iaq-res-mistakes-count">0</span>)
			</button>
			<button type="button" class="iaq-btn-retake" id="iaq-res-retake-btn">
				<svg class="iaq-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true" style="vertical-align:-2px;margin-right:6px;"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" /></svg> <?php esc_html_e( 'Retake Test', 'indianagri-quiz-engine' ); ?>
			</button>
			<a href="<?php echo esc_url( remove_query_arg( array( 'iaq_quiz_id', 'iaq_action', 'attempt_id' ), ( function_exists( 'get_permalink' ) && get_permalink() ? get_permalink() : '/' ) ) ); ?>" class="iaq-btn-back-list">
				&larr; <?php esc_html_e( 'Back to Student Dashboard', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>
	</div>
</div>

<!-- Detailed Solutions & Question Explanations Portal (Hidden until review requested) -->
<div class="iaq-portal-wrapper" id="iaq-solutions-portal" style="display:none;">
	<div class="iaq-solutions-wrapper">
		
		<!-- Solutions Header -->
		<div class="iaq-solutions-header">
			<div class="iaq-solutions-title-group">
				<span class="iaq-pill iaq-pill-category"><?php esc_html_e( 'Question Paper Review', 'indianagri-quiz-engine' ); ?></span>
				<h2 class="iaq-solutions-heading"><?php esc_html_e( 'Detailed Solutions & Explanations', 'indianagri-quiz-engine' ); ?></h2>
				<p class="iaq-solutions-subheading" id="iaq-sol-quiz-title"></p>
			</div>
			<div class="iaq-solutions-header-actions" style="display: flex; gap: 8px; align-items: center;">
				<button type="button" class="iaq-btn-print" id="iaq-sol-print-btn">
					<svg class="iaq-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true" style="vertical-align:-2px;margin-right:6px;"><path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a1 1 0 001 1h8a1 1 0 001-1v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a1 1 0 00-1-1H6a1 1 0 00-1 1zm2 1h6v2H7V5zm8 6a1 1 0 11-2 0 1 1 0 012 0zm-8 2h6v3H7v-3z" clip-rule="evenodd" /></svg> <?php esc_html_e( 'Print Solutions / Download PDF', 'indianagri-quiz-engine' ); ?>
				</button>
				<button type="button" class="iaq-btn-action-secondary" id="iaq-sol-back-btn">
					&larr; <?php esc_html_e( 'Return to Scorecard', 'indianagri-quiz-engine' ); ?>
				</button>
			</div>
		</div>

		<!-- Filter Tabs Bar -->
		<div class="iaq-filter-tabs-bar">
			<button type="button" class="iaq-filter-tab is-active" data-filter="all">
				<?php esc_html_e( 'All Questions', 'indianagri-quiz-engine' ); ?> (<span id="iaq-tab-count-all">0</span>)
			</button>
			<button type="button" class="iaq-filter-tab is-correct-tab" data-filter="correct">
				<span class="iaq-tab-dot is-correct-dot"></span> <?php esc_html_e( 'Correct', 'indianagri-quiz-engine' ); ?> (<span id="iaq-tab-count-correct">0</span>)
			</button>
			<button type="button" class="iaq-filter-tab is-incorrect-tab" data-filter="incorrect">
				<span class="iaq-tab-dot is-incorrect-dot"></span> <?php esc_html_e( 'Incorrect', 'indianagri-quiz-engine' ); ?> (<span id="iaq-tab-count-incorrect">0</span>)
			</button>
			<button type="button" class="iaq-filter-tab is-unattempted-tab" data-filter="unattempted">
				<span class="iaq-tab-dot is-unattempted-dot"></span> <?php esc_html_e( 'Unattempted', 'indianagri-quiz-engine' ); ?> (<span id="iaq-tab-count-unattempted">0</span>)
			</button>
		</div>

		<!-- Solutions List Container -->
		<div class="iaq-solutions-list" id="iaq-solutions-list">
			<!-- Injected dynamically via JS -->
		</div>

		<!-- Bottom Navigation Bar -->
		<div class="iaq-solutions-footer" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
			<button type="button" class="iaq-btn-action-secondary" id="iaq-sol-bottom-back-btn">
				&larr; <?php esc_html_e( 'Return to Scorecard', 'indianagri-quiz-engine' ); ?>
			</button>
			<button type="button" class="iaq-btn-print" id="iaq-sol-bottom-print-btn">
				<svg class="iaq-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16" aria-hidden="true" style="vertical-align:-2px;margin-right:6px;"><path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a1 1 0 001 1h8a1 1 0 001-1v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a1 1 0 00-1-1H6a1 1 0 00-1 1zm2 1h6v2H7V5zm8 6a1 1 0 11-2 0 1 1 0 012 0zm-8 2h6v3H7v-3z" clip-rule="evenodd" /></svg> <?php esc_html_e( 'Print Solutions / Download PDF', 'indianagri-quiz-engine' ); ?>
			</button>
		</div>

	</div>
</div>
