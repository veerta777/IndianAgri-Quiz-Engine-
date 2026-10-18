<?php
/**
 * Admin Dashboard & Platform Analytics overview template.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$table_questions = IAQ_Database::get_table_name( 'questions' );
$table_quizzes   = IAQ_Database::get_table_name( 'quizzes' );
$table_attempts  = IAQ_Database::get_table_name( 'quiz_attempts' );
$table_exams     = IAQ_Database::get_table_name( 'examinations' );
$table_plans     = IAQ_Database::get_table_name( 'subscription_plans' );
$table_subs      = IAQ_Database::get_table_name( 'user_subscriptions' );

$count_questions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_questions}" );
$count_quizzes   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_quizzes}" );
$count_mocks     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_quizzes} WHERE quiz_type = 'mock'" );
$count_practice  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_quizzes} WHERE quiz_type = 'practice'" );
$count_exams     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_exams}" );
$count_plans     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_plans} WHERE status = 'active'" );
$count_subs      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_subs} WHERE status = 'active' AND expiry_date > NOW()" );
$count_attempts  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_attempts} WHERE status = 'completed'" );

// Recent 5 attempts.
$recent_attempts = $wpdb->get_results(
	"SELECT a.*, q.title as quiz_title, q.exam_category
	 FROM {$table_attempts} a
	 LEFT JOIN {$table_quizzes} q ON a.quiz_id = q.id
	 WHERE a.status = 'completed'
	 ORDER BY a.id DESC LIMIT 5"
);
?>

<div class="wrap iaq-admin-wrap">
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php esc_html_e( 'Quiz Platform Dashboard & Administration', 'indianagri-quiz-engine' ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Welcome to the complete administrative control center for competitive agricultural examinations, monetization, and student performance.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
		<div class="iaq-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-add-quiz' ) ); ?>" class="iaq-btn iaq-btn-primary">
				<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Create New Quiz', 'indianagri-quiz-engine' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-add-question' ) ); ?>" class="iaq-btn iaq-btn-outline">
				<span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add Question', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>
	</div>

	<!-- Analytics Stat Tiles -->
	<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:20px;margin-top:24px;">
		<div class="iaq-card" style="padding:20px;border-left:4px solid #117E3A;">
			<div style="display:flex;justify-content:space-between;align-items:center;">
				<div>
					<div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">
						<?php esc_html_e( 'Question Bank', 'indianagri-quiz-engine' ); ?>
					</div>
					<div style="font-size:28px;font-weight:800;color:#0f172a;margin-top:4px;">
						<?php echo esc_html( number_format_i18n( $count_questions ) ); ?>
					</div>
					<div style="font-size:12px;color:#117E3A;margin-top:2px;">
						<?php esc_html_e( 'Bilingual MCQs', 'indianagri-quiz-engine' ); ?>
					</div>
				</div>
				<div style="width:44px;height:44px;border-radius:10px;background:#e8f5e9;color:#117E3A;display:flex;align-items:center;justify-content:center;font-size:20px;">
					<span class="dashicons dashicons-welcome-learn-more"></span>
				</div>
			</div>
		</div>

		<div class="iaq-card" style="padding:20px;border-left:4px solid #0284c7;">
			<div style="display:flex;justify-content:space-between;align-items:center;">
				<div>
					<div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">
						<?php esc_html_e( 'Quizzes & Mocks', 'indianagri-quiz-engine' ); ?>
					</div>
					<div style="font-size:28px;font-weight:800;color:#0f172a;margin-top:4px;">
						<?php echo esc_html( number_format_i18n( $count_quizzes ) ); ?>
					</div>
					<div style="font-size:12px;color:#0284c7;margin-top:2px;">
						<?php printf( esc_html__( '%1$d Mocks | %2$d Practice Sets', 'indianagri-quiz-engine' ), $count_mocks, $count_practice ); ?>
					</div>
				</div>
				<div style="width:44px;height:44px;border-radius:10px;background:#e0f2fe;color:#0284c7;display:flex;align-items:center;justify-content:center;font-size:20px;">
					<span class="dashicons dashicons-clipboard"></span>
				</div>
			</div>
		</div>

		<div class="iaq-card" style="padding:20px;border-left:4px solid #7c3aed;">
			<div style="display:flex;justify-content:space-between;align-items:center;">
				<div>
					<div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">
						<?php esc_html_e( 'Examinations', 'indianagri-quiz-engine' ); ?>
					</div>
					<div style="font-size:28px;font-weight:800;color:#0f172a;margin-top:4px;">
						<?php echo esc_html( number_format_i18n( $count_exams ) ); ?>
					</div>
					<div style="font-size:12px;color:#7c3aed;margin-top:2px;">
						<?php esc_html_e( 'Configured Categories', 'indianagri-quiz-engine' ); ?>
					</div>
				</div>
				<div style="width:44px;height:44px;border-radius:10px;background:#f3e8ff;color:#7c3aed;display:flex;align-items:center;justify-content:center;font-size:20px;">
					<span class="dashicons dashicons-category"></span>
				</div>
			</div>
		</div>

		<div class="iaq-card" style="padding:20px;border-left:4px solid #f59e0b;">
			<div style="display:flex;justify-content:space-between;align-items:center;">
				<div>
					<div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">
						<?php esc_html_e( 'Active Subscribers', 'indianagri-quiz-engine' ); ?>
					</div>
					<div style="font-size:28px;font-weight:800;color:#0f172a;margin-top:4px;">
						<?php echo esc_html( number_format_i18n( $count_subs ) ); ?>
					</div>
					<div style="font-size:12px;color:#b45309;margin-top:2px;">
						<?php printf( esc_html__( '%d active monetization plans', 'indianagri-quiz-engine' ), $count_plans ); ?>
					</div>
				</div>
				<div style="width:44px;height:44px;border-radius:10px;background:#fef3c7;color:#f59e0b;display:flex;align-items:center;justify-content:center;font-size:20px;">
					<span class="dashicons dashicons-tickets-alt"></span>
				</div>
			</div>
		</div>

		<div class="iaq-card" style="padding:20px;border-left:4px solid #10b981;">
			<div style="display:flex;justify-content:space-between;align-items:center;">
				<div>
					<div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">
						<?php esc_html_e( 'Completed Attempts', 'indianagri-quiz-engine' ); ?>
					</div>
					<div style="font-size:28px;font-weight:800;color:#0f172a;margin-top:4px;">
						<?php echo esc_html( number_format_i18n( $count_attempts ) ); ?>
					</div>
					<div style="font-size:12px;color:#10b981;margin-top:2px;">
						<?php esc_html_e( 'Graded & Evaluated', 'indianagri-quiz-engine' ); ?>
					</div>
				</div>
				<div style="width:44px;height:44px;border-radius:10px;background:#ecfdf5;color:#10b981;display:flex;align-items:center;justify-content:center;font-size:20px;">
					<span class="dashicons dashicons-awards"></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Quick Nav Links -->
	<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:14px;margin-top:20px;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-examinations' ) ); ?>" class="iaq-card" style="padding:14px;text-decoration:none;display:flex;align-items:center;gap:10px;color:#0f172a;font-weight:600;border:1px solid #e2e8f0;">
			<span class="dashicons dashicons-welcome-learn-more" style="color:#117E3A;"></span> <?php esc_html_e( 'Examinations', 'indianagri-quiz-engine' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subjects-topics' ) ); ?>" class="iaq-card" style="padding:14px;text-decoration:none;display:flex;align-items:center;gap:10px;color:#0f172a;font-weight:600;border:1px solid #e2e8f0;">
			<span class="dashicons dashicons-category" style="color:#117E3A;"></span> <?php esc_html_e( 'Subjects & Topics', 'indianagri-quiz-engine' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-question-bank' ) ); ?>" class="iaq-card" style="padding:14px;text-decoration:none;display:flex;align-items:center;gap:10px;color:#0f172a;font-weight:600;border:1px solid #e2e8f0;">
			<span class="dashicons dashicons-list-view" style="color:#117E3A;"></span> <?php esc_html_e( 'Question Bank', 'indianagri-quiz-engine' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subscription-plans' ) ); ?>" class="iaq-card" style="padding:14px;text-decoration:none;display:flex;align-items:center;gap:10px;color:#0f172a;font-weight:600;border:1px solid #e2e8f0;">
			<span class="dashicons dashicons-cart" style="color:#117E3A;"></span> <?php esc_html_e( 'Subscription Plans', 'indianagri-quiz-engine' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-user-subscriptions' ) ); ?>" class="iaq-card" style="padding:14px;text-decoration:none;display:flex;align-items:center;gap:10px;color:#0f172a;font-weight:600;border:1px solid #e2e8f0;">
			<span class="dashicons dashicons-admin-users" style="color:#117E3A;"></span> <?php esc_html_e( 'Student Subscriptions', 'indianagri-quiz-engine' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-attempts' ) ); ?>" class="iaq-card" style="padding:14px;text-decoration:none;display:flex;align-items:center;gap:10px;color:#0f172a;font-weight:600;border:1px solid #e2e8f0;">
			<span class="dashicons dashicons-analytics" style="color:#117E3A;"></span> <?php esc_html_e( 'Evaluation & Attempts', 'indianagri-quiz-engine' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-import-export' ) ); ?>" class="iaq-card" style="padding:14px;text-decoration:none;display:flex;align-items:center;gap:10px;color:#0f172a;font-weight:600;border:1px solid #e2e8f0;">
			<span class="dashicons dashicons-upload" style="color:#117E3A;"></span> <?php esc_html_e( 'CSV Import/Export', 'indianagri-quiz-engine' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-settings' ) ); ?>" class="iaq-card" style="padding:14px;text-decoration:none;display:flex;align-items:center;gap:10px;color:#0f172a;font-weight:600;border:1px solid #e2e8f0;">
			<span class="dashicons dashicons-admin-settings" style="color:#117E3A;"></span> <?php esc_html_e( 'Global Settings', 'indianagri-quiz-engine' ); ?>
		</a>
	</div>

	<!-- Recent Attempts Section -->
	<div class="iaq-card" style="margin-top:24px;">
		<div class="iaq-card-header" style="display:flex;justify-content:space-between;align-items:center;">
			<h2><span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Recent Student Attempts', 'indianagri-quiz-engine' ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-attempts' ) ); ?>" class="iaq-btn iaq-btn-outline" style="font-size:12px;padding:4px 10px;">
				<?php esc_html_e( 'View All Attempts', 'indianagri-quiz-engine' ); ?> &rarr;
			</a>
		</div>
		<div class="iaq-card-body" style="padding:0;">
			<table class="iaq-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Student', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Exam', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Category', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Score', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Percentage', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Result', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Completed At', 'indianagri-quiz-engine' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $recent_attempts ) ) : ?>
						<tr>
							<td colspan="7" class="iaq-no-records" style="text-align:center;padding:24px;">
								<?php esc_html_e( 'No student examination attempts completed yet.', 'indianagri-quiz-engine' ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $recent_attempts as $ra ) : ?>
							<?php
							$u = get_userdata( $ra->user_id );
							$u_name = $u ? $u->display_name : 'User #' . $ra->user_id;
							?>
							<tr>
								<td><strong><?php echo esc_html( $u_name ); ?></strong></td>
								<td><?php echo esc_html( $ra->quiz_title ); ?></td>
								<td><span class="iaq-badge iaq-badge-exam"><?php echo esc_html( $ra->exam_category ); ?></span></td>
								<td><strong><?php echo esc_html( number_format( (float) $ra->score, 2 ) ); ?></strong> / <?php echo esc_html( number_format( (float) $ra->total_marks, 2 ) ); ?></td>
								<td><?php echo esc_html( number_format( (float) $ra->percentage, 1 ) ); ?>%</td>
								<td>
									<?php if ( 1 === (int) $ra->passed ) : ?>
										<span class="iaq-badge" style="background:#dcfce7;color:#15803d;font-weight:700;">&#10004; <?php esc_html_e( 'PASSED', 'indianagri-quiz-engine' ); ?></span>
									<?php else : ?>
										<span class="iaq-badge" style="background:#fee2e2;color:#991b1b;font-weight:700;">&#10008; <?php esc_html_e( 'FAILED', 'indianagri-quiz-engine' ); ?></span>
									<?php endif; ?>
								</td>
								<td><span style="font-size:12px;color:#64748b;"><?php echo esc_html( $ra->end_time ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
