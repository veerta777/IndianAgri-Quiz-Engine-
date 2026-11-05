<?php
/**
 * Frontend: Individual Previous Year Question Paper (PYQ) Card Component.
 * Displays official examination year, session, blueprint specifications, access status, attempt state, and launch CTA.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $paper ) ) {
	return;
}

$pyq_year       = ! empty( $paper->pyq_year ) ? (int) $paper->pyq_year : (int) date( 'Y' );
$pyq_session    = ! empty( $paper->pyq_session ) ? trim( $paper->pyq_session ) : '';
$is_paid        = ( 'paid' === $paper->access_type );
$is_accessible  = isset( $paper->can_solve ) ? (bool) $paper->can_solve : ( ! $is_paid );
$attempt_st     = isset( $paper->attempt_status ) ? $paper->attempt_status : 'not_attempted';
$score_pct      = isset( $paper->latest_percentage ) ? (float) $paper->latest_percentage : null;
$score_val      = isset( $paper->latest_score ) ? (float) $paper->latest_score : null;
$is_exam_mode   = ! empty( $paper->is_exam_mode );

// Target attempt link
$attempt_url = add_query_arg(
	array( 'iaq_quiz_id' => $paper->id ),
	function_exists( 'get_permalink' ) ? get_permalink() : '/'
);
if ( isset( $_GET['page'] ) && strpos( sanitize_key( $_GET['page'] ), 'iaq-preview' ) !== false ) {
	$attempt_url = add_query_arg(
		array(
			'page'        => sanitize_key( $_GET['page'] ),
			'iaq_quiz_id' => $paper->id,
		),
		'/'
	);
}

$sub_plans_url = add_query_arg(
	array( 'page' => 'iaq-preview-subscription-plans' ),
	function_exists( 'get_permalink' ) ? get_permalink() : '/'
);
?>

<div class="iaq-mock-card iaq-pyq-card <?php echo $is_paid ? 'is-paid' : 'is-free'; ?> <?php echo ! $is_accessible ? 'is-locked' : ''; ?>" style="border-left: 4px solid <?php echo $is_paid ? '#8b5cf6' : '#10b981'; ?>;">
	
	<!-- Header Bar: Year Badge & Access Type -->
	<div class="iaq-mock-card-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:8px;flex-wrap:wrap;">
		<div class="iaq-pyq-year-badge" style="display:inline-flex;align-items:center;background:linear-gradient(135deg, #1e1b4b, #312e81);color:#ffffff;font-size:12px;font-weight:800;padding:4px 10px;border-radius:6px;letter-spacing:0.3px;">
			<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
			<span><?php echo esc_html( $pyq_year ); ?> <?php esc_html_e( 'Official Paper', 'indianagri-quiz-engine' ); ?></span>
			<?php if ( ! empty( $pyq_session ) ) : ?>
				<span style="opacity:0.75;margin-left:6px;font-weight:600;font-size:11px;">(<?php echo esc_html( $pyq_session ); ?>)</span>
			<?php endif; ?>
		</div>

		<div class="iaq-mock-badges-wrap" style="display:inline-flex;gap:6px;align-items:center;">
			<?php if ( $is_exam_mode ) : ?>
				<span class="iaq-pill" style="background:#f1f5f9;color:#334155;font-size:11px;font-weight:700;">
					<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
					<?php esc_html_e( 'Exam Mode', 'indianagri-quiz-engine' ); ?>
				</span>
			<?php else : ?>
				<span class="iaq-pill" style="background:#ecfdf5;color:#047857;font-size:11px;font-weight:700;">
					<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><path d="m9 12 2 2 4-4"/></svg>
					<?php esc_html_e( 'Practice Mode', 'indianagri-quiz-engine' ); ?>
				</span>
			<?php endif; ?>

			<?php if ( $is_paid ) : ?>
				<span class="iaq-pill iaq-pill-paid" style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:700;">
					<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px;"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
					<?php esc_html_e( 'Subscribers Only', 'indianagri-quiz-engine' ); ?>
				</span>
			<?php else : ?>
				<span class="iaq-pill iaq-pill-free" style="background:#e0f2fe;color:#0369a1;font-size:11px;font-weight:700;">
					<?php esc_html_e( 'Free PYQ', 'indianagri-quiz-engine' ); ?>
				</span>
			<?php endif; ?>
		</div>
	</div>

	<!-- Title & Description -->
	<div class="iaq-mock-card-body">
		<h4 class="iaq-mock-title" style="margin:0 0 8px;font-size:16px;line-height:1.4;">
			<a href="<?php echo esc_url( $attempt_url ); ?>" style="color:#0f172a;text-decoration:none;">
				<?php echo esc_html( $paper->title ); ?>
			</a>
		</h4>

		<?php if ( ! empty( $paper->instructions ) ) : ?>
			<p class="iaq-mock-desc" style="color:#64748b;font-size:13px;line-height:1.45;margin:0 0 14px;">
				<?php echo esc_html( wp_trim_words( $paper->instructions, 18 ) ); ?>
			</p>
		<?php endif; ?>

		<!-- Examination Specifications Grid -->
		<div class="iaq-mock-specs-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(80px, 1fr));gap:8px;background:#f8fafc;padding:10px 12px;border-radius:8px;margin-bottom:14px;">
			<div class="iaq-mock-spec-item" style="display:flex;flex-direction:column;align-items:center;text-align:center;">
				<strong style="color:#0f172a;font-size:14px;"><?php echo (int) $paper->total_questions; ?></strong>
				<span style="color:#64748b;font-size:11px;"><?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?></span>
			</div>

			<div class="iaq-mock-spec-item" style="display:flex;flex-direction:column;align-items:center;text-align:center;">
				<strong style="color:#0f172a;font-size:14px;"><?php echo floatval( $paper->total_marks ); ?></strong>
				<span style="color:#64748b;font-size:11px;"><?php esc_html_e( 'Marks', 'indianagri-quiz-engine' ); ?></span>
			</div>

			<div class="iaq-mock-spec-item" style="display:flex;flex-direction:column;align-items:center;text-align:center;">
				<strong style="color:#0f172a;font-size:14px;">
					<?php echo $paper->duration_minutes > 0 ? (int) $paper->duration_minutes . 'm' : __( 'Untimed', 'indianagri-quiz-engine' ); ?>
				</strong>
				<span style="color:#64748b;font-size:11px;"><?php esc_html_e( 'Duration', 'indianagri-quiz-engine' ); ?></span>
			</div>

			<div class="iaq-mock-spec-item" style="display:flex;flex-direction:column;align-items:center;text-align:center;">
				<strong style="color:#dc2626;font-size:14px;">
					<?php echo floatval( $paper->negative_marks ) > 0 ? '-' . floatval( $paper->negative_marks ) : __( 'None', 'indianagri-quiz-engine' ); ?>
				</strong>
				<span style="color:#64748b;font-size:11px;"><?php esc_html_e( 'Negative', 'indianagri-quiz-engine' ); ?></span>
			</div>
		</div>

		<!-- Attempt State / Score Bar -->
		<?php if ( 'completed' === $attempt_st ) : ?>
			<div class="iaq-mock-attempt-summary" style="margin-bottom:14px;padding:8px 12px;background:#ecfdf5;border-radius:6px;display:flex;justify-content:space-between;align-items:center;">
				<div style="font-size:12px;color:#065f46;font-weight:700;">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
					<?php esc_html_e( 'Attempt Completed', 'indianagri-quiz-engine' ); ?>
				</div>
				<div style="font-size:13px;font-weight:800;color:#047857;">
					<?php echo esc_html( $score_pct ); ?>% 
					<span style="font-size:11px;font-weight:500;color:#065f46;">(<?php echo esc_html( $score_val ); ?>/<?php echo floatval( $paper->total_marks ); ?>)</span>
				</div>
			</div>
		<?php elseif ( 'in_progress' === $attempt_st ) : ?>
			<div class="iaq-mock-attempt-summary" style="margin-bottom:14px;padding:8px 12px;background:#fffbeb;border-radius:6px;display:flex;justify-content:space-between;align-items:center;">
				<div style="font-size:12px;color:#92400e;font-weight:700;">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
					<?php esc_html_e( 'Session In Progress', 'indianagri-quiz-engine' ); ?>
				</div>
				<span style="font-size:11px;color:#b45309;font-weight:600;"><?php esc_html_e( 'Resume Paper', 'indianagri-quiz-engine' ); ?></span>
			</div>
		<?php endif; ?>

		<!-- Launch / Access Action -->
		<div class="iaq-mock-card-action">
			<?php if ( $is_accessible ) : ?>
				<?php if ( 'completed' === $attempt_st ) : ?>
					<div style="display:flex;gap:8px;">
						<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-btn iaq-btn-primary iaq-btn-exam" style="flex:1;background:#047857;border-color:#047857;">
							<?php esc_html_e( 'Re-attempt Paper', 'indianagri-quiz-engine' ); ?> &rarr;
						</a>
						<a href="<?php echo esc_url( add_query_arg( array( 'iaq_action' => 'review', 'iaq_quiz_id' => $paper->id ), $attempt_url ) ); ?>" class="iaq-btn iaq-btn-outline" style="padding:8px 12px;" title="<?php esc_attr_e( 'Review Answers & Explanations', 'indianagri-quiz-engine' ); ?>">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
						</a>
					</div>
				<?php elseif ( 'in_progress' === $attempt_st ) : ?>
					<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-btn iaq-btn-primary iaq-btn-exam iaq-full-width" style="background:#d97706;border-color:#d97706;">
						<?php esc_html_e( 'Resume PYQ Paper', 'indianagri-quiz-engine' ); ?> &rarr;
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( $attempt_url ); ?>" class="iaq-btn iaq-btn-primary iaq-btn-exam iaq-full-width" style="background:linear-gradient(135deg, #1e1b4b, #4338ca);border-color:#312e81;">
						<?php esc_html_e( 'Start PYQ Paper', 'indianagri-quiz-engine' ); ?> &rarr;
					</a>
				<?php endif; ?>
			<?php else : ?>
				<a href="<?php echo esc_url( $sub_plans_url ); ?>" class="iaq-btn iaq-btn-locked iaq-full-width" style="display:flex;justify-content:center;align-items:center;background:#f8fafc;border:1px dashed #cbd5e1;color:#64748b;font-weight:700;">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
					<?php esc_html_e( 'Unlock with Subscription', 'indianagri-quiz-engine' ); ?>
				</a>
			<?php endif; ?>
		</div>

	</div>

</div>
