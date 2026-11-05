<?php
/**
 * Automated Test Suite for Dedicated Previous Year Question Papers (PYQ)
 * Reusing Existing Mock Test Hierarchy with Zero Duplication or Cross-Contamination.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Test_PYQ {

	private static function assert_true( $condition, $message = 'Assertion failed' ) {
		if ( ! $condition ) {
			throw new Exception( $message );
		}
	}

	public static function run() {
		echo "Running Dedicated PYQ Test Suite...\n";

		self::test_pyq_creation_and_hierarchy_reuse();
		self::test_strict_isolation_from_mock_and_practice();
		self::test_pyq_editing_and_year_retrieval();
		self::test_pyq_access_control();

		echo "Test_PYQ: ALL PASS\n\n";
	}

	/**
	 * 1. Verify PYQ paper creation reusing existing Mock Test hierarchy categories.
	 */
	private static function test_pyq_creation_and_hierarchy_reuse() {
		$categories = IAQ_PYQ::get_categories();
		self::assert_true( ! empty( $categories ), 'PYQ must dynamically reuse existing Mock Test categories' );
		self::assert_true( count( $categories ) >= 10, 'Must have access to all 10 national categories' );

		$cat_1 = $categories[0];
		$cat_id = (int) $cat_1->id;

		// Save a new PYQ paper under this category
		$paper_id = IAQ_PYQ::save_paper(
			array(
				'title'            => 'ICAR AIEEA PG 2023 Agronomy Question Paper',
				'category_id'      => $cat_id,
				'pyq_year'         => 2023,
				'pyq_session'      => 'Morning Shift',
				'duration_minutes' => 120,
				'correct_marks'    => 4.0,
				'negative_marks'   => 1.0,
				'access_type'      => 'free',
				'status'           => 'published',
			)
		);

		self::assert_true( $paper_id > 0, 'PYQ Paper must be successfully saved' );

		$quiz = IAQ_Quiz_Builder::get_quiz( $paper_id, true );
		self::assert_true( null !== $quiz, 'Saved PYQ paper must be retrievable via Quiz Builder' );
		self::assert_true( 'previous_year' === $quiz->quiz_type, "PYQ quiz_type must strictly be 'previous_year'" );
		self::assert_true( 2023 === (int) $quiz->pyq_year, 'PYQ year must be 2023' );
		self::assert_true( 'Morning Shift' === $quiz->pyq_session, 'PYQ session must be saved' );

		echo "  [PASS] PYQ creation reusing existing Mock Test hierarchy verified.\n";
	}

	/**
	 * 2. Strict isolation: PYQ papers must NEVER leak into Mock Tests or Practice Sets.
	 */
	private static function test_strict_isolation_from_mock_and_practice() {
		$categories = IAQ_PYQ::get_categories();
		$cat_id = (int) $categories[0]->id;

		$pyq_id = IAQ_PYQ::save_paper(
			array(
				'title'          => 'Strict Isolation PYQ 2022',
				'category_id'    => $cat_id,
				'pyq_year'       => 2022,
				'status'         => 'published',
			)
		);

		// Must appear in PYQ context papers
		$pyq_papers = IAQ_PYQ::get_context_pyq_papers( array( 'category_id' => $cat_id ) );
		$pyq_ids = array_map( function( $p ) { return (int) $p->id; }, $pyq_papers );
		self::assert_true( in_array( $pyq_id, $pyq_ids, true ), 'PYQ paper must be present in PYQ context list' );

		// Must NEVER appear in Mock Tests
		$mock_tests = IAQ_Mock_Tests::get_context_mock_tests( array( 'category_id' => $cat_id ) );
		$mock_ids = array_map( function( $m ) { return (int) $m->id; }, $mock_tests );
		self::assert_true( ! in_array( $pyq_id, $mock_ids, true ), 'PYQ paper must NEVER appear in Mock Tests list' );

		// Must NEVER appear in Practice Sets
		$subject_quizzes = IAQ_Practice_Sets::get_subject_quizzes( 1, 'published' );
		$subject_quiz_ids = array_map( function( $sq ) { return (int) $sq->id; }, $subject_quizzes );
		self::assert_true( ! in_array( $pyq_id, $subject_quiz_ids, true ), 'PYQ paper must NEVER appear in Practice Sets' );

		echo "  [PASS] Strict primary content type separation verified: zero cross-contamination.\n";
	}

	/**
	 * 3. Editing PYQ properties and year retrieval.
	 */
	private static function test_pyq_editing_and_year_retrieval() {
		$categories = IAQ_PYQ::get_categories();
		$cat_id = (int) $categories[0]->id;

		$paper_id = IAQ_PYQ::save_paper(
			array(
				'title'       => 'Editable PYQ Test',
				'category_id' => $cat_id,
				'pyq_year'    => 2021,
				'status'      => 'published',
			)
		);

		// Update to 2024
		IAQ_PYQ::save_paper(
			array(
				'id'          => $paper_id,
				'title'       => 'Updated PYQ Test 2024',
				'category_id' => $cat_id,
				'pyq_year'    => 2024,
				'status'      => 'published',
			)
		);

		$updated = IAQ_Quiz_Builder::get_quiz( $paper_id, true );
		self::assert_true( 2024 === (int) $updated->pyq_year, 'PYQ year update must be persisted' );

		$years = IAQ_PYQ::get_available_years();
		self::assert_true( in_array( 2024, $years, true ), 'Updated year 2024 must be present in available years filter' );

		echo "  [PASS] PYQ editing and available years extraction verified.\n";
	}

	/**
	 * 4. Access control gating for PYQ papers (free vs paid).
	 */
	private static function test_pyq_access_control() {
		$categories = IAQ_PYQ::get_categories();
		$cat_id = (int) $categories[0]->id;

		$paid_paper_id = IAQ_PYQ::save_paper(
			array(
				'title'       => 'Premium PYQ 2024 Complete Solutions',
				'category_id' => $cat_id,
				'pyq_year'    => 2024,
				'access_type' => 'paid',
				'status'      => 'published',
			)
		);

		$guest_check = IAQ_Access_Control::can_attempt_quiz( 0, $paid_paper_id );
		self::assert_true( ! $guest_check, 'Guest student must be denied access to paid PYQ paper' );

		$free_paper_id = IAQ_PYQ::save_paper(
			array(
				'title'       => 'Free Demo PYQ 2024',
				'category_id' => $cat_id,
				'pyq_year'    => 2024,
				'access_type' => 'free',
				'status'      => 'published',
			)
		);

		$free_check = IAQ_Access_Control::can_attempt_quiz( 0, $free_paper_id );
		self::assert_true( $free_check, 'Guest student must have access to free PYQ paper' );

		echo "  [PASS] PYQ access control gating (free vs paid) verified.\n";
	}
}
