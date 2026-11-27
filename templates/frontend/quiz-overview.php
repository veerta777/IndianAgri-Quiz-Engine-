<?php
/**
 * Frontend: Quiz Overview & Candidate Instructions Card.
 * Displayed via shortcode [iaq_quiz id="123"].
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $quiz ) || ! is_object( $quiz ) ) {
	return;
}

$types     = IAQ_Quiz_Builder::get_quiz_types();
$type_meta = isset( $types[ $quiz->quiz_type ] ) ? $types[ $quiz->quiz_type ] : array( 'label' => ucfirst( $quiz->quiz_type ), 'badge_color' => '#117E3A' );

$is_practice_mode = class_exists( 'IAQ_Quiz_Builder' ) ? IAQ_Quiz_Builder::is_practice_quiz( $quiz ) : ( 'mock' !== $quiz->quiz_type && 'previous_year' !== $quiz->quiz_type );

// Preload questions for instantaneous 0ms client-side CBT startup
$preload_payload = array(
	'quiz_id'           => (int) $quiz->id,
	'quiz_type'         => $quiz->quiz_type,
	'attempt_id'        => ( ! empty( $active_attempt ) && ! empty( $active_attempt->id ) ) ? (int) $active_attempt->id : ( 9999000 + (int) $quiz->id ),
	'questions'         => array(),
	'remaining_seconds' => ( (int) $quiz->duration_minutes > 0 ) ? ( (int) $quiz->duration_minutes * 60 ) : null,
	'is_immediate'      => $is_practice_mode,
	'show_answers_mode' => $is_practice_mode ? 'immediate' : $quiz->show_answers_mode,
);

if ( class_exists( 'IAQ_Quiz_Builder' ) ) {
	$raw_assigned = IAQ_Quiz_Builder::get_quiz_questions( $quiz->id );
	if ( empty( $raw_assigned ) && ! empty( $quiz->subject_id ) ) {
		global $wpdb;
		$t_q = $wpdb->prefix . 'iaq_questions';
		$raw_assigned = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t_q} WHERE subject_id = %d ORDER BY id ASC LIMIT 50", $quiz->subject_id ) );
	}
	if ( ! empty( $raw_assigned ) && ! empty( $mistake_question_ids ) && is_array( $mistake_question_ids ) ) {
		$raw_assigned = array_filter(
			$raw_assigned,
			function( $rq ) use ( $mistake_question_ids ) {
				return in_array( (int) $rq->id, $mistake_question_ids, true );
			}
		);
		$raw_assigned = array_values( $raw_assigned );
	}
	if ( ! empty( $raw_assigned ) ) {
		// Randomize Question Order (q no) across Practice Sets, Mock Tests, and PYQ Papers
		$should_shuffle_questions = ! isset( $quiz->shuffle_questions ) || (int) $quiz->shuffle_questions !== 0;
		if ( $should_shuffle_questions ) {
			$q_seed = ( ! empty( $active_attempt ) && ! empty( $active_attempt->id ) ) ? (int) $active_attempt->id : ( (int) $quiz->id * 1000 + mt_rand( 1000, 99999999 ) );
			mt_srand( $q_seed );
			shuffle( $raw_assigned );
			mt_srand();
		}

		$should_shuffle_options = ! isset( $quiz->shuffle_options ) || (int) $quiz->shuffle_options !== 0;
		$p_idx = 1;
		foreach ( $raw_assigned as $rq ) {
			$clean_fn = function( $txt ) {
				return class_exists( 'IAQ_Quiz_Attempt' ) ? IAQ_Quiz_Attempt::clean_content( $txt ) : trim( preg_replace( '/\s*\[cite:?\s*[^\]]+\]/i', '', $txt ) );
			};

			$opt_a = array( 'key' => 'A', 'text' => $clean_fn( $rq->option_a ) );
			$opt_b = array( 'key' => 'B', 'text' => $clean_fn( $rq->option_b ) );
			$opt_c = array( 'key' => 'C', 'text' => $clean_fn( $rq->option_c ) );
			$opt_d = array( 'key' => 'D', 'text' => $clean_fn( $rq->option_d ) );
			if ( ! empty( $rq->option_a_hi ) ) $opt_a['text_hi'] = $clean_fn( $rq->option_a_hi );
			if ( ! empty( $rq->option_b_hi ) ) $opt_b['text_hi'] = $clean_fn( $rq->option_b_hi );
			if ( ! empty( $rq->option_c_hi ) ) $opt_c['text_hi'] = $clean_fn( $rq->option_c_hi );
			if ( ! empty( $rq->option_d_hi ) ) $opt_d['text_hi'] = $clean_fn( $rq->option_d_hi );

			$opts = array( $opt_a, $opt_b, $opt_c, $opt_d );

			// Randomize Option Order (A, B, C, D) for every question
			if ( $should_shuffle_options ) {
				$opt_seed = ( ! empty( $active_attempt ) && ! empty( $active_attempt->id ) ) ? (int) ( $active_attempt->id * 1000 + $rq->id ) : ( (int) $rq->id * 100 + mt_rand( 100, 999999 ) );
				mt_srand( $opt_seed );
				shuffle( $opts );
				mt_srand();
			}

			$q_payload = array(
				'index'            => $p_idx++,
				'id'               => (int) $rq->id,
				'question_text'    => $clean_fn( $rq->question_text ),
				'question_text_hi' => ! empty( $rq->question_text_hi ) ? $clean_fn( $rq->question_text_hi ) : '',
				'subject_name'     => isset( $rq->subject_name ) ? $rq->subject_name : '',
				'topic_name'       => isset( $rq->topic_name ) ? $rq->topic_name : '',
				'marks'            => (float) $rq->marks,
				'negative_marks'   => (float) $rq->negative_marks,
				'option_a'         => $clean_fn( $rq->option_a ),
				'option_b'         => $clean_fn( $rq->option_b ),
				'option_c'         => $clean_fn( $rq->option_c ),
				'option_d'         => $clean_fn( $rq->option_d ),
				'option_a_hi'      => ! empty( $rq->option_a_hi ) ? $clean_fn( $rq->option_a_hi ) : '',
				'option_b_hi'      => ! empty( $rq->option_b_hi ) ? $clean_fn( $rq->option_b_hi ) : '',
				'option_c_hi'      => ! empty( $rq->option_c_hi ) ? $clean_fn( $rq->option_c_hi ) : '',
				'option_d_hi'      => ! empty( $rq->option_d_hi ) ? $clean_fn( $rq->option_d_hi ) : '',
				'options'          => $opts,
				'selected'         => null,
				'is_marked'        => 0,
			);

			if ( $is_practice_mode ) {
				$q_payload['correct_option'] = strtoupper( trim( $rq->correct_option ) );
				$q_payload['explanation']    = class_exists( 'IAQ_Quiz_Attempt' ) ? IAQ_Quiz_Attempt::clean_explanation( $rq->explanation ) : $rq->explanation;
			}

			$preload_payload['questions'][] = $q_payload;
		}
	}
}
?>

<script type="application/json" id="iaq-preloaded-quiz-<?php echo esc_attr( $quiz->id ); ?>">
<?php echo wp_json_encode( $preload_payload ); ?>
</script>

<div class="iaq-exam-card" id="iaq-quiz-overview-<?php echo esc_attr( $quiz->id ); ?>">
	<!-- Header -->
	<div class="iaq-exam-header">
		<div class="iaq-exam-badges">
			<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $quiz->exam_category ); ?></span>
			<span class="iaq-pill iaq-pill-type" style="background: <?php echo esc_attr( $type_meta['badge_color'] ); ?>15; color: <?php echo esc_attr( $type_meta['badge_color'] ); ?>;">
				<?php echo esc_html( $type_meta['label'] ); ?>
			</span>
			<?php if ( 'paid' === $quiz->access_type ) : ?>
				<span class="iaq-pill iaq-pill-paid"><?php esc_html_e( 'Premium Test', 'indianagri-quiz-engine' ); ?></span>
			<?php else : ?>
				<span class="iaq-pill iaq-pill-free"><?php esc_html_e( 'Free Test', 'indianagri-quiz-engine' ); ?></span>
			<?php endif; ?>
		</div>
		<h2 class="iaq-exam-title"><?php echo esc_html( $quiz->title ); ?></h2>
		<?php if ( ! empty( $quiz->description ) ) : ?>
			<div class="iaq-exam-description"><?php echo wp_kses_post( wpautop( $quiz->description ) ); ?></div>
		<?php endif; ?>
	</div>

	<!-- Parameters Matrix -->
	<div class="iaq-param-grid">
		<div class="iaq-param-box">
			<span class="iaq-param-label"><?php esc_html_e( 'Total Questions', 'indianagri-quiz-engine' ); ?></span>
			<span class="iaq-param-val"><?php echo ! empty( $mistake_question_ids ) && ! empty( $raw_assigned ) ? count( $raw_assigned ) : esc_html( $quiz->total_questions ); ?></span>
		</div>
		<div class="iaq-param-box">
			<span class="iaq-param-label"><?php esc_html_e( 'Total Marks', 'indianagri-quiz-engine' ); ?></span>
			<span class="iaq-param-val"><?php echo ! empty( $mistake_question_ids ) && ! empty( $raw_assigned ) ? number_format( (float) count( $raw_assigned ) * (float) $quiz->correct_marks, 2 ) : esc_html( $quiz->total_marks ); ?></span>
		</div>
		<div class="iaq-param-box">
			<span class="iaq-param-label"><?php esc_html_e( 'Duration', 'indianagri-quiz-engine' ); ?></span>
			<span class="iaq-param-val">
				<?php echo ( $quiz->duration_minutes > 0 ) ? sprintf( esc_html__( '%d Mins', 'indianagri-quiz-engine' ), $quiz->duration_minutes ) : esc_html__( 'Untimed', 'indianagri-quiz-engine' ); ?>
			</span>
		</div>
		<div class="iaq-param-box">
			<span class="iaq-param-label"><?php esc_html_e( 'Marking Scheme', 'indianagri-quiz-engine' ); ?></span>
			<span class="iaq-param-val iaq-val-marking">
				+<?php echo esc_html( $quiz->correct_marks ); ?> / -<?php echo esc_html( $quiz->negative_marks ); ?>
			</span>
		</div>
		<div class="iaq-param-box">
			<span class="iaq-param-label"><?php esc_html_e( 'Pass Score', 'indianagri-quiz-engine' ); ?></span>
			<span class="iaq-param-val"><?php echo esc_html( $quiz->pass_percentage ); ?>%</span>
		</div>
		<div class="iaq-param-box">
			<span class="iaq-param-label"><?php esc_html_e( 'Attempts Allowed', 'indianagri-quiz-engine' ); ?></span>
			<span class="iaq-param-val">
				<?php echo ( $quiz->max_attempts > 0 ) ? esc_html( $quiz->max_attempts ) : esc_html__( 'Unlimited', 'indianagri-quiz-engine' ); ?>
			</span>
		</div>
	</div>

	<!-- Instructions -->
	<?php if ( ! empty( $quiz->instructions ) ) : ?>
		<div class="iaq-instructions-card">
			<h4 class="iaq-instructions-title">
				<span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Examination Instructions & Guidelines', 'indianagri-quiz-engine' ); ?>
			</h4>
			<div class="iaq-instructions-content">
				<?php echo wp_kses_post( wpautop( $quiz->instructions ) ); ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- CTA Bar -->
	<div class="iaq-start-bar">
		<?php if ( ! is_user_logged_in() ) : ?>
			<?php if ( $is_practice_mode && 'paid' !== $quiz->access_type ) : ?>
				<div class="iaq-cta-actions">
					<button type="button" class="iaq-btn-start" id="iaq-start-quiz-btn" data-quiz-id="<?php echo esc_attr( $quiz->id ); ?>">
						<?php esc_html_e( 'Start Practice Now', 'indianagri-quiz-engine' ); ?> &rarr;
					</button>
				</div>
			<?php else : ?>
				<div class="iaq-login-alert">
					<?php
					printf(
						/* translators: %s: login url */
						wp_kses_post( __( 'Please <a href="%s">log in to your account</a> to attempt this examination.', 'indianagri-quiz-engine' ) ),
						esc_url( wp_login_url( get_permalink() ) )
					);
					?>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<?php
			$user_id     = get_current_user_id();
			$entitlement = IAQ_Access_Control::check_user_entitlement( $user_id, $quiz );
			?>
			<?php if ( ! $entitlement['allowed'] ) : ?>
				<div class="iaq-locked-card" style="padding: 24px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; text-align: center; width: 100%;">
					<div style="font-size: 32px; color: #dc2626; margin-bottom: 8px;">🔒</div>
					<h3 style="margin: 0 0 6px 0; color: #991b1b; font-size: 18px;"><?php esc_html_e( 'Premium Examination — Subscription Required', 'indianagri-quiz-engine' ); ?></h3>
					<p style="margin: 0 0 16px 0; color: #7f1d1d; font-size: 14px; max-width: 600px; margin-left: auto; margin-right: auto;">
						<?php echo esc_html( $entitlement['reason'] ); ?>
					</p>
					<a href="<?php echo esc_url( apply_filters( 'iaq_pricing_page_url', '?page=iaq-preview-subscription-plans' ) ); ?>" class="iaq-btn-start" style="display:inline-block; text-decoration:none; background:#117E3A; color:#fff;">
						<span class="dashicons dashicons-cart" style="vertical-align:middle;margin-right:4px;"></span>
						<?php esc_html_e( 'Explore Subscription Plans & Unlock', 'indianagri-quiz-engine' ); ?> &rarr;
					</a>
				</div>
			<?php else : ?>
				<div class="iaq-cta-actions">
					<?php if ( ! empty( $active_attempt ) ) : ?>
						<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
							<span style="background:#fef3c7;color:#b45309;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:700;">
								<span class="dashicons dashicons-clock" style="font-size:14px;vertical-align:middle;"></span>
								<?php esc_html_e( 'Attempt in progress', 'indianagri-quiz-engine' ); ?>
							</span>
							<button type="button" class="iaq-btn-start" id="iaq-start-quiz-btn" data-quiz-id="<?php echo esc_attr( $quiz->id ); ?>" style="background:#b45309;">
								<?php esc_html_e( 'Resume Examination Now', 'indianagri-quiz-engine' ); ?> &rarr;
							</button>
						</div>
					<?php elseif ( ! empty( $mistake_question_ids ) && ! empty( $raw_assigned ) ) : ?>
						<div style="width:100%; margin-bottom:16px; padding:14px 18px; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
							<div>
								<span style="display:inline-block; background:#fee2e2; color:#991b1b; font-weight:700; font-size:12px; padding:2px 8px; border-radius:4px; margin-right:6px;">
									<?php esc_html_e( 'MISTAKES DRILL', 'indianagri-quiz-engine' ); ?>
								</span>
								<strong style="color:#991b1b; font-size:14px;">
									<?php printf( esc_html__( 'Targeted Drill: Re-attempting %d weak questions', 'indianagri-quiz-engine' ), count( $raw_assigned ) ); ?>
								</strong>
							</div>
							<div>
								<a href="<?php echo esc_url( add_query_arg( array( 'iaq_quiz_id' => $quiz->id, 'iaq_action' => 'retake' ) ) ); ?>" class="iaq-btn-outline" style="text-decoration:none; padding:8px 14px; font-size:12px;">
									<?php esc_html_e( 'Switch to Full Test', 'indianagri-quiz-engine' ); ?>
								</a>
							</div>
						</div>
						<button type="button" class="iaq-btn-start" id="iaq-start-quiz-btn" data-quiz-id="<?php echo esc_attr( $quiz->id ); ?>" style="background:#dc2626; border-color:#dc2626;">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:6px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
							<?php printf( esc_html__( 'Start Mistakes Drill Now (%d Questions)', 'indianagri-quiz-engine' ), count( $raw_assigned ) ); ?> &rarr;
						</button>
					<?php elseif ( ! empty( $completed_attempt ) ) : ?>
						<div style="width:100%; margin-bottom:16px; padding:14px 18px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
							<div>
								<span style="display:inline-block; background:#dcfce7; color:#166534; font-weight:700; font-size:12px; padding:2px 8px; border-radius:4px; margin-right:6px;">
									<?php echo ( ! empty( $completed_attempt->passed ) ) ? esc_html__( 'PASSED', 'indianagri-quiz-engine' ) : esc_html__( 'COMPLETED', 'indianagri-quiz-engine' ); ?>
								</span>
								<strong style="color:#0f172a; font-size:14px;">
									<?php printf( esc_html__( 'Previous Score: %s / %s (%s%%)', 'indianagri-quiz-engine' ), esc_html( $completed_attempt->score ), esc_html( $completed_attempt->total_marks ), esc_html( $completed_attempt->percentage ) ); ?>
								</strong>
							</div>
							<div>
								<a href="<?php echo esc_url( add_query_arg( array( 'iaq_quiz_id' => $quiz->id, 'iaq_action' => 'view_result', 'attempt_id' => $completed_attempt->id ) ) ); ?>" class="iaq-btn-start" style="text-decoration:none; padding:8px 16px; font-size:13px; background:#16a34a; color:#fff; display:inline-flex; align-items:center; gap:6px;">
									<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
									<?php esc_html_e( 'View Result & Solutions', 'indianagri-quiz-engine' ); ?>
								</a>
							</div>
						</div>
						<button type="button" class="iaq-btn-start" id="iaq-start-quiz-btn" data-quiz-id="<?php echo esc_attr( $quiz->id ); ?>">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:6px;"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
							<?php esc_html_e( 'Retake Examination', 'indianagri-quiz-engine' ); ?> &rarr;
						</button>
					<?php else : ?>
						<button type="button" class="iaq-btn-start" id="iaq-start-quiz-btn" data-quiz-id="<?php echo esc_attr( $quiz->id ); ?>">
							<?php esc_html_e( 'Start Examination Now', 'indianagri-quiz-engine' ); ?> &rarr;
						</button>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
