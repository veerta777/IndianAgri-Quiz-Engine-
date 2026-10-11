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

		echo "All Quiz Attempt tests passed successfully!\n\n";
		return true;
	}
}
