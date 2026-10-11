<?php
/**
 * Quiz Attempt Engine: Session Lifecycle, Real-Time Heartbeat, and Server-Side Scoring.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IAQ_Quiz_Attempt
 */
class IAQ_Quiz_Attempt {

	/**
	 * Initialize AJAX hooks for student quiz attempts.
	 */
	public function init() {
		// Logged in users.
		add_action( 'wp_ajax_iaq_start_quiz_attempt', array( $this, 'ajax_start_or_resume_attempt' ) );
		add_action( 'wp_ajax_iaq_save_answer', array( $this, 'ajax_save_answer' ) );
		add_action( 'wp_ajax_iaq_submit_quiz_attempt', array( $this, 'ajax_submit_quiz_attempt' ) );
		add_action( 'wp_ajax_iaq_get_attempt_review', array( $this, 'ajax_get_attempt_review' ) );

		// Non-logged in users (returns auth error).
		add_action( 'wp_ajax_nopriv_iaq_start_quiz_attempt', array( $this, 'ajax_unauthorized' ) );
		add_action( 'wp_ajax_nopriv_iaq_save_answer', array( $this, 'ajax_unauthorized' ) );
		add_action( 'wp_ajax_nopriv_iaq_submit_quiz_attempt', array( $this, 'ajax_unauthorized' ) );
		add_action( 'wp_ajax_nopriv_iaq_get_attempt_review', array( $this, 'ajax_unauthorized' ) );
	}

	/**
	 * Send JSON unauthorized error for guests.
	 */
	public function ajax_unauthorized() {
		wp_send_json_error(
			array(
				'message' => __( 'Authentication required. Please log in to attempt this test.', 'indianagri-quiz-engine' ),
				'code'    => 'auth_required',
			),
			401
		);
	}

	/**
	 * AJAX: Start or Resume an active attempt session.
	 */
	public function ajax_start_or_resume_attempt() {
		check_ajax_referer( 'iaq_quiz_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			$this->ajax_unauthorized();
		}

		$quiz_id = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0;
		$user_id = get_current_user_id();

		if ( ! $quiz_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Quiz ID.', 'indianagri-quiz-engine' ) ) );
		}

		$session = self::start_or_resume_attempt( $quiz_id, $user_id );
		if ( is_wp_error( $session ) ) {
			wp_send_json_error( array( 'message' => $session->get_error_message() ) );
		}

		wp_send_json_success( $session );
	}

	/**
	 * AJAX: Save or clear a student's answer selection.
	 */
	public function ajax_save_answer() {
		check_ajax_referer( 'iaq_quiz_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			$this->ajax_unauthorized();
		}

		$attempt_id      = isset( $_POST['attempt_id'] ) ? absint( $_POST['attempt_id'] ) : 0;
		$question_id     = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;
		$selected_option = isset( $_POST['selected_option'] ) ? sanitize_text_field( wp_unslash( $_POST['selected_option'] ) ) : '';
		$is_marked       = ! empty( $_POST['is_marked'] ) ? 1 : 0;
		$time_spent      = isset( $_POST['time_spent'] ) ? absint( $_POST['time_spent'] ) : 0;
		$user_id         = get_current_user_id();

		$result = self::save_answer( $attempt_id, $user_id, $question_id, $selected_option, $is_marked, $time_spent );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: Final submission of examination attempt.
	 */
	public function ajax_submit_quiz_attempt() {
		check_ajax_referer( 'iaq_quiz_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			$this->ajax_unauthorized();
		}

		$attempt_id = isset( $_POST['attempt_id'] ) ? absint( $_POST['attempt_id'] ) : 0;
		$time_spent = isset( $_POST['time_spent'] ) ? absint( $_POST['time_spent'] ) : 0;
		$user_id    = get_current_user_id();

		$result = self::submit_attempt( $attempt_id, $user_id, $time_spent );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: Fetch detailed solutions and topic mastery breakdown for post-exam review.
	 */
	public function ajax_get_attempt_review() {
		check_ajax_referer( 'iaq_quiz_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			$this->ajax_unauthorized();
		}

		$attempt_id = isset( $_REQUEST['attempt_id'] ) ? absint( $_REQUEST['attempt_id'] ) : 0;
		$user_id    = get_current_user_id();

		if ( ! $attempt_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Attempt ID.', 'indianagri-quiz-engine' ) ) );
		}

		$scorecard = self::get_attempt_scorecard( $attempt_id, $user_id );
		if ( is_wp_error( $scorecard ) ) {
			wp_send_json_error( array( 'message' => $scorecard->get_error_message() ) );
		}

		$questions = self::get_attempt_review_questions( $attempt_id, $user_id );
		$breakdown = self::get_attempt_subject_breakdown( $attempt_id, $user_id );

		wp_send_json_success(
			array(
				'scorecard'         => $scorecard,
				'questions'         => $questions,
				'subject_breakdown' => $breakdown,
			)
		);
	}

	/**
	 * Core Logic: Start a new attempt or resume an existing in-progress session.
	 *
	 * @param int $quiz_id Quiz ID.
	 * @param int $user_id WordPress User ID.
	 * @return array|WP_Error
	 */
	public static function start_or_resume_attempt( $quiz_id, $user_id ) {
		global $wpdb;

		$quiz = IAQ_Quiz_Builder::get_quiz( $quiz_id );
		if ( ! $quiz || ( 'published' !== $quiz->status && ! current_user_can( 'manage_options' ) ) ) {
			return new WP_Error( 'quiz_not_found', __( 'Quiz not found or not published.', 'indianagri-quiz-engine' ) );
		}

		// Central Entitlement Verification (Phase 6 Access Control).
		$entitlement = IAQ_Access_Control::check_user_entitlement( $user_id, $quiz );
		if ( ! $entitlement['allowed'] ) {
			return new WP_Error(
				'access_denied',
				! empty( $entitlement['reason'] ) ? $entitlement['reason'] : __( 'This is a premium examination. An active subscription is required to take this test.', 'indianagri-quiz-engine' )
			);
		}

		$table_attempts = $wpdb->prefix . 'iaq_quiz_attempts';
		$table_answers  = $wpdb->prefix . 'iaq_attempt_answers';

		// Check for an existing in-progress attempt.
		$active_attempt = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_attempts} WHERE quiz_id = %d AND user_id = %d AND status = 'in_progress' ORDER BY id DESC LIMIT 1",
				$quiz_id,
				$user_id
			)
		);

		if ( $active_attempt ) {
			$attempt = $active_attempt;
		} else {
			// Check Max Attempts limit.
			if ( $quiz->max_attempts > 0 ) {
				$completed_count = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(id) FROM {$table_attempts} WHERE quiz_id = %d AND user_id = %d AND status = 'completed'",
						$quiz_id,
						$user_id
					)
				);
				if ( $completed_count >= $quiz->max_attempts && ! current_user_can( 'manage_options' ) ) {
					return new WP_Error(
						'max_attempts_exceeded',
						sprintf(
							/* translators: %d: max attempts */
							__( 'You have reached the maximum allowed attempts (%d) for this examination.', 'indianagri-quiz-engine' ),
							$quiz->max_attempts
						)
					);
				}
			}

			// Get next attempt number.
			$last_number = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(attempt_number) FROM {$table_attempts} WHERE quiz_id = %d AND user_id = %d",
					$quiz_id,
					$user_id
				)
			);
			$attempt_number = $last_number + 1;

			// Fetch assigned questions count.
			$assigned_q_ids = IAQ_Quiz_Builder::get_quiz_question_ids( $quiz_id );
			$total_questions = count( $assigned_q_ids );

			if ( 0 === $total_questions ) {
				return new WP_Error( 'no_questions', __( 'This examination has no questions assigned yet. Please contact the administrator.', 'indianagri-quiz-engine' ) );
			}

			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '';

			$attempt_data = array(
				'quiz_id'               => $quiz_id,
				'user_id'               => $user_id,
				'attempt_number'        => $attempt_number,
				'status'                => 'in_progress',
				'start_time'            => current_time( 'mysql' ),
				'time_spent_seconds'    => 0,
				'total_questions'       => $total_questions,
				'attempted_questions'   => 0,
				'correct_answers'       => 0,
				'incorrect_answers'     => 0,
				'unattempted_questions' => $total_questions,
				'score'                 => 0.00,
				'total_marks'           => $quiz->total_marks,
				'percentage'            => 0.00,
				'passed'                => 0,
				'ip_address'            => $ip,
				'user_agent'            => $ua,
				'created_at'            => current_time( 'mysql' ),
			);

			$inserted = $wpdb->insert( $table_attempts, $attempt_data );
			if ( ! $inserted ) {
				return new WP_Error( 'db_error', __( 'Failed to initialize examination session.', 'indianagri-quiz-engine' ) );
			}

			$attempt_id = $wpdb->insert_id;
			$attempt    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_attempts} WHERE id = %d", $attempt_id ) );
		}

		// Calculate remaining time.
		$elapsed_seconds   = max( 0, time() - strtotime( $attempt->start_time ) );
		$duration_seconds  = (int) $quiz->duration_minutes * 60;
		$remaining_seconds = ( $duration_seconds > 0 ) ? max( 0, $duration_seconds - $elapsed_seconds ) : null;

		// Check if timer already expired while user was away.
		if ( null !== $remaining_seconds && 0 === $remaining_seconds ) {
			self::submit_attempt( $attempt->id, $user_id, $duration_seconds, 'timed_out' );
			return new WP_Error( 'time_expired', __( 'Your examination session has timed out.', 'indianagri-quiz-engine' ) );
		}

		// Fetch assigned questions with custom marks.
		$raw_questions = IAQ_Quiz_Builder::get_quiz_questions( $quiz_id );

		// Apply question shuffling if configured.
		if ( ! empty( $quiz->shuffle_questions ) ) {
			// Seed shuffle with attempt ID for consistent order on page reload during same attempt.
			mt_srand( (int) $attempt->id );
			shuffle( $raw_questions );
			mt_srand();
		}

		// Fetch saved answers for this attempt.
		$saved_answers_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT question_id, selected_option, is_marked_for_review FROM {$table_answers} WHERE attempt_id = %d",
				$attempt->id
			)
		);

		$saved_answers = array();
		foreach ( $saved_answers_rows as $row ) {
			$saved_answers[ (int) $row->question_id ] = array(
				'selected'  => $row->selected_option,
				'is_marked' => (int) $row->is_marked_for_review,
			);
		}

		// SECURE SANITIZATION: Strip correct answer and explanation from questions delivered to candidate!
		$safe_questions = array();
		$index = 1;

		foreach ( $raw_questions as $q ) {
			$opts = array(
				array( 'key' => 'A', 'text' => $q->option_a ),
				array( 'key' => 'B', 'text' => $q->option_b ),
				array( 'key' => 'C', 'text' => $q->option_c ),
				array( 'key' => 'D', 'text' => $q->option_d ),
			);

			// Shuffle options if enabled.
			if ( ! empty( $quiz->shuffle_options ) ) {
				mt_srand( (int) ( $attempt->id * 1000 + $q->id ) );
				shuffle( $opts );
				mt_srand();
			}

			$qid = (int) $q->id;
			$user_ans = isset( $saved_answers[ $qid ] ) ? $saved_answers[ $qid ] : array( 'selected' => null, 'is_marked' => 0 );

			$safe_questions[] = array(
				'index'          => $index++,
				'id'             => $qid,
				'question_text'  => $q->question_text,
				'subject_name'   => isset( $q->subject_name ) ? $q->subject_name : '',
				'topic_name'     => isset( $q->topic_name ) ? $q->topic_name : '',
				'marks'          => ! is_null( $q->custom_marks ) ? (float) $q->custom_marks : (float) $q->marks,
				'negative_marks' => ! is_null( $q->custom_negative_marks ) ? (float) $q->custom_negative_marks : (float) $q->negative_marks,
				'options'        => $opts,
				'selected'       => $user_ans['selected'],
				'is_marked'      => $user_ans['is_marked'],
			);
		}

		return array(
			'attempt_id'        => (int) $attempt->id,
			'quiz_id'           => (int) $quiz->id,
			'quiz_title'        => $quiz->title,
			'quiz_type'         => $quiz->quiz_type,
			'exam_category'     => $quiz->exam_category,
			'duration_minutes'  => (int) $quiz->duration_minutes,
			'remaining_seconds' => $remaining_seconds,
			'total_questions'   => count( $safe_questions ),
			'total_marks'       => (float) $quiz->total_marks,
			'pass_percentage'   => (float) $quiz->pass_percentage,
			'questions'         => $safe_questions,
		);
	}

	/**
	 * Core Logic: Save student answer selection live.
	 *
	 * @param int    $attempt_id Attempt ID.
	 * @param int    $user_id WordPress User ID.
	 * @param int    $question_id Question ID.
	 * @param string $selected_option Option chosen ('A','B','C','D' or empty).
	 * @param int    $is_marked Mark for review flag (0 or 1).
	 * @param int    $time_spent Time spent in seconds.
	 * @return array|WP_Error
	 */
	public static function save_answer( $attempt_id, $user_id, $question_id, $selected_option, $is_marked = 0, $time_spent = 0 ) {
		global $wpdb;

		$table_attempts = $wpdb->prefix . 'iaq_quiz_attempts';
		$table_answers  = $wpdb->prefix . 'iaq_attempt_answers';

		$attempt = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT a.*, q.duration_minutes FROM {$table_attempts} a JOIN {$wpdb->prefix}iaq_quizzes q ON a.quiz_id = q.id WHERE a.id = %d AND a.user_id = %d",
				$attempt_id,
				$user_id
			)
		);

		if ( ! $attempt || 'in_progress' !== $attempt->status ) {
			return new WP_Error( 'invalid_attempt', __( 'Active attempt not found or already submitted.', 'indianagri-quiz-engine' ) );
		}

		// Validate timer expiry with 30s grace window.
		if ( $attempt->duration_minutes > 0 ) {
			$elapsed = time() - strtotime( $attempt->start_time );
			$allowed = ( $attempt->duration_minutes * 60 ) + 30;
			if ( $elapsed > $allowed ) {
				self::submit_attempt( $attempt_id, $user_id, $elapsed, 'timed_out' );
				return new WP_Error( 'time_expired', __( 'Time has expired. Test submitted automatically.', 'indianagri-quiz-engine' ) );
			}
		}

		$selected_option = strtoupper( trim( $selected_option ) );
		if ( ! in_array( $selected_option, array( 'A', 'B', 'C', 'D' ), true ) ) {
			$selected_option = null;
		}

		$is_marked = $is_marked ? 1 : 0;

		// Check if record exists in wp_iaq_attempt_answers.
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_answers} WHERE attempt_id = %d AND question_id = %d",
				$attempt_id,
				$question_id
			)
		);

		if ( $existing_id ) {
			$wpdb->update(
				$table_answers,
				array(
					'selected_option'      => $selected_option,
					'is_marked_for_review' => $is_marked,
					'answered_at'          => current_time( 'mysql' ),
				),
				array( 'id' => $existing_id )
			);
		} else {
			$wpdb->insert(
				$table_answers,
				array(
					'attempt_id'           => $attempt_id,
					'question_id'          => $question_id,
					'selected_option'      => $selected_option,
					'marks_awarded'        => 0.00,
					'is_marked_for_review' => $is_marked,
					'answered_at'          => current_time( 'mysql' ),
				)
			);
		}

		// Update attempt time spent.
		if ( $time_spent > 0 ) {
			$wpdb->update(
				$table_attempts,
				array( 'time_spent_seconds' => $time_spent ),
				array( 'id' => $attempt_id )
			);
		}

		// Recalculate answered counts for live client state.
		$answered_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(id) FROM {$table_answers} WHERE attempt_id = %d AND selected_option IS NOT NULL AND selected_option != ''",
				$attempt_id
			)
		);

		$marked_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(id) FROM {$table_answers} WHERE attempt_id = %d AND is_marked_for_review = 1",
				$attempt_id
			)
		);

		return array(
			'success'        => true,
			'question_id'    => $question_id,
			'selected'       => $selected_option,
			'is_marked'      => $is_marked,
			'answered_count' => $answered_count,
			'marked_count'   => $marked_count,
		);
	}

	/**
	 * Core Logic: Grade and finalize test attempt.
	 *
	 * @param int    $attempt_id Attempt ID.
	 * @param int    $user_id WordPress User ID.
	 * @param int    $time_spent Time spent in seconds.
	 * @param string $final_status Completion status ('completed' or 'timed_out').
	 * @return array|WP_Error
	 */
	public static function submit_attempt( $attempt_id, $user_id, $time_spent = 0, $final_status = 'completed' ) {
		global $wpdb;

		$table_attempts  = $wpdb->prefix . 'iaq_quiz_attempts';
		$table_answers   = $wpdb->prefix . 'iaq_attempt_answers';
		$table_questions = $wpdb->prefix . 'iaq_questions';
		$table_qq        = $wpdb->prefix . 'iaq_quiz_questions';
		$table_quizzes   = $wpdb->prefix . 'iaq_quizzes';

		$attempt = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT a.*, q.correct_marks as q_correct, q.negative_marks as q_negative, q.pass_percentage, q.show_answers_mode, q.title as quiz_title
				 FROM {$table_attempts} a
				 JOIN {$table_quizzes} q ON a.quiz_id = q.id
				 WHERE a.id = %d AND a.user_id = %d",
				$attempt_id,
				$user_id
			)
		);

		if ( ! $attempt ) {
			return new WP_Error( 'invalid_attempt', __( 'Attempt record not found.', 'indianagri-quiz-engine' ) );
		}

		if ( 'completed' === $attempt->status || 'timed_out' === $attempt->status ) {
			// Already submitted, return existing scorecard.
			return self::get_attempt_scorecard( $attempt_id, $user_id );
		}

		// Fetch all assigned questions with correct answer keys from DB.
		$assigned_questions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT q.id, q.correct_option, q.marks, q.negative_marks, qq.custom_marks, qq.custom_negative_marks
				 FROM {$table_qq} qq
				 JOIN {$table_questions} q ON qq.question_id = q.id
				 WHERE qq.quiz_id = %d",
				$attempt->quiz_id
			)
		);

		// Fetch candidate's stored answers.
		$answers_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_answers} WHERE attempt_id = %d",
				$attempt_id
			)
		);

		$candidate_answers = array();
		foreach ( $answers_rows as $row ) {
			$candidate_answers[ (int) $row->question_id ] = $row;
		}

		$total_questions       = count( $assigned_questions );
		$attempted_questions   = 0;
		$correct_answers       = 0;
		$incorrect_answers     = 0;
		$unattempted_questions = 0;
		$earned_score          = 0.00;
		$max_score             = 0.00;

		foreach ( $assigned_questions as $q ) {
			$qid = (int) $q->id;
			$correct_opt = strtoupper( trim( $q->correct_option ) );

			$marks_for_correct = ! is_null( $q->custom_marks ) ? (float) $q->custom_marks : (float) $q->marks;
			$marks_for_neg     = ! is_null( $q->custom_negative_marks ) ? (float) $q->custom_negative_marks : (float) $q->negative_marks;

			$max_score += $marks_for_correct;

			$selected = isset( $candidate_answers[ $qid ] ) ? $candidate_answers[ $qid ]->selected_option : null;
			$selected = $selected ? strtoupper( trim( $selected ) ) : null;

			$is_correct    = null;
			$marks_awarded = 0.00;

			if ( ! empty( $selected ) ) {
				$attempted_questions++;
				if ( $selected === $correct_opt ) {
					$correct_answers++;
					$is_correct    = 1;
					$marks_awarded = $marks_for_correct;
				} else {
					$incorrect_answers++;
					$is_correct    = 0;
					$marks_awarded = -1 * abs( $marks_for_neg );
				}
			} else {
				$unattempted_questions++;
			}

			$earned_score += $marks_awarded;

			// Update wp_iaq_attempt_answers record with score and verdict.
			if ( isset( $candidate_answers[ $qid ] ) ) {
				$wpdb->update(
					$table_answers,
					array(
						'is_correct'    => $is_correct,
						'marks_awarded' => $marks_awarded,
					),
					array( 'id' => $candidate_answers[ $qid ]->id )
				);
			} else {
				$wpdb->insert(
					$table_answers,
					array(
						'attempt_id'           => $attempt_id,
						'question_id'          => $qid,
						'selected_option'      => null,
						'is_correct'           => null,
						'marks_awarded'        => 0.00,
						'is_marked_for_review' => 0,
						'answered_at'          => current_time( 'mysql' ),
					)
				);
			}
		}

		// Calculate percentage and pass status.
		$percentage = $max_score > 0 ? ( (float) $earned_score / (float) $max_score ) * 100 : 0.00;
		$passed     = ( $percentage >= (float) $attempt->pass_percentage ) ? 1 : 0;

		$elapsed_seconds = ( $time_spent > 0 ) ? $time_spent : max( 0, time() - strtotime( $attempt->start_time ) );

		// Update attempt record.
		$wpdb->update(
			$table_attempts,
			array(
				'status'                => $final_status,
				'end_time'              => current_time( 'mysql' ),
				'time_spent_seconds'    => $elapsed_seconds,
				'total_questions'       => $total_questions,
				'attempted_questions'   => $attempted_questions,
				'correct_answers'       => $correct_answers,
				'incorrect_answers'     => $incorrect_answers,
				'unattempted_questions' => $unattempted_questions,
				'score'                 => $earned_score,
				'total_marks'           => $max_score,
				'percentage'            => round( $percentage, 2 ),
				'passed'                => $passed,
			),
			array( 'id' => $attempt_id )
		);

		// Record wrong answers into student's personal Wrong Answer Notebook.
		if ( 'completed' === $final_status && class_exists( 'IAQ_Wrong_Notebook' ) ) {
			IAQ_Wrong_Notebook::ingest_attempt_wrong_answers( $attempt_id );
		}

		// Record qualifying learning activity for Study Streak and Goals.
		if ( 'completed' === $final_status && class_exists( 'IAQ_Study_Streaks' ) ) {
			IAQ_Study_Streaks::record_activity(
				$user_id,
				'quiz_completed',
				$attempt_id,
				$attempted_questions,
				$elapsed_seconds
			);
		}

		return self::get_attempt_scorecard( $attempt_id, $user_id );
	}

	/**
	 * Retrieve detailed scorecard for an attempt.
	 *
	 * @param int $attempt_id Attempt ID.
	 * @param int $user_id WordPress User ID.
	 * @return array|WP_Error
	 */
	public static function get_attempt_scorecard( $attempt_id, $user_id ) {
		global $wpdb;

		$table_attempts = $wpdb->prefix . 'iaq_quiz_attempts';
		$table_quizzes  = $wpdb->prefix . 'iaq_quizzes';

		$attempt = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT a.*, q.title as quiz_title, q.quiz_type, q.exam_category, q.show_answers_mode
				 FROM {$table_attempts} a
				 JOIN {$table_quizzes} q ON a.quiz_id = q.id
				 WHERE a.id = %d AND (a.user_id = %d OR %d = 1)",
				$attempt_id,
				$user_id,
				current_user_can( 'manage_options' ) ? 1 : 0
			)
		);

		if ( ! $attempt ) {
			return new WP_Error( 'not_found', __( 'Attempt scorecard not found.', 'indianagri-quiz-engine' ) );
		}

		$mins = floor( $attempt->time_spent_seconds / 60 );
		$secs = $attempt->time_spent_seconds % 60;
		$formatted_time = sprintf( '%02dm %02ds', $mins, $secs );

		return array(
			'attempt_id'            => (int) $attempt->id,
			'quiz_id'               => (int) $attempt->quiz_id,
			'quiz_title'            => $attempt->quiz_title,
			'quiz_type'             => $attempt->quiz_type,
			'exam_category'         => $attempt->exam_category,
			'status'                => $attempt->status,
			'start_time'            => $attempt->start_time,
			'end_time'              => $attempt->end_time,
			'time_spent_seconds'    => (int) $attempt->time_spent_seconds,
			'time_spent_formatted'  => $formatted_time,
			'total_questions'       => (int) $attempt->total_questions,
			'attempted_questions'   => (int) $attempt->attempted_questions,
			'correct_answers'       => (int) $attempt->correct_answers,
			'incorrect_answers'     => (int) $attempt->incorrect_answers,
			'unattempted_questions' => (int) $attempt->unattempted_questions,
			'score'                 => (float) $attempt->score,
			'total_marks'           => (float) $attempt->total_marks,
			'percentage'            => (float) $attempt->percentage,
			'passed'                => (bool) $attempt->passed,
			'show_answers_mode'     => $attempt->show_answers_mode,
			'subject_breakdown'     => self::get_attempt_subject_breakdown( $attempt_id, $user_id ),
		);
	}

	/**
	 * Retrieve question breakdown for review (if permitted by quiz show_answers_mode).
	 *
	 * @param int $attempt_id Attempt ID.
	 * @param int $user_id WordPress User ID.
	 * @return array|WP_Error
	 */
	public static function get_attempt_review_questions( $attempt_id, $user_id ) {
		global $wpdb;

		$table_attempts  = $wpdb->prefix . 'iaq_quiz_attempts';
		$table_answers   = $wpdb->prefix . 'iaq_attempt_answers';
		$table_questions = $wpdb->prefix . 'iaq_questions';
		$table_quizzes   = $wpdb->prefix . 'iaq_quizzes';
		$table_subjects  = $wpdb->prefix . 'iaq_subjects';
		$table_topics    = $wpdb->prefix . 'iaq_topics';

		$attempt = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT a.*, q.show_answers_mode
				 FROM {$table_attempts} a
				 JOIN {$table_quizzes} q ON a.quiz_id = q.id
				 WHERE a.id = %d AND (a.user_id = %d OR %d = 1)",
				$attempt_id,
				$user_id,
				current_user_can( 'manage_options' ) ? 1 : 0
			)
		);

		if ( ! $attempt || 'never' === $attempt->show_answers_mode ) {
			return array();
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ans.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option, q.explanation,
				        s.name AS subject_name, t.name AS topic_name
				 FROM {$table_answers} ans
				 JOIN {$table_questions} q ON ans.question_id = q.id
				 LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
				 LEFT JOIN {$table_topics} t ON q.topic_id = t.id
				 WHERE ans.attempt_id = %d
				 ORDER BY ans.id ASC",
				$attempt_id
			)
		);

		$questions = array();
		$index = 1;
		foreach ( $rows as $r ) {
			$questions[] = array(
				'index'           => $index++,
				'id'              => (int) $r->question_id,
				'question_text'   => $r->question_text,
				'subject_name'    => isset( $r->subject_name ) ? $r->subject_name : '',
				'topic_name'      => isset( $r->topic_name ) ? $r->topic_name : '',
				'option_a'        => $r->option_a,
				'option_b'        => $r->option_b,
				'option_c'        => $r->option_c,
				'option_d'        => $r->option_d,
				'selected_option' => $r->selected_option,
				'correct_option'  => $r->correct_option,
				'is_correct'      => ! is_null( $r->is_correct ) ? (int) $r->is_correct : null,
				'marks_awarded'   => (float) $r->marks_awarded,
				'explanation'     => $r->explanation,
			);
		}

		return $questions;
	}

	/**
	 * Retrieve subject-wise and topic-wise performance breakdown for candidate mastery analytics.
	 *
	 * @param int $attempt_id Attempt ID.
	 * @param int $user_id WordPress User ID.
	 * @return array
	 */
	public static function get_attempt_subject_breakdown( $attempt_id, $user_id ) {
		global $wpdb;

		$table_attempts  = $wpdb->prefix . 'iaq_quiz_attempts';
		$table_answers   = $wpdb->prefix . 'iaq_attempt_answers';
		$table_questions = $wpdb->prefix . 'iaq_questions';
		$table_subjects  = $wpdb->prefix . 'iaq_subjects';
		$table_topics    = $wpdb->prefix . 'iaq_topics';

		$attempt = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT a.* FROM {$table_attempts} a WHERE a.id = %d AND (a.user_id = %d OR %d = 1)",
				$attempt_id,
				$user_id,
				current_user_can( 'manage_options' ) ? 1 : 0
			)
		);

		if ( ! $attempt ) {
			return array();
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ans.*, q.subject_id, q.topic_id, s.name as subject_name, t.name as topic_name
				 FROM {$table_answers} ans
				 JOIN {$table_questions} q ON ans.question_id = q.id
				 LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
				 LEFT JOIN {$table_topics} t ON q.topic_id = t.id
				 WHERE ans.attempt_id = %d",
				$attempt_id
			)
		);

		$breakdown = array();
		foreach ( $rows as $r ) {
			$subj_key  = $r->subject_id ? (int) $r->subject_id : 0;
			$subj_name = ! empty( $r->subject_name ) ? $r->subject_name : __( 'General Agriculture', 'indianagri-quiz-engine' );

			if ( ! isset( $breakdown[ $subj_key ] ) ) {
				$breakdown[ $subj_key ] = array(
					'subject_id'       => $subj_key,
					'subject_name'     => $subj_name,
					'total_questions'  => 0,
					'attempted'        => 0,
					'correct'          => 0,
					'incorrect'        => 0,
					'unattempted'      => 0,
					'score'            => 0.00,
					'accuracy_percent' => 0.00,
					'mastery_level'    => 'weak',
					'mastery_label'    => __( 'Needs Revision', 'indianagri-quiz-engine' ),
				);
			}

			$breakdown[ $subj_key ]['total_questions']++;

			if ( ! empty( $r->selected_option ) ) {
				$breakdown[ $subj_key ]['attempted']++;
				if ( 1 == $r->is_correct ) {
					$breakdown[ $subj_key ]['correct']++;
				} else {
					$breakdown[ $subj_key ]['incorrect']++;
				}
			} else {
				$breakdown[ $subj_key ]['unattempted']++;
			}

			$breakdown[ $subj_key ]['score'] += (float) $r->marks_awarded;
		}

		// Calculate accuracy and mastery tier.
		foreach ( $breakdown as $k => $b ) {
			$acc = $b['total_questions'] > 0 ? ( $b['correct'] / $b['total_questions'] ) * 100 : 0.00;
			$breakdown[ $k ]['accuracy_percent'] = round( $acc, 1 );

			if ( $acc >= 75 ) {
				$breakdown[ $k ]['mastery_level'] = 'strong';
				$breakdown[ $k ]['mastery_label'] = __( 'Strong Mastery', 'indianagri-quiz-engine' );
			} elseif ( $acc >= 50 ) {
				$breakdown[ $k ]['mastery_level'] = 'moderate';
				$breakdown[ $k ]['mastery_label'] = __( 'Moderate Competence', 'indianagri-quiz-engine' );
			} else {
				$breakdown[ $k ]['mastery_level'] = 'weak';
				$breakdown[ $k ]['mastery_label'] = __( 'Needs Revision', 'indianagri-quiz-engine' );
			}
		}

		return array_values( $breakdown );
	}

	/**
	 * Retrieve cumulative examination statistics for student dashboard.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return array
	 */
	public static function get_user_dashboard_stats( $user_id ) {
		global $wpdb;

		if ( ! $user_id || $user_id <= 0 ) {
			return array(
				'total_attempts'            => 0,
				'completed_attempts'        => 0,
				'in_progress_attempts'      => 0,
				'passed_attempts'           => 0,
				'pass_rate_percent'         => 0.0,
				'avg_percentage'            => 0.0,
				'best_percentage'           => 0.0,
				'total_questions_solved'    => 0,
				'total_questions_attempted' => 0,
				'total_correct_solved'      => 0,
			);
		}

		$table_attempts = $wpdb->prefix . 'iaq_quiz_attempts';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					COUNT(id) AS total_attempts,
					SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_attempts,
					SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_attempts,
					SUM(CASE WHEN status = 'completed' AND passed = 1 THEN 1 ELSE 0 END) AS passed_attempts,
					AVG(CASE WHEN status = 'completed' THEN percentage ELSE NULL END) AS avg_percentage,
					AVG(CASE WHEN status = 'completed' THEN score ELSE NULL END) AS avg_score,
					MAX(CASE WHEN status = 'completed' THEN percentage ELSE 0 END) AS best_percentage,
					SUM(CASE WHEN status = 'completed' THEN total_questions ELSE 0 END) AS total_questions_solved,
					SUM(CASE WHEN status = 'completed' THEN attempted_questions ELSE 0 END) AS total_questions_attempted,
					SUM(CASE WHEN status = 'completed' THEN correct_answers ELSE 0 END) AS total_correct_solved
				 FROM {$table_attempts}
				 WHERE user_id = %d",
				$user_id
			)
		);

		$total        = $row ? (int) $row->total_attempts : 0;
		$completed    = $row ? (int) $row->completed_attempts : 0;
		$in_progress  = $row ? (int) $row->in_progress_attempts : 0;
		$passed       = $row ? (int) $row->passed_attempts : 0;
		$pass_rate    = $completed > 0 ? round( ( $passed / $completed ) * 100, 1 ) : 0.0;
		$avg_pct      = $row && ! is_null( $row->avg_percentage ) ? round( (float) $row->avg_percentage, 1 ) : 0.0;
		$best_pct     = $row && ! is_null( $row->best_percentage ) ? round( (float) $row->best_percentage, 1 ) : 0.0;
		$q_total      = $row ? (int) $row->total_questions_solved : 0;
		$q_attempted  = $row ? (int) $row->total_questions_attempted : 0;
		$q_correct    = $row ? (int) $row->total_correct_solved : 0;

		return array(
			'total_attempts'            => $total,
			'completed_attempts'        => $completed,
			'in_progress_attempts'      => $in_progress,
			'passed_attempts'           => $passed,
			'pass_rate_percent'         => $pass_rate,
			'avg_percentage'            => $avg_pct,
			'best_percentage'           => $best_pct,
			'total_questions_solved'    => $q_total,
			'total_questions_attempted' => $q_attempted,
			'total_correct_solved'      => $q_correct,
		);
	}

	/**
	 * Retrieve paginated student examination history.
	 *
	 * @param int $user_id WordPress User ID.
	 * @param int $limit Max items to return.
	 * @param int $offset Pagination offset.
	 * @return array
	 */
	public static function get_user_attempt_history( $user_id, $limit = 20, $offset = 0 ) {
		global $wpdb;

		$table_attempts = $wpdb->prefix . 'iaq_quiz_attempts';
		$table_quizzes  = $wpdb->prefix . 'iaq_quizzes';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*, q.title as quiz_title, q.quiz_type, q.exam_category
				 FROM {$table_attempts} a
				 JOIN {$table_quizzes} q ON a.quiz_id = q.id
				 WHERE a.user_id = %d
				 ORDER BY a.id DESC
				 LIMIT %d OFFSET %d",
				$user_id,
				$limit,
				$offset
			)
		);

		$history = array();
		if ( $rows ) {
			foreach ( $rows as $r ) {
				$mins = floor( $r->time_spent_seconds / 60 );
				$secs = $r->time_spent_seconds % 60;
				$history[] = array(
					'id'                   => (int) $r->id,
					'quiz_id'              => (int) $r->quiz_id,
					'quiz_title'           => $r->quiz_title,
					'quiz_type'            => $r->quiz_type,
					'exam_category'        => $r->exam_category,
					'status'               => $r->status,
					'start_time'           => $r->start_time,
					'end_time'             => isset( $r->end_time ) ? $r->end_time : '',
					'time_spent_formatted' => sprintf( '%02dm %02ds', $mins, $secs ),
					'score'                => (float) $r->score,
					'total_marks'          => (float) $r->total_marks,
					'percentage'           => (float) $r->percentage,
					'passed'               => (bool) $r->passed,
					'total_questions'      => (int) $r->total_questions,
					'correct_answers'      => (int) $r->correct_answers,
				);
			}
		}

		return $history;
	}

	/**
	 * Retrieve student's current active in-progress examination (if any).
	 *
	 * @param int $user_id WordPress User ID.
	 * @return array|null
	 */
	public static function get_user_active_attempt( $user_id ) {
		global $wpdb;

		$table_attempts = $wpdb->prefix . 'iaq_quiz_attempts';
		$table_quizzes  = $wpdb->prefix . 'iaq_quizzes';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT a.*, q.title as quiz_title, q.duration_minutes, q.exam_category
				 FROM {$table_attempts} a
				 JOIN {$table_quizzes} q ON a.quiz_id = q.id
				 WHERE a.user_id = %d AND a.status = 'in_progress'
				 ORDER BY a.id DESC LIMIT 1",
				$user_id
			)
		);

		if ( ! $row ) {
			return null;
		}

		return array(
			'attempt_id'       => (int) $row->id,
			'quiz_id'          => (int) $row->quiz_id,
			'quiz_title'       => $row->quiz_title,
			'exam_category'    => $row->exam_category,
			'start_time'       => $row->start_time,
			'duration_minutes' => (int) $row->duration_minutes,
		);
	}

	/**
	 * Retrieve cumulative subject mastery across all completed examinations of a candidate.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return array
	 */
	public static function get_user_overall_subject_mastery( $user_id ) {
		global $wpdb;

		$table_attempts  = $wpdb->prefix . 'iaq_quiz_attempts';
		$table_answers   = $wpdb->prefix . 'iaq_attempt_answers';
		$table_questions = $wpdb->prefix . 'iaq_questions';
		$table_subjects  = $wpdb->prefix . 'iaq_subjects';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ans.selected_option, ans.is_correct, ans.marks_awarded,
				        q.subject_id, s.name as subject_name
				 FROM {$table_answers} ans
				 JOIN {$table_attempts} a ON ans.attempt_id = a.id
				 JOIN {$table_questions} q ON ans.question_id = q.id
				 LEFT JOIN {$table_subjects} s ON q.subject_id = s.id
				 WHERE a.user_id = %d AND a.status = 'completed'",
				$user_id
			)
		);

		$mastery = array();
		if ( $rows ) {
			foreach ( $rows as $r ) {
				$subj_id   = $r->subject_id ? (int) $r->subject_id : 0;
				$subj_name = ! empty( $r->subject_name ) ? $r->subject_name : __( 'General Agriculture', 'indianagri-quiz-engine' );

				if ( ! isset( $mastery[ $subj_id ] ) ) {
					$mastery[ $subj_id ] = array(
						'subject_id'       => $subj_id,
						'subject_name'     => $subj_name,
						'total_questions'  => 0,
						'attempted'        => 0,
						'correct'          => 0,
						'incorrect'        => 0,
						'accuracy_percent' => 0.0,
						'mastery_level'    => 'weak',
						'mastery_label'    => __( 'Needs Revision', 'indianagri-quiz-engine' ),
					);
				}

				$mastery[ $subj_id ]['total_questions']++;
				if ( ! empty( $r->selected_option ) ) {
					$mastery[ $subj_id ]['attempted']++;
					if ( 1 == $r->is_correct ) {
						$mastery[ $subj_id ]['correct']++;
					} else {
						$mastery[ $subj_id ]['incorrect']++;
					}
				}
			}

			foreach ( $mastery as $k => $m ) {
				$acc = $m['total_questions'] > 0 ? ( $m['correct'] / $m['total_questions'] ) * 100 : 0.0;
				$mastery[ $k ]['accuracy_percent'] = round( $acc, 1 );
				if ( $acc >= 75 ) {
					$mastery[ $k ]['mastery_level'] = 'strong';
					$mastery[ $k ]['mastery_label'] = __( 'Mastered', 'indianagri-quiz-engine' );
				} elseif ( $acc >= 50 ) {
					$mastery[ $k ]['mastery_level'] = 'moderate';
					$mastery[ $k ]['mastery_label'] = __( 'Proficient', 'indianagri-quiz-engine' );
				} else {
					$mastery[ $k ]['mastery_level'] = 'weak';
					$mastery[ $k ]['mastery_label'] = __( 'Needs Revision', 'indianagri-quiz-engine' );
				}
				// Connect to relevant practice set where available.
				$mastery[ $k ]['recommended_quiz'] = self::get_subject_recommended_quiz( (int) $m['subject_id'] );
			}

			uasort( $mastery, function( $a, $b ) {
				return $b['total_questions'] - $a['total_questions'];
			} );
		}

		return array_values( $mastery );
	}

	/**
	 * Find a published practice set or quiz recommended for revising a specific subject.
	 *
	 * @param int $subject_id Agricultural subject ID.
	 * @return object|null
	 */
	public static function get_subject_recommended_quiz( $subject_id ) {
		global $wpdb;

		if ( ! $subject_id ) {
			return null;
		}

		$table_quizzes = $wpdb->prefix . 'iaq_quizzes';

		// Direct match on subject_id.
		$quiz = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, title, quiz_type, exam_category, duration_minutes, total_questions 
				 FROM {$table_quizzes}
				 WHERE subject_id = %d AND status = 'published'
				 ORDER BY id DESC LIMIT 1",
				$subject_id
			)
		);

		if ( $quiz ) {
			return $quiz;
		}

		// Match via linked questions.
		$table_qq = $wpdb->prefix . 'iaq_quiz_questions';
		$table_q  = $wpdb->prefix . 'iaq_questions';

		$quiz_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT q.id, q.title, q.quiz_type, q.exam_category, q.duration_minutes, q.total_questions
				 FROM {$table_quizzes} q
				 JOIN {$table_qq} qq ON q.id = qq.quiz_id
				 JOIN {$table_q} qu ON qq.question_id = qu.id
				 WHERE qu.subject_id = %d AND q.status = 'published'
				 ORDER BY q.id DESC LIMIT 1",
				$subject_id
			)
		);

		return $quiz_row ? $quiz_row : null;
	}

	/**
	 * Retrieve personalized continue-practising quizzes based on actual database records.
	 * Prioritizes:
	 * 1. Current in-progress attempts (to resume)
	 * 2. Recently taken quizzes (to retake/reinforce)
	 * 3. Fresh recommended published practice sets
	 *
	 * @param int $user_id WordPress User ID.
	 * @param int $limit Max items to return.
	 * @return array
	 */
	public static function get_user_continue_practicing_quizzes( $user_id, $limit = 4 ) {
		global $wpdb;

		$table_quizzes  = $wpdb->prefix . 'iaq_quizzes';
		$table_attempts = $wpdb->prefix . 'iaq_quiz_attempts';

		$continue_items = array();
		$seen_quiz_ids  = array();

		if ( $user_id > 0 ) {
			// 1. In-progress attempts.
			$active_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT a.id as attempt_id, a.quiz_id, a.start_time, a.status as attempt_status,
					        q.title, q.quiz_type, q.exam_category, q.total_questions, q.total_marks, q.duration_minutes, q.access_type
					 FROM {$table_attempts} a
					 JOIN {$table_quizzes} q ON a.quiz_id = q.id
					 WHERE a.user_id = %d AND a.status = 'in_progress' AND q.status = 'published'
					 ORDER BY a.id DESC LIMIT %d",
					$user_id,
					$limit
				)
			);

			if ( $active_rows ) {
				foreach ( $active_rows as $row ) {
					$qid = (int) $row->quiz_id;
					$seen_quiz_ids[ $qid ] = true;
					$continue_items[] = array(
						'quiz_id'          => $qid,
						'attempt_id'       => (int) $row->attempt_id,
						'title'            => $row->title,
						'quiz_type'        => $row->quiz_type,
						'exam_category'    => $row->exam_category,
						'total_questions'  => (int) $row->total_questions,
						'total_marks'      => (float) $row->total_marks,
						'duration_minutes' => (int) $row->duration_minutes,
						'access_type'      => $row->access_type,
						'status'           => 'in_progress',
						'badge_text'       => __( 'In Progress', 'indianagri-quiz-engine' ),
						'action_label'     => __( 'Resume Attempt', 'indianagri-quiz-engine' ),
						'action_type'      => 'resume',
						'last_date'        => $row->start_time,
					);
					if ( count( $continue_items ) >= $limit ) {
						return $continue_items;
					}
				}
			}

			// 2. Recently completed attempts.
			$recent_completed = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT a.id as attempt_id, a.quiz_id, a.score, a.total_marks as scored_out_of, a.percentage, a.passed, a.start_time,
					        q.title, q.quiz_type, q.exam_category, q.total_questions, q.total_marks, q.duration_minutes, q.access_type
					 FROM {$table_attempts} a
					 JOIN {$table_quizzes} q ON a.quiz_id = q.id
					 WHERE a.user_id = %d AND a.status = 'completed' AND q.status = 'published'
					 ORDER BY a.id DESC LIMIT %d",
					$user_id,
					$limit * 2
				)
			);

			if ( $recent_completed ) {
				foreach ( $recent_completed as $row ) {
					$qid = (int) $row->quiz_id;
					if ( isset( $seen_quiz_ids[ $qid ] ) ) {
						continue;
					}
					$seen_quiz_ids[ $qid ] = true;
					$continue_items[] = array(
						'quiz_id'          => $qid,
						'attempt_id'       => (int) $row->attempt_id,
						'title'            => $row->title,
						'quiz_type'        => $row->quiz_type,
						'exam_category'    => $row->exam_category,
						'total_questions'  => (int) $row->total_questions,
						'total_marks'      => (float) $row->total_marks,
						'duration_minutes' => (int) $row->duration_minutes,
						'access_type'      => $row->access_type,
						'status'           => 'completed',
						'percentage'       => (float) $row->percentage,
						'score'            => (float) $row->score,
						'passed'           => (bool) $row->passed,
						'badge_text'       => sprintf( __( 'Last Score: %s%%', 'indianagri-quiz-engine' ), (float) $row->percentage ),
						'action_label'     => __( 'Practice Again', 'indianagri-quiz-engine' ),
						'action_type'      => 'retake',
						'last_date'        => $row->start_time,
					);
					if ( count( $continue_items ) >= $limit ) {
						return $continue_items;
					}
				}
			}
		}

		// 3. Recommended published quizzes based on weak subjects and eligible content.
		$remaining_needed = $limit - count( $continue_items );
		if ( $remaining_needed > 0 ) {
			// Find student's weak subjects to personalize recommendations
			$weak_subject_ids = array();
			if ( $user_id > 0 ) {
				$mastery = self::get_user_overall_subject_mastery( $user_id );
				foreach ( $mastery as $m ) {
					if ( 'weak' === $m['mastery_level'] && ! empty( $m['subject_id'] ) ) {
						$weak_subject_ids[] = (int) $m['subject_id'];
					}
				}
			}

			// First priority: Quizzes matching student's weak subjects
			if ( ! empty( $weak_subject_ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $weak_subject_ids ), '%d' ) );
				$weak_quizzes = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT q.id as quiz_id, q.title, q.quiz_type, q.exam_category, q.total_questions, q.total_marks, q.duration_minutes, q.access_type
						 FROM {$table_quizzes} q
						 WHERE q.status = 'published' AND q.is_custom = 0 AND q.subject_id IN ($placeholders)
						 ORDER BY q.id DESC LIMIT %d",
						array_merge( $weak_subject_ids, array( $limit ) )
					)
				);

				if ( $weak_quizzes ) {
					foreach ( $weak_quizzes as $row ) {
						$qid = (int) $row->quiz_id;
						if ( isset( $seen_quiz_ids[ $qid ] ) ) {
							continue;
						}

						// Check accessibility: exclude premium quizzes if inaccessible
						if ( 'premium' === $row->access_type ) {
							$ent = IAQ_Access_Control::check_user_entitlement( $user_id, $qid );
							if ( ! $ent['allowed'] ) {
								continue;
							}
						}

						$seen_quiz_ids[ $qid ] = true;
						$continue_items[] = array(
							'quiz_id'          => $qid,
							'attempt_id'       => 0,
							'title'            => $row->title,
							'quiz_type'        => $row->quiz_type,
							'exam_category'    => $row->exam_category,
							'total_questions'  => (int) $row->total_questions,
							'total_marks'      => (float) $row->total_marks,
							'duration_minutes' => (int) $row->duration_minutes,
							'access_type'      => $row->access_type,
							'status'           => 'not_attempted',
							'badge_text'       => __( 'Weak Area Focus', 'indianagri-quiz-engine' ),
							'action_label'     => __( 'Practice Weak Subject', 'indianagri-quiz-engine' ),
							'action_type'      => 'start',
							'last_date'        => '',
						);
						if ( count( $continue_items ) >= $limit ) {
							return $continue_items;
						}
					}
				}
			}

			// General published quizzes that student has access to
			$all_published = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT q.id as quiz_id, q.title, q.quiz_type, q.exam_category, q.total_questions, q.total_marks, q.duration_minutes, q.access_type
					 FROM {$table_quizzes} q
					 WHERE q.status = 'published' AND q.is_custom = 0
					 ORDER BY q.id DESC LIMIT %d",
					$limit * 4
				)
			);

			if ( $all_published ) {
				foreach ( $all_published as $row ) {
					$qid = (int) $row->quiz_id;
					if ( isset( $seen_quiz_ids[ $qid ] ) ) {
						continue;
					}

					// Check accessibility: exclude premium quizzes if inaccessible
					if ( 'premium' === $row->access_type ) {
						$ent = IAQ_Access_Control::check_user_entitlement( $user_id, $qid );
						if ( ! $ent['allowed'] ) {
							continue;
						}
					}

					$seen_quiz_ids[ $qid ] = true;
					$continue_items[] = array(
						'quiz_id'          => $qid,
						'attempt_id'       => 0,
						'title'            => $row->title,
						'quiz_type'        => $row->quiz_type,
						'exam_category'    => $row->exam_category,
						'total_questions'  => (int) $row->total_questions,
						'total_marks'      => (float) $row->total_marks,
						'duration_minutes' => (int) $row->duration_minutes,
						'access_type'      => $row->access_type,
						'status'           => 'not_attempted',
						'badge_text'       => __( 'Recommended', 'indianagri-quiz-engine' ),
						'action_label'     => __( 'Start Practice', 'indianagri-quiz-engine' ),
						'action_type'      => 'start',
						'last_date'        => '',
					);
					if ( count( $continue_items ) >= $limit ) {
						break;
					}
				}
			}
		}

		return $continue_items;
	}
}

