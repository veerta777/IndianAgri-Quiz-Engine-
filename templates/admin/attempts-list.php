<?php
/**
 * Student Quiz Attempts & Results List admin template.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_attempts = IAQ_Database::get_table_name( 'quiz_attempts' );
$table_quizzes  = IAQ_Database::get_table_name( 'quizzes' );

$quiz_id_filter = isset( $_GET['quiz_id'] ) ? absint( $_GET['quiz_id'] ) : 0;
$status_filter  = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';

$where_clauses = array( '1=1' );
$params        = array();

if ( $quiz_id_filter > 0 ) {
	$where_clauses[] = 'a.quiz_id = %d';
	$params[]        = $quiz_id_filter;
}

if ( ! empty( $status_filter ) ) {
	$where_clauses[] = 'a.status = %s';
	$params[]        = $status_filter;
}

$where = implode( ' AND ', $where_clauses );

$sql = "
	SELECT a.*, q.title AS quiz_title, q.quiz_type, q.exam_category
	FROM {$table_attempts} a
	LEFT JOIN {$table_quizzes} q ON a.quiz_id = q.id
	WHERE {$where}
	ORDER BY a.id DESC
	LIMIT 100
";

if ( ! empty( $params ) ) {
	$attempts = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
} else {
	$attempts = $wpdb->get_results( $sql );
}

$all_quizzes = IAQ_Quiz_Builder::get_quizzes( array( 'per_page' => 100 ) );
?>

<div class="wrap iaq-admin-wrap">
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php esc_html_e( 'Quiz Attempts & Results Evaluation', 'indianagri-quiz-engine' ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Monitor live student test sessions, evaluate performance scorecards, review accuracy percentages, and inspect attempt timestamps.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
	</div>

	<!-- Filters Filter Bar -->
	<div class="iaq-filter-card" style="margin-top:20px;">
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="iaq-filter-form">
			<input type="hidden" name="page" value="iaq-attempts">

			<div class="iaq-filter-row">
				<div class="iaq-filter-field" style="flex:2;">
					<label for="filter-quiz"><?php esc_html_e( 'Filter by Quiz', 'indianagri-quiz-engine' ); ?></label>
					<select name="quiz_id" id="filter-quiz" class="iaq-select iaq-full-width">
						<option value=""><?php esc_html_e( 'All Quizzes & Mock Tests', 'indianagri-quiz-engine' ); ?></option>
						<?php foreach ( $all_quizzes as $qz ) : ?>
							<option value="<?php echo esc_attr( $qz->id ); ?>" <?php selected( $quiz_id_filter, $qz->id ); ?>>
								#<?php echo esc_html( $qz->id ); ?> &mdash; <?php echo esc_html( $qz->title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="iaq-filter-field">
					<label for="filter-status"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></label>
					<select name="status" id="filter-status" class="iaq-select">
						<option value=""><?php esc_html_e( 'All Statuses', 'indianagri-quiz-engine' ); ?></option>
						<option value="completed" <?php selected( $status_filter, 'completed' ); ?>><?php esc_html_e( 'Completed', 'indianagri-quiz-engine' ); ?></option>
						<option value="in_progress" <?php selected( $status_filter, 'in_progress' ); ?>><?php esc_html_e( 'In Progress', 'indianagri-quiz-engine' ); ?></option>
						<option value="abandoned" <?php selected( $status_filter, 'abandoned' ); ?>><?php esc_html_e( 'Abandoned', 'indianagri-quiz-engine' ); ?></option>
					</select>
				</div>

				<div class="iaq-filter-actions">
					<button type="submit" class="iaq-btn iaq-btn-primary">
						<span class="dashicons dashicons-filter"></span> <?php esc_html_e( 'Filter', 'indianagri-quiz-engine' ); ?>
					</button>
					<?php if ( $quiz_id_filter || $status_filter ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-attempts' ) ); ?>" class="iaq-btn iaq-btn-outline">
							<?php esc_html_e( 'Reset', 'indianagri-quiz-engine' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</form>
	</div>

	<!-- Attempts Table -->
	<div class="iaq-card" style="margin-top:20px;">
		<div class="iaq-card-header" style="display:flex;justify-content:space-between;align-items:center;">
			<h2><span class="dashicons dashicons-analytics"></span> <?php esc_html_e( 'Student Examination Records', 'indianagri-quiz-engine' ); ?></h2>
			<span class="iaq-card-help"><?php printf( esc_html__( '%d attempts displayed', 'indianagri-quiz-engine' ), count( $attempts ) ); ?></span>
		</div>
		<div class="iaq-card-body" style="padding:0;">
			<table class="iaq-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Attempt ID', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Student Candidate', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Examination Quiz', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Score / Total', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Percentage', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Verdict', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Time Spent', 'indianagri-quiz-engine' ); ?></th>
						<th><?php esc_html_e( 'Date & Time', 'indianagri-quiz-engine' ); ?></th>
						<th style="text-align:right;"><?php esc_html_e( 'Scorecard', 'indianagri-quiz-engine' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $attempts ) ) : ?>
						<tr>
							<td colspan="9" class="iaq-no-records" style="text-align:center;padding:30px;">
								<?php esc_html_e( 'No student examination attempts found matching your criteria.', 'indianagri-quiz-engine' ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $attempts as $att ) : ?>
							<?php
							$user_obj = get_userdata( $att->user_id );
							$student_name = $user_obj ? $user_obj->display_name : 'User #' . $att->user_id;
							$student_email = $user_obj ? $user_obj->user_email : '';
							$mins = floor( $att->time_spent_seconds / 60 );
							$secs = $att->time_spent_seconds % 60;
							$time_str = sprintf( '%02dm %02ds', $mins, $secs );
							?>
							<tr>
								<td>
									<span class="iaq-id-pill">#<?php echo esc_html( $att->id ); ?></span>
								</td>
								<td>
									<strong><?php echo esc_html( $student_name ); ?></strong>
									<?php if ( $student_email ) : ?>
										<div style="font-size:11px;color:#64748b;"><?php echo esc_html( $student_email ); ?></div>
									<?php endif; ?>
								</td>
								<td>
									<strong><?php echo esc_html( $att->quiz_title ); ?></strong>
									<div style="margin-top:2px;display:flex;gap:6px;">
										<span class="iaq-badge iaq-badge-exam"><?php echo esc_html( $att->exam_category ); ?></span>
										<span class="iaq-topic-badge"><?php echo esc_html( ucfirst( $att->quiz_type ) ); ?></span>
									</div>
								</td>
								<td>
									<strong><?php echo esc_html( number_format( (float) $att->score, 2 ) ); ?></strong> / <?php echo esc_html( number_format( (float) $att->total_marks, 2 ) ); ?>
								</td>
								<td>
									<strong><?php echo esc_html( number_format( (float) $att->percentage, 1 ) ); ?>%</strong>
								</td>
								<td>
									<?php if ( 'completed' === $att->status ) : ?>
										<?php if ( 1 === (int) $att->passed ) : ?>
											<span class="iaq-badge" style="background:#dcfce7;color:#15803d;font-weight:700;">&#10004; <?php esc_html_e( 'PASSED', 'indianagri-quiz-engine' ); ?></span>
										<?php else : ?>
											<span class="iaq-badge" style="background:#fee2e2;color:#991b1b;font-weight:700;">&#10008; <?php esc_html_e( 'FAILED', 'indianagri-quiz-engine' ); ?></span>
										<?php endif; ?>
									<?php else : ?>
										<span class="iaq-badge iaq-status-draft"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $att->status ) ) ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<span style="font-size:12px;color:#475569;"><?php echo esc_html( $time_str ); ?></span>
								</td>
								<td>
									<span style="font-size:12px;color:#64748b;"><?php echo esc_html( $att->end_time ? $att->end_time : $att->start_time ); ?></span>
								</td>
								<td style="text-align:right;">
									<?php if ( 'completed' === $att->status ) : ?>
										<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'iaq-preview-student-results', 'attempt_id' => $att->id ), home_url( '/' ) ) ); ?>" target="_blank" class="iaq-action-btn" title="<?php esc_attr_e( 'View Scorecard & Solutions', 'indianagri-quiz-engine' ); ?>" style="color:#117E3A;">
											<span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Scorecard', 'indianagri-quiz-engine' ); ?>
										</a>
									<?php else : ?>
										<span style="color:#94a3b8;font-size:12px;">&mdash;</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
