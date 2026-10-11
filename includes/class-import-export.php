<?php
/**
 * CSV Import, Export, and Sample Template generator for the Question Bank.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IAQ_Import_Export
 */
class IAQ_Import_Export {

	/**
	 * Expected CSV Header Columns.
	 *
	 * @return array
	 */
	public static function get_expected_headers() {
		return array(
			'question_text',
			'option_a',
			'option_b',
			'option_c',
			'option_d',
			'correct_option',
			'explanation',
			'subject',
			'topic',
			'exam_category',
			'difficulty',
			'marks',
			'negative_marks',
			'status',
		);
	}

	/**
	 * Generate and stream downloadable sample CSV template containing both English & Hindi agriculture MCQs.
	 */
	public static function generate_sample_csv() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_iaq_quizzes' ) ) {
			wp_die( esc_html__( 'Unauthorized access.', 'indianagri-quiz-engine' ) );
		}

		$filename = 'indianagri-quiz-sample-template.csv';

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Clean output buffer before outputting.
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		$output = fopen( 'php://output', 'w' );

		// Prepend UTF-8 BOM for seamless rendering of Hindi characters in Microsoft Excel.
		fputs( $output, "\xEF\xBB\xBF" );

		// Output Header.
		fputcsv( $output, self::get_expected_headers() );

		// Sample Rows (featuring realistic Indian agriculture exam questions in English and Hindi).
		$sample_rows = array(
			array(
				'Which of the following is known as the "King of Cereals"?',
				'Rice (Oryza sativa)',
				'Wheat (Triticum aestivum)',
				'Maize (Zea mays)',
				'Barley (Hordeum vulgare)',
				'B',
				'Wheat (Triticum aestivum) is widely referred to as the King of Cereals due to its dietary importance and acreage.',
				'Agronomy',
				'Crop Production & Agro-techniques',
				'ICAR AIEEA (UG / PG)',
				'easy',
				'1.00',
				'0.25',
				'published',
			),
			array(
				'धान (Rice) में "खैरा रोग" (Khaira Disease) किसकी कमी के कारण होता है?',
				'नाइट्रोजन (N)',
				'फास्फोरस (P)',
				'जस्ता (Zinc - Zn)',
				'लोहा (Iron - Fe)',
				'C',
				'धान में खैरा रोग जिंक (Zn) की कमी के कारण होता है। इसकी खोज डॉ. वाई. एल. नेने ने पंतनगर में की थी।',
				'Plant Pathology',
				'Diseases of Field & Horticultural Crops',
				'IBPS AFO (Mains)',
				'medium',
				'1.00',
				'0.25',
				'published',
			),
			array(
				'What is the optimum seed rate for Hybrid Rice per hectare in India?',
				'12 - 15 kg/ha',
				'25 - 30 kg/ha',
				'40 - 50 kg/ha',
				'75 - 100 kg/ha',
				'A',
				'The recommended seed rate for hybrid rice production under transplanted conditions is 12 to 15 kg per hectare.',
				'Agronomy',
				'Crop Production & Agro-techniques',
				'ICAR JRF / SRF / NET',
				'medium',
				'1.00',
				'0.25',
				'published',
			),
			array(
				'काली मिट्टी (Black Soil) में किस क्ले खनिज (Clay Mineral) की प्रधानता होती है?',
				'काओलिनाइट (Kaolinite)',
				'मोंटमोरिलोनाइट (Montmorillonite)',
				'इलाइट (Illite)',
				'क्लोराइट (Chlorite)',
				'B',
				'काली मिट्टी (Regur soil/Vertisol) में मोंटमोरिलोनाइट खनिज की अधिकता होती है, जिससे इसमें फूलने और सिकुड़ने का गुण पाया जाता है।',
				'Soil Science & Agricultural Chemistry',
				'Soil Physics & Mineralogy',
				'State Agriculture PSC',
				'hard',
				'1.00',
				'0.33',
				'published',
			),
			array(
				'National Bank for Agriculture and Rural Development (NABARD) was established on the recommendation of which committee?',
				'Narasimham Committee',
				'B. Sivaraman Committee (CRAFICARD)',
				'Hilton Young Commission',
				'Kelkar Committee',
				'B',
				'NABARD was established on 12 July 1982 based on the recommendations of the B. Sivaraman Committee.',
				'Agricultural Economics',
				'Agricultural Finance & Co-operation',
				'NABARD Grade A & B',
				'easy',
				'1.00',
				'0.25',
				'published',
			),
		);

		foreach ( $sample_rows as $row ) {
			fputcsv( $output, $row );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Export questions to CSV based on current filters.
	 *
	 * @param array $filter_args Filter arguments matching Question Bank query.
	 */
	public static function export_to_csv( $filter_args = array() ) {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_iaq_quizzes' ) ) {
			wp_die( esc_html__( 'Unauthorized access.', 'indianagri-quiz-engine' ) );
		}

		// Disable pagination to export all matching questions.
		$filter_args['per_page'] = 100000;
		$filter_args['page']     = 1;

		$questions = IAQ_Question_Bank::get_questions( $filter_args );

		$filename = 'indianagri-questions-export-' . gmdate( 'Y-m-d-His' ) . '.csv';

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		if ( ob_get_level() ) {
			ob_end_clean();
		}

		$output = fopen( 'php://output', 'w' );
		// Prepend UTF-8 BOM.
		fputs( $output, "\xEF\xBB\xBF" );

		fputcsv( $output, self::get_expected_headers() );

		foreach ( $questions as $q ) {
			$row = array(
				self::sanitize_for_csv_cell( $q->question_text ),
				self::sanitize_for_csv_cell( $q->option_a ),
				self::sanitize_for_csv_cell( $q->option_b ),
				self::sanitize_for_csv_cell( $q->option_c ),
				self::sanitize_for_csv_cell( $q->option_d ),
				$q->correct_option,
				self::sanitize_for_csv_cell( $q->explanation ),
				$q->subject_name ? $q->subject_name : '',
				$q->topic_name ? $q->topic_name : '',
				$q->exam_category,
				$q->difficulty,
				$q->marks,
				$q->negative_marks,
				$q->status,
			);
			fputcsv( $output, $row );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Parse and import questions from uploaded CSV file.
	 *
	 * @param string $file_path Path to uploaded CSV file.
	 * @return array Import results summary.
	 */
	public static function import_from_csv( $file_path ) {
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return array(
				'success' => false,
				'message' => __( 'Uploaded CSV file could not be read.', 'indianagri-quiz-engine' ),
				'stats'   => array(),
			);
		}

		$handle = fopen( $file_path, 'r' );
		if ( ! $handle ) {
			return array(
				'success' => false,
				'message' => __( 'Could not open CSV file stream.', 'indianagri-quiz-engine' ),
				'stats'   => array(),
			);
		}

		// Handle UTF-8 BOM if present at the beginning of the file.
		$bom = fread( $handle, 3 );
		if ( "\xEF\xBB\xBF" !== $bom ) {
			rewind( $handle );
		}

		// Read and normalize headers.
		$raw_header = fgetcsv( $handle );
		if ( ! $raw_header ) {
			fclose( $handle );
			return array(
				'success' => false,
				'message' => __( 'CSV file is empty or missing headers.', 'indianagri-quiz-engine' ),
				'stats'   => array(),
			);
		}

		$header = array();
		foreach ( $raw_header as $col ) {
			// Strip spaces and hidden BOM/control chars.
			$cleaned = trim( preg_replace( '/[\x00-\x1F\x80-\xFF]/', '', strtolower( $col ) ) );
			$cleaned = str_replace( array( ' ', '-' ), '_', $cleaned );
			$header[] = $cleaned;
		}

		// Ensure mandatory columns exist.
		$mandatory_cols = array( 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_option' );
		foreach ( $mandatory_cols as $mand ) {
			if ( ! in_array( $mand, $header, true ) ) {
				fclose( $handle );
				return array(
					'success' => false,
					'message' => sprintf(
						/* translators: %s: column name */
						__( 'Missing required column "%s" in CSV header.', 'indianagri-quiz-engine' ),
						esc_html( $mand )
					),
					'stats'   => array(),
				);
			}
		}

		// Preload subjects and topics lookup maps for fast batch insertion.
		$subject_map = self::get_subject_lookup_map();
		$topic_map   = self::get_topic_lookup_map();

		$row_number = 1; // Header is row 1
		$imported   = 0;
		$skipped    = 0;
		$errors     = array();

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_number++;

			// Skip completely empty lines.
			if ( array( null ) === $row || empty( array_filter( $row ) ) ) {
				continue;
			}

			// Map associative row.
			$row_data = array();
			foreach ( $header as $idx => $key ) {
				$row_data[ $key ] = isset( $row[ $idx ] ) ? trim( $row[ $idx ] ) : '';
			}

			// Validate question text.
			if ( empty( $row_data['question_text'] ) ) {
				$skipped++;
				$errors[] = sprintf( __( 'Row %d skipped: Question text is empty.', 'indianagri-quiz-engine' ), $row_number );
				continue;
			}

			// Validate options.
			if ( '' === $row_data['option_a'] || '' === $row_data['option_b'] || '' === $row_data['option_c'] || '' === $row_data['option_d'] ) {
				$skipped++;
				$errors[] = sprintf( __( 'Row %d skipped: One or more options (A, B, C, D) are empty.', 'indianagri-quiz-engine' ), $row_number );
				continue;
			}

			// Validate correct option.
			$correct_option = strtoupper( $row_data['correct_option'] );
			if ( ! in_array( $correct_option, array( 'A', 'B', 'C', 'D' ), true ) ) {
				$skipped++;
				$errors[] = sprintf( __( 'Row %d skipped: Correct option "%s" is invalid (must be A, B, C, or D).', 'indianagri-quiz-engine' ), $row_number, esc_html( $row_data['correct_option'] ) );
				continue;
			}

			// Resolve or dynamically create Subject.
			$subject_id = 0;
			$subj_name  = ! empty( $row_data['subject'] ) ? $row_data['subject'] : 'General Agriculture';
			$subj_key   = strtolower( trim( $subj_name ) );

			if ( isset( $subject_map[ $subj_key ] ) ) {
				$subject_id = $subject_map[ $subj_key ];
			} else {
				$res = IAQ_Question_Bank::save_subject( array( 'name' => $subj_name ) );
				if ( ! is_wp_error( $res ) ) {
					$subject_id                 = (int) $res;
					$subject_map[ $subj_key ]   = $subject_id;
				}
			}

			// Resolve or dynamically create Topic.
			$topic_id = null;
			if ( ! empty( $row_data['topic'] ) ) {
				$topic_name = $row_data['topic'];
				$topic_key  = $subject_id . '_' . strtolower( trim( $topic_name ) );

				if ( isset( $topic_map[ $topic_key ] ) ) {
					$topic_id = $topic_map[ $topic_key ];
				} else {
					$res = IAQ_Question_Bank::save_topic(
						array(
							'subject_id' => $subject_id,
							'name'       => $topic_name,
						)
					);
					if ( ! is_wp_error( $res ) ) {
						$topic_id               = (int) $res;
						$topic_map[ $topic_key ] = $topic_id;
					}
				}
			}

			// Difficulty.
			$difficulty = ! empty( $row_data['difficulty'] ) ? strtolower( $row_data['difficulty'] ) : 'medium';
			if ( ! in_array( $difficulty, array( 'easy', 'medium', 'hard' ), true ) ) {
				$difficulty = 'medium';
			}

			// Exam category.
			$exam_cat = ! empty( $row_data['exam_category'] ) ? $row_data['exam_category'] : 'General Agriculture';

			// Marks & negative marks.
			$marks          = isset( $row_data['marks'] ) && is_numeric( $row_data['marks'] ) ? floatval( $row_data['marks'] ) : 1.00;
			$negative_marks = isset( $row_data['negative_marks'] ) && is_numeric( $row_data['negative_marks'] ) ? floatval( $row_data['negative_marks'] ) : 0.00;

			// Status.
			$status = ! empty( $row_data['status'] ) ? strtolower( $row_data['status'] ) : 'published';
			if ( ! in_array( $status, array( 'draft', 'published', 'archived' ), true ) ) {
				$status = 'published';
			}

			// Build payload for Question Bank.
			$question_payload = array(
				'subject_id'     => $subject_id,
				'topic_id'       => $topic_id,
				'exam_category'  => $exam_cat,
				'difficulty'     => $difficulty,
				'question_text'  => $row_data['question_text'],
				'option_a'       => $row_data['option_a'],
				'option_b'       => $row_data['option_b'],
				'option_c'       => $row_data['option_c'],
				'option_d'       => $row_data['option_d'],
				'correct_option' => $correct_option,
				'marks'          => $marks,
				'negative_marks' => $negative_marks,
				'explanation'    => isset( $row_data['explanation'] ) ? $row_data['explanation'] : '',
				'status'         => $status,
				'created_by'     => get_current_user_id(),
			);

			$saved = IAQ_Question_Bank::save_question( $question_payload );
			if ( is_wp_error( $saved ) ) {
				$skipped++;
				$errors[] = sprintf( __( 'Row %d error: %s', 'indianagri-quiz-engine' ), $row_number, $saved->get_error_message() );
			} else {
				$imported++;
			}
		}

		fclose( $handle );

		return array(
			'success'  => true,
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => array_slice( $errors, 0, 15 ), // Return first 15 errors to keep notice clean
			'total'    => $row_number - 1,
		);
	}

	/**
	 * Build subject lookup map (name_lowercase => id).
	 *
	 * @return array
	 */
	private static function get_subject_lookup_map() {
		$subjects = IAQ_Question_Bank::get_subjects();
		$map      = array();
		foreach ( $subjects as $s ) {
			$map[ strtolower( trim( $s->name ) ) ] = (int) $s->id;
		}
		return $map;
	}

	/**
	 * Build topic lookup map (subjectId_nameLowercase => id).
	 *
	 * @return array
	 */
	private static function get_topic_lookup_map() {
		$topics = IAQ_Question_Bank::get_topics();
		$map    = array();
		foreach ( $topics as $t ) {
			$map[ $t->subject_id . '_' . strtolower( trim( $t->name ) ) ] = (int) $t->id;
		}
		return $map;
	}

	/**
	 * Sanitize cell value to prevent CSV formula injection while preserving Hindi Unicode text.
	 *
	 * @param string $value Cell string.
	 * @return string
	 */
	public static function sanitize_for_csv_cell( $value ) {
		$value = wp_strip_all_tags( (string) $value );
		// If text begins with dangerous spreadsheet formula triggers, prefix with apostrophe.
		if ( ! empty( $value ) && in_array( substr( $value, 0, 1 ), array( '=', '+', '-', '@' ), true ) ) {
			$value = "'" . $value;
		}
		return $value;
	}

	/**
	 * Alias for sanitize_for_csv_cell.
	 *
	 * @param string $value Cell string.
	 * @return string
	 */
	public static function sanitize_cell( $value ) {
		return self::sanitize_for_csv_cell( $value );
	}
}
