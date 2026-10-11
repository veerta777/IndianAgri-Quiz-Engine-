<?php
/**
 * Import & Export Question Bank template.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sample_csv_url = wp_nonce_url(
	admin_url( 'admin.php?page=iaq-import-export&iaq_action=download_sample_csv' ),
	'iaq_download_sample_csv'
);

$export_all_url = wp_nonce_url(
	admin_url( 'admin.php?page=iaq-import-export&iaq_action=export_csv' ),
	'iaq_export_csv'
);

$expected_cols = IAQ_Import_Export::get_expected_headers();
?>

<div class="wrap iaq-admin-wrap">
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php esc_html_e( 'Bulk Question Import & Export', 'indianagri-quiz-engine' ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Seamlessly upload thousands of agricultural MCQs from CSV spreadsheets or export your question repository with full English & Hindi UTF-8 support.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
		<div class="iaq-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-question-bank' ) ); ?>" class="iaq-btn iaq-btn-outline">
				<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to Question Bank', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>
	</div>

	<div class="iaq-grid-two-col">
		<!-- LEFT COLUMN: IMPORT -->
		<div class="iaq-col-panel">
			<!-- Upload Form Card -->
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2><span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Import Questions via CSV', 'indianagri-quiz-engine' ); ?></h2>
				</div>
				<div class="iaq-card-body">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-import-export' ) ); ?>" enctype="multipart/form-data">
						<?php wp_nonce_field( 'iaq_upload_csv_nonce' ); ?>

						<div class="iaq-upload-dropzone" id="iaq-dropzone">
							<span class="dashicons dashicons-media-spreadsheet iaq-upload-icon"></span>
							<h3><?php esc_html_e( 'Select or Drop your CSV file here', 'indianagri-quiz-engine' ); ?></h3>
							<p><?php esc_html_e( 'File must be UTF-8 encoded with .csv extension.', 'indianagri-quiz-engine' ); ?></p>
							<input type="file" name="csv_file" id="iaq-csv-file-input" accept=".csv" required>
						</div>

						<div class="iaq-file-chosen-preview" id="iaq-file-preview" style="display:none;">
							<span class="dashicons dashicons-yes-alt"></span>
							<span id="iaq-file-name-display"></span>
						</div>

						<div class="iaq-form-actions" style="margin-top: 20px;">
							<button type="submit" name="iaq_upload_csv" class="iaq-btn iaq-btn-primary iaq-btn-large">
								<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload and Import Questions', 'indianagri-quiz-engine' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>

			<!-- Sample Template Card -->
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2><span class="dashicons dashicons-media-default"></span> <?php esc_html_e( 'Download Sample CSV Template', 'indianagri-quiz-engine' ); ?></h2>
				</div>
				<div class="iaq-card-body">
					<p>
						<?php esc_html_e( 'Download our pre-formatted CSV template populated with sample English & Hindi agricultural MCQs. Use this format in Microsoft Excel, Google Sheets, or LibreOffice Calc.', 'indianagri-quiz-engine' ); ?>
					</p>
					<a href="<?php echo esc_url( $sample_csv_url ); ?>" class="iaq-btn iaq-btn-secondary">
						<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Download Sample Template (.CSV)', 'indianagri-quiz-engine' ); ?>
					</a>
				</div>
			</div>
		</div>

		<!-- RIGHT COLUMN: EXPORT & FORMAT GUIDE -->
		<div class="iaq-col-panel">
			<!-- Export Card -->
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2><span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export Questions to CSV', 'indianagri-quiz-engine' ); ?></h2>
				</div>
				<div class="iaq-card-body">
					<p>
						<?php esc_html_e( 'Export all questions currently stored in your question bank to an Excel-ready UTF-8 CSV file with Unicode BOM protection for Hindi characters.', 'indianagri-quiz-engine' ); ?>
					</p>
					<a href="<?php echo esc_url( $export_all_url ); ?>" class="iaq-btn iaq-btn-primary">
						<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export All Questions (.CSV)', 'indianagri-quiz-engine' ); ?>
					</a>
				</div>
			</div>

			<!-- Column Format Specification -->
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'CSV Column Structure Guide', 'indianagri-quiz-engine' ); ?></h2>
				</div>
				<div class="iaq-card-body">
					<div class="iaq-spec-table-wrap">
						<table class="iaq-spec-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Column Header', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Required', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Description & Valid Values', 'indianagri-quiz-engine' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><code>question_text</code></td>
									<td><strong class="iaq-req-yes"><?php esc_html_e( 'Yes', 'indianagri-quiz-engine' ); ?></strong></td>
									<td><?php esc_html_e( 'The MCQ stem in English or Hindi Unicode.', 'indianagri-quiz-engine' ); ?></td>
								</tr>
								<tr>
									<td><code>option_a</code></td>
									<td><strong class="iaq-req-yes"><?php esc_html_e( 'Yes', 'indianagri-quiz-engine' ); ?></strong></td>
									<td><?php esc_html_e( 'First option text.', 'indianagri-quiz-engine' ); ?></td>
								</tr>
								<tr>
									<td><code>option_b</code></td>
									<td><strong class="iaq-req-yes"><?php esc_html_e( 'Yes', 'indianagri-quiz-engine' ); ?></strong></td>
									<td><?php esc_html_e( 'Second option text.', 'indianagri-quiz-engine' ); ?></td>
								</tr>
								<tr>
									<td><code>option_c</code></td>
									<td><strong class="iaq-req-yes"><?php esc_html_e( 'Yes', 'indianagri-quiz-engine' ); ?></strong></td>
									<td><?php esc_html_e( 'Third option text.', 'indianagri-quiz-engine' ); ?></td>
								</tr>
								<tr>
									<td><code>option_d</code></td>
									<td><strong class="iaq-req-yes"><?php esc_html_e( 'Yes', 'indianagri-quiz-engine' ); ?></strong></td>
									<td><?php esc_html_e( 'Fourth option text.', 'indianagri-quiz-engine' ); ?></td>
								</tr>
								<tr>
									<td><code>correct_option</code></td>
									<td><strong class="iaq-req-yes"><?php esc_html_e( 'Yes', 'indianagri-quiz-engine' ); ?></strong></td>
									<td><code>A</code>, <code>B</code>, <code>C</code>, or <code>D</code></td>
								</tr>
								<tr>
									<td><code>explanation</code></td>
									<td><em><?php esc_html_e( 'Optional', 'indianagri-quiz-engine' ); ?></em></td>
									<td><?php esc_html_e( 'Explanation / rationale for the answer.', 'indianagri-quiz-engine' ); ?></td>
								</tr>
								<tr>
									<td><code>subject</code></td>
									<td><em><?php esc_html_e( 'Optional', 'indianagri-quiz-engine' ); ?></em></td>
									<td><?php esc_html_e( 'Subject name (e.g., Agronomy). Auto-created if new.', 'indianagri-quiz-engine' ); ?></td>
								</tr>
								<tr>
									<td><code>topic</code></td>
									<td><em><?php esc_html_e( 'Optional', 'indianagri-quiz-engine' ); ?></em></td>
									<td><?php esc_html_e( 'Topic under the subject. Auto-created if new.', 'indianagri-quiz-engine' ); ?></td>
								</tr>
								<tr>
									<td><code>exam_category</code></td>
									<td><em><?php esc_html_e( 'Optional', 'indianagri-quiz-engine' ); ?></em></td>
									<td><?php esc_html_e( 'e.g., ICAR AIEEA, IBPS AFO, State Agriculture PSC.', 'indianagri-quiz-engine' ); ?></td>
								</tr>
								<tr>
									<td><code>difficulty</code></td>
									<td><em><?php esc_html_e( 'Optional', 'indianagri-quiz-engine' ); ?></em></td>
									<td><code>easy</code>, <code>medium</code>, or <code>hard</code> (default: medium)</td>
								</tr>
								<tr>
									<td><code>marks</code></td>
									<td><em><?php esc_html_e( 'Optional', 'indianagri-quiz-engine' ); ?></em></td>
									<td><?php esc_html_e( 'Positive marks (e.g. 1.00, 2.00)', 'indianagri-quiz-engine' ); ?></td>
								</tr>
								<tr>
									<td><code>negative_marks</code></td>
									<td><em><?php esc_html_e( 'Optional', 'indianagri-quiz-engine' ); ?></em></td>
									<td><?php esc_html_e( 'Penalty marks (e.g. 0.25, 0.33, 0.00)', 'indianagri-quiz-engine' ); ?></td>
								</tr>
								<tr>
									<td><code>status</code></td>
									<td><em><?php esc_html_e( 'Optional', 'indianagri-quiz-engine' ); ?></em></td>
									<td><code>published</code> or <code>draft</code></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
