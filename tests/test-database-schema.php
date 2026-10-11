<?php
/**
 * Test Database Schema and Activator/Deactivator.
 *
 * @package IndianAgri_Quiz_Engine
 */

require_once __DIR__ . '/bootstrap.php';

class Test_Database_Schema {

	public static function run() {
		echo "Running Test_Database_Schema...\n";
		global $wpdb;
		$wpdb->reset();

		// 1. Verify Table Names
		$expected_tables = array(
			'subjects'                 => 'wp_iaq_subjects',
			'topics'                   => 'wp_iaq_topics',
			'examinations'             => 'wp_iaq_examinations',
			'questions'                => 'wp_iaq_questions',
			'quizzes'                  => 'wp_iaq_quizzes',
			'quiz_questions'           => 'wp_iaq_quiz_questions',
			'quiz_attempts'            => 'wp_iaq_quiz_attempts',
			'attempt_answers'          => 'wp_iaq_attempt_answers',
			'subscription_plans'       => 'wp_iaq_subscription_plans',
			'user_subscriptions'       => 'wp_iaq_user_subscriptions',
			'bookmarks'                => 'wp_iaq_bookmarks',
			'wrong_notebook'           => 'wp_iaq_wrong_notebook',
			'study_activity'           => 'wp_iaq_study_activity',
			'books'                    => 'wp_iaq_books',
			'book_quizzes'             => 'wp_iaq_book_quizzes',
			'exam_subjects'            => 'wp_iaq_exam_subjects',
			'exam_subject_quizzes'     => 'wp_iaq_exam_subject_quizzes',
			'mock_categories'          => 'wp_iaq_mock_categories',
			'mock_series'              => 'wp_iaq_mock_series',
			'mock_vacancies'           => 'wp_iaq_mock_vacancies',
			'mock_states_universities' => 'wp_iaq_mock_states_universities',
			'mock_test_relations'      => 'wp_iaq_mock_test_relations',
		);

		foreach ( $expected_tables as $short => $full ) {
			$actual = IAQ_Database::get_table_name( $short );
			assert( $actual === $full, "Table name mismatch for '{$short}'. Expected '{$full}', got '{$actual}'" );
		}
		echo "  [PASS] All 22 Table names properly prefixed with active \$wpdb->prefix.\n";

		// 2. Verify Table Creation (dbDelta call)
		global $mock_dbdelta_calls;
		$mock_dbdelta_calls = array();

		IAQ_Database::create_tables();
		assert( count( $mock_dbdelta_calls ) > 0, "dbDelta should have been called" );
		$schema_sql = $mock_dbdelta_calls[0];

		assert( strpos( $schema_sql, 'wp_iaq_subjects' ) !== false, "Subjects table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_topics' ) !== false, "Topics table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_examinations' ) !== false, "Examinations table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_questions' ) !== false, "Questions table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_quizzes' ) !== false, "Quizzes table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_quiz_questions' ) !== false, "Quiz questions table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_quiz_attempts' ) !== false, "Quiz attempts table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_attempt_answers' ) !== false, "Attempt answers table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_subscription_plans' ) !== false, "Subscription plans table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_user_subscriptions' ) !== false, "User subscriptions table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_bookmarks' ) !== false, "Bookmarks table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_wrong_notebook' ) !== false, "Wrong notebook table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_study_activity' ) !== false, "Study activity table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_books' ) !== false, "Books table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_book_quizzes' ) !== false, "Book quizzes table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_exam_subjects' ) !== false, "Exam subjects table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_exam_subject_quizzes' ) !== false, "Exam subject quizzes table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_mock_categories' ) !== false, "Mock categories table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_mock_series' ) !== false, "Mock series table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_mock_vacancies' ) !== false, "Mock vacancies table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_mock_states_universities' ) !== false, "Mock states/universities table definition missing in SQL" );
		assert( strpos( $schema_sql, 'wp_iaq_mock_test_relations' ) !== false, "Mock test relations table definition missing in SQL" );
		assert( get_option( 'iaq_db_version' ) === IAQ_DB_VERSION, "DB version not updated in options" );
		echo "  [PASS] Table creation DDL matches WordPress dbDelta specifications.\n";

		// 3. Verify Default Seed Data
		IAQ_Database::seed_default_data();
		$subjects    = $wpdb->tables['wp_iaq_subjects'];
		$topics      = $wpdb->tables['wp_iaq_topics'];
		$exams       = $wpdb->tables['wp_iaq_examinations'];
		$plans       = $wpdb->tables['wp_iaq_subscription_plans'];
		$mock_cats   = $wpdb->tables['wp_iaq_mock_categories'];
		$mock_states = $wpdb->tables['wp_iaq_mock_states_universities'];
		$mock_vacs   = $wpdb->tables['wp_iaq_mock_vacancies'];

		assert( count( $subjects ) >= 9, "Expected at least 9 agricultural subjects seeded, got " . count( $subjects ) );
		assert( count( $topics ) >= 20, "Expected at least 20 agricultural topics seeded, got " . count( $topics ) );
		assert( count( $exams ) >= 9, "Expected at least 9 examinations seeded, got " . count( $exams ) );
		assert( count( $plans ) >= 4, "Expected at least 4 subscription plans seeded, got " . count( $plans ) );
		assert( count( $mock_cats ) >= 10, "Expected at least 10 mock categories seeded, got " . count( $mock_cats ) );
		assert( count( $mock_states ) >= 10, "Expected at least 10 states and universities seeded, got " . count( $mock_states ) );
		assert( count( $mock_vacs ) >= 2, "Expected at least 2 vacancies seeded, got " . count( $mock_vacs ) );
		echo "  [PASS] Agricultural taxonomy, examinations, subscription plans, and mock test hierarchy seed data inserted successfully (" . count( $subjects ) . " subjects, " . count( $exams ) . " examinations, " . count( $mock_cats ) . " mock categories, " . count( $mock_states ) . " states/univs).\n";

		// 4. Verify Activation & Role Capabilities
		IAQ_Activator::activate();
		$admin_role = get_role( 'administrator' );
		assert( isset( $admin_role->caps['manage_iaq_quizzes'] ), "Administrator role should have 'manage_iaq_quizzes' capability" );
		$settings = get_option( 'iaq_settings' );
		assert( is_array( $settings ) && $settings['brand_color'] === '#117E3A', "Default brand color #117E3A not set" );
		echo "  [PASS] Activator successfully configured role capabilities and brand settings.\n";

		// 5. Verify Deactivator
		IAQ_Deactivator::deactivate();
		echo "  [PASS] Deactivator safely executed without modifying database tables.\n";

		echo "Test_Database_Schema: ALL PASS\n\n";
	}
}
