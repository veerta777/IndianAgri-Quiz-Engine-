<?php
/**
 * Test Suite: Quiz Attempt Lifecycle, Heartbeat Auto-Save & Scoring Engine.
 *
 * @package IndianAgri_Quiz_Engine
 */

class Test_Quiz_Attempt {

	public static function run() {
		echo "Running Quiz Attempt Test Suite...\n";

		// 1. Ensure Quiz Builder has test quiz
		$quiz_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'             => 'Test Exam for Attempt Suite',
				'slug'              => 'test-attempt-quiz-' . time(),
				'quiz_type'         => 'mock',
				'exam_category'     => 'ICAR JRF / SRF / NET',
				'duration_minutes'  => 30,
				'correct_marks'     => 4.00,
				'negative_marks'    => 1.00,
				'pass_percentage'   => 50.00,
				'shuffle_questions' => 0,
				'shuffle_options'   => 0,
				'max_attempts'      => 2,
				'access_type'       => 'free',
				'status'            => 'published',
			)
		);
		assert( $quiz_id > 0, 'Quiz creation for attempt test should succeed' );

		// Create 2 test questions
		$q1 = IAQ_Question_Bank::save_question(
			array(
				'subject_id'     => 1,
				'exam_category'  => 'ICAR JRF / SRF / NET',
				'difficulty'     => 'easy',
				'question_text'  => 'Attempt Test Q1: Best soil for cotton?',
				'option_a'       => 'Red Soil',
				'option_b'       => 'Black Cotton Soil (Vertisol)',
				'option_c'       => 'Laterite Soil',
				'option_d'       => 'Alluvial Soil',
				'correct_option' => 'B',
				'marks'          => 4.00,
				'negative_marks' => 1.00,
				'explanation'    => 'Black cotton soil (Vertisols) has high montmorillonite clay content.',
				'status'         => 'published',
			)
		);

		$q2 = IAQ_Question_Bank::save_question(
			array(
				'subject_id'     => 1,
				'exam_category'  => 'ICAR JRF / SRF / NET',
				'difficulty'     => 'medium',
				'question_text'  => 'Attempt Test Q2: Photoperiodic category of Rice?',
				'option_a'       => 'Short-day plant',
				'option_b'       => 'Long-day plant',
				'option_c'       => 'Day-neutral plant',
				'option_d'       => 'Intermediate-day plant',
				'correct_option' => 'A',
				'marks'          => 4.00,
				'negative_marks' => 1.00,
				'explanation'    => 'Cultivated rice (Oryza sativa) is predominantly a short-day plant.',
				'status'         => 'published',
			)
		);

		// Assign questions to quiz
		IAQ_Quiz_Builder::assign_questions_to_quiz( $quiz_id, array( $q1, $q2 ) );

		$user_id = 1;

		// 2. Start or Resume Attempt
		$session = IAQ_Quiz_Attempt::start_or_resume_attempt( $quiz_id, $user_id );
		assert( ! is_wp_error( $session ), 'Starting attempt should succeed' );
		assert( isset( $session['attempt_id'] ) && $session['attempt_id'] > 0, 'Attempt ID should be valid' );
		assert( $session['total_questions'] === 2, 'Total questions should be 2' );
		assert( count( $session['questions'] ) === 2, 'Questions count in session payload should be 2' );

		// CRITICAL SECURITY TEST: Ensure correct_option and explanation are stripped from payload!
		foreach ( $session['questions'] as $sq ) {
			assert( ! isset( $sq['correct_option'] ), 'SECURITY: correct_option must not be exposed in client questions!' );
			assert( ! isset( $sq['explanation'] ), 'SECURITY: explanation must not be exposed in client questions!' );
		}
		echo "  [PASS] Candidate questions securely stripped of answer keys and explanations\n";

		$attempt_id = $session['attempt_id'];

		// 3. Test Heartbeat Auto-Save Answer
		// Select option 'B' for Q1
		$save_res1 = IAQ_Quiz_Attempt::save_answer( $attempt_id, $user_id, $q1, 'B', 0, 15 );
		assert( ! is_wp_error( $save_res1 ), 'Saving answer for Q1 should succeed' );
		assert( $save_res1['selected'] === 'B', 'Selected option should be B' );
		assert( $save_res1['answered_count'] === 1, 'Answered count should be 1' );
		echo "  [PASS] Auto-save answer option B for Q1\n";

		// Mark Q2 for review without selecting option
		$save_res2 = IAQ_Quiz_Attempt::save_answer( $attempt_id, $user_id, $q2, '', 1, 30 );
		assert( ! is_wp_error( $save_res2 ), 'Marking Q2 for review should succeed' );
		assert( $save_res2['is_marked'] === 1, 'Q2 should be marked for review' );
		assert( $save_res2['marked_count'] === 1, 'Marked count should be 1' );
		echo "  [PASS] Mark for review flag persisted\n";

		// Resume session to verify persistence on page reload
		$resumed = IAQ_Quiz_Attempt::start_or_resume_attempt( $quiz_id, $user_id );
		assert( $resumed['attempt_id'] === $attempt_id, 'Resuming should return same attempt ID' );
		assert( $resumed['questions'][0]['selected'] === 'B', 'Saved answer B should be restored upon reload' );
		assert( $resumed['questions'][1]['is_marked'] === 1, 'Marked for review flag should be restored upon reload' );
		echo "  [PASS] Session answers and review flags restored on simulated reload\n";

		// Now candidate also answers Q2 with wrong option 'B' (correct is A)
		$save_res3 = IAQ_Quiz_Attempt::save_answer( $attempt_id, $user_id, $q2, 'B', 0, 45 );
		assert( $save_res3['answered_count'] === 2, 'Both questions now answered' );

		// 4. Test Final Submission & Server-Side Scoring
		// Q1 is correct (+4), Q2 is incorrect (-1) -> Score = 3 / 8 = 37.5% -> Pass score is 50% -> Failed
		$scorecard = IAQ_Quiz_Attempt::submit_attempt( $attempt_id, $user_id, 45 );
		assert( ! is_wp_error( $scorecard ), 'Submission should succeed' );
		assert( $scorecard['status'] === 'completed', 'Status should be completed' );
		assert( $scorecard['total_questions'] === 2, 'Total questions should be 2' );
		assert( $scorecard['attempted_questions'] === 2, 'Attempted questions should be 2' );
		assert( $scorecard['correct_answers'] === 1, 'Correct answers should be 1' );
		assert( $scorecard['incorrect_answers'] === 1, 'Incorrect answers should be 1' );
		assert( (float) $scorecard['score'] === 3.00, 'Score should be 3.00 (4 - 1)' );
		assert( (float) $scorecard['total_marks'] === 8.00, 'Total marks should be 8.00' );
		assert( (float) $scorecard['percentage'] === 37.5, 'Percentage should be 37.5%' );
		assert( $scorecard['passed'] === false, 'Pass status should be false (37.5% < 50%)' );
		echo "  [PASS] Final scoring calculated accurately: +4.00 -1.00 = 3.00 / 8.00 (37.5%, Failed)\n";

		// 5. Test Review Solutions (Post-Submission)
		$review = IAQ_Quiz_Attempt::get_attempt_review_questions( $attempt_id, $user_id );
		assert( count( $review ) === 2, 'Review questions count should be 2' );
		assert( $review[0]['selected_option'] === 'B' && $review[0]['correct_option'] === 'B' && $review[0]['is_correct'] == 1, 'Q1 review should indicate correct answer' );
		assert( $review[1]['selected_option'] === 'B' && $review[1]['correct_option'] === 'A' && $review[1]['is_correct'] == 0, 'Q2 review should indicate incorrect answer' );
		echo "  [PASS] Post-submission solutions and explanations available in review mode\n";

		// =========================================================================
		// 6. Test Immediate Feedback Mode in Practice Sets
		// =========================================================================
		echo "  Testing Immediate Feedback Mode for Practice Sets...\n";

		$practice_quiz_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'             => 'Agronomy Immediate Practice Set',
				'slug'              => 'agronomy-practice-immediate-' . time(),
				'quiz_type'         => 'practice',
				'exam_category'     => 'General Agriculture',
				'duration_minutes'  => 0, // untimed self-paced practice
				'correct_marks'     => 2.00,
				'negative_marks'    => 0.50,
				'pass_percentage'   => 50.00,
				'shuffle_questions' => 0,
				'shuffle_options'   => 0,
				'max_attempts'      => 0,
				'access_type'       => 'free',
				'show_answers_mode' => 'immediate',
				'status'            => 'published',
			)
		);
		assert( $practice_quiz_id > 0, 'Practice quiz creation should succeed' );

		// Assign test questions
		IAQ_Quiz_Builder::assign_questions_to_quiz( $practice_quiz_id, array( $q1, $q2 ) );

		$student_id = 101;

		// 6a. Start Practice Attempt: verify questions start sanitized (anti-cheat) and is_immediate is true
		$p_session = IAQ_Quiz_Attempt::start_or_resume_attempt( $practice_quiz_id, $student_id );
		assert( ! is_wp_error( $p_session ), 'Starting practice attempt should succeed' );
		assert( true === $p_session['is_immediate'], 'Practice set with immediate review mode must report is_immediate = true' );
		assert( 'immediate' === $p_session['show_answers_mode'], 'show_answers_mode should be immediate' );

		foreach ( $p_session['questions'] as $pq ) {
			assert( ! isset( $pq['correct_option'] ), 'SECURITY: Unanswered questions must NOT expose correct_option!' );
			assert( ! isset( $pq['explanation'] ), 'SECURITY: Unanswered questions must NOT expose explanation!' );
		}
		echo "    [PASS] Immediate practice set initialized securely with answer keys hidden\n";

		$p_attempt_id = $p_session['attempt_id'];

		// 6b. Correct Answer test: Student selects 'B' for Q1
		$q1_feedback = IAQ_Quiz_Attempt::save_answer( $p_attempt_id, $student_id, $q1, 'B', 0, 10 );
		assert( ! is_wp_error( $q1_feedback ), 'Saving answer for Q1 in practice set should succeed' );
		assert( true === $q1_feedback['is_immediate'], 'save_answer should return is_immediate = true' );
		assert( true === $q1_feedback['is_correct'], 'Q1 selection B is correct' );
		assert( 'B' === $q1_feedback['correct_option'], 'Q1 correct option is B' );
		assert( ! empty( $q1_feedback['explanation'] ), 'Q1 explanation must be provided immediately' );
		assert( 4.00 === (float) $q1_feedback['marks_awarded'], 'Marks awarded for correct answer should be +4.00' );
		assert( strpos( $q1_feedback['feedback_message'], 'Correct' ) !== false, 'Positive message displayed for correct answer' );
		echo "    [PASS] Immediate feedback for correct answer: +4.00, explanation, and positive message\n";

		// 6c. Anti-Tamper & Score Manipulation Protection:
		// Student tries to repeatedly click or change their answer to 'A'
		$tamper_attempt = IAQ_Quiz_Attempt::save_answer( $p_attempt_id, $student_id, $q1, 'A', 0, 15 );
		assert( ! is_wp_error( $tamper_attempt ), 'Tamper request handled gracefully' );
		assert( true === $tamper_attempt['is_locked'], 'Response should be locked against tampering' );
		assert( 'B' === $tamper_attempt['selected'], 'Original selected option B must NOT be changed to A' );
		assert( 4.00 === (float) $tamper_attempt['marks_awarded'], 'Marks must remain original awarded score (+4.00)' );
		echo "    [PASS] Anti-cheat protection: Repeated clicks and answer manipulation prevented\n";

		// 6d. Incorrect Answer test: Student selects 'B' for Q2 (correct is 'A')
		$q2_feedback = IAQ_Quiz_Attempt::save_answer( $p_attempt_id, $student_id, $q2, 'B', 0, 25 );
		assert( ! is_wp_error( $q2_feedback ), 'Saving answer for Q2 should succeed' );
		assert( true === $q2_feedback['is_immediate'], 'save_answer should return is_immediate = true' );
		assert( false === $q2_feedback['is_correct'], 'Q2 selection B is incorrect' );
		assert( 'A' === $q2_feedback['correct_option'], 'Q2 correct option is A' );
		assert( ! empty( $q2_feedback['explanation'] ), 'Q2 explanation must explain why A is correct' );
		assert( -1.00 === (float) $q2_feedback['marks_awarded'], 'Negative marks awarded for incorrect answer (-1.00)' );
		assert( ! empty( $q2_feedback['feedback_message'] ), 'Supportive non-discouraging message displayed' );
		echo "    [PASS] Immediate feedback for wrong answer: -1.00, correct option, explanation, and supportive message\n";

		// 6e. Verify wrong answer auto-registered into Wrong Answer Notebook for student retry drills
		if ( class_exists( 'IAQ_Wrong_Notebook' ) ) {
			$nb_items = IAQ_Wrong_Notebook::get_user_notebook( $student_id );
			$found_q2_in_nb = false;
			foreach ( $nb_items as $item ) {
				if ( (int) $item->question_id === (int) $q2 ) {
					$found_q2_in_nb = true;
					break;
				}
			}
			assert( $found_q2_in_nb, 'Incorrect question must be auto-logged into student Wrong Answer Notebook' );
			echo "    [PASS] Incorrect question auto-logged in Wrong Answer Notebook for revision drills\n";
		}

		// 6f. Consistency across reload / interrupted session:
		$p_resumed = IAQ_Quiz_Attempt::start_or_resume_attempt( $practice_quiz_id, $student_id );
		assert( $p_resumed['attempt_id'] === $p_attempt_id, 'Resumed session must match active attempt ID' );
		assert( ! empty( $p_resumed['questions'][0]['immediate_feedback'] ), 'Answered Q1 feedback preserved on page reload' );
		assert( true === $p_resumed['questions'][0]['immediate_feedback']['is_correct'], 'Q1 correctness preserved' );
		assert( ! empty( $p_resumed['questions'][1]['immediate_feedback'] ), 'Answered Q2 feedback preserved on page reload' );
		assert( false === $p_resumed['questions'][1]['immediate_feedback']['is_correct'], 'Q2 incorrect status preserved' );
		echo "    [PASS] Page reload / connection loss preserves feedback state consistently\n";

		// 6g. Final submission of practice set and scorecard display
		$p_scorecard = IAQ_Quiz_Attempt::submit_attempt( $p_attempt_id, $student_id, 30 );
		assert( ! is_wp_error( $p_scorecard ), 'Practice set submission should succeed' );
		assert( (float) $p_scorecard['score'] === 3.00, 'Practice set score accurately calculated (+4.00 - 1.00 = 3.00)' );
		assert( (float) $p_scorecard['total_marks'] === 8.00, 'Total marks should be 8.00' );
		assert( $p_scorecard['correct_answers'] === 1, '1 correct answer' );
		assert( $p_scorecard['incorrect_answers'] === 1, '1 incorrect answer' );
		assert( $p_scorecard['attempted_questions'] === 2, '2 attempted questions' );
		echo "    [PASS] Practice set scorecard calculated accurately (+4.00 - 1.00 = 3.00 / 8.00)\n";

		// =========================================================================
		// 7. Mock Test Protection Verification (Server-Side Enforced)
		// =========================================================================
		echo "  Testing Mock Test Strict Answer Key Protection...\n";

		$mock_quiz_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'             => 'Strict Full-Length Mock Exam',
				'slug'              => 'strict-mock-exam-' . time(),
				'quiz_type'         => 'mock',
				'exam_category'     => 'ICAR AIEEA PG',
				'duration_minutes'  => 60,
				'correct_marks'     => 4.00,
				'negative_marks'    => 1.00,
				'pass_percentage'   => 40.00,
				'show_answers_mode' => 'immediate', // Intentionally configured to test server override!
				'status'            => 'published',
			)
		);
		IAQ_Quiz_Builder::assign_questions_to_quiz( $mock_quiz_id, array( $q1 ) );

		$mock_session = IAQ_Quiz_Attempt::start_or_resume_attempt( $mock_quiz_id, $student_id );
		assert( false === $mock_session['is_immediate'], 'SERVER OVERRIDE: Mock tests must NEVER have is_immediate = true!' );

		$mock_save = IAQ_Quiz_Attempt::save_answer( $mock_session['attempt_id'], $student_id, $q1, 'B', 0, 10 );
		assert( false === $mock_save['is_immediate'], 'SERVER OVERRIDE: Mock test save_answer must NEVER return is_immediate = true' );
		assert( ! isset( $mock_save['correct_option'] ), 'SECURITY: Mock test save_answer must NOT reveal correct_option!' );
		assert( ! isset( $mock_save['explanation'] ), 'SECURITY: Mock test save_answer must NOT reveal explanation!' );
		assert( ! isset( $mock_save['is_correct'] ), 'SECURITY: Mock test save_answer must NOT reveal is_correct!' );
		echo "    [PASS] Mock test answer key and explanation strictly protected on the server\n";

		echo "All Quiz Attempt tests passed successfully!\n\n";
		return true;
	}
}
