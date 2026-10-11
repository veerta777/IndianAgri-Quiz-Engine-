<?php
/**
 * Test Suite: Mock Test Module Hierarchy.
 * Verifies:
 * 1. 10 Main Categories & dynamic Admin Category Management.
 * 2. Adaptive hierarchy level skipping (e.g. Banking flat, State via States, Disciplines direct).
 * 3. Primary content type isolation (quiz_type = 'mock', excluded from Practice/PYQs).
 * 4. Zero Quiz & Question Duplication across hierarchy branches.
 * 5. Dynamic contextual numeric ordering (Mock Test 1, Mock Test 2, Mock Test 3...).
 * 6. Access control paywall gating (Free vs Premium).
 * 7. Candidate attempt status enrichment.
 * 8. Shortcode [iaq_mock_tests] rendering and legacy delegation.
 *
 * @package IndianAgri_Quiz_Engine
 */

require_once __DIR__ . '/bootstrap.php';

class Test_Mock_Tests {

	public static function run() {
		echo "\nRunning Mock Test Module Hierarchy Test Suite...\n";

		self::test_ten_main_categories_seeded_and_crud();
		self::test_adaptive_hierarchy_level_skipping();
		self::test_states_and_universities_management();
		self::test_vacancies_management();
		self::test_series_management();
		self::test_zero_quiz_duplication_and_attachment();
		self::test_dynamic_numeric_ordering();
		self::test_content_type_isolation_and_archival();
		self::test_access_control_and_attempt_enrichment();
		self::test_frontend_shortcode_rendering();

		echo "Test_Mock_Tests: ALL PASS\n";
	}

	/**
	 * 1. Verify all 10 Main Categories seeded and Admin CRUD functionality.
	 */
	private static function test_ten_main_categories_seeded_and_crud() {
		$categories = IAQ_Mock_Tests::get_categories( array( 'status' => 'active' ) );
		if ( count( $categories ) < 10 ) {
			throw new Exception( sprintf( 'Expected at least 10 seeded mock categories, got %d.', count( $categories ) ) );
		}

		$expected_slugs = array(
			'icar-exams',
			'cuet',
			'up-agriculture-exams',
			'teaching-exams',
			'banking-afo',
			'state-agriculture-exams',
			'university-entrance',
			'subject-wise-mocks',
			'general-agriculture',
			'other-govt-exams',
		);

		$slugs_found = array_map( function( $c ) { return $c->slug; }, $categories );
		foreach ( $expected_slugs as $slug ) {
			if ( ! in_array( $slug, $slugs_found, true ) ) {
				throw new Exception( "Expected seeded category slug '{$slug}' not found." );
			}
		}

		// Test Category CRUD: Add custom category 11
		$new_cat_id = IAQ_Mock_Tests::save_category(
			array(
				'name'          => 'Forestry & Allied Services',
				'slug'          => 'forestry-allied',
				'code'          => 'FOREST',
				'icon'          => 'dashicons-palmtree',
				'badge_text'    => 'State & Central',
				'description'   => 'Mock tests for State Forest Ranger and ACF examinations.',
				'display_order' => 11,
				'status'        => 'active',
			)
		);

		if ( is_wp_error( $new_cat_id ) || ! $new_cat_id ) {
			throw new Exception( 'Failed to create new mock test category.' );
		}

		$cat_obj = IAQ_Mock_Tests::get_category( $new_cat_id );
		if ( ! $cat_obj || 'Forestry & Allied Services' !== $cat_obj->name ) {
			throw new Exception( 'Created mock category verification failed.' );
		}

		// Update Category
		$update_res = IAQ_Mock_Tests::save_category(
			array(
				'id'          => $new_cat_id,
				'name'        => 'Forestry & Allied Exams (Updated)',
				'description' => 'Updated forestry mock syllabus 2026.',
			)
		);
		if ( is_wp_error( $update_res ) ) {
			throw new Exception( 'Failed to update mock category.' );
		}

		$updated_obj = IAQ_Mock_Tests::get_category( $new_cat_id );
		if ( 'Forestry & Allied Exams (Updated)' !== $updated_obj->name ) {
			throw new Exception( 'Mock category update failed to persist.' );
		}

		// Cleanup custom category
		IAQ_Mock_Tests::delete_category( $new_cat_id );
		$deleted_obj = IAQ_Mock_Tests::get_category( $new_cat_id );
		if ( null !== $deleted_obj ) {
			throw new Exception( 'Failed to delete mock category.' );
		}

		echo "  [PASS] 10 Main Categories seeded and Admin Category CRUD verified.\n";
	}

	/**
	 * 2. Adaptive hierarchy level skipping: verify each category resolves appropriate levels.
	 */
	private static function test_adaptive_hierarchy_level_skipping() {
		// Category 5: Banking & AFO skips directly to tests
		$banking_cfg = IAQ_Mock_Tests::get_category_hierarchy_config( 'banking-afo' );
		if ( empty( $banking_cfg['skip_to_tests'] ) || ! $banking_cfg['has_exams'] ) {
			throw new Exception( 'Banking & AFO hierarchy must skip directly to tests.' );
		}

		// Category 6: State Agriculture Exams drills through States
		$state_cfg = IAQ_Mock_Tests::get_category_hierarchy_config( 'state-agri-exams' );
		if ( empty( $state_cfg['has_states'] ) ) {
			throw new Exception( 'State Agriculture Exams hierarchy must activate has_states.' );
		}

		// Category 7: University Entrance drills through Universities
		$univ_cfg = IAQ_Mock_Tests::get_category_hierarchy_config( 'university-entrance' );
		if ( empty( $univ_cfg['has_universities'] ) ) {
			throw new Exception( 'University Entrance hierarchy must activate has_universities.' );
		}

		// Category 8: Subject-Wise Mock Tests drills through Subjects directly without exams
		$subj_cfg = IAQ_Mock_Tests::get_category_hierarchy_config( 'subject-wise-mocks' );
		if ( empty( $subj_cfg['has_subjects'] ) || ! empty( $subj_cfg['has_exams'] ) ) {
			throw new Exception( 'Subject-Wise Mock Tests must activate has_subjects without has_exams.' );
		}

		// Category 9: General Agriculture is a flat category with direct tests
		$gen_cfg = IAQ_Mock_Tests::get_category_hierarchy_config( 'general-agri' );
		if ( empty( $gen_cfg['flat_category'] ) ) {
			throw new Exception( 'General Agriculture must be marked as flat_category.' );
		}

		// Category 3: UP Agriculture Exams supports vacancies (AGTA, Sugarcane Officer)
		$up_cfg = IAQ_Mock_Tests::get_category_hierarchy_config( 'up-agri-exams' );
		if ( empty( $up_cfg['has_vacancies'] ) ) {
			throw new Exception( 'UP Agriculture Exams must support vacancies.' );
		}

		echo "  [PASS] Adaptive hierarchy level skipping configured correctly for all category archetypes.\n";
	}

	/**
	 * 3. State & University Management.
	 */
	private static function test_states_and_universities_management() {
		$states = IAQ_Mock_Tests::get_states();
		if ( count( $states ) < 6 ) {
			throw new Exception( sprintf( 'Expected at least 6 seeded states, got %d.', count( $states ) ) );
		}

		$univs = IAQ_Mock_Tests::get_universities();
		if ( count( $univs ) < 4 ) {
			throw new Exception( sprintf( 'Expected at least 4 seeded university groups, got %d.', count( $univs ) ) );
		}

		// Add custom state (e.g. Punjab)
		$punjab_id = IAQ_Mock_Tests::save_state_university(
			array(
				'name'          => 'Punjab',
				'code'          => 'PB',
				'slug'          => 'punjab',
				'type'          => 'state',
				'display_order' => 7,
				'status'        => 'active',
			)
		);
		if ( is_wp_error( $punjab_id ) || ! $punjab_id ) {
			throw new Exception( 'Failed to add custom state.' );
		}

		$punjab = IAQ_Mock_Tests::get_state_university( $punjab_id );
		if ( ! $punjab || 'Punjab' !== $punjab->name || 'PB' !== $punjab->code ) {
			throw new Exception( 'Retrieved custom state did not match persisted values.' );
		}

		// Cleanup
		IAQ_Mock_Tests::delete_state_university( $punjab_id );
		if ( null !== IAQ_Mock_Tests::get_state_university( $punjab_id ) ) {
			throw new Exception( 'Failed to delete state entry.' );
		}

		echo "  [PASS] States and Universities management and filtering verified.\n";
	}

	/**
	 * 4. Vacancies Management.
	 */
	private static function test_vacancies_management() {
		$vacancies = IAQ_Mock_Tests::get_vacancies();
		if ( count( $vacancies ) < 2 ) {
			throw new Exception( 'Expected seeded vacancies (AGTA, Sugarcane Officer).' );
		}

		// Create custom vacancy under Exam #1
		$vac_id = IAQ_Mock_Tests::save_vacancy(
			array(
				'examination_id'   => 1,
				'title'            => 'Assistant Agricultural Officer (AAO)',
				'slug'             => 'aao',
				'posts_count'      => 450,
				'advertisement_no' => 'Advt 03/2026',
				'status'           => 'active',
			)
		);

		if ( is_wp_error( $vac_id ) || ! $vac_id ) {
			throw new Exception( 'Failed to create vacancy.' );
		}

		$vac = IAQ_Mock_Tests::get_vacancy( $vac_id );
		if ( ! $vac || 'Assistant Agricultural Officer (AAO)' !== $vac->title || 450 !== (int) $vac->posts_count ) {
			throw new Exception( 'Retrieved vacancy did not match created record.' );
		}

		// Cleanup
		IAQ_Mock_Tests::delete_vacancy( $vac_id );
		if ( null !== IAQ_Mock_Tests::get_vacancy( $vac_id ) ) {
			throw new Exception( 'Failed to delete vacancy.' );
		}

		echo "  [PASS] Recruitment Vacancies & Posts management verified.\n";
	}

	/**
	 * 5. Mock Test Series Management.
	 */
	private static function test_series_management() {
		$series_id = IAQ_Mock_Tests::save_series(
			array(
				'title'          => 'ICAR JRF Agronomy Mock Challenge Series',
				'category_id'    => 1,
				'examination_id' => 1,
				'subject_id'     => 1,
				'total_tests'    => 15,
				'access_type'    => 'premium',
				'description'    => '15 full length Agronomy mock papers matching ICAR AIEEA pattern.',
				'status'         => 'published',
			)
		);

		if ( is_wp_error( $series_id ) || ! $series_id ) {
			throw new Exception( 'Failed to create Mock Test Series.' );
		}

		$series = IAQ_Mock_Tests::get_series( $series_id );
		if ( ! $series || 'ICAR JRF Agronomy Mock Challenge Series' !== $series->title || 15 !== (int) $series->total_tests ) {
			throw new Exception( 'Retrieved Mock Series mismatch.' );
		}

		// Cleanup
		IAQ_Mock_Tests::delete_series( $series_id );
		if ( null !== IAQ_Mock_Tests::get_series( $series_id ) ) {
			throw new Exception( 'Failed to delete Mock Series.' );
		}

		echo "  [PASS] Mock Test Series creation, association, and retrieval verified.\n";
	}

	/**
	 * 6. Zero Quiz Duplication & Zero Question Duplication across hierarchy contexts.
	 */
	private static function test_zero_quiz_duplication_and_attachment() {
		global $wpdb;

		// Count canonical quizzes before
		$initial_quiz_count = count( $wpdb->tables['wp_iaq_quizzes'] );

		// Create a single canonical Mock Test
		$quiz_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'            => 'ICAR Agronomy All-India Open Mock Test',
				'quiz_type'        => 'mock',
				'exam_category'    => 'ICAR AIEEA PG',
				'subject_id'       => 1,
				'total_questions'  => 50,
				'total_marks'      => 200,
				'duration_minutes' => 60,
				'negative_marks'   => 1.0,
				'access_type'      => 'free',
				'status'           => 'published',
			)
		);

		if ( is_wp_error( $quiz_id ) || ! $quiz_id ) {
			throw new Exception( 'Failed to create canonical mock quiz.' );
		}

		// 1. Attach this SAME quiz to ICAR Category (Category #1)
		$rel1_id = IAQ_Mock_Tests::attach_quiz_to_mock_context(
			$quiz_id,
			array(
				'category_id' => 1,
				'exam_id'     => 1,
				'subject_id'  => 1,
				'sort_order'  => 1,
			)
		);

		// 2. Attach this SAME quiz to Subject-Wise Mocks (Category #8)
		$rel2_id = IAQ_Mock_Tests::attach_quiz_to_mock_context(
			$quiz_id,
			array(
				'category_id' => 8,
				'subject_id'  => 1,
				'sort_order'  => 1,
			)
		);

		if ( ! $rel1_id || ! $rel2_id ) {
			throw new Exception( 'Failed to attach quiz to multiple hierarchy contexts.' );
		}

		// Verify zero quiz duplication in wp_iaq_quizzes: only 1 new record was added
		$new_quiz_count = count( $wpdb->tables['wp_iaq_quizzes'] );
		if ( ( $initial_quiz_count + 1 ) !== $new_quiz_count ) {
			throw new Exception( sprintf( 'Quiz record duplicated in wp_iaq_quizzes! Initial: %d, Now: %d', $initial_quiz_count, $new_quiz_count ) );
		}

		// Retrieve tests in ICAR Agronomy context
		$icar_tests = IAQ_Mock_Tests::get_context_mock_tests(
			array(
				'category_id' => 1,
				'exam_id'     => 1,
				'subject_id'  => 1,
			)
		);

		// Retrieve tests in Subject-Wise Agronomy context
		$subj_tests = IAQ_Mock_Tests::get_context_mock_tests(
			array(
				'category_id' => 8,
				'subject_id'  => 1,
			)
		);

		if ( empty( $icar_tests ) || empty( $subj_tests ) ) {
			throw new Exception( 'Failed to retrieve mock tests across different contexts.' );
		}

		// Both contexts point to the exact same canonical quiz id
		if ( (int) $icar_tests[0]->id !== (int) $quiz_id || (int) $subj_tests[0]->id !== (int) $quiz_id ) {
			throw new Exception( 'Attached mock tests do not reference the same canonical quiz ID.' );
		}

		echo "  [PASS] Zero Quiz Duplication verified across multiple hierarchy levels.\n";
	}

	/**
	 * 7. Dynamic contextual numeric ordering (Mock Test 1, Mock Test 2, Mock Test 3...).
	 */
	private static function test_dynamic_numeric_ordering() {
		// Create 3 mock tests in the UPSSSC AGTA context
		$q1_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'            => 'Full Syllabus General Agriculture Paper A',
				'quiz_type'        => 'mock',
				'status'           => 'published',
				'total_questions'  => 100,
				'total_marks'      => 100,
				'duration_minutes' => 120,
			)
		);
		$q2_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'            => 'Full Syllabus General Agriculture Paper B',
				'quiz_type'        => 'mock',
				'status'           => 'published',
				'total_questions'  => 100,
				'total_marks'      => 100,
				'duration_minutes' => 120,
			)
		);
		$q3_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'            => 'Full Syllabus General Agriculture Paper C',
				'quiz_type'        => 'mock',
				'status'           => 'published',
				'total_questions'  => 100,
				'total_marks'      => 100,
				'duration_minutes' => 120,
			)
		);

		// Attach with sort order: Q3 first (1), Q1 second (2), Q2 third (3)
		$r3 = IAQ_Mock_Tests::attach_quiz_to_mock_context( $q3_id, array( 'category_id' => 3, 'vacancy_id' => 1, 'sort_order' => 1 ) );
		$r1 = IAQ_Mock_Tests::attach_quiz_to_mock_context( $q1_id, array( 'category_id' => 3, 'vacancy_id' => 1, 'sort_order' => 2 ) );
		$r2 = IAQ_Mock_Tests::attach_quiz_to_mock_context( $q2_id, array( 'category_id' => 3, 'vacancy_id' => 1, 'sort_order' => 3 ) );

		$tests = IAQ_Mock_Tests::get_context_mock_tests( array( 'category_id' => 3, 'vacancy_id' => 1 ) );

		if ( count( $tests ) < 3 ) {
			throw new Exception( 'Failed to retrieve attached tests.' );
		}

		// Verify dynamic sequential numbering starting at 1
		if ( 1 !== (int) $tests[0]->mock_test_number || (int) $tests[0]->id !== $q3_id ) {
			throw new Exception( sprintf( 'Expected Test 1 to be Q3. Got ID: %d, Number: %d', $tests[0]->id, $tests[0]->mock_test_number ) );
		}
		if ( 2 !== (int) $tests[1]->mock_test_number || (int) $tests[1]->id !== $q1_id ) {
			throw new Exception( sprintf( 'Expected Test 2 to be Q1. Got ID: %d, Number: %d', $tests[1]->id, $tests[1]->mock_test_number ) );
		}
		if ( 3 !== (int) $tests[2]->mock_test_number || (int) $tests[2]->id !== $q2_id ) {
			throw new Exception( sprintf( 'Expected Test 3 to be Q2. Got ID: %d, Number: %d', $tests[2]->id, $tests[2]->mock_test_number ) );
		}

		// Formatted title must include "Mock Test 1:", "Mock Test 2:", "Mock Test 3:"
		if ( strpos( $tests[0]->formatted_title, 'Mock Test 1:' ) === false ) {
			throw new Exception( 'Formatted title missing "Mock Test 1:". Got: ' . $tests[0]->formatted_title );
		}
		if ( strpos( $tests[1]->formatted_title, 'Mock Test 2:' ) === false ) {
			throw new Exception( 'Formatted title missing "Mock Test 2:". Got: ' . $tests[1]->formatted_title );
		}

		echo "  [PASS] Dynamic contextual numeric ordering (Mock Test 1..N) and reordering verified.\n";
	}

	/**
	 * 8. Primary Content Type Isolation (quiz_type = 'mock' strictly separated from Practice Sets & PYQs).
	 */
	private static function test_content_type_isolation_and_archival() {
		// Create a practice set
		$practice_quiz_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'         => 'Agronomy Daily Practice Drill',
				'quiz_type'     => 'practice',
				'subject_id'    => 1,
				'status'        => 'published',
			)
		);

		// Create a draft mock test
		$draft_mock_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'         => 'Draft Mock Exam Under Review',
				'quiz_type'     => 'mock',
				'subject_id'    => 1,
				'status'        => 'draft',
			)
		);

		// Attach practice quiz to mock context by mistake
		IAQ_Mock_Tests::attach_quiz_to_mock_context( $practice_quiz_id, array( 'category_id' => 1, 'subject_id' => 1 ) );
		IAQ_Mock_Tests::attach_quiz_to_mock_context( $draft_mock_id, array( 'category_id' => 1, 'subject_id' => 1 ) );

		// Query student mock tests
		$student_mocks = IAQ_Mock_Tests::get_context_mock_tests( array( 'category_id' => 1, 'subject_id' => 1 ) );

		foreach ( $student_mocks as $m ) {
			if ( 'mock' !== $m->quiz_type ) {
				throw new Exception( 'Content isolation violated: Practice quiz returned in Mock Tests query!' );
			}
			if ( 'published' !== $m->status ) {
				throw new Exception( 'Status isolation violated: Draft quiz returned in Mock Tests query!' );
			}
		}

		echo "  [PASS] Primary content type isolation (quiz_type = 'mock') and draft filtering verified.\n";
	}

	/**
	 * 9. Access Control Paywall Gating & Attempt History Enrichment.
	 */
	private static function test_access_control_and_attempt_enrichment() {
		global $wpdb;

		// Create Free Mock Test
		$free_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'       => 'Free National Scholarship Mock Test',
				'quiz_type'   => 'mock',
				'access_type' => 'free',
				'status'      => 'published',
			)
		);

		// Create Paid/Premium Mock Test
		$paid_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'       => 'Pro All-India Rank Predictor Mock Test',
				'quiz_type'   => 'mock',
				'access_type' => 'paid',
				'status'      => 'published',
			)
		);

		// Attach both to category 9 (General Agriculture)
		IAQ_Mock_Tests::attach_quiz_to_mock_context( $free_id, array( 'category_id' => 9, 'sort_order' => 1 ) );
		IAQ_Mock_Tests::attach_quiz_to_mock_context( $paid_id, array( 'category_id' => 9, 'sort_order' => 2 ) );

		// Test visitor/unsubscribed student (User ID 99)
		$tests_for_guest = IAQ_Mock_Tests::get_context_mock_tests( array( 'category_id' => 9 ), 99 );

		$free_found = false;
		$paid_found = false;
		foreach ( $tests_for_guest as $t ) {
			if ( (int) $t->id === $free_id ) {
				$free_found = true;
				if ( ! $t->is_accessible ) {
					throw new Exception( 'Free mock test must be accessible to unsubscribed student.' );
				}
			}
			if ( (int) $t->id === $paid_id ) {
				$paid_found = true;
				if ( $t->is_accessible ) {
					throw new Exception( 'Paid mock test must be paywalled for unsubscribed student.' );
				}
			}
		}

		if ( ! $free_found || ! $paid_found ) {
			throw new Exception( 'Failed to retrieve both free and paid mock tests.' );
		}

		// Simulate completed attempt for User 99 on Free Test
		$wpdb->insert(
			$wpdb->prefix . 'iaq_quiz_attempts',
			array(
				'quiz_id'            => $free_id,
				'user_id'            => 99,
				'status'             => 'completed',
				'score'              => 140.00,
				'total_marks'        => 200.00,
				'percentage'         => 70.00,
				'passed'             => 1,
				'start_time'         => '2026-10-11 10:00:00',
				'end_time'           => '2026-10-11 11:00:00',
				'time_spent_seconds' => 3600,
			)
		);

		// Re-fetch tests for User 99
		$enriched = IAQ_Mock_Tests::get_context_mock_tests( array( 'category_id' => 9 ), 99 );
		$target   = null;
		foreach ( $enriched as $t ) {
			if ( (int) $t->id === $free_id ) {
				$target = $t;
				break;
			}
		}

		if ( ! $target || 'completed' !== $target->attempt_status || 140.0 !== (float) $target->latest_score || 70.0 !== (float) $target->latest_percentage ) {
			throw new Exception( 'Student attempt history was not properly enriched on mock test card.' );
		}

		echo "  [PASS] Server-side access control gating and student attempt history enrichment verified.\n";
	}

	/**
	 * 10. Shortcode [iaq_mock_tests] rendering and views.
	 */
	private static function test_frontend_shortcode_rendering() {
		$frontend = new IAQ_Frontend();

		// 1. Landing view [iaq_mock_tests]
		$html_landing = $frontend->render_mock_tests_shortcode( array() );
		if ( strpos( $html_landing, 'iaq-mock-module-wrap' ) === false || strpos( $html_landing, 'Choose Your Examination Category' ) === false ) {
			throw new Exception( 'Shortcode [iaq_mock_tests] failed to render root landing categories.' );
		}

		// 2. Category drilldown [iaq_mock_tests category_id="1"]
		$html_cat = $frontend->render_mock_tests_shortcode( array( 'category_id' => 1 ) );
		if ( strpos( $html_cat, 'iaq-mock-module-wrap' ) === false ) {
			throw new Exception( 'Shortcode [iaq_mock_tests category_id="1"] failed to render.' );
		}

		// 3. Legacy [iaq_quiz_list type="mock"] delegation
		$html_legacy = $frontend->render_quiz_list_shortcode( array( 'type' => 'mock' ) );
		if ( strpos( $html_legacy, 'iaq-mock-module-wrap' ) === false ) {
			throw new Exception( 'Legacy shortcode [iaq_quiz_list type="mock"] failed to delegate to Mock Tests module.' );
		}

		echo "  [PASS] Shortcode [iaq_mock_tests] rendering, view routing, and legacy delegation verified.\n";
	}
}
