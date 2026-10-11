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

		echo "Test_Import_Export: ALL PASS\n\n";
	}
}
