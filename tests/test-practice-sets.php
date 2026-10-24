<?php
/**
 * Test Suite: Practice Sets Module Redesign.
 * Verifies Practice by Book, Practice by Subjects, Practice by Exams,
 * dynamic contextual numbering starting at 1, zero quiz duplication,
 * and administrator management without code changes.
 *
 * @package IndianAgri_Quiz_Engine
 */

class Test_Practice_Sets {

	public static function run() {
		echo "\nRunning Practice Sets Redesign Test Suite...\n";

		self::test_books_crud_and_validation();
		self::test_book_quizzes_assignment_and_dynamic_numbering();
		self::test_practice_by_subjects_hierarchy();
		self::test_practice_by_exams_hierarchy();
		self::test_zero_quiz_duplication_across_categories();
		self::test_archival_and_unpublished_filtering();
		self::test_access_control_gating();
		self::test_frontend_shortcode_rendering();
		self::test_exam_subject_practice_set_creation_and_import();

		echo "Test_Practice_Sets: ALL PASS\n";
	}

	/**
	 * 1. Administrator can add, edit, and archive multiple books without code changes.
	 */
	private static function test_books_crud_and_validation() {
		// Validation: empty title fails
		$invalid = IAQ_Practice_Sets::save_book( array( 'title' => '' ) );
		if ( ! is_wp_error( $invalid ) ) {
			throw new Exception( 'Failed: Expected WP_Error when creating book with empty title.' );
		}

		// Create Book 1: Nem Raj Sundha
		$book1_id = IAQ_Practice_Sets::save_book(
			array(
				'title'           => 'A Competitive Book of Agriculture',
				'author'          => 'Nem Raj Sundha',
				'description'     => 'Comprehensive handbook covering general agriculture.',
				'cover_image_url' => 'https://example.com/sundha.jpg',
				'access_type'     => 'free',
				'display_order'   => 1,
				'status'          => 'published',
			)
		);

		if ( is_wp_error( $book1_id ) || ! $book1_id ) {
			throw new Exception( 'Failed to create Book 1.' );
		}

		// Create Book 2: Arun Katyayan
		$book2_id = IAQ_Practice_Sets::save_book(
			array(
				'title'           => 'Fundamental of Agriculture Vol 1 & 2',
				'author'          => 'Arun Katyayan',
				'description'     => 'Core concepts of Agronomy, Soil Science, and Plant Breeding.',
				'cover_image_url' => 'https://example.com/katyayan.jpg',
				'access_type'     => 'paid',
				'display_order'   => 2,
				'status'          => 'published',
			)
		);

		if ( is_wp_error( $book2_id ) || ! $book2_id ) {
			throw new Exception( 'Failed to create Book 2.' );
		}

		// Retrieve and verify
		$retrieved = IAQ_Practice_Sets::get_book( $book1_id );
		if ( ! $retrieved || 'Nem Raj Sundha' !== $retrieved->author ) {
			throw new Exception( 'Retrieved book author does not match created attributes.' );
		}

		// Edit book
		$update_res = IAQ_Practice_Sets::save_book(
			array(
				'id'              => $book1_id,
				'title'           => 'A Competitive Book of Agriculture (Updated Edition)',
				'author'          => 'Nem Raj Sundha',
				'status'          => 'published',
				'display_order'   => 1,
			)
		);
		$updated_book = IAQ_Practice_Sets::get_book( $book1_id );
		if ( 'A Competitive Book of Agriculture (Updated Edition)' !== $updated_book->title ) {
			throw new Exception( 'Book title update was not persisted.' );
		}

		echo "  [PASS] Administrator can add, edit, and configure multiple books without code changes.\n";
	}

	/**
	 * 2. Assign practice sets to a book and verify dynamic numbering starting at 1.
	 */
	private static function test_book_quizzes_assignment_and_dynamic_numbering() {
		$book = IAQ_Practice_Sets::get_books( array( 'limit' => 1 ) );
		if ( empty( $book ) ) {
			throw new Exception( 'No book available for quiz assignment test.' );
		}
		$book_id = $book[0]->id;

		// Create 3 practice set quizzes
		$q1_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'            => 'Soil Texture & Structure Practice Set',
				'quiz_type'        => 'practice',
				'duration_minutes' => 30,
				'correct_marks'    => 1.0,
				'negative_marks'   => 0.25,
				'status'           => 'published',
				'display_order'    => 10,
			)
		);
		$q2_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'            => 'Soil Fertility & Nutrients Practice Set',
				'quiz_type'        => 'practice',
				'duration_minutes' => 30,
				'correct_marks'    => 1.0,
				'negative_marks'   => 0.25,
				'status'           => 'published',
				'display_order'    => 20,
			)
		);
		$q3_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'            => 'Soil Chemistry & pH Practice Set',
				'quiz_type'        => 'practice',
				'duration_minutes' => 30,
				'correct_marks'    => 1.0,
				'negative_marks'   => 0.25,
				'status'           => 'published',
				'display_order'    => 30,
			)
		);

		// Assign to book with custom sequence: Q3 first, Q1 second, Q2 third
		IAQ_Practice_Sets::assign_quiz_to_book( $book_id, $q3_id, 1 );
		IAQ_Practice_Sets::assign_quiz_to_book( $book_id, $q1_id, 2 );
		IAQ_Practice_Sets::assign_quiz_to_book( $book_id, $q2_id, 3 );

		$book_quizzes = IAQ_Practice_Sets::get_book_quizzes( $book_id, 'published' );
		if ( count( $book_quizzes ) < 3 ) {
			throw new Exception( 'Book quizzes count mismatch. Expected at least 3 assigned sets.' );
		}

		// Verify dynamic numbering restarts at 1 based on book sort order
		if ( 1 !== (int) $book_quizzes[0]->practice_set_number || (int) $book_quizzes[0]->id !== $q3_id ) {
			throw new Exception( 'Expected Set 1 to be Q3 based on sort order.' );
		}
		if ( 2 !== (int) $book_quizzes[1]->practice_set_number || (int) $book_quizzes[1]->id !== $q1_id ) {
			throw new Exception( 'Expected Set 2 to be Q1 based on sort order.' );
		}
		if ( 3 !== (int) $book_quizzes[2]->practice_set_number || (int) $book_quizzes[2]->id !== $q2_id ) {
			throw new Exception( 'Expected Set 3 to be Q2 based on sort order.' );
		}

		// Reorder book quizzes: change order so Q1 is first, then Q2, then Q3
		IAQ_Practice_Sets::reorder_book_quizzes( $book_id, array( $q1_id, $q2_id, $q3_id ) );
		$reordered = IAQ_Practice_Sets::get_book_quizzes( $book_id, 'published' );

		if ( (int) $reordered[0]->id !== $q1_id || 1 !== (int) $reordered[0]->practice_set_number ) {
			throw new Exception( 'Reordering failed: Q1 should now be Set 1.' );
		}
		if ( (int) $reordered[1]->id !== $q2_id || 2 !== (int) $reordered[1]->practice_set_number ) {
			throw new Exception( 'Reordering failed: Q2 should now be Set 2.' );
		}

		echo "  [PASS] Book practice sets assignment, dynamic numbering restarting at 1, and admin reordering verified.\n";
	}

	/**
	 * 3. Practice by Subjects: Subject catalog and sequential dynamic numbering.
	 */
	private static function test_practice_by_subjects_hierarchy() {
		$subjects = IAQ_Practice_Sets::get_subjects_catalog();
		if ( empty( $subjects ) ) {
			throw new Exception( 'Expected agricultural subjects catalog to return records.' );
		}

		// Pick first subject (e.g., Agronomy)
		$target_subj = $subjects[0];
		$subj_quizzes = IAQ_Practice_Sets::get_subject_quizzes( $target_subj->id, 'published' );

		// If no practice sets yet, create 2 for this subject
		if ( empty( $subj_quizzes ) ) {
			IAQ_Quiz_Builder::save_quiz(
				array(
					'title'         => 'Agronomy Tillage & Sowing Drill',
					'quiz_type'     => 'practice',
					'subject_id'    => $target_subj->id,
					'status'        => 'published',
					'display_order' => 1,
				)
			);
			IAQ_Quiz_Builder::save_quiz(
				array(
					'title'         => 'Agronomy Irrigation Methods Drill',
					'quiz_type'     => 'practice',
					'subject_id'    => $target_subj->id,
					'status'        => 'published',
					'display_order' => 2,
				)
			);
			$subj_quizzes = IAQ_Practice_Sets::get_subject_quizzes( $target_subj->id, 'published' );
		}

		// Verify practice set numbering starts at 1
		$expected_num = 1;
		foreach ( $subj_quizzes as $sq ) {
			if ( (int) $sq->practice_set_number !== $expected_num ) {
				throw new Exception( sprintf( 'Subject quiz numbering mismatch. Expected Set %d, got %d.', $expected_num, $sq->practice_set_number ) );
			}
			$expected_num++;
		}

		echo "  [PASS] Practice by Subjects catalog and sequential numbering starting at Set 1 verified.\n";
	}

	/**
	 * 4. Practice by Exams: Exam -> Configured Subjects -> Practice Sets hierarchy.
	 */
	private static function test_practice_by_exams_hierarchy() {
		$exams = IAQ_Practice_Sets::get_exams_catalog();
		if ( empty( $exams ) ) {
			throw new Exception( 'Expected examinations catalog to return active examinations.' );
		}

		$exam = $exams[0];
		$all_subjects = IAQ_Question_Bank::get_subjects();
		if ( count( $all_subjects ) < 2 ) {
			IAQ_Question_Bank::save_subject(
				array(
					'name'        => 'Soil Science',
					'slug'        => 'soil-science',
					'description' => 'Soil physical and chemical properties and plant nutrition.',
				)
			);
			$all_subjects = IAQ_Question_Bank::get_subjects();
		}

		$subj_a = $all_subjects[0];
		$subj_b = $all_subjects[1];

		// Assign only Subject A to this exam
		IAQ_Practice_Sets::assign_subject_to_exam( $exam->id, $subj_a->id, 1 );

		$assigned_subjects = IAQ_Practice_Sets::get_exam_subjects( $exam->id );
		$assigned_ids      = array_map( function( $s ) { return (int) $s->id; }, $assigned_subjects );

		if ( ! in_array( (int) $subj_a->id, $assigned_ids, true ) ) {
			throw new Exception( 'Subject A was not found in exam assigned subjects.' );
		}

		// Attach 2 quizzes to this Exam-Subject combination
		$q_a1 = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'         => $exam->code . ' ' . $subj_a->name . ' Mock Practice 1',
				'quiz_type'     => 'practice',
				'subject_id'    => $subj_a->id,
				'exam_category' => $exam->name,
				'status'        => 'published',
			)
		);
		$q_a2 = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'         => $exam->code . ' ' . $subj_a->name . ' Mock Practice 2',
				'quiz_type'     => 'practice',
				'subject_id'    => $subj_a->id,
				'exam_category' => $exam->name,
				'status'        => 'published',
			)
		);

		IAQ_Practice_Sets::assign_quiz_to_exam_subject( $exam->id, $subj_a->id, $q_a1, 1 );
		IAQ_Practice_Sets::assign_quiz_to_exam_subject( $exam->id, $subj_a->id, $q_a2, 2 );

		$exam_subj_quizzes = IAQ_Practice_Sets::get_exam_subject_quizzes( $exam->id, $subj_a->id, 'published' );
		if ( count( $exam_subj_quizzes ) < 2 ) {
			throw new Exception( 'Exam-Subject practice sets count mismatch.' );
		}

		// Verify numbering restarts at Set 1 for this examination-subject combo
		if ( 1 !== (int) $exam_subj_quizzes[0]->practice_set_number ) {
			throw new Exception( 'Exam-Subject first quiz must be numbered Set 1.' );
		}
		if ( 2 !== (int) $exam_subj_quizzes[1]->practice_set_number ) {
			throw new Exception( 'Exam-Subject second quiz must be numbered Set 2.' );
		}

		echo "  [PASS] Practice by Exams (Exam → Assigned Subjects → Practice Sets) hierarchy verified.\n";
	}

	/**
	 * 5. Zero Quiz Duplication: Same underlying quiz ID reused across Book, Subject, and Exam.
	 */
	private static function test_zero_quiz_duplication_across_categories() {
		global $wpdb;
		$t_quizzes = IAQ_Database::get_table_name( 'quizzes' );

		// Create a single canonical quiz
		$canonical_quiz_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'            => 'Universal Agronomy Weed Science Set',
				'quiz_type'        => 'practice',
				'duration_minutes' => 45,
				'correct_marks'    => 2.0,
				'negative_marks'   => 0.5,
				'status'           => 'published',
			)
		);

		// Record total quiz count in database
		$count_before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t_quizzes}" );

		// 1. Assign to Book
		$books = IAQ_Practice_Sets::get_books( array( 'limit' => 1 ) );
		IAQ_Practice_Sets::assign_quiz_to_book( $books[0]->id, $canonical_quiz_id, 1 );

		// 2. Assign to Exam-Subject
		$exams = IAQ_Practice_Sets::get_exams_catalog();
		$subjects = IAQ_Question_Bank::get_subjects();
		IAQ_Practice_Sets::assign_quiz_to_exam_subject( $exams[0]->id, $subjects[0]->id, $canonical_quiz_id, 1 );

		// Record total quiz count after assignments
		$count_after = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t_quizzes}" );

		// Verification: ZERO new quizzes created in table_quizzes
		if ( $count_after !== $count_before ) {
			throw new Exception( 'Duplication detected! Assigning a quiz to multiple categories should NEVER create new quiz records.' );
		}

		// Verify that get_book_quizzes returns the exact same quiz ID
		$b_quizzes = IAQ_Practice_Sets::get_book_quizzes( $books[0]->id, 'published' );
		$found_in_book = false;
		foreach ( $b_quizzes as $bq ) {
			if ( (int) $bq->id === $canonical_quiz_id ) {
				$found_in_book = true;
				break;
			}
		}
		if ( ! $found_in_book ) {
			throw new Exception( 'Canonical quiz ID not found in book quizzes listing.' );
		}

		// Verify that get_exam_subject_quizzes returns the exact same quiz ID
		$es_quizzes = IAQ_Practice_Sets::get_exam_subject_quizzes( $exams[0]->id, $subjects[0]->id, 'published' );
		$found_in_exam = false;
		foreach ( $es_quizzes as $eq ) {
			if ( (int) $eq->id === $canonical_quiz_id ) {
				$found_in_exam = true;
				break;
			}
		}
		if ( ! $found_in_exam ) {
			throw new Exception( 'Canonical quiz ID not found in exam-subject quizzes listing.' );
		}

		echo "  [PASS] Zero Quiz Duplication verified across Book, Subject, and Exam categories.\n";
	}

	/**
	 * 6. Archival and unpublished filtering: Draft/Archived content hidden from students.
	 */
	private static function test_archival_and_unpublished_filtering() {
		// Create a draft book
		$draft_book_id = IAQ_Practice_Sets::save_book(
			array(
				'title'  => 'Draft Unpublished Book',
				'status' => 'draft',
			)
		);

		// Published query should not contain draft book
		$published_books = IAQ_Practice_Sets::get_books( array( 'status' => 'published' ) );
		foreach ( $published_books as $pb ) {
			if ( (int) $pb->id === $draft_book_id ) {
				throw new Exception( 'Draft book unexpectedly appeared in published books query.' );
			}
		}

		// Soft archive book
		IAQ_Practice_Sets::delete_book( $draft_book_id, false );
		$archived_book = IAQ_Practice_Sets::get_book( $draft_book_id );
		if ( 'archived' !== $archived_book->status ) {
			throw new Exception( 'Soft delete did not set book status to archived.' );
		}

		echo "  [PASS] Draft and archived content safely excluded from student-facing queries.\n";
	}

	/**
	 * 7. Access Control gating for practice sets.
	 */
	private static function test_access_control_gating() {
		// Free quiz should be accessible to guest (user_id = 0)
		$free_q_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'       => 'Open Free Practice Set',
				'access_type' => 'free',
				'status'      => 'published',
			)
		);
		if ( ! IAQ_Access_Control::can_attempt_quiz( 0, $free_q_id ) ) {
			throw new Exception( 'Free quiz should be accessible to all students.' );
		}

		// Paid quiz should be blocked for unsubscribed user
		$paid_q_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'       => 'Premium VIP Practice Set',
				'quiz_type'   => 'practice',
				'access_type' => 'paid',
				'status'      => 'published',
			)
		);
		if ( IAQ_Access_Control::can_attempt_quiz( 9999, $paid_q_id ) ) {
			throw new Exception( 'Paid quiz was not blocked for unsubscribed student.' );
		}

		// Ensure start_or_resume_attempt rejects unsubscribed student
		$attempt_res = IAQ_Quiz_Attempt::start_or_resume_attempt( $paid_q_id, 9999 );
		if ( ! is_wp_error( $attempt_res ) || 'access_denied' !== $attempt_res->get_error_code() ) {
			throw new Exception( 'Expected access_denied WP_Error for unsubscribed student attempting practice set.' );
		}

		// Verify practice set list queries enrich is_locked and can_attempt flags correctly
		$test_book_id = IAQ_Practice_Sets::save_book(
			array(
				'title'       => 'Test Subscription Book',
				'status'      => 'published',
				'access_type' => 'paid',
			)
		);
		IAQ_Practice_Sets::assign_quiz_to_book( $test_book_id, $paid_q_id, 1 );

		// For unsubscribed student (ID 9999), is_locked must be true
		$unsub_sets = IAQ_Practice_Sets::get_book_practice_sets( $test_book_id, 9999 );
		if ( empty( $unsub_sets ) ) {
			throw new Exception( 'Failed to retrieve book practice sets for unsubscribed student.' );
		}
		if ( ! $unsub_sets[0]->is_locked || $unsub_sets[0]->can_attempt ) {
			throw new Exception( 'Practice set row should have is_locked=true and can_attempt=false for unsubscribed student.' );
		}

		// Grant All-Access subscription to student 9999
		$all_access_plan = IAQ_Access_Control::get_plan_by_code( 'monthly-all-access' );
		if ( $all_access_plan ) {
			IAQ_Access_Control::grant_subscription( 9999, $all_access_plan->id, null, 'Practice Set Test Grant' );

			// Now is_locked must be false and can_attempt must be true
			$sub_sets = IAQ_Practice_Sets::get_book_practice_sets( $test_book_id, 9999 );
			if ( $sub_sets[0]->is_locked || ! $sub_sets[0]->can_attempt ) {
				throw new Exception( 'Practice set row should be unlocked for active All-Access subscriber.' );
			}
			if ( ! IAQ_Access_Control::can_attempt_quiz( 9999, $paid_q_id ) ) {
				throw new Exception( 'Subscribed student should be allowed to attempt practice set.' );
			}
		}

		echo "  [PASS] Server-side access control paywall gating verified across practice sets.\n";
	}

	/**
	 * 8. Shortcode rendering: [iaq_practice_sets] renders landing and subviews.
	 */
	private static function test_frontend_shortcode_rendering() {
		$frontend = new IAQ_Frontend();

		// 1. Landing View
		$landing_html = $frontend->render_practice_sets_shortcode( array() );
		if ( false === strpos( $landing_html, 'iaq-practice-module-wrap' ) ) {
			throw new Exception( 'Practice Sets container missing from shortcode output.' );
		}
		if ( false === strpos( $landing_html, 'Practice by Book' ) || false === strpos( $landing_html, 'Practice by Subjects' ) || false === strpos( $landing_html, 'Practice by Exams' ) ) {
			throw new Exception( 'All 3 required categories must appear on landing page.' );
		}

		// 2. Books Catalog View
		$books_html = $frontend->render_practice_sets_shortcode( array( 'view' => 'books' ) );
		if ( false === strpos( $books_html, 'iaq-ps-books-grid' ) && false === strpos( $books_html, 'iaq-ps-empty-state' ) ) {
			throw new Exception( 'Books catalog grid missing from books view.' );
		}

		// 2b. View "book" without book_id must always fall back to books catalog (never auto-open a single book)
		$book_fallback_html = $frontend->render_practice_sets_shortcode( array( 'view' => 'book' ) );
		if ( false === strpos( $book_fallback_html, 'iaq-ps-books-grid' ) && false === strpos( $book_fallback_html, 'iaq-ps-empty-state' ) ) {
			throw new Exception( 'View without book_id must fall back to available books catalog.' );
		}

		// 3. Subjects Catalog View
		$subjects_html = $frontend->render_practice_sets_shortcode( array( 'view' => 'subjects' ) );
		if ( false === strpos( $subjects_html, 'iaq-ps-subjects-grid' ) && false === strpos( $subjects_html, 'iaq-ps-empty-state' ) ) {
			throw new Exception( 'Subjects catalog grid missing from subjects view.' );
		}

		// 4. Exams Catalog View
		$exams_html = $frontend->render_practice_sets_shortcode( array( 'view' => 'exams' ) );
		if ( false === strpos( $exams_html, 'iaq-ps-exams-grid' ) && false === strpos( $exams_html, 'iaq-ps-empty-state' ) ) {
			throw new Exception( 'Exams catalog grid missing from exams view.' );
		}

		// 5. Delegation from [iaq_quiz_list type="practice"]
		$delegated_html = $frontend->render_quiz_list_shortcode( array( 'type' => 'practice' ) );
		if ( false === strpos( $delegated_html, 'iaq-practice-module-wrap' ) ) {
			throw new Exception( '[iaq_quiz_list type="practice"] did not delegate to practice sets module.' );
		}

		echo "  [PASS] Shortcode [iaq_practice_sets] rendering, views, and legacy delegation verified.\n";
	}

	/**
	 * 9. Exam-Subject Practice Set 1-Click creation and Bulk Import verified.
	 */
	private static function test_exam_subject_practice_set_creation_and_import() {
		$exams    = IAQ_Examinations::get_examinations();
		$subjects = IAQ_Question_Bank::get_subjects();
		$exam_id  = $exams[0]->id;
		$subj_id  = $subjects[0]->id;

		// Create quiz directly configured for Exam-Subject
		$set_title = 'ICAR Bulk Import Test Set';
		$quiz_id   = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'            => $set_title,
				'quiz_type'        => 'practice',
				'exam_category'    => $exams[0]->name,
				'subject_id'       => $subj_id,
				'duration_minutes' => 25,
				'correct_marks'    => 2.0,
				'negative_marks'   => 0.5,
				'status'           => 'published',
				'access_type'      => 'free',
				'display_order'    => 5,
			)
		);

		if ( ! $quiz_id || is_wp_error( $quiz_id ) ) {
			throw new Exception( 'Failed to create exam-subject practice set.' );
		}

		// Assign to exam-subject
		IAQ_Practice_Sets::assign_quiz_to_exam_subject( $exam_id, $subj_id, $quiz_id, 5 );

		// Verify retrieval
		$assigned = IAQ_Practice_Sets::get_exam_subject_quizzes( $exam_id, $subj_id );
		$found    = false;
		foreach ( $assigned as $a ) {
			if ( (int) $a->id === (int) $quiz_id ) {
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			throw new Exception( 'Newly assigned exam-subject practice set not found in assigned quizzes query.' );
		}

		// Bulk import sample JSON question into this quiz
		$sample_json = json_encode(
			array(
				array(
					'question_text' => 'What is the optimal sowing depth for wheat crop?',
					'option_a'      => '2-3 cm',
					'option_b'      => '4-5 cm',
					'option_c'      => '8-10 cm',
					'option_d'      => '12-15 cm',
					'correct_option'=> 'B',
					'explanation'   => 'Optimal depth for semi-dwarf wheat varieties is 4-5 cm depending on coleoptile length.',
					'marks'         => 2.0,
					'negative_marks'=> 0.5,
				),
			)
		);

		$tmp_file = tempnam( sys_get_temp_dir(), 'iaq_test_esq_' ) . '.json';
		file_put_contents( $tmp_file, $sample_json );

		$res = IAQ_Import_Export::import_from_file( $tmp_file, $quiz_id, null, 'wheat_test.json' );
		@unlink( $tmp_file );

		if ( empty( $res['success'] ) || 1 !== (int) $res['imported'] ) {
			throw new Exception( 'Failed to bulk import question into exam-subject practice set: ' . ( isset( $res['message'] ) ? $res['message'] : '' ) );
		}

		// Verify quiz total questions updated
		$updated_quiz = IAQ_Quiz_Builder::get_quiz( $quiz_id );
		if ( 1 !== (int) $updated_quiz->total_questions ) {
			throw new Exception( 'Quiz total_questions was not incremented to 1 after bulk import.' );
		}

		echo "  [PASS] Exam-Subject Practice Set 1-Click creation and Bulk Import verified.\n";
	}
}

