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
	 * Generate and stream downloadable sample JSON template containing sample agriculture MCQs in exact requested schema.
	 */
	public static function generate_sample_json() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_iaq_quizzes' ) ) {
			wp_die( esc_html__( 'Unauthorized access.', 'indianagri-quiz-engine' ) );
		}

		$filename = 'indianagri-quiz-sample-template.json';

		header( 'Content-Type: application/json; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		if ( ob_get_level() ) {
			ob_end_clean();
		}

		$sample_data = array(
			'questions' => array(
				array(
					'questionType' => 'MCQ_SINGLE',
					'stem'         => 'Which one of the following is considered the primary objective of tillage?',
					'explanation'  => 'Preparing a suitable seedbed for crop establishment is a primary objective of tillage.',
					'difficulty'   => 'MEDIUM',
					'subject'      => 'Agronomy',
					'topic'        => 'Tillage & Tilth',
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
					'subject'      => 'Agronomy',
					'topic'        => 'Tillage & Tilth',
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
					'subject'      => 'Agronomy',
					'topic'        => 'Tillage & Tilth',
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
					'stem'         => 'धान (Rice) में "खैरा रोग" (Khaira Disease) किसकी कमी के कारण होता है?',
					'explanation'  => 'धान में खैरा रोग जिंक (Zn) की कमी के कारण होता है। इसकी खोज डॉ. वाई. एल. नेने ने पंतनगर में की थी।',
					'difficulty'   => 'MEDIUM',
					'subject'      => 'Plant Pathology',
					'topic'        => 'Diseases of Field & Horticultural Crops',
					'options'      => array(
						array(
							'label'     => 'A',
							'text'      => 'नाइट्रोजन (N)',
							'isCorrect' => false,
						),
						array(
							'label'     => 'B',
							'text'      => 'फास्फोरस (P)',
							'isCorrect' => false,
						),
						array(
							'label'     => 'C',
							'text'      => 'जस्ता (Zinc - Zn)',
							'isCorrect' => true,
						),
						array(
							'label'     => 'D',
							'text'      => 'लोहा (Iron - Fe)',
							'isCorrect' => false,
						),
					),
				),
			),
		);

		echo wp_json_encode( $sample_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
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
	 * Default maximum number of MCQs allowed per single bulk CSV import.
	 *
	 * @var int
	 */
	const DEFAULT_MAX_IMPORT_LIMIT = 200;

	/**
	 * Get the maximum allowed number of MCQs per bulk CSV import batch.
	 *
	 * Defaults to 200 MCQs, customizable via plugin settings and filterable via 'iaq_bulk_import_limit'.
	 *
	 * @return int
	 */
	public static function get_max_import_limit() {
		$settings = get_option( 'iaq_settings', array() );
		$limit    = ! empty( $settings['bulk_import_max_limit'] ) ? absint( $settings['bulk_import_max_limit'] ) : self::DEFAULT_MAX_IMPORT_LIMIT;
		if ( $limit <= 0 ) {
			$limit = self::DEFAULT_MAX_IMPORT_LIMIT;
		}
		return (int) apply_filters( 'iaq_bulk_import_limit', $limit );
	}

	/**
	 * Parse and import questions from uploaded CSV file.
	 *
	 * @param string   $file_path Path to uploaded CSV file.
	 * @param int      $target_quiz_id Optional Quiz / Practice Set ID to auto-assign imported questions to.
	 * @param int|null $max_limit Maximum number of questions to import in this batch (defaults to 200).
	 * @param string   $duplicate_handling How to handle duplicate questions: 'skip', 'update', or 'allow'.
	 * @return array Import results summary.
	 */
	public static function import_from_csv( $file_path, $target_quiz_id = 0, $max_limit = null, $duplicate_handling = 'skip' ) {
		if ( null === $max_limit ) {
			$max_limit = self::get_max_import_limit();
		} else {
			$max_limit = absint( $max_limit );
		}

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

		$row_number            = 1; // Header is row 1
		$imported              = 0;
		$skipped               = 0;
		$errors                = array();
		$imported_question_ids = array();
		$seen_stems            = array();

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( $max_limit > 0 && $imported >= $max_limit ) {
				$errors[] = sprintf(
					/* translators: 1: max limit, 2: imported count */
					__( 'Bulk import batch limit reached (%1$d MCQs max). The first %2$d valid questions were imported; remaining rows were skipped.', 'indianagri-quiz-engine' ),
					$max_limit,
					$imported
				);
				break;
			}

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

			// Clean and normalize stem for duplicate detection (preserves Hindi / Devanagari Unicode).
			$clean_stem = preg_replace( '/\s+/u', ' ', trim( wp_strip_all_tags( $row_data['question_text'] ) ) );
			$stem_hash  = md5( mb_strtolower( $clean_stem, 'UTF-8' ) );

			// Check 1: Intra-batch duplicate check (duplicate within the same CSV file).
			if ( 'allow' !== $duplicate_handling && isset( $seen_stems[ $stem_hash ] ) ) {
				$prev_row = $seen_stems[ $stem_hash ]['row'];
				$skipped++;
				$errors[] = sprintf(
					/* translators: 1: current row number, 2: previous row number */
					__( 'Row %1$d skipped: Duplicate question within the import file (already encountered at row %2$d).', 'indianagri-quiz-engine' ),
					$row_number,
					$prev_row
				);
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

			// Check 2: Database duplicate check (existing question in Question Bank).
			$existing_id = null;
			if ( 'allow' !== $duplicate_handling ) {
				$existing_id = IAQ_Question_Bank::find_duplicate_question( $row_data['question_text'], $subject_id );
			}

			if ( $existing_id ) {
				if ( 'skip' === $duplicate_handling ) {
					// In Zero Quiz Duplication architecture:
					// If importing directly into a target quiz, link existing question without creating duplicate row in DB.
					if ( $target_quiz_id > 0 ) {
						if ( ! in_array( $existing_id, $imported_question_ids, true ) ) {
							$imported_question_ids[] = (int) $existing_id;
						}
						$imported++;
						$seen_stems[ $stem_hash ] = array(
							'row' => $row_number,
							'id'  => (int) $existing_id,
						);
						continue;
					}

					// When importing into Question Bank only, skip and record duplicate notification.
					$skipped++;
					$errors[] = sprintf(
						/* translators: 1: row number, 2: existing question id */
						__( 'Row %1$d skipped: Question already exists in Question Bank (ID #%2$d).', 'indianagri-quiz-engine' ),
						$row_number,
						$existing_id
					);
					$seen_stems[ $stem_hash ] = array(
						'row' => $row_number,
						'id'  => (int) $existing_id,
					);
					continue;
				} elseif ( 'update' === $duplicate_handling ) {
					// Update existing question record.
					$question_payload['id'] = $existing_id;
					$saved = IAQ_Question_Bank::save_question( $question_payload );
					if ( is_wp_error( $saved ) ) {
						$skipped++;
						$errors[] = sprintf( __( 'Row %d error: %s', 'indianagri-quiz-engine' ), $row_number, $saved->get_error_message() );
					} else {
						$imported++;
						$imported_question_ids[] = (int) $existing_id;
						$seen_stems[ $stem_hash ] = array(
							'row' => $row_number,
							'id'  => (int) $existing_id,
						);
					}
					continue;
				}
			}

			$saved = IAQ_Question_Bank::save_question( $question_payload );
			if ( is_wp_error( $saved ) ) {
				$skipped++;
				$errors[] = sprintf( __( 'Row %d error: %s', 'indianagri-quiz-engine' ), $row_number, $saved->get_error_message() );
			} else {
				$imported++;
				$imported_question_ids[] = (int) $saved;
				$seen_stems[ $stem_hash ] = array(
					'row' => $row_number,
					'id'  => (int) $saved,
				);
			}
		}

		fclose( $handle );

		// If a target quiz ID was provided, auto-assign all newly imported questions.
		if ( ! empty( $target_quiz_id ) && ! empty( $imported_question_ids ) ) {
			IAQ_Quiz_Builder::assign_questions_to_quiz( (int) $target_quiz_id, $imported_question_ids );
		}

		return array(
			'success'               => true,
			'imported'              => $imported,
			'skipped'               => $skipped,
			'max_limit'             => $max_limit,
			'errors'                => array_slice( $errors, 0, 15 ), // Return first 15 errors to keep notice clean
			'total'                 => $row_number - 1,
			'imported_question_ids' => $imported_question_ids,
			'target_quiz_id'        => (int) $target_quiz_id,
		);
	}

	/**
	 * Parse and import questions from uploaded JSON file.
	 *
	 * Supports the format:
	 * {
	 *   "questions": [
	 *     {
	 *       "questionType": "MCQ_SINGLE",
	 *       "stem": "Which one of the following is considered the primary objective of tillage?",
	 *       "explanation": "...",
	 *       "difficulty": "MEDIUM",
	 *       "options": [
	 *         { "label": "A", "text": "Increasing soil compaction", "isCorrect": false },
	 *         { "label": "B", "text": "Preparing a suitable seedbed for crop establishment", "isCorrect": true },
	 *         ...
	 *       ]
	 *     }
	 *   ]
	 * }
	 *
	 * @param string   $file_path Path to uploaded JSON file.
	 * @param int      $target_quiz_id Optional Quiz / Practice Set ID to auto-assign imported questions to.
	 * @param int|null $max_limit Maximum number of questions to import in this batch (defaults to 200).
	 * @param string   $duplicate_handling How to handle duplicate questions: 'skip', 'update', or 'allow'.
	 * @return array Import results summary.
	 */
	public static function import_from_json( $file_path, $target_quiz_id = 0, $max_limit = null, $duplicate_handling = 'skip' ) {
		if ( null === $max_limit ) {
			$max_limit = self::get_max_import_limit();
		} else {
			$max_limit = absint( $max_limit );
		}

		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return array(
				'success' => false,
				'message' => __( 'Uploaded JSON file could not be read.', 'indianagri-quiz-engine' ),
				'stats'   => array(),
			);
		}

		$raw_content = file_get_contents( $file_path );
		if ( false === $raw_content || '' === trim( $raw_content ) ) {
			return array(
				'success' => false,
				'message' => __( 'JSON file is empty or could not be loaded.', 'indianagri-quiz-engine' ),
				'stats'   => array(),
			);
		}

		// Handle UTF-8 BOM if present.
		if ( substr( $raw_content, 0, 3 ) === "\xEF\xBB\xBF" ) {
			$raw_content = substr( $raw_content, 3 );
		}

		$decoded = json_decode( $raw_content, true );
		if ( null === $decoded && json_last_error() !== JSON_ERROR_NONE ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: json error message */
					__( 'Invalid JSON syntax: %s', 'indianagri-quiz-engine' ),
					json_last_error_msg()
				),
				'stats'   => array(),
			);
		}

		$raw_questions = array();
		if ( isset( $decoded['questions'] ) && is_array( $decoded['questions'] ) ) {
			$raw_questions = $decoded['questions'];
		} elseif ( is_array( $decoded ) && ! empty( $decoded ) && isset( $decoded[0] ) ) {
			$raw_questions = $decoded;
		} else {
			return array(
				'success' => false,
				'message' => __( 'JSON structure must contain a "questions" array containing question objects.', 'indianagri-quiz-engine' ),
				'stats'   => array(),
			);
		}

		// Preload lookup maps.
		$subject_map = self::get_subject_lookup_map();
		$topic_map   = self::get_topic_lookup_map();

		$imported              = 0;
		$skipped               = 0;
		$errors                = array();
		$imported_question_ids = array();
		$seen_stems            = array();
		$item_number           = 0;

		foreach ( $raw_questions as $q_data ) {
			$item_number++;

			if ( $max_limit > 0 && $imported >= $max_limit ) {
				$errors[] = sprintf(
					/* translators: 1: max limit, 2: imported count */
					__( 'Bulk import batch limit reached (%1$d MCQs max). The first %2$d valid questions were imported; remaining questions in the JSON file were skipped.', 'indianagri-quiz-engine' ),
					$max_limit,
					$imported
				);
				break;
			}

			if ( ! is_array( $q_data ) ) {
				$skipped++;
				$errors[] = sprintf( __( 'Question #%d skipped: Invalid question object structure.', 'indianagri-quiz-engine' ), $item_number );
				continue;
			}

			// Extract stem / question_text.
			$stem = '';
			if ( ! empty( $q_data['stem'] ) ) {
				$stem = trim( $q_data['stem'] );
			} elseif ( ! empty( $q_data['question_text'] ) ) {
				$stem = trim( $q_data['question_text'] );
			} elseif ( ! empty( $q_data['question'] ) ) {
				$stem = trim( $q_data['question'] );
			}

			if ( empty( $stem ) ) {
				$skipped++;
				$errors[] = sprintf( __( 'Question #%d skipped: Question stem/text is empty.', 'indianagri-quiz-engine' ), $item_number );
				continue;
			}

			// Clean and normalize stem for duplicate detection.
			$clean_stem = preg_replace( '/\s+/u', ' ', trim( wp_strip_all_tags( $stem ) ) );
			$stem_hash  = md5( mb_strtolower( $clean_stem, 'UTF-8' ) );

			// Check 1: Intra-batch duplicate check.
			if ( 'allow' !== $duplicate_handling && isset( $seen_stems[ $stem_hash ] ) ) {
				$prev_item = $seen_stems[ $stem_hash ]['item'];
				$skipped++;
				$errors[] = sprintf(
					/* translators: 1: current item number, 2: previous item number */
					__( 'Question #%1$d skipped: Duplicate question within the import file (already encountered at question #%2$d).', 'indianagri-quiz-engine' ),
					$item_number,
					$prev_item
				);
				continue;
			}

			// Extract and map options.
			$options_raw    = isset( $q_data['options'] ) && is_array( $q_data['options'] ) ? $q_data['options'] : array();
			$options_map    = array();
			$correct_option = '';

			$label_keys = array( 'A', 'B', 'C', 'D' );
			foreach ( $options_raw as $idx => $opt ) {
				if ( ! is_array( $opt ) ) {
					continue;
				}
				$label = ! empty( $opt['label'] ) ? strtoupper( trim( $opt['label'] ) ) : ( isset( $label_keys[ $idx ] ) ? $label_keys[ $idx ] : '' );
				$text  = isset( $opt['text'] ) ? trim( $opt['text'] ) : ( isset( $opt['option'] ) ? trim( $opt['option'] ) : '' );

				if ( ! empty( $label ) ) {
					$options_map[ $label ] = $text;
				}

				$is_correct = false;
				if ( isset( $opt['isCorrect'] ) ) {
					$is_correct = ( true === $opt['isCorrect'] || 'true' === $opt['isCorrect'] || 1 === $opt['isCorrect'] || '1' === $opt['isCorrect'] );
				} elseif ( isset( $opt['is_correct'] ) ) {
					$is_correct = ( true === $opt['is_correct'] || 'true' === $opt['is_correct'] || 1 === $opt['is_correct'] || '1' === $opt['is_correct'] );
				}

				if ( $is_correct && ! empty( $label ) ) {
					$correct_option = $label;
				}
			}

			// Fallback: If options weren't in array or correct_option wasn't in options, check direct fields.
			if ( empty( $options_map['A'] ) && isset( $q_data['option_a'] ) ) {
				$options_map['A'] = trim( $q_data['option_a'] );
			}
			if ( empty( $options_map['B'] ) && isset( $q_data['option_b'] ) ) {
				$options_map['B'] = trim( $q_data['option_b'] );
			}
			if ( empty( $options_map['C'] ) && isset( $q_data['option_c'] ) ) {
				$options_map['C'] = trim( $q_data['option_c'] );
			}
			if ( empty( $options_map['D'] ) && isset( $q_data['option_d'] ) ) {
				$options_map['D'] = trim( $q_data['option_d'] );
			}

			if ( empty( $correct_option ) ) {
				if ( ! empty( $q_data['correctOption'] ) ) {
					$correct_option = strtoupper( trim( $q_data['correctOption'] ) );
				} elseif ( ! empty( $q_data['correct_option'] ) ) {
					$correct_option = strtoupper( trim( $q_data['correct_option'] ) );
				}
			}

			// Validate all 4 options exist.
			if ( ! isset( $options_map['A'] ) || '' === $options_map['A'] ||
			     ! isset( $options_map['B'] ) || '' === $options_map['B'] ||
			     ! isset( $options_map['C'] ) || '' === $options_map['C'] ||
			     ! isset( $options_map['D'] ) || '' === $options_map['D'] ) {
				$skipped++;
				$errors[] = sprintf( __( 'Question #%d skipped: One or more options (A, B, C, D) are empty.', 'indianagri-quiz-engine' ), $item_number );
				continue;
			}

			// Validate correct option.
			if ( ! in_array( $correct_option, array( 'A', 'B', 'C', 'D' ), true ) ) {
				$skipped++;
				$errors[] = sprintf( __( 'Question #%d skipped: No valid correct option specified (isCorrect=true on A, B, C, or D).', 'indianagri-quiz-engine' ), $item_number );
				continue;
			}

			// Resolve or dynamically create Subject.
			$subject_id = 0;
			$subj_name  = ! empty( $q_data['subject'] ) ? $q_data['subject'] : 'General Agriculture';
			$subj_key   = strtolower( trim( $subj_name ) );

			if ( isset( $subject_map[ $subj_key ] ) ) {
				$subject_id = $subject_map[ $subj_key ];
			} else {
				$res = IAQ_Question_Bank::save_subject( array( 'name' => $subj_name ) );
				if ( ! is_wp_error( $res ) ) {
					$subject_id               = (int) $res;
					$subject_map[ $subj_key ] = $subject_id;
				}
			}

			// Resolve or dynamically create Topic.
			$topic_id = null;
			if ( ! empty( $q_data['topic'] ) ) {
				$topic_name = $q_data['topic'];
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
						$topic_id                 = (int) $res;
						$topic_map[ $topic_key ] = $topic_id;
					}
				}
			}

			// Difficulty.
			$difficulty = ! empty( $q_data['difficulty'] ) ? strtolower( $q_data['difficulty'] ) : 'medium';
			if ( ! in_array( $difficulty, array( 'easy', 'medium', 'hard' ), true ) ) {
				$difficulty = 'medium';
			}

			// Exam category.
			$exam_cat = ! empty( $q_data['exam_category'] ) ? $q_data['exam_category'] : ( ! empty( $q_data['examCategory'] ) ? $q_data['examCategory'] : 'General Agriculture' );

			// Marks & negative marks.
			$marks = isset( $q_data['marks'] ) && is_numeric( $q_data['marks'] ) ? floatval( $q_data['marks'] ) : 1.00;
			$negative_marks = 0.00;
			if ( isset( $q_data['negative_marks'] ) && is_numeric( $q_data['negative_marks'] ) ) {
				$negative_marks = floatval( $q_data['negative_marks'] );
			} elseif ( isset( $q_data['negativeMarks'] ) && is_numeric( $q_data['negativeMarks'] ) ) {
				$negative_marks = floatval( $q_data['negativeMarks'] );
			}

			// Status.
			$status = ! empty( $q_data['status'] ) ? strtolower( $q_data['status'] ) : 'published';
			if ( ! in_array( $status, array( 'draft', 'published', 'archived' ), true ) ) {
				$status = 'published';
			}

			$explanation = ! empty( $q_data['explanation'] ) ? $q_data['explanation'] : '';

			$question_payload = array(
				'subject_id'     => $subject_id,
				'topic_id'       => $topic_id,
				'exam_category'  => $exam_cat,
				'difficulty'     => $difficulty,
				'question_text'  => $stem,
				'option_a'       => $options_map['A'],
				'option_b'       => $options_map['B'],
				'option_c'       => $options_map['C'],
				'option_d'       => $options_map['D'],
				'correct_option' => $correct_option,
				'marks'          => $marks,
				'negative_marks' => $negative_marks,
				'explanation'    => $explanation,
				'status'         => $status,
				'created_by'     => get_current_user_id(),
			);

			// Check 2: Database duplicate check (existing question in Question Bank).
			$existing_id = null;
			if ( 'allow' !== $duplicate_handling ) {
				$existing_id = IAQ_Question_Bank::find_duplicate_question( $stem, $subject_id );
			}

			if ( $existing_id ) {
				if ( 'skip' === $duplicate_handling ) {
					if ( $target_quiz_id > 0 ) {
						if ( ! in_array( $existing_id, $imported_question_ids, true ) ) {
							$imported_question_ids[] = (int) $existing_id;
						}
						$imported++;
						$seen_stems[ $stem_hash ] = array(
							'item' => $item_number,
							'id'   => (int) $existing_id,
						);
						continue;
					}

					$skipped++;
					$errors[] = sprintf(
						/* translators: 1: item number, 2: existing question id */
						__( 'Question #%1$d skipped: Question already exists in Question Bank (ID #%2$d).', 'indianagri-quiz-engine' ),
						$item_number,
						$existing_id
					);
					$seen_stems[ $stem_hash ] = array(
						'item' => $item_number,
						'id'   => (int) $existing_id,
					);
					continue;
				} elseif ( 'update' === $duplicate_handling ) {
					$question_payload['id'] = $existing_id;
					$saved = IAQ_Question_Bank::save_question( $question_payload );
					if ( is_wp_error( $saved ) ) {
						$skipped++;
						$errors[] = sprintf( __( 'Question #%d error: %s', 'indianagri-quiz-engine' ), $item_number, $saved->get_error_message() );
					} else {
						$imported++;
						$imported_question_ids[] = (int) $existing_id;
						$seen_stems[ $stem_hash ] = array(
							'item' => $item_number,
							'id'   => (int) $existing_id,
						);
					}
					continue;
				}
			}

			$saved = IAQ_Question_Bank::save_question( $question_payload );
			if ( is_wp_error( $saved ) ) {
				$skipped++;
				$errors[] = sprintf( __( 'Question #%d error: %s', 'indianagri-quiz-engine' ), $item_number, $saved->get_error_message() );
			} else {
				$imported++;
				$imported_question_ids[] = (int) $saved;
				$seen_stems[ $stem_hash ] = array(
					'item' => $item_number,
					'id'   => (int) $saved,
				);
			}
		}

		if ( ! empty( $target_quiz_id ) && ! empty( $imported_question_ids ) ) {
			IAQ_Quiz_Builder::assign_questions_to_quiz( (int) $target_quiz_id, $imported_question_ids );
		}

		return array(
			'success'               => true,
			'imported'              => $imported,
			'skipped'               => $skipped,
			'max_limit'             => $max_limit,
			'errors'                => array_slice( $errors, 0, 15 ),
			'total'                 => $item_number,
			'imported_question_ids' => $imported_question_ids,
			'target_quiz_id'        => (int) $target_quiz_id,
		);
	}

	/**
	 * Automatically detect format (JSON or CSV) and import questions.
	 *
	 * @param string   $file_path Path to uploaded file.
	 * @param int      $target_quiz_id Optional Quiz / Practice Set ID.
	 * @param int|null $max_limit Maximum questions limit.
	 * @param string   $original_filename Optional original filename to check extension.
	 * @param string   $duplicate_handling How to handle duplicate questions: 'skip', 'update', or 'allow'.
	 * @return array
	 */
	public static function import_from_file( $file_path, $target_quiz_id = 0, $max_limit = null, $original_filename = '', $duplicate_handling = 'skip' ) {
		$ext = strtolower( pathinfo( $original_filename ? $original_filename : $file_path, PATHINFO_EXTENSION ) );
		if ( 'json' === $ext ) {
			return self::import_from_json( $file_path, $target_quiz_id, $max_limit, $duplicate_handling );
		}
		if ( 'csv' === $ext ) {
			return self::import_from_csv( $file_path, $target_quiz_id, $max_limit, $duplicate_handling );
		}

		// Content sniffing fallback if extension is generic or temporary.
		$handle = fopen( $file_path, 'r' );
		if ( $handle ) {
			$first_bytes = fread( $handle, 20 );
			fclose( $handle );
			$trimmed = trim( $first_bytes );
			if ( substr( $trimmed, 0, 1 ) === '{' || substr( $trimmed, 0, 1 ) === '[' ) {
				return self::import_from_json( $file_path, $target_quiz_id, $max_limit, $duplicate_handling );
			}
		}

		return self::import_from_csv( $file_path, $target_quiz_id, $max_limit, $duplicate_handling );
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
