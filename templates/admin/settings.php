<?php
/**
 * Settings admin template: Global Defaults, Monetization Config, and Retention Rules.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brand_color               = isset( $settings['brand_color'] ) ? $settings['brand_color'] : '#117E3A';
$default_duration          = isset( $settings['default_duration'] ) ? $settings['default_duration'] : 60;
$default_marks             = isset( $settings['default_marks'] ) ? $settings['default_marks'] : 1.00;
$default_negative_marks    = isset( $settings['default_negative_marks'] ) ? $settings['default_negative_marks'] : 0.25;
$default_pass_percentage   = isset( $settings['default_pass_percentage'] ) ? $settings['default_pass_percentage'] : 40.00;
$default_max_attempts      = isset( $settings['default_max_attempts'] ) ? $settings['default_max_attempts'] : 0;
$default_shuffle_questions = isset( $settings['default_shuffle_questions'] ) ? $settings['default_shuffle_questions'] : 1;
$default_shuffle_options   = isset( $settings['default_shuffle_options'] ) ? $settings['default_shuffle_options'] : 1;
$default_access_type       = isset( $settings['default_access_type'] ) ? $settings['default_access_type'] : 'free';
$default_show_answers_mode = isset( $settings['default_show_answers_mode'] ) ? $settings['default_show_answers_mode'] : 'after_submission';
$delete_data               = isset( $settings['delete_data_on_uninstall'] ) ? $settings['delete_data_on_uninstall'] : 0;
$hindi_support             = isset( $settings['enable_hindi_support'] ) ? $settings['enable_hindi_support'] : 1;
$default_instructions      = isset( $settings['default_instructions'] ) ? $settings['default_instructions'] : "1. This examination contains multiple-choice questions with 4 alternatives.\n2. Mark your answers carefully. Negative marking applies for incorrect attempts.\n3. The test will auto-submit once the countdown timer expires.\n4. You can use the question palette to jump between questions and mark items for review.";

// Additional Student Learning Features
$enable_bookmarks          = isset( $settings['enable_bookmarks'] ) ? (int) $settings['enable_bookmarks'] : (int) get_option( 'iaq_enable_bookmarks', 1 );
$enable_wrong_notebook     = isset( $settings['enable_wrong_notebook'] ) ? (int) $settings['enable_wrong_notebook'] : (int) get_option( 'iaq_enable_wrong_notebook', 1 );
$enable_custom_practice    = isset( $settings['enable_custom_practice'] ) ? (int) $settings['enable_custom_practice'] : 1;
$enable_leaderboard        = isset( $settings['enable_leaderboard'] ) ? (int) $settings['enable_leaderboard'] : (int) get_option( 'iaq_leaderboard_enabled', 1 );
$enable_study_streaks      = isset( $settings['enable_study_streaks'] ) ? (int) $settings['enable_study_streaks'] : (int) get_option( 'iaq_enable_study_streaks', 1 );
$leaderboard_name_type     = isset( $settings['leaderboard_name_type'] ) ? $settings['leaderboard_name_type'] : get_option( 'iaq_leaderboard_name_type', 'pseudonym' );
$leaderboard_limit         = isset( $settings['leaderboard_limit'] ) ? (int) $settings['leaderboard_limit'] : 10;
$custom_practice_max       = isset( $settings['custom_practice_max_questions'] ) ? (int) $settings['custom_practice_max_questions'] : (int) get_option( 'iaq_custom_practice_max_questions', 50 );
$recommendations_weak      = isset( $settings['recommendations_focus_weak_areas'] ) ? (int) $settings['recommendations_focus_weak_areas'] : 1;
$streak_qualifying         = isset( $settings['streak_qualifying_activity'] ) ? $settings['streak_qualifying_activity'] : 'quiz_completed';
?>

<div class="wrap iaq-admin-wrap">
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php esc_html_e( 'Quiz Engine Global Settings & Standards', 'indianagri-quiz-engine' ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Configure global defaults for new tests, scoring standards, anti-cheat behaviors, brand accents, and database retention rules.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
		<div class="iaq-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-dashboard' ) ); ?>" class="iaq-btn iaq-btn-outline">
				<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to Dashboard', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-settings' ) ); ?>" style="max-width: 900px; margin-top: 24px;">
		<?php wp_nonce_field( 'iaq_save_settings_nonce' ); ?>

		<!-- Brand & Display Settings -->
		<div class="iaq-card" style="margin-bottom: 24px;">
			<div class="iaq-card-header">
				<h2><span class="dashicons dashicons-art"></span> <?php esc_html_e( 'Branding & Theme Accents', 'indianagri-quiz-engine' ); ?></h2>
			</div>
			<div class="iaq-card-body">
				<div class="iaq-form-group">
					<label for="iaq-brand-color"><?php esc_html_e( 'Brand Primary Color', 'indianagri-quiz-engine' ); ?></label>
					<div style="display:flex;align-items:center;gap:12px;">
						<input type="color" name="brand_color" id="iaq-brand-color" value="<?php echo esc_attr( $brand_color ); ?>" style="width:50px;height:40px;padding:2px;cursor:pointer;border-radius:6px;border:1px solid #cbd5e1;">
						<code style="font-weight:700;font-size:14px;"><?php echo esc_html( $brand_color ); ?></code>
						<span class="iaq-card-help"><?php esc_html_e( 'Primary accent color for buttons, timer ring, scorecards, and student dashboard.', 'indianagri-quiz-engine' ); ?></span>
					</div>
				</div>

				<div class="iaq-form-group" style="margin-top:16px;">
					<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
						<input type="checkbox" name="enable_hindi_support" value="1" <?php checked( $hindi_support, 1 ); ?>>
						<strong><?php esc_html_e( 'Enable Bilingual Hindi (Devanagari) Unicode formatting support', 'indianagri-quiz-engine' ); ?></strong>
					</label>
					<span class="iaq-card-help" style="margin-left:26px;display:block;">
						<?php esc_html_e( 'Ensures questions, options, and explanations can be entered and rendered in Hindi.', 'indianagri-quiz-engine' ); ?>
					</span>
				</div>
			</div>
		</div>

		<!-- Global Scoring Defaults -->
		<div class="iaq-card" style="margin-bottom: 24px;">
			<div class="iaq-card-header">
				<h2><span class="dashicons dashicons-calculator"></span> <?php esc_html_e( 'Default Examination & Scoring Parameters', 'indianagri-quiz-engine' ); ?></h2>
				<span class="iaq-card-help"><?php esc_html_e( 'Applied when creating new quizzes. Can be overridden on each individual quiz.', 'indianagri-quiz-engine' ); ?></span>
			</div>
			<div class="iaq-card-body">
				<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;">
					<div class="iaq-form-group">
						<label for="iaq-default-marks"><?php esc_html_e( 'Default Marks per MCQ (+)', 'indianagri-quiz-engine' ); ?></label>
						<input type="number" step="0.25" min="0.25" max="100" name="default_marks" id="iaq-default-marks" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $default_marks ); ?>" required>
					</div>

					<div class="iaq-form-group">
						<label for="iaq-default-neg-marks"><?php esc_html_e( 'Default Negative Marks (-)', 'indianagri-quiz-engine' ); ?></label>
						<input type="number" step="0.01" min="0" max="100" name="default_negative_marks" id="iaq-default-neg-marks" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $default_negative_marks ); ?>" required>
					</div>

					<div class="iaq-form-group">
						<label for="iaq-default-duration"><?php esc_html_e( 'Default Duration (Mins)', 'indianagri-quiz-engine' ); ?></label>
						<input type="number" step="1" min="0" max="600" name="default_duration" id="iaq-default-duration" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $default_duration ); ?>" required>
					</div>

					<div class="iaq-form-group">
						<label for="iaq-default-pass"><?php esc_html_e( 'Default Pass Percentage (%)', 'indianagri-quiz-engine' ); ?></label>
						<input type="number" step="1" min="0" max="100" name="default_pass_percentage" id="iaq-default-pass" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $default_pass_percentage ); ?>" required>
					</div>
				</div>

				<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
					<div class="iaq-form-group">
						<label for="iaq-default-access"><?php esc_html_e( 'Default Access Type', 'indianagri-quiz-engine' ); ?></label>
						<select name="default_access_type" id="iaq-default-access" class="iaq-select iaq-full-width">
							<option value="free" <?php selected( $default_access_type, 'free' ); ?>><?php esc_html_e( 'Free (Open to logged-in students)', 'indianagri-quiz-engine' ); ?></option>
							<option value="paid" <?php selected( $default_access_type, 'paid' ); ?>><?php esc_html_e( 'Paid (Requires Subscription / Entitlement)', 'indianagri-quiz-engine' ); ?></option>
						</select>
					</div>

					<div class="iaq-form-group">
						<label for="iaq-default-review"><?php esc_html_e( 'Default Review & Solutions Visibility', 'indianagri-quiz-engine' ); ?></label>
						<select name="default_show_answers_mode" id="iaq-default-review" class="iaq-select iaq-full-width">
							<option value="after_submission" <?php selected( $default_show_answers_mode, 'after_submission' ); ?>><?php esc_html_e( 'Show Solutions After Submission', 'indianagri-quiz-engine' ); ?></option>
							<option value="immediate" <?php selected( $default_show_answers_mode, 'immediate' ); ?>><?php esc_html_e( 'Show Answer Immediately (Practice)', 'indianagri-quiz-engine' ); ?></option>
							<option value="never" <?php selected( $default_show_answers_mode, 'never' ); ?>><?php esc_html_e( 'Hide Answer Keys & Explanations', 'indianagri-quiz-engine' ); ?></option>
						</select>
					</div>
				</div>

				<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
					<div class="iaq-form-group">
						<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
							<input type="checkbox" name="default_shuffle_questions" value="1" <?php checked( $default_shuffle_questions, 1 ); ?>>
							<strong><?php esc_html_e( 'Enable Question Shuffling by Default', 'indianagri-quiz-engine' ); ?></strong>
						</label>
					</div>

					<div class="iaq-form-group">
						<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
							<input type="checkbox" name="default_shuffle_options" value="1" <?php checked( $default_shuffle_options, 1 ); ?>>
							<strong><?php esc_html_e( 'Enable Option Shuffling (A/B/C/D) by Default', 'indianagri-quiz-engine' ); ?></strong>
						</label>
					</div>
				</div>

				<div class="iaq-form-group" style="margin-top:16px;">
					<label for="iaq-default-instructions"><?php esc_html_e( 'Default Pre-Exam Candidate Instructions', 'indianagri-quiz-engine' ); ?></label>
					<textarea name="default_instructions" id="iaq-default-instructions" rows="4" class="iaq-textarea iaq-full-width"><?php echo esc_textarea( $default_instructions ); ?></textarea>
				</div>
			</div>
		</div>

		<!-- Student Learning Features Toggles & Parameters -->
		<div class="iaq-card" style="margin-bottom: 24px;">
			<div class="iaq-card-header">
				<h2><span class="dashicons dashicons-welcome-learn-more"></span> <?php esc_html_e( 'Student Learning Features & Tools', 'indianagri-quiz-engine' ); ?></h2>
				<span class="iaq-card-help"><?php esc_html_e( 'Enable or disable interactive learning modules on the student dashboard.', 'indianagri-quiz-engine' ); ?></span>
			</div>
			<div class="iaq-card-body">
				<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:16px;">
					<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
						<input type="checkbox" name="enable_bookmarks" value="1" <?php checked( $enable_bookmarks, 1 ); ?>>
						<span><strong><?php esc_html_e( 'Question Bookmarks', 'indianagri-quiz-engine' ); ?></strong><br><small style="color:#64748b;"><?php esc_html_e( 'Save MCQs for revision practice', 'indianagri-quiz-engine' ); ?></small></span>
					</label>

					<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
						<input type="checkbox" name="enable_wrong_notebook" value="1" <?php checked( $enable_wrong_notebook, 1 ); ?>>
						<span><strong><?php esc_html_e( 'Wrong Answer Notebook', 'indianagri-quiz-engine' ); ?></strong><br><small style="color:#64748b;"><?php esc_html_e( 'Auto-track errors and retry drills', 'indianagri-quiz-engine' ); ?></small></span>
					</label>

					<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
						<input type="checkbox" name="enable_custom_practice" value="1" <?php checked( $enable_custom_practice, 1 ); ?>>
						<span><strong><?php esc_html_e( 'Custom Practice Builder', 'indianagri-quiz-engine' ); ?></strong><br><small style="color:#64748b;"><?php esc_html_e( 'On-demand personalized practice tests', 'indianagri-quiz-engine' ); ?></small></span>
					</label>

					<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
						<input type="checkbox" name="enable_study_streaks" value="1" <?php checked( $enable_study_streaks, 1 ); ?>>
						<span><strong><?php esc_html_e( 'Study Streaks & Goals', 'indianagri-quiz-engine' ); ?></strong><br><small style="color:#64748b;"><?php esc_html_e( 'Daily consistency and study targets', 'indianagri-quiz-engine' ); ?></small></span>
					</label>

					<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
						<input type="checkbox" name="enable_leaderboard" value="1" <?php checked( $enable_leaderboard, 1 ); ?>>
						<span><strong><?php esc_html_e( 'Leaderboard & Rankings', 'indianagri-quiz-engine' ); ?></strong><br><small style="color:#64748b;"><?php esc_html_e( 'Competitive rankings & scholar aliases', 'indianagri-quiz-engine' ); ?></small></span>
					</label>
				</div>

				<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:24px;border-top:1px solid #f1f5f9;padding-top:20px;">
					<div class="iaq-form-group">
						<label for="iaq-leaderboard-name-type"><?php esc_html_e( 'Leaderboard Privacy & Display Name', 'indianagri-quiz-engine' ); ?></label>
						<select name="leaderboard_name_type" id="iaq-leaderboard-name-type" class="iaq-select iaq-full-width">
							<option value="pseudonym" <?php selected( $leaderboard_name_type, 'pseudonym' ); ?>><?php esc_html_e( 'Anonymous Alias (e.g. AgriScholar #0012) — Maximum Privacy', 'indianagri-quiz-engine' ); ?></option>
							<option value="first_name" <?php selected( $leaderboard_name_type, 'first_name' ); ?>><?php esc_html_e( 'First Name Only (e.g. Rahul S.)', 'indianagri-quiz-engine' ); ?></option>
							<option value="display_name" <?php selected( $leaderboard_name_type, 'display_name' ); ?>><?php esc_html_e( 'Student WordPress Display Name (No Emails)', 'indianagri-quiz-engine' ); ?></option>
						</select>
					</div>

					<div class="iaq-form-group">
						<label for="iaq-custom-max-q"><?php esc_html_e( 'Custom Practice: Max Questions Limit', 'indianagri-quiz-engine' ); ?></label>
						<input type="number" name="custom_practice_max_questions" id="iaq-custom-max-q" class="iaq-input iaq-full-width" min="5" max="100" value="<?php echo esc_attr( $custom_practice_max ); ?>">
					</div>
				</div>

				<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
					<div class="iaq-form-group">
						<label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
							<input type="checkbox" name="recommendations_focus_weak_areas" value="1" <?php checked( $recommendations_weak, 1 ); ?>>
							<strong><?php esc_html_e( 'Prioritize Weak Subjects in Practice Recommendations', 'indianagri-quiz-engine' ); ?></strong>
						</label>
						<span class="iaq-card-help" style="margin-left:26px;display:block;">
							<?php esc_html_e( 'Dynamically suggests revision sets covering topics where student scored lowest.', 'indianagri-quiz-engine' ); ?>
						</span>
					</div>

					<div class="iaq-form-group">
						<label for="iaq-streak-qual"><?php esc_html_e( 'Streak Qualifying Activity', 'indianagri-quiz-engine' ); ?></label>
						<select name="streak_qualifying_activity" id="iaq-streak-qual" class="iaq-select iaq-full-width">
							<option value="quiz_completed" <?php selected( $streak_qualifying, 'quiz_completed' ); ?>><?php esc_html_e( 'Completing any published Quiz, Mock Test, or Custom Drill', 'indianagri-quiz-engine' ); ?></option>
						</select>
					</div>
				</div>
			</div>
		</div>

		<!-- Data Safety & Uninstallation Rules -->
		<div class="iaq-card" style="margin-bottom: 24px;">
			<div class="iaq-card-header">
				<h2><span class="dashicons dashicons-database"></span> <?php esc_html_e( 'Database Retention & Safety Policy', 'indianagri-quiz-engine' ); ?></h2>
			</div>
			<div class="iaq-card-body">
				<div class="iaq-form-group">
					<label style="display:flex;align-items:center;gap:10px;cursor:pointer;color:#dc2626;">
						<input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( $delete_data, 1 ); ?>>
						<strong><?php esc_html_e( 'Permanently delete all 10 plugin database tables upon uninstall', 'indianagri-quiz-engine' ); ?></strong>
					</label>
					<span class="iaq-card-help" style="margin-left:26px;display:block;color:#64748b;">
						<?php esc_html_e( 'Recommended setting: Leave unchecked. This guarantees your questions, historical attempts, and subscriptions are never lost during plugin updates.', 'indianagri-quiz-engine' ); ?>
					</span>
				</div>
			</div>
		</div>

		<div class="iaq-form-actions" style="margin-top:24px;">
			<button type="submit" name="iaq_save_settings" class="iaq-btn iaq-btn-primary iaq-btn-large">
				<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Global Settings', 'indianagri-quiz-engine' ); ?>
			</button>
		</div>
	</form>
</div>
