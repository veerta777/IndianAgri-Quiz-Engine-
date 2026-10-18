<?php
/**
 * Master test runner for IndianAgri Quiz Engine.
 *
 * @package IndianAgri_Quiz_Engine
 */

require_once __DIR__ . '/test-database-schema.php';
require_once __DIR__ . '/test-question-bank.php';
require_once __DIR__ . '/test-import-export.php';
require_once __DIR__ . '/test-quiz-builder.php';
require_once __DIR__ . '/test-quiz-attempt.php';
require_once __DIR__ . '/test-results-analytics.php';
require_once __DIR__ . '/test-student-dashboard.php';
require_once __DIR__ . '/test-examinations-admin.php';
require_once __DIR__ . '/test-subscriptions-monetization.php';
require_once __DIR__ . '/test-access-control-security.php';
require_once __DIR__ . '/test-student-learning-features.php';
require_once __DIR__ . '/test-practice-sets.php';
require_once __DIR__ . '/test-mock-tests.php';
require_once __DIR__ . '/test-rest-api.php';
require_once __DIR__ . '/test-staging-and-release.php';

echo "========================================================\n";
echo "INDIANAGRI QUIZ ENGINE — AUTOMATED TEST HARNESS\n";
echo "Testing Environment: PHP " . PHP_VERSION . "\n";
echo "========================================================\n\n";

try {
	Test_Database_Schema::run();
	Test_Question_Bank::run();
	Test_Import_Export::run();
	Test_Quiz_Builder::run();
	Test_Quiz_Attempt::run();
	Test_Results_Analytics::run();
	Test_Student_Dashboard::run();
	Test_Examinations_Admin::run();
	Test_Subscriptions_Monetization::run();
	Test_Access_Control_Security::run();
	Test_Student_Learning_Features::run();
	Test_Practice_Sets::run();
	Test_Mock_Tests::run();
	Test_Rest_Api::run();
	$staging_suite = new Test_Staging_And_Release();
	$staging_suite->run();

	echo "========================================================\n";
	echo ">>> ALL TEST SUITES PASSED SUCCESSFULLY (100%)\n";
	echo "========================================================\n";
	exit( 0 );
} catch ( AssertionError $e ) {
	echo "\n!!! TEST ASSERTION FAILED:\n";
	echo $e->getMessage() . "\n";
	echo "File: " . $e->getFile() . " (Line " . $e->getLine() . ")\n";
	exit( 1 );
} catch ( Exception $e ) {
	echo "\n!!! TEST EXCEPTION:\n";
	echo $e->getMessage() . "\n";
	exit( 1 );
}
