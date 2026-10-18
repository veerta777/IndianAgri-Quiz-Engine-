<?php
/**
 * Test Suite: Phase 5 & Dashboard Improvements Test Suite.
 * Covers Student Dashboard, Performance Analytics, Quick Actions,
 * Continue Practising, Subject Mastery Revision links, and Empty States.
 *
 * @package IndianAgri_Quiz_Engine
 */

class Test_Student_Dashboard {

	public static function run() {
		echo "Running Student Dashboard Test Suite...\n";

		$user_id = 1;

		// 1. Verify User Dashboard Stats Computation (Distinguishing completed vs in-progress)
		$stats = IAQ_Quiz_Attempt::get_user_dashboard_stats( $user_id );
		assert( is_array( $stats ), 'Dashboard stats should be an array' );
		assert( isset( $stats['total_attempts'] ), 'Total attempts should be present in stats' );
		assert( isset( $stats['completed_attempts'] ), 'Completed attempts should be present in stats' );
		assert( isset( $stats['in_progress_attempts'] ), 'In-progress attempts should be present in stats' );
		assert( isset( $stats['passed_attempts'] ), 'Passed attempts should be present in stats' );
		assert( isset( $stats['pass_rate_percent'] ), 'Pass rate should be present in stats' );
		assert( isset( $stats['avg_percentage'] ), 'Average percentage should be present in stats' );
		assert( isset( $stats['total_questions_solved'] ), 'Total questions solved should be present in stats' );
		assert( $stats['completed_attempts'] > 0, 'Completed attempts should be greater than 0 from previous test attempts' );
		echo "  [PASS] Cumulative dashboard statistics aggregation verified\n";

		// 2. Verify Examination History Retrieval
		$history = IAQ_Quiz_Attempt::get_user_attempt_history( $user_id, 10, 0 );
		assert( is_array( $history ), 'History should be an array' );
		assert( count( $history ) > 0, 'History should contain past completed attempts' );
		assert( ! empty( $history[0]['quiz_title'] ), 'History row should contain quiz title' );
		assert( isset( $history[0]['score'] ), 'History row should contain score' );
		assert( isset( $history[0]['percentage'] ), 'History row should contain percentage' );
		assert( isset( $history[0]['time_spent_formatted'] ), 'History row should format elapsed time' );
		echo "  [PASS] Student examination history retrieval and formatting verified\n";

		// 3. Verify Cumulative Subject Mastery & Connected Practice Recommendations
		$mastery = IAQ_Quiz_Attempt::get_user_overall_subject_mastery( $user_id );
		assert( is_array( $mastery ), 'Overall subject mastery should be an array' );
		assert( count( $mastery ) > 0, 'Mastery should contain agricultural subjects from completed tests' );
		assert( isset( $mastery[0]['subject_name'] ), 'Mastery card should contain subject name' );
		assert( isset( $mastery[0]['accuracy_percent'] ), 'Mastery card should contain accuracy percent' );
		assert( isset( $mastery[0]['mastery_level'] ), 'Mastery card should assign mastery level' );
		assert( in_array( $mastery[0]['mastery_level'], array( 'strong', 'moderate', 'weak' ), true ), 'Mastery level should be strong, moderate, or weak' );
		assert( array_key_exists( 'recommended_quiz', $mastery[0] ), 'Mastery card should have recommended_quiz key' );
		echo "  [PASS] Cumulative multi-exam subject mastery aggregation verified\n";

		// 4. Verify Published Quiz Counts for Quick Actions
		$counts = IAQ_Quiz_Builder::get_published_quiz_counts();
		assert( is_array( $counts ), 'Quiz counts should be an array' );
		assert( isset( $counts['practice'] ), 'Practice quiz count should be present' );
		assert( isset( $counts['mock'] ), 'Mock test count should be present' );
		assert( isset( $counts['total'] ), 'Total count should be present' );
		assert( $counts['total'] > 0, 'Total published quizzes should be greater than 0' );
		echo "  [PASS] Published quiz counts for Quick Actions verified\n";

		// 5. Verify Continue Practising Quizzes Generation
		$continue = IAQ_Quiz_Attempt::get_user_continue_practicing_quizzes( $user_id, 4 );
		assert( is_array( $continue ), 'Continue practicing should be an array' );
		assert( count( $continue ) > 0, 'Continue practicing should return available quizzes' );
		assert( isset( $continue[0]['quiz_id'] ), 'Continue item should contain quiz_id' );
		assert( isset( $continue[0]['title'] ), 'Continue item should contain title' );
		assert( isset( $continue[0]['action_type'] ), 'Continue item should define action_type' );
		echo "  [PASS] Continue Practising actual database records retrieval verified\n";

		// 6. Verify Active In-Progress Test Resume Detection
		// Create a mock quiz and begin an attempt without submitting
		$quiz_id = IAQ_Quiz_Builder::save_quiz(
			array(
				'title'             => 'Agronomy In-Progress Resume Test',
				'slug'              => 'resume-test-' . time(),
				'quiz_type'         => 'practice',
				'exam_category'     => 'ICAR JRF',
				'duration_minutes'  => 30,
				'correct_marks'     => 2.0,
				'negative_marks'    => 0.5,
				'pass_percentage'   => 50.0,
				'status'            => 'published',
			)
		);
		assert( $quiz_id > 0, 'Test quiz should be created' );

		// Seed a question and link it
		$q_id = IAQ_Question_Bank::save_question(
			array(
				'subject_id'     => 1,
				'exam_category'  => 'ICAR JRF',
				'question_text'  => 'What is the seed rate of wheat?',
				'option_a'       => '100 kg/ha',
				'option_b'       => '150 kg/ha',
				'option_c'       => '75 kg/ha',
				'option_d'       => '200 kg/ha',
				'correct_option' => 'A',
				'marks'          => 2.0,
				'negative_marks' => 0.5,
				'status'         => 'published',
			)
		);
		IAQ_Quiz_Builder::assign_questions_to_quiz( $quiz_id, array( $q_id ) );

		// Start attempt session without submitting
		$active_session = IAQ_Quiz_Attempt::start_or_resume_attempt( $quiz_id, $user_id );
		assert( ! empty( $active_session['attempt_id'] ), 'Active attempt session started' );

		$detected_active = IAQ_Quiz_Attempt::get_user_active_attempt( $user_id );
		assert( ! is_null( $detected_active ), 'Active in-progress attempt should be detected' );
		assert( $detected_active['quiz_id'] === $quiz_id, 'Detected active attempt quiz ID matches' );
		assert( $detected_active['quiz_title'] === 'Agronomy In-Progress Resume Test', 'Detected quiz title matches' );
		echo "  [PASS] Active in-progress test detection and resume payload verified\n";

		// 7. Verify [iaq_student_dashboard] Shortcode Output for Logged-In Student
		$frontend = new IAQ_Frontend();
		$dashboard_html = $frontend->render_student_dashboard( array() );
		assert( strpos( $dashboard_html, 'iaq-student-dashboard' ) !== false, 'Dashboard container rendered' );
		assert( strpos( $dashboard_html, 'iaq-active-resume-banner' ) !== false, 'Active exam resume banner rendered when in-progress test exists' );
		assert( strpos( $dashboard_html, 'iaq-quick-actions-section' ) !== false, 'Quick actions section rendered' );
		assert( strpos( $dashboard_html, 'iaq-continue-practising-section' ) !== false, 'Continue practising section rendered' );
		assert( strpos( $dashboard_html, 'iaq-dashboard-kpis' ) !== false, 'Dashboard KPI metric cards rendered' );
		assert( strpos( $dashboard_html, 'iaq-mastery-grid' ) !== false, 'Cumulative subject mastery grid rendered' );
		assert( strpos( $dashboard_html, 'iaq-history-table' ) !== false, 'Examination attempt history table rendered' );
		assert( strpos( $dashboard_html, 'Scorecard &amp; Solutions' ) !== false || strpos( $dashboard_html, 'Scorecard & Solutions' ) !== false, 'Action link to review scorecard rendered in history table' );

		// Verify All 12 Unified Dashboard Navigation View Panes
		assert( strpos( $dashboard_html, 'class="iaq-student-portal iaq-portal-fullscreen"' ) !== false, 'Full-screen scoped portal container rendered' );
		assert( strpos( $dashboard_html, 'id="iaq-pane-dashboard"' ) !== false, 'Home/Dashboard view pane rendered' );
		assert( strpos( $dashboard_html, 'id="iaq-pane-practice-sets"' ) !== false, 'Practice Sets view pane rendered' );
		assert( strpos( $dashboard_html, 'id="iaq-pane-mock-tests"' ) !== false, 'Mock Tests view pane rendered' );
		assert( strpos( $dashboard_html, 'id="iaq-pane-examinations"' ) !== false, 'Examinations view pane rendered' );
		assert( strpos( $dashboard_html, 'id="iaq-pane-history"' ) !== false, 'Attempt History view pane rendered' );
		assert( strpos( $dashboard_html, 'id="iaq-pane-bookmarks"' ) !== false, 'Bookmarks view pane rendered' );
		assert( strpos( $dashboard_html, 'id="iaq-pane-wrong-notebook"' ) !== false, 'Wrong Question Notebook view pane rendered' );
		assert( strpos( $dashboard_html, 'id="iaq-pane-custom-test"' ) !== false, 'Custom Practice Test view pane rendered' );
		assert( strpos( $dashboard_html, 'id="iaq-pane-leaderboard"' ) !== false, 'Leaderboard view pane rendered' );
		assert( strpos( $dashboard_html, 'id="iaq-pane-study-streaks"' ) !== false, 'Study Streaks view pane rendered' );
		assert( strpos( $dashboard_html, 'id="iaq-pane-subscriptions"' ) !== false, 'Subscriptions view pane rendered' );
		assert( strpos( $dashboard_html, 'id="iaq-pane-profile"' ) !== false, 'Profile view pane rendered' );
		assert( strpos( $dashboard_html, 'Historical Performance Trend Data Table' ) !== false, 'Screen reader accessible performance trend data table should be rendered' );
		echo "  [PASS] All 12 Unified Portal View Panes rendered inside [iaq_student_dashboard]\n";

		// Verify In-Portal CBT Examination Taking via ?iaq_quiz_id
		$_GET['iaq_quiz_id'] = $quiz_id;
		$exam_html = $frontend->render_student_dashboard( array() );
		unset( $_GET['iaq_quiz_id'] );
		assert( strpos( $exam_html, 'class="iaq-student-portal iaq-portal-fullscreen"' ) !== false, 'Full-screen scoped portal container rendered on exam subview' );
		assert( strpos( $exam_html, 'iaq-examination-portal' ) !== false, 'CBT Examination Portal rendered in-dashboard' );
		assert( strpos( $exam_html, 'iaq-exam-return-nav' ) !== false, 'Exam return navigation bar rendered in-dashboard' );
		assert( strpos( $exam_html, 'iaq-exam-back-dashboard-btn' ) !== false, 'Return to Dashboard header button rendered in exam portal' );
		assert( strpos( $exam_html, 'Back to Student Dashboard' ) !== false, 'Return to Student Dashboard CTA rendered' );
		echo "  [PASS] Unified in-portal CBT Examination taking verified\n";

		// Verify In-Portal Scorecard & Solutions Review via ?attempt_id
		$completed_attempt_id = ! empty( $history[0]['id'] ) ? $history[0]['id'] : $active_session['attempt_id'];
		$_GET['attempt_id'] = $completed_attempt_id;
		$scorecard_html = $frontend->render_student_dashboard( array() );
		unset( $_GET['attempt_id'] );
		assert( strpos( $scorecard_html, 'class="iaq-student-portal iaq-portal-fullscreen"' ) !== false, 'Full-screen scoped portal container rendered on scorecard subview' );
		assert( strpos( $scorecard_html, 'iaq-standalone-scorecard' ) !== false, 'Scorecard & Solutions Portal rendered in-dashboard' );
		assert( strpos( $scorecard_html, 'iaq-exam-return-nav' ) !== false, 'Scorecard return navigation bar rendered in-dashboard' );
		assert( strpos( $scorecard_html, 'Back to Student Dashboard' ) !== false, 'Return to Student Dashboard CTA rendered on scorecard' );
		echo "  [PASS] Unified in-portal Scorecard & Solutions review verified\n";

		// 8. Verify Performance Trend History Calculation
		$trend = IAQ_Quiz_Attempt::get_user_performance_trend( $user_id, 6 );
		assert( is_array( $trend ), 'Performance trend should be an array' );
		assert( count( $trend ) > 0, 'Performance trend should return data points for completed attempts' );
		assert( isset( $trend[0]['percentage'] ), 'Trend point should contain percentage' );
		assert( isset( $trend[0]['label'] ), 'Trend point should contain date label' );
		echo "  [PASS] Performance trend chronological aggregation verified\n";

		// 9. Verify Empty State Handling for New Student (Zero Attempts & Zero Fakes)
		$new_user_id = 999;
		$new_stats = IAQ_Quiz_Attempt::get_user_dashboard_stats( $new_user_id );
		assert( 0 === $new_stats['completed_attempts'], 'New student has 0 completed attempts' );
		assert( 0 === $new_stats['total_attempts'], 'New student has 0 total attempts' );
		assert( 0.0 === $new_stats['pass_rate_percent'], 'New student pass rate is 0.0%' );

		$new_history = IAQ_Quiz_Attempt::get_user_attempt_history( $new_user_id, 10, 0 );
		assert( empty( $new_history ), 'New student history is empty' );

		$new_mastery = IAQ_Quiz_Attempt::get_user_overall_subject_mastery( $new_user_id );
		assert( empty( $new_mastery ), 'New student mastery diagnostics is empty' );

		// Switch current user to new student to test real empty state HTML
		global $current_user_id;
		$orig_uid = $current_user_id;
		$current_user_id = $new_user_id;

		$new_dashboard_html = $frontend->render_student_dashboard( array() );
		$current_user_id = $orig_uid;

		assert( strpos( $new_dashboard_html, 'No examination attempts found' ) !== false, 'Clean empty state rendered for attempt history' );
		assert( strpos( $new_dashboard_html, '2026-10-11 07:34:40' ) === false, 'Zero fake sample dates rendered for new student' );
		assert( strpos( $new_dashboard_html, 'No test attempts recorded yet' ) !== false, 'Clean empty state rendered for performance trend chart' );
		echo "  [PASS] Empty states graceful handling and zero fake statistics verified\n";

		// 9. Verify Guest Gate Protection When Not Logged In
		// Temporarily simulate guest state
		global $mock_is_logged_in;
		$mock_is_logged_in = false;

		$guest_dashboard = $frontend->render_student_dashboard( array() );
		assert( strpos( $guest_dashboard, 'iaq-guest-gate' ) !== false, 'Guest gate rendered when logged out' );
		assert( strpos( $guest_dashboard, 'Log In to Student Portal' ) !== false, 'Login CTA rendered in guest gate' );
		
		// Reset login state
		$mock_is_logged_in = true;
		echo "  [PASS] Guest access authentication gating verified\n";

		echo "All Student Dashboard tests passed successfully!\n\n";
		return true;
	}
}
