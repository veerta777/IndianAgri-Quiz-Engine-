<?php
/**
 * Test Suite: Phase 4 Subject Weak-Area Analytics, Solutions Review & Scorecard Shortcode.
 *
 * @package IndianAgri_Quiz_Engine
 */

class Test_Results_Analytics {

	public static function run() {
		echo "Running Results & Analytics Test Suite...\n";

		// 1. Create a multi-subject test quiz
		$quiz_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'             => 'Comprehensive Agricultural Examination for Analytics Test',
				'slug'              => 'analytics-test-quiz-' . time(),
				'quiz_type'         => 'mock',
				'exam_category'     => 'ICAR AIEEA (UG / PG)',
				'duration_minutes'  => 45,
				'correct_marks'     => 4.00,
				'negative_marks'    => 1.00,
				'pass_percentage'   => 50.00,
				'shuffle_questions' => 0,
				'shuffle_options'   => 0,
				'access_type'       => 'free',
				'status'            => 'published',
			)
		);
		assert( $quiz_id > 0, 'Multi-subject quiz should be created' );

		// Seed questions in 3 distinct subjects:
		// Subject 1: Agronomy (Subject ID 1)
		$q_agro_1 = IAQ_Question_Bank::save_question(
			array(
				'subject_id'     => 1,
				'exam_category'  => 'ICAR AIEEA (UG / PG)',
				'question_text'  => 'Agro Q1: Critical stage of irrigation in Wheat?',
				'option_a'       => 'Tillering stage',
				'option_b'       => 'CRI (Crown Root Initiation) stage',
				'option_c'       => 'Jointing stage',
				'option_d'       => 'Dough stage',
				'correct_option' => 'B',
				'marks'          => 4.00,
				'negative_marks' => 1.00,
				'explanation'    => 'CRI occurs 21 days after sowing and is the most sensitive irrigation stage.',
				'status'         => 'published',
			)
		);

		$q_agro_2 = IAQ_Question_Bank::save_question(
			array(
				'subject_id'     => 1,
				'exam_category'  => 'ICAR AIEEA (UG / PG)',
				'question_text'  => 'Agro Q2: Sowing depth of semi-dwarf wheat?',
				'option_a'       => '4-5 cm',
				'option_b'       => '8-10 cm',
				'option_c'       => '1-2 cm',
				'option_d'       => '12 cm',
				'correct_option' => 'A',
				'marks'          => 4.00,
				'negative_marks' => 1.00,
				'explanation'    => 'Coleoptile length of semi-dwarf wheat (Norin-10) is short, requiring 4-5 cm depth.',
				'status'         => 'published',
			)
		);

		// Subject 2: Soil Science (Subject ID 2)
		$q_soil_1 = IAQ_Question_Bank::save_question(
			array(
				'subject_id'     => 2,
				'exam_category'  => 'ICAR AIEEA (UG / PG)',
				'question_text'  => 'Soil Q1: Most abundant mineral in Earth crust?',
				'option_a'       => 'Feldspar',
				'option_b'       => 'Quartz',
				'option_c'       => 'Mica',
				'option_d'       => 'Olivine',
				'correct_option' => 'A',
				'marks'          => 4.00,
				'negative_marks' => 1.00,
				'explanation'    => 'Feldspars make up approximately 48% of the Earth crust.',
				'status'         => 'published',
			)
		);

		$q_soil_2 = IAQ_Question_Bank::save_question(
			array(
				'subject_id'     => 2,
				'exam_category'  => 'ICAR AIEEA (UG / PG)',
				'question_text'  => 'Soil Q2: Soil reclamation agent for sodic soils?',
				'option_a'       => 'Lime (CaCO3)',
				'option_b'       => 'Gypsum (CaSO4.2H2O)',
				'option_c'       => 'Zinc Sulfate',
				'option_d'       => 'Rock Phosphate',
				'correct_option' => 'B',
				'marks'          => 4.00,
				'negative_marks' => 1.00,
				'explanation'    => 'Gypsum replaces exchangeable sodium with calcium ions in sodic soils.',
				'status'         => 'published',
			)
		);

		// Subject 3: Genetics & Plant Breeding (Subject ID 3)
		$q_gen_1 = IAQ_Question_Bank::save_question(
			array(
				'subject_id'     => 3,
				'exam_category'  => 'ICAR AIEEA (UG / PG)',
				'question_text'  => 'Genetics Q1: Law of Segregation discovered by?',
				'option_a'       => 'Gregor Johann Mendel',
				'option_b'       => 'Thomas Hunt Morgan',
				'option_c'       => 'Hugo de Vries',
				'option_d'       => 'Carl Correns',
				'correct_option' => 'A',
				'marks'          => 4.00,
				'negative_marks' => 1.00,
				'explanation'    => 'Mendel formulated the first law of inheritance based on monohybrid crosses in Pisum sativum.',
				'status'         => 'published',
			)
		);

		// Assign all 5 questions across 3 subjects
		IAQ_Quiz_Builder::assign_questions_to_quiz( $quiz_id, array( $q_agro_1, $q_agro_2, $q_soil_1, $q_soil_2, $q_gen_1 ) );

		$user_id = 1;

		// 2. Start examination attempt session
		$session = IAQ_Quiz_Attempt::start_or_resume_attempt( $quiz_id, $user_id );
		$attempt_id = $session['attempt_id'];
		assert( $session['total_questions'] === 5, 'Session should have 5 total questions' );

		// Candidate answers:
		// Agronomy: Q1 correct ('B'), Q2 correct ('A') -> 2/2 = 100% -> Strong
		IAQ_Quiz_Attempt::save_answer( $attempt_id, $user_id, $q_agro_1, 'B', 0, 10 );
		IAQ_Quiz_Attempt::save_answer( $attempt_id, $user_id, $q_agro_2, 'A', 0, 20 );

		// Soil Science: Q1 correct ('A'), Q2 incorrect ('C', correct is B) -> 1/2 = 50% -> Moderate
		IAQ_Quiz_Attempt::save_answer( $attempt_id, $user_id, $q_soil_1, 'A', 0, 30 );
		IAQ_Quiz_Attempt::save_answer( $attempt_id, $user_id, $q_soil_2, 'C', 0, 40 );

		// Genetics: Q1 unattempted -> 0/1 = 0% -> Weak

		// 3. Submit and evaluate attempt
		$scorecard = IAQ_Quiz_Attempt::submit_attempt( $attempt_id, $user_id, 40 );
		assert( $scorecard['total_questions'] === 5, 'Total questions should be 5' );
		assert( $scorecard['correct_answers'] === 3, 'Correct answers should be 3' );
		assert( $scorecard['incorrect_answers'] === 1, 'Incorrect answers should be 1' );
		assert( $scorecard['unattempted_questions'] === 1, 'Unattempted questions should be 1' );
		// Score = (3 * 4) - (1 * 1) = 11.00
		assert( (float) $scorecard['score'] === 11.00, 'Score should be 11.00' );
		echo "  [PASS] Multi-subject test submission and negative marks grading verified\n";

		// 4. Verify Subject Weak-Area Analytics Breakdown
		$breakdown = IAQ_Quiz_Attempt::get_attempt_subject_breakdown( $attempt_id, $user_id );
		assert( count( $breakdown ) === 3, 'Breakdown should aggregate into 3 subjects' );

		$by_subject = array();
		foreach ( $breakdown as $b ) {
			$by_subject[ $b['subject_id'] ] = $b;
		}

		// Verify Agronomy Mastery (100% -> strong)
		assert( isset( $by_subject[1] ), 'Agronomy subject should be present in breakdown' );
		assert( $by_subject[1]['total_questions'] === 2, 'Agronomy total questions should be 2' );
		assert( $by_subject[1]['correct'] === 2, 'Agronomy correct answers should be 2' );
		assert( $by_subject[1]['accuracy_percent'] == 100.0, 'Agronomy accuracy should be 100%' );
		assert( $by_subject[1]['mastery_level'] === 'strong', 'Agronomy mastery level should be strong' );
		echo "  [PASS] Agronomy mastery correctly identified as 'strong' (100% accuracy)\n";

		// Verify Soil Science Mastery (50% -> moderate)
		assert( isset( $by_subject[2] ), 'Soil Science subject should be present in breakdown' );
		assert( $by_subject[2]['total_questions'] === 2, 'Soil total questions should be 2' );
		assert( $by_subject[2]['correct'] === 1, 'Soil correct answers should be 1' );
		assert( $by_subject[2]['incorrect'] === 1, 'Soil incorrect answers should be 1' );
		assert( $by_subject[2]['accuracy_percent'] == 50.0, 'Soil accuracy should be 50%' );
		assert( $by_subject[2]['mastery_level'] === 'moderate', 'Soil mastery level should be moderate' );
		echo "  [PASS] Soil Science mastery correctly identified as 'moderate' (50% accuracy)\n";

		// Verify Genetics Mastery (0% -> weak)
		assert( isset( $by_subject[3] ), 'Genetics subject should be present in breakdown' );
		assert( $by_subject[3]['total_questions'] === 1, 'Genetics total questions should be 1' );
		assert( $by_subject[3]['unattempted'] === 1, 'Genetics unattempted should be 1' );
		assert( $by_subject[3]['accuracy_percent'] == 0.0, 'Genetics accuracy should be 0%' );
		assert( $by_subject[3]['mastery_level'] === 'weak', 'Genetics mastery level should be weak' );
		echo "  [PASS] Genetics & Plant Breeding mastery correctly identified as 'weak' (Needs Revision)\n";

		// 5. Verify Review Questions Structure
		$review_questions = IAQ_Quiz_Attempt::get_attempt_review_questions( $attempt_id, $user_id );
		assert( count( $review_questions ) === 5, 'Review questions count should be 5' );
		assert( ! empty( $review_questions[0]['explanation'] ), 'Detailed explanation should be present for review' );
		assert( ! empty( $review_questions[0]['subject_name'] ), 'Subject name should be tagged on review item' );
		echo "  [PASS] Review questions contain full verified keys, subject tags, and scientific explanations\n";

		// 6. Verify Negative Deductions & Cohort Percentile Guard
		assert( isset( $scorecard['negative_deductions'] ), 'Negative deductions field should be present in scorecard' );
		assert( $scorecard['negative_deductions'] == 1.0, '1 incorrect question should result in 1.00 negative deduction' );
		assert( $scorecard['percentile'] === null, 'Percentile should be null when cohort < 5 to prevent false statistics' );
		echo "  [PASS] Negative marking deductions (-1.00) and Insufficient Cohort Percentile protection verified\n";

		// Seed 4 additional attempts to reach cohort >= 5
		global $wpdb;
		for ( $i = 2; $i <= 5; $i++ ) {
			$wpdb->insert(
				$wpdb->prefix . 'iaq_quiz_attempts',
				array(
					'quiz_id'             => $quiz_id,
					'user_id'             => 100 + $i,
					'status'              => 'completed',
					'score'               => ( $i * 2 ),
					'total_marks'         => 20.0,
					'percentage'          => ( $i * 10 ),
					'time_spent_seconds'  => 600,
					'total_questions'     => 5,
					'attempted_questions' => 5,
					'correct_answers'     => $i,
					'incorrect_answers'   => 5 - $i,
					'start_time'          => current_time( 'mysql' ),
					'end_time'            => current_time( 'mysql' ),
				)
			);
		}
		$scorecard_with_cohort = IAQ_Quiz_Attempt::get_attempt_scorecard( $attempt_id, $user_id );
		assert( $scorecard_with_cohort['cohort_count'] >= 5, 'Cohort count should now be at least 5' );
		assert( $scorecard_with_cohort['percentile'] !== null, 'Percentile should now be calculated with valid cohort' );
		assert( $scorecard_with_cohort['percentile'] >= 0 && $scorecard_with_cohort['percentile'] <= 100, 'Percentile must be between 0 and 100' );
		echo "  [PASS] Cohort Percentile successfully computed (" . $scorecard_with_cohort['percentile'] . "th percentile) with valid comparison population (N=" . $scorecard_with_cohort['cohort_count'] . ")\n";

		// 7. Verify Shortcode [iaq_quiz_results id="..."]
		$frontend = new IAQ_Frontend();
		$html = $frontend->render_results_shortcode( array( 'id' => $attempt_id ) );
		assert( strpos( $html, 'iaq-standalone-scorecard' ) !== false, 'Scorecard template should render in shortcode' );
		assert( strpos( $html, 'iaq-subject-analytics-card' ) !== false, 'Subject analytics table should be included in shortcode output' );
		assert( strpos( $html, 'iaq-solution-card' ) !== false, 'Question solutions should be included in shortcode output' );
		assert( strpos( $html, 'is-negative-deduction' ) !== false, 'Negative deduction badge should be rendered on scorecard' );
		echo "  [PASS] Shortcode [iaq_quiz_results id=\"...\"] rendered complete scorecard & solutions portal\n";

		echo "All Results & Analytics tests passed successfully!\n\n";
		return true;
	}
}
