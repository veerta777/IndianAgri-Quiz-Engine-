<?php
/**
 * Test Quiz Builder, Question Linking, Scoring Rules, and Frontend Shortcodes.
 *
 * @package IndianAgri_Quiz_Engine
 */

require_once __DIR__ . '/bootstrap.php';

class Test_Quiz_Builder {

	public static function run() {
		echo "Running Test_Quiz_Builder...\n";
		global $wpdb;
		$wpdb->reset();

		// 1. Seed Subject & Sample Questions
		$subj_id = IAQ_Question_Bank::save_subject(
			array(
				'name' => 'Agronomy',
				'slug' => 'agronomy',
			)
		);

		$q1 = IAQ_Question_Bank::save_question(
			array(
				'subject_id'     => $subj_id,
				'question_text'  => 'What is the optimum depth of sowing for dwarf wheat?',
				'option_a'       => '2 - 3 cm',
				'option_b'       => '4 - 5 cm',
				'option_c'       => '7 - 8 cm',
				'option_d'       => '10 - 12 cm',
				'correct_option' => 'B',
				'marks'          => 2.00,
				'negative_marks' => 0.50,
				'status'         => 'published',
			)
		);

		$q2 = IAQ_Question_Bank::save_question(
			array(
				'subject_id'     => $subj_id,
				'question_text'  => 'Which crop is known as "Drosophila of Crop Plants"?',
				'option_a'       => 'Maize (Zea mays)',
				'option_b'       => 'Rice',
				'option_c'       => 'Wheat',
				'option_d'       => 'Barley',
				'correct_option' => 'A',
				'marks'          => 2.00,
				'negative_marks' => 0.50,
				'status'         => 'published',
			)
		);

		$q3 = IAQ_Question_Bank::save_question(
			array(
				'subject_id'     => $subj_id,
				'question_text'  => 'पौधों में जस्ता (Zn) का मुख्य कार्य क्या है?',
				'option_a'       => 'क्लोरोफिल निर्माण',
				'option_b'       => 'ऑक्सिन (Auxin) संश्लेषण',
				'option_c'       => 'नाइट्रोजन स्थिरीकरण',
				'option_d'       => 'प्रोटीन संश्लेषण',
				'correct_option' => 'B',
				'marks'          => 2.00,
				'negative_marks' => 0.50,
				'status'         => 'published',
			)
		);

		echo "  [PASS] Prerequisite agricultural questions seeded.\n";

		// 2. Test Quiz Validation & Creation
		// Should fail on empty title
		$err_quiz = IAQ_Quiz_Builder::save_quiz( array( 'title' => '' ) );
		assert( is_wp_error( $err_quiz ) && $err_quiz->get_error_message() === 'Quiz title is required.', "Validation should reject empty title" );

		// Valid Quiz Creation
		$quiz_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'             => 'ICAR JRF Agronomy Full Mock Test 01',
				'slug'              => 'icar-jrf-agronomy-mock-01',
				'quiz_type'         => 'mock',
				'exam_category'     => 'ICAR JRF / SRF / NET',
				'subject_id'        => $subj_id,
				'duration_minutes'  => 60,
				'correct_marks'     => 2.00,
				'negative_marks'    => 0.50,
				'pass_percentage'   => 40.00,
				'shuffle_questions' => 1,
				'shuffle_options'   => 1,
				'max_attempts'      => 3,
				'access_type'       => 'free',
				'show_answers_mode' => 'after_submission',
				'instructions'      => 'Please read all questions carefully. Time limit is 60 minutes.',
				'status'            => 'published',
			)
		);
		assert( ! is_wp_error( $quiz_id ), "Quiz creation failed" );
		$quiz_id = (int) $quiz_id;

		$fetched_quiz = IAQ_Quiz_Builder::get_quiz( $quiz_id );
		assert( $fetched_quiz && $fetched_quiz->title === 'ICAR JRF Agronomy Full Mock Test 01', "Quiz title mismatch" );
		assert( (int) $fetched_quiz->duration_minutes === 60, "Duration mismatch" );
		assert( floatval( $fetched_quiz->correct_marks ) === 2.00, "Marks mismatch" );
		echo "  [PASS] Quiz creation, parameter assignment, and validation verified.\n";

		// 3. Question Linking & Sequence
		$added_count = IAQ_Quiz_Builder::assign_questions_to_quiz( $quiz_id, array( $q1, $q2, $q3 ) );
		assert( $added_count === 3, "Expected 3 questions linked, got " . $added_count );

		// Duplicate assign guard: assigning same questions again should add 0
		$dup_count = IAQ_Quiz_Builder::assign_questions_to_quiz( $quiz_id, array( $q1, $q2 ) );
		assert( $dup_count === 0, "Duplicate questions were erroneously allowed" );

		$assigned_q = IAQ_Quiz_Builder::get_quiz_questions( $quiz_id );
		assert( count( $assigned_q ) === 3, "Assigned questions count mismatch" );
		assert( (int) $assigned_q[0]->id === $q1, "Question order sequence mismatch" );

		// Verify automatic totals recalculation
		$updated_quiz = IAQ_Quiz_Builder::get_quiz( $quiz_id );
		assert( (int) $updated_quiz->total_questions === 3, "Total questions total not updated" );
		assert( floatval( $updated_quiz->total_marks ) === 6.00, "Total marks calculation incorrect (expected 6.00, got " . $updated_quiz->total_marks . ")" );
		echo "  [PASS] Question assignment, duplicate protection, and automatic score aggregation verified.\n";

		// 4. Test Question Removal & Recalculation
		$removed = IAQ_Quiz_Builder::remove_question_from_quiz( $quiz_id, $q3 );
		assert( $removed === true, "Failed to remove question" );

		$after_removal_q = IAQ_Quiz_Builder::get_quiz_questions( $quiz_id );
		assert( count( $after_removal_q ) === 2, "Question removal failed" );

		$recalced_quiz = IAQ_Quiz_Builder::get_quiz( $quiz_id );
		assert( (int) $recalced_quiz->total_questions === 2, "Total questions not updated after removal" );
		assert( floatval( $recalced_quiz->total_marks ) === 4.00, "Total marks not updated after removal" );
		echo "  [PASS] Question detachment and live score adjustment verified.\n";

		// 5. Test Frontend Shortcode Rendering
		$frontend = new IAQ_Frontend();
		$frontend->init();

		// Shortcode [iaq_quiz id="X"]
		$quiz_html = $frontend->render_quiz_shortcode( array( 'id' => $quiz_id ) );
		assert( strpos( $quiz_html, 'ICAR JRF Agronomy Full Mock Test 01' ) !== false, "Quiz title missing in shortcode output" );
		assert( strpos( $quiz_html, 'Total Questions' ) !== false, "Parameters matrix missing in shortcode" );
		assert( strpos( $quiz_html, 'Start Examination Now' ) !== false, "Start button CTA missing in shortcode" );
		echo "  [PASS] Shortcode [iaq_quiz id=\"...\"] rendered candidate overview correctly.\n";

		// Shortcode [iaq_quiz_list]
		$list_html = $frontend->render_quiz_list_shortcode( array() );
		assert( strpos( $list_html, 'iaq-quiz-grid' ) !== false, "Quiz grid missing in list shortcode" );
		assert( strpos( $list_html, 'ICAR JRF Agronomy Full Mock Test 01' ) !== false, "Quiz card missing in list shortcode" );
		// 5b. Test Default Randomize Question Order and Option Order
		$default_practice_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'     => 'Default Practice Set Random Order Check',
				'quiz_type' => 'practice',
			)
		);
		assert( ! is_wp_error( $default_practice_id ), 'Default practice set creation failed' );
		$def_practice = IAQ_Quiz_Builder::get_quiz( $default_practice_id );
		assert( (int) $def_practice->shuffle_questions === 1, 'Practice set should default shuffle_questions to 1' );
		assert( (int) $def_practice->shuffle_options === 1, 'Practice set should default shuffle_options to 1' );
		IAQ_Quiz_Builder::delete_quiz( $default_practice_id );

		$default_mcq_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'     => 'Default MCQ Quiz Random Order Check',
				'quiz_type' => 'mock',
			)
		);
		assert( ! is_wp_error( $default_mcq_id ), 'Default MCQ creation failed' );
		$def_mcq = IAQ_Quiz_Builder::get_quiz( $default_mcq_id );
		assert( (int) $def_mcq->shuffle_questions === 1, 'MCQ Quiz should default shuffle_questions to 1' );
		assert( (int) $def_mcq->shuffle_options === 1, 'MCQ Quiz should default shuffle_options to 1' );
		IAQ_Quiz_Builder::delete_quiz( $default_mcq_id );
		echo "  [PASS] Default Randomize Question Order and Option Order (A/B/C/D) verified.\n";

		// 6. Test Quiz Deletion & Cascade Clean-up
		$del_res = IAQ_Quiz_Builder::delete_quiz( $quiz_id );
		assert( $del_res === true, "Quiz deletion failed" );
		assert( null === IAQ_Quiz_Builder::get_quiz( $quiz_id ), "Quiz still exists after deletion" );
		assert( empty( IAQ_Quiz_Builder::get_quiz_questions( $quiz_id ) ), "Question links not cleaned up after quiz deletion" );
		echo "  [PASS] Quiz deletion and relationship cascade cleanup verified.\n";

		echo "Test_Quiz_Builder: ALL PASS\n\n";
	}
}
