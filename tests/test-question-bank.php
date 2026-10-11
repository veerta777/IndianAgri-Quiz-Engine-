<?php
/**
 * Test Question Bank CRUD, Taxonomies, and Bilingual Validation.
 *
 * @package IndianAgri_Quiz_Engine
 */

require_once __DIR__ . '/bootstrap.php';

class Test_Question_Bank {

	public static function run() {
		echo "Running Test_Question_Bank...\n";
		global $wpdb;
		$wpdb->reset();

		// 1. Subject CRUD
		$subj_res = IAQ_Question_Bank::save_subject(
			array(
				'name'        => 'Agronomy',
				'slug'        => 'agronomy',
				'description' => 'Crop production principles',
			)
		);
		assert( ! is_wp_error( $subj_res ), "Failed to save subject: " . ( is_wp_error( $subj_res ) ? $subj_res->get_error_message() : '' ) );
		$subject_id = (int) $subj_res;

		$fetched_subj = IAQ_Question_Bank::get_subject( $subject_id );
		assert( $fetched_subj && $fetched_subj->name === 'Agronomy', "Fetched subject mismatch" );
		echo "  [PASS] Subject CRUD works correctly.\n";

		// 2. Topic CRUD
		$topic_res = IAQ_Question_Bank::save_topic(
			array(
				'subject_id'  => $subject_id,
				'name'        => 'Weed Science',
				'slug'        => 'weed-science',
				'description' => 'Herbicide classification and weed biology',
			)
		);
		assert( ! is_wp_error( $topic_res ), "Failed to save topic" );
		$topic_id = (int) $topic_res;

		$fetched_topic = IAQ_Question_Bank::get_topic( $topic_id );
		assert( $fetched_topic && $fetched_topic->name === 'Weed Science', "Fetched topic mismatch" );
		echo "  [PASS] Topic CRUD works correctly.\n";

		// 3. Question Validation Tests
		// Test A: Empty question text should fail
		$err_q = IAQ_Question_Bank::save_question(
			array(
				'question_text'  => '',
				'option_a'       => 'Opt A',
				'option_b'       => 'Opt B',
				'option_c'       => 'Opt C',
				'option_d'       => 'Opt D',
				'correct_option' => 'A',
			)
		);
		assert( is_wp_error( $err_q ) && $err_q->get_error_message() === 'Question text is required.', "Validation should reject empty question text" );

		// Test B: Missing option should fail
		$err_opt = IAQ_Question_Bank::save_question(
			array(
				'question_text'  => 'What is the seed rate of wheat?',
				'option_a'       => '100 kg/ha',
				'option_b'       => '',
				'option_c'       => '125 kg/ha',
				'option_d'       => '150 kg/ha',
				'correct_option' => 'A',
			)
		);
		assert( is_wp_error( $err_opt ), "Validation should reject empty option" );

		// Test C: Invalid correct option should fail
		$err_corr = IAQ_Question_Bank::save_question(
			array(
				'question_text'  => 'What is the seed rate of wheat?',
				'option_a'       => '100 kg/ha',
				'option_b'       => '110 kg/ha',
				'option_c'       => '125 kg/ha',
				'option_d'       => '150 kg/ha',
				'correct_option' => 'Z',
			)
		);
		assert( is_wp_error( $err_corr ), "Validation should reject invalid correct option" );
		echo "  [PASS] Input validation accurately enforces integrity constraints.\n";

		// 4. Save English MCQ
		$q1_res = IAQ_Question_Bank::save_question(
			array(
				'subject_id'     => $subject_id,
				'topic_id'       => $topic_id,
				'exam_category'  => 'ICAR AIEEA (UG / PG)',
				'difficulty'     => 'easy',
				'question_text'  => 'Which herbicide is commonly used as a selective pre-emergence herbicide in Wheat?',
				'option_a'       => 'Pendimethalin',
				'option_b'       => 'Glyphosate',
				'option_c'       => 'Paraquat',
				'option_d'       => 'Atrazine',
				'correct_option' => 'A',
				'marks'          => 1.00,
				'negative_marks' => 0.25,
				'explanation'    => 'Pendimethalin 30% EC is recommended as a pre-emergence selective herbicide in wheat crops.',
				'status'         => 'published',
			)
		);
		assert( ! is_wp_error( $q1_res ), "Failed to save English MCQ" );
		$q1_id = (int) $q1_res;

		// 5. Save Hindi (Devanagari Unicode) MCQ
		$hindi_q_text    = 'धान (Rice) में "खैरा रोग" (Khaira Disease) किसकी कमी के कारण होता है?';
		$hindi_opt_c     = 'जस्ता (Zinc - Zn)';
		$hindi_expl      = 'धान में खैरा रोग जिंक की कमी से होता है।';

		$q2_res = IAQ_Question_Bank::save_question(
			array(
				'subject_id'     => $subject_id,
				'topic_id'       => $topic_id,
				'exam_category'  => 'IBPS AFO (Mains)',
				'difficulty'     => 'medium',
				'question_text'  => $hindi_q_text,
				'option_a'       => 'नाइट्रोजन (N)',
				'option_b'       => 'फास्फोरस (P)',
				'option_c'       => $hindi_opt_c,
				'option_d'       => 'पोटाश (K)',
				'correct_option' => 'C',
				'marks'          => 2.00,
				'negative_marks' => 0.50,
				'explanation'    => $hindi_expl,
				'status'         => 'published',
			)
		);
		assert( ! is_wp_error( $q2_res ), "Failed to save Hindi MCQ" );
		$q2_id = (int) $q2_res;

		// 6. Verify Bilingual Unicode Integrity
		$fetched_hindi_q = IAQ_Question_Bank::get_question( $q2_id );
		assert( $fetched_hindi_q->question_text === $hindi_q_text, "Hindi question text corrupted" );
		assert( $fetched_hindi_q->option_c === $hindi_opt_c, "Hindi option text corrupted" );
		assert( $fetched_hindi_q->explanation === $hindi_expl, "Hindi explanation corrupted" );
		assert( $fetched_hindi_q->subject_name === 'Agronomy', "Joined subject name mismatch" );
		assert( $fetched_hindi_q->topic_name === 'Weed Science', "Joined topic name mismatch" );
		echo "  [PASS] Full bilingual Hindi (Devanagari) Unicode preservation verified.\n";

		// 7. Test Bulk Status Update
		$bulk_stat = IAQ_Question_Bank::bulk_update_status( array( $q1_id, $q2_id ), 'draft' );
		assert( $bulk_stat === 1, "Bulk status update failed" );
		echo "  [PASS] Bulk status update works as expected.\n";

		// 8. Test Delete Question
		$del_res = IAQ_Question_Bank::delete_question( $q1_id );
		assert( $del_res === true, "Delete question failed" );
		assert( null === IAQ_Question_Bank::get_question( $q1_id ), "Question still exists after deletion" );
		echo "  [PASS] Single Question deletion and cleanup verified.\n";

		echo "Test_Question_Bank: ALL PASS\n\n";
	}
}
