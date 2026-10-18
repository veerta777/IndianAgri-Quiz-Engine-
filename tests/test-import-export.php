<?php
/**
 * Test CSV Import, Export, Validation, and Formula Injection Guard.
 *
 * @package IndianAgri_Quiz_Engine
 */

require_once __DIR__ . '/bootstrap.php';

class Test_Import_Export {

	public static function run() {
		echo "Running Test_Import_Export...\n";
		global $wpdb;
		$wpdb->reset();

		// 1. Verify Expected Headers
		$expected = IAQ_Import_Export::get_expected_headers();
		assert( in_array( 'question_text', $expected, true ), "Header missing question_text" );
		assert( in_array( 'correct_option', $expected, true ), "Header missing correct_option" );
		assert( in_array( 'subject', $expected, true ), "Header missing subject" );
		echo "  [PASS] Expected CSV headers structure verified.\n";

		// 2. Test CSV Import with Both English & Hindi MCQs
		$temp_csv = sys_get_temp_dir() . '/test_iaq_import_' . time() . '.csv';

		$fp = fopen( $temp_csv, 'w' );
		// Prepend UTF-8 BOM
		fputs( $fp, "\xEF\xBB\xBF" );

		// Header
		fputcsv( $fp, $expected );

		// Row 1: Valid English Question
		fputcsv(
			$fp,
			array(
				'What is the optimum temperature for wheat tillering?',
				'10 - 15 °C',
				'16 - 20 °C',
				'25 - 30 °C',
				'30 - 35 °C',
				'B',
				'The optimum temperature for tillering stage in wheat is 16-20 degrees Celsius.',
				'Agronomy',
				'Crop Production & Agro-techniques',
				'ICAR AIEEA (UG / PG)',
				'medium',
				'1.00',
				'0.25',
				'published',
			)
		);

		// Row 2: Valid Hindi Question
		fputcsv(
			$fp,
			array(
				'राष्ट्रीय कृषि और ग्रामीण विकास बैंक (NABARD) की स्थापना किस वर्ष हुई थी?',
				'1975',
				'1982',
				'1991',
				'2000',
				'B',
				'नाबार्ड की स्थापना 12 जुलाई 1982 को शिवरामन समिति की सिफारिश पर की गई थी।',
				'Agricultural Economics',
				'Agricultural Finance & Co-operation',
				'NABARD Grade A & B',
				'easy',
				'1.00',
				'0.25',
				'published',
			)
		);

		// Row 3: Invalid Question (Missing Options)
		fputcsv(
			$fp,
			array(
				'Which element causes blossom end rot in Tomato?',
				'Calcium',
				'',
				'Nitrogen',
				'Potassium',
				'A',
				'Blossom end rot is caused by Calcium deficiency.',
				'Horticulture',
				'Vegetable Science',
				'General Agriculture',
				'easy',
				'1.00',
				'0.25',
				'published',
			)
		);

		// Row 4: Invalid Correct Option (Z instead of A/B/C/D)
		fputcsv(
			$fp,
			array(
				'What is the family of Sunflower?',
				'Asteraceae',
				'Fabaceae',
				'Poaceae',
				'Solanaceae',
				'Z',
				'Sunflower belongs to Asteraceae.',
				'Genetics & Plant Breeding',
				'Principles of Genetics',
				'General Agriculture',
				'easy',
				'1.00',
				'0.25',
				'published',
			)
		);

		fclose( $fp );

		// Run Import
		$result = IAQ_Import_Export::import_from_csv( $temp_csv );
		unlink( $temp_csv );

		assert( $result['success'] === true, "Import failed unexpectedly" );
		assert( $result['imported'] === 2, "Expected 2 imported questions, got " . $result['imported'] );
		assert( $result['skipped'] === 2, "Expected 2 skipped rows, got " . $result['skipped'] );
		assert( count( $result['errors'] ) === 2, "Expected 2 error messages" );
		echo "  [PASS] CSV Import accurately parsed valid rows (English & Hindi) and logged row-by-row validation errors for malformed entries.\n";

		// 3. Verify Auto-Created Subjects & Topics
		$subjects = IAQ_Question_Bank::get_subjects();
		$found_econ = false;
		foreach ( $subjects as $s ) {
			if ( $s->name === 'Agricultural Economics' ) {
				$found_econ = true;
				break;
			}
		}
		assert( $found_econ, "CSV import should auto-create new subject if not existing" );
		echo "  [PASS] Auto-taxonomy creation during CSV import verified.\n";

		// 4. Verify CSV Formula Injection Protection
		$ref_class = new ReflectionClass( 'IAQ_Import_Export' );
		$method    = $ref_class->getMethod( 'sanitize_for_csv_cell' );
		$method->setAccessible( true );

		$dangerous_val = '=SUM(A1:A10)';
		$safe_val      = $method->invoke( null, $dangerous_val );
		assert( $safe_val === "'=SUM(A1:A10)", "Dangerous spreadsheet formula was not neutralized" );

		$regular_hindi = 'गेहूं की बुवाई का समय';
		$safe_hindi    = $method->invoke( null, $regular_hindi );
		assert( $safe_hindi === $regular_hindi, "Hindi Unicode was altered during formula sanitization" );
		echo "  [PASS] Formula injection attack prevention verified without corrupting Hindi characters.\n";

		// 5. Verify Direct Auto-Assignment to Target Quiz / Practice Set
		$quiz_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'     => 'Direct CSV Target Quiz',
				'quiz_type' => 'practice',
			)
		);
		assert( $quiz_id > 0, "Target quiz creation failed" );

		$temp_csv_target = tempnam( sys_get_temp_dir(), 'iaq_csv_target_' ) . '.csv';
		$fp = fopen( $temp_csv_target, 'w' );
		fputcsv( $fp, IAQ_Import_Export::get_expected_headers() );
		fputcsv( $fp, array( 'Direct Question 1', 'A', 'B', 'C', 'D', 'A', 'Expl 1', 'Agronomy', '', 'General', 'easy', '1.00', '0.00', 'published' ) );
		fputcsv( $fp, array( 'Direct Question 2', 'A', 'B', 'C', 'D', 'B', 'Expl 2', 'Agronomy', '', 'General', 'easy', '1.00', '0.00', 'published' ) );
		fclose( $fp );

		$target_result = IAQ_Import_Export::import_from_csv( $temp_csv_target, $quiz_id );
		unlink( $temp_csv_target );

		assert( $target_result['success'] === true, "Target CSV import failed" );
		assert( $target_result['imported'] === 2, "Expected 2 imported questions" );
		$assigned_qids = IAQ_Quiz_Builder::get_quiz_question_ids( $quiz_id );
		assert( count( $assigned_qids ) === 2, "Imported questions should be directly linked to the target quiz" );
		// 6. Verify 200 MCQ Bulk Import Batch Limit
		assert( IAQ_Import_Export::DEFAULT_MAX_IMPORT_LIMIT === 200, "Default import limit should be 200" );
		assert( IAQ_Import_Export::get_max_import_limit() === 200, "get_max_import_limit should return 200" );

		$temp_csv_batch = tempnam( sys_get_temp_dir(), 'iaq_csv_batch_' ) . '.csv';
		$fp = fopen( $temp_csv_batch, 'w' );
		fputcsv( $fp, IAQ_Import_Export::get_expected_headers() );
		// Generate 205 questions to test that it safely stops at exactly 200 MCQs
		for ( $i = 1; $i <= 205; $i++ ) {
			fputcsv(
				$fp,
				array(
					"Bulk Question #{$i} for 200 Limit Test",
					"Option A {$i}",
					"Option B {$i}",
					"Option C {$i}",
					"Option D {$i}",
					'A',
					"Explanation #{$i}",
					'Agronomy',
					'',
					'General',
					'medium',
					'1.00',
					'0.25',
					'published',
				)
			);
		}
		fclose( $fp );

		$batch_res = IAQ_Import_Export::import_from_csv( $temp_csv_batch );
		unlink( $temp_csv_batch );

		assert( $batch_res['success'] === true, "Batch import failed" );
		assert( $batch_res['imported'] === 200, "Expected exactly 200 imported MCQs, got " . $batch_res['imported'] );
		assert( $batch_res['max_limit'] === 200, "Expected max_limit = 200" );
		$has_limit_notice = false;
		foreach ( $batch_res['errors'] as $err ) {
			if ( strpos( $err, '200' ) !== false ) {
				$has_limit_notice = true;
				break;
			}
		}
		assert( $has_limit_notice, "Batch import error notice should mention 200 MCQ limit" );
		echo "  [PASS] Bulk import batch limit of 200 MCQs verified with automatic truncation and notification.\n";

		// 7. Verify JSON Bulk Import with User's Exact Schema
		$sample_user_json = array(
			'questions' => array(
				array(
					'questionType' => 'MCQ_SINGLE',
					'stem'         => 'Which one of the following is considered the primary objective of tillage?',
					'explanation'  => 'Preparing a suitable seedbed for crop establishment is a primary objective of tillage.',
					'difficulty'   => 'MEDIUM',
					'options'      => array(
						array(
							'label'     => 'A',
							'text'      => 'Increasing soil compaction',
							'isCorrect' => false,
						),
						array(
							'label'     => 'B',
							'text'      => 'Preparing a suitable seedbed for crop establishment',
							'isCorrect' => true,
						),
						array(
							'label'     => 'C',
							'text'      => 'Increasing soil erosion',
							'isCorrect' => false,
						),
						array(
							'label'     => 'D',
							'text'      => 'Reducing soil aeration',
							'isCorrect' => false,
						),
					),
				),
				array(
					'questionType' => 'MCQ_SINGLE',
					'stem'         => 'The process of breaking down large soil clods into smaller aggregates during tillage is known as:',
					'explanation'  => 'Pulverization refers to breaking large soil clods into smaller aggregates during tillage.',
					'difficulty'   => 'EASY',
					'options'      => array(
						array(
							'label'     => 'A',
							'text'      => 'Inversion',
							'isCorrect' => false,
						),
						array(
							'label'     => 'B',
							'text'      => 'Pulverization',
							'isCorrect' => true,
						),
						array(
							'label'     => 'C',
							'text'      => 'Compaction',
							'isCorrect' => false,
						),
						array(
							'label'     => 'D',
							'text'      => 'Levelling',
							'isCorrect' => false,
						),
					),
				),
				array(
					'questionType' => 'MCQ_SINGLE',
					'stem'         => 'Which of the following is a primary tillage implement?',
					'explanation'  => 'A mouldboard plough is a primary tillage implement used for opening and turning the soil.',
					'difficulty'   => 'EASY',
					'options'      => array(
						array(
							'label'     => 'A',
							'text'      => 'Plank',
							'isCorrect' => false,
						),
						array(
							'label'     => 'B',
							'text'      => 'Cultivator',
							'isCorrect' => false,
						),
						array(
							'label'     => 'C',
							'text'      => 'Mouldboard plough',
							'isCorrect' => true,
						),
						array(
							'label'     => 'D',
							'text'      => 'Blade harrow',
							'isCorrect' => false,
						),
					),
				),
				array(
					'questionType' => 'MCQ_SINGLE',
					'stem'         => 'मिट्टी में वायु संचार (Soil Aeration) बढ़ाने के लिए किस कृषि क्रिया का उपयोग किया जाता है?',
					'explanation'  => 'जुताई (Tillage) से मिट्टी की संरचना में सुधार होता है और वायु संचार बढ़ता है।',
					'difficulty'   => 'EASY',
					'options'      => array(
						array(
							'label'     => 'A',
							'text'      => 'जुताई (Tillage)',
							'isCorrect' => true,
						),
						array(
							'label'     => 'B',
							'text'      => 'सघन सिंचाई',
							'isCorrect' => false,
						),
						array(
							'label'     => 'C',
							'text'      => 'मृदा संपीड़न',
							'isCorrect' => false,
						),
						array(
							'label'     => 'D',
							'text'      => 'अत्यधिक रोलिंग',
							'isCorrect' => false,
						),
					),
				),
			),
		);

		$temp_json = tempnam( sys_get_temp_dir(), 'iaq_test_json_' ) . '.json';
		file_put_contents( $temp_json, json_encode( $sample_user_json, JSON_UNESCAPED_UNICODE ) );

		$json_import_res = IAQ_Import_Export::import_from_json( $temp_json );
		assert( $json_import_res['success'] === true, "JSON import should succeed" );
		assert( $json_import_res['imported'] === 4, "Expected 4 imported questions, got " . $json_import_res['imported'] );
		assert( $json_import_res['skipped'] === 0, "Expected 0 skipped questions" );

		// Verify database records for the imported JSON questions
		$q1_id = $json_import_res['imported_question_ids'][0];
		$q1 = IAQ_Question_Bank::get_question( $q1_id );
		assert( $q1 !== null, "Question 1 not found in DB" );
		assert( $q1->question_text === 'Which one of the following is considered the primary objective of tillage?', "Stem mismatch" );
		assert( $q1->option_a === 'Increasing soil compaction', "Option A mismatch" );
		assert( $q1->option_b === 'Preparing a suitable seedbed for crop establishment', "Option B mismatch" );
		assert( $q1->correct_option === 'B', "Correct option should be B" );
		assert( $q1->difficulty === 'medium', "Difficulty should be normalized to medium" );
		assert( strpos( $q1->explanation, 'primary objective of tillage' ) !== false, "Explanation mismatch" );

		$q3_id = $json_import_res['imported_question_ids'][2];
		$q3 = IAQ_Question_Bank::get_question( $q3_id );
		assert( $q3->correct_option === 'C', "Question 3 correct option should be C" );
		assert( $q3->option_c === 'Mouldboard plough', "Option C mismatch on Q3" );

		$q4_id = $json_import_res['imported_question_ids'][3];
		$q4 = IAQ_Question_Bank::get_question( $q4_id );
		assert( $q4->correct_option === 'A', "Question 4 (Hindi) correct option should be A" );
		assert( strpos( $q4->question_text, 'वायु संचार' ) !== false, "Hindi Unicode preserved" );

		// Verify auto-routing through import_from_file with allow duplicate mode
		$auto_res = IAQ_Import_Export::import_from_file( $temp_json, 0, null, 'tillage_questions.json', 'allow' );
		unlink( $temp_json );
		assert( $auto_res['success'] === true, "import_from_file auto routing failed" );
		assert( $auto_res['imported'] === 4, "Expected 4 imported questions via auto-detection" );
		echo "  [PASS] JSON bulk import format verified with stem, explanation, options array, and Hindi Unicode support.\n";

		// 8. Verify CSV Duplicate Handling (Intra-batch & Database Duplicates for English & Hindi)
		$temp_dup_csv = tempnam( sys_get_temp_dir(), 'iaq_dup_csv_' ) . '.csv';
		$fp = fopen( $temp_dup_csv, 'w' );
		fputs( $fp, "\xEF\xBB\xBF" ); // Prepend UTF-8 BOM
		fputcsv( $fp, IAQ_Import_Export::get_expected_headers() );

		// Row 1: Fresh English Question
		fputcsv( $fp, array( 'What is the standard seed rate of normal sown wheat?', '50-60 kg/ha', '100-125 kg/ha', '150-175 kg/ha', '200 kg/ha', 'B', 'Standard seed rate of wheat is 100-125 kg/ha.', 'Agronomy', '', 'General', 'medium', '1.00', '0.25', 'published' ) );

		// Row 2: Fresh Hindi Question
		fputcsv( $fp, array( 'सामान्य बुवाई के लिए गेहूं की बीज दर क्या है?', '50-60 किग्रा/हेक्टेयर', '100-125 किग्रा/हेक्टेयर', '150-175 किग्रा/हेक्टेयर', '200 किग्रा/हेक्टेयर', 'B', 'गेहूं की सामान्य बीज दर 100-125 किग्रा प्रति हेक्टेयर होती है।', 'Agronomy', '', 'General', 'medium', '1.00', '0.25', 'published' ) );

		// Row 3: Intra-batch duplicate of Row 1 (English)
		fputcsv( $fp, array( 'What is the standard seed rate of normal sown wheat?', '50-60 kg/ha', '100-125 kg/ha', '150-175 kg/ha', '200 kg/ha', 'B', 'Duplicate entry in same file.', 'Agronomy', '', 'General', 'medium', '1.00', '0.25', 'published' ) );

		// Row 4: Intra-batch duplicate of Row 2 (Hindi)
		fputcsv( $fp, array( 'सामान्य बुवाई के लिए गेहूं की बीज दर क्या है?', '50-60 किग्रा/हेक्टेयर', '100-125 किग्रा/हेक्टेयर', '150-175 किग्रा/हेक्टेयर', '200 किग्रा/हेक्टेयर', 'B', 'समान फ़ाइल में दोहराव।', 'Agronomy', '', 'General', 'medium', '1.00', '0.25', 'published' ) );

		fclose( $fp );

		// Run import with default duplicate handling ('skip')
		$dup_res = IAQ_Import_Export::import_from_csv( $temp_dup_csv, 0, null, 'skip' );
		assert( $dup_res['success'] === true, "Duplicate test CSV import failed" );
		assert( $dup_res['imported'] === 2, "Expected exactly 2 imported questions (1 Eng, 1 Hindi), got " . $dup_res['imported'] );
		assert( $dup_res['skipped'] === 2, "Expected exactly 2 skipped intra-batch duplicate questions, got " . $dup_res['skipped'] );
		assert( count( $dup_res['errors'] ) === 2, "Expected 2 intra-batch duplicate errors" );
		assert( strpos( $dup_res['errors'][0], 'already encountered' ) !== false, "Error message should mention already encountered" );

		// Now test cross-batch / database duplicate detection:
		// Re-uploading the exact same CSV with 'skip' mode should detect both rows as already existing in Question Bank
		$db_dup_res = IAQ_Import_Export::import_from_csv( $temp_dup_csv, 0, null, 'skip' );
		unlink( $temp_dup_csv );

		assert( $db_dup_res['success'] === true, "Re-upload CSV import failed" );
		assert( $db_dup_res['imported'] === 0, "Expected 0 imported questions on re-upload (all duplicates), got " . $db_dup_res['imported'] );
		assert( $db_dup_res['skipped'] === 4, "Expected all 4 rows skipped as database/intra duplicates, got " . $db_dup_res['skipped'] );

		// Verify Zero Quiz Duplication architecture:
		// When importing questions into a target quiz, existing questions are linked to the quiz without creating duplicate rows in Question Bank
		$target_quiz_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'     => 'Target Quiz For Zero Duplication Test',
				'quiz_type' => 'practice',
			)
		);
		$temp_target_csv = tempnam( sys_get_temp_dir(), 'iaq_target_dup_' ) . '.csv';
		$fp = fopen( $temp_target_csv, 'w' );
		fputcsv( $fp, IAQ_Import_Export::get_expected_headers() );
		// Row 1: Existing English question
		fputcsv( $fp, array( 'What is the standard seed rate of normal sown wheat?', '50-60 kg/ha', '100-125 kg/ha', '150-175 kg/ha', '200 kg/ha', 'B', 'Seed rate explanation', 'Agronomy', '', 'General', 'medium', '1.00', '0.25', 'published' ) );
		fclose( $fp );

		$total_q_before = IAQ_Question_Bank::get_question_count();
		$quiz_dup_res   = IAQ_Import_Export::import_from_csv( $temp_target_csv, $target_quiz_id, null, 'skip' );
		unlink( $temp_target_csv );

		$total_q_after = IAQ_Question_Bank::get_question_count();
		assert( $quiz_dup_res['success'] === true, "Quiz target import failed" );
		assert( $quiz_dup_res['imported'] === 1, "Expected 1 linked question to target quiz" );
		assert( $total_q_after === $total_q_before, "Zero Quiz Duplication violated: Question count in Question Bank should NOT increase when reusing existing question!" );

		$linked_qids = IAQ_Quiz_Builder::get_quiz_question_ids( $target_quiz_id );
		assert( count( $linked_qids ) === 1, "Target quiz should be linked to the existing question" );
		echo "  [PASS] CSV Duplicate handling verified: intra-batch duplicates, database duplicates, and Zero Quiz Duplication linking for English & Hindi.\n";

		echo "Test_Import_Export: ALL PASS\n\n";
	}
}
