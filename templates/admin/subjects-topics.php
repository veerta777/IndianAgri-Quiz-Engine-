<?php
/**
 * Subjects & Topics taxonomy management template.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$subjects = IAQ_Question_Bank::get_subjects();
$topics   = IAQ_Question_Bank::get_topics();

// Check if editing a subject.
$edit_subject = null;
if ( isset( $_GET['edit_subject'] ) ) {
	$edit_subject = IAQ_Question_Bank::get_subject( absint( $_GET['edit_subject'] ) );
}

// Check if editing a topic.
$edit_topic = null;
if ( isset( $_GET['edit_topic'] ) ) {
	$edit_topic = IAQ_Question_Bank::get_topic( absint( $_GET['edit_topic'] ) );
}
?>

<div class="wrap iaq-admin-wrap">
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php esc_html_e( 'Subjects & Topics Taxonomy', 'indianagri-quiz-engine' ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Structure agricultural domains into core subjects (e.g. Agronomy, Genetics, Soil Science) and specialized topics.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
		<div class="iaq-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-question-bank' ) ); ?>" class="iaq-btn iaq-btn-outline">
				<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to Question Bank', 'indianagri-quiz-engine' ); ?>
			</a>
		</div>
	</div>

	<div class="iaq-grid-two-col">
		<!-- LEFT COLUMN: SUBJECTS -->
		<div class="iaq-col-panel">
			<!-- Add/Edit Subject Form -->
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2>
						<span class="dashicons dashicons-category"></span> 
						<?php echo $edit_subject ? esc_html__( 'Edit Subject', 'indianagri-quiz-engine' ) : esc_html__( 'Add New Subject', 'indianagri-quiz-engine' ); ?>
					</h2>
				</div>
				<div class="iaq-card-body">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subjects-topics' ) ); ?>">
						<?php wp_nonce_field( 'iaq_save_subject_nonce' ); ?>
						<?php if ( $edit_subject ) : ?>
							<input type="hidden" name="id" value="<?php echo esc_attr( $edit_subject->id ); ?>">
						<?php endif; ?>

						<div class="iaq-form-group">
							<label for="subj-name"><?php esc_html_e( 'Subject Name', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<input type="text" name="name" id="subj-name" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_subject ? $edit_subject->name : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., Agronomy, Soil Science', 'indianagri-quiz-engine' ); ?>" required>
						</div>

						<div class="iaq-form-group">
							<label for="subj-slug"><?php esc_html_e( 'Slug (Optional)', 'indianagri-quiz-engine' ); ?></label>
							<input type="text" name="slug" id="subj-slug" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_subject ? $edit_subject->slug : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., agronomy', 'indianagri-quiz-engine' ); ?>">
						</div>

						<div class="iaq-form-group">
							<label for="subj-desc"><?php esc_html_e( 'Description', 'indianagri-quiz-engine' ); ?></label>
							<textarea name="description" id="subj-desc" rows="3" class="iaq-textarea iaq-full-width" placeholder="<?php esc_attr_e( 'Short summary of the agricultural discipline...', 'indianagri-quiz-engine' ); ?>"><?php echo esc_textarea( $edit_subject ? $edit_subject->description : '' ); ?></textarea>
						</div>

						<div class="iaq-form-actions">
							<button type="submit" name="iaq_save_subject" class="iaq-btn iaq-btn-primary">
								<span class="dashicons dashicons-saved"></span> <?php echo $edit_subject ? esc_html__( 'Update Subject', 'indianagri-quiz-engine' ) : esc_html__( 'Create Subject', 'indianagri-quiz-engine' ); ?>
							</button>
							<?php if ( $edit_subject ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subjects-topics' ) ); ?>" class="iaq-btn iaq-btn-outline">
									<?php esc_html_e( 'Cancel', 'indianagri-quiz-engine' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</form>
				</div>
			</div>

			<!-- Subjects List -->
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'All Subjects', 'indianagri-quiz-engine' ); ?> (<?php echo count( $subjects ); ?>)</h2>
				</div>
				<div class="iaq-card-body iaq-p0">
					<table class="iaq-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Subject', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Topics', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $subjects ) ) : ?>
								<tr><td colspan="4" class="iaq-no-records"><?php esc_html_e( 'No subjects registered yet.', 'indianagri-quiz-engine' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ( $subjects as $s ) : ?>
									<?php
									$del_url = wp_nonce_url(
										admin_url( 'admin.php?page=iaq-subjects-topics&iaq_action=delete_subject&id=' . $s->id ),
										'iaq_delete_subject_' . $s->id
									);
									$edit_url = admin_url( 'admin.php?page=iaq-subjects-topics&edit_subject=' . $s->id );
									?>
									<tr>
										<td>
											<strong><?php echo esc_html( $s->name ); ?></strong>
											<div class="iaq-slug-subtext"><?php echo esc_html( $s->slug ); ?></div>
										</td>
										<td>
											<span class="iaq-count-badge"><?php echo esc_html( $s->topic_count ); ?></span>
										</td>
										<td>
											<span class="iaq-count-badge iaq-badge-green"><?php echo esc_html( $s->question_count ); ?></span>
										</td>
										<td>
											<div class="iaq-row-action-btns">
												<a href="<?php echo esc_url( $edit_url ); ?>" class="iaq-action-btn iaq-btn-edit" title="<?php esc_attr_e( 'Edit', 'indianagri-quiz-engine' ); ?>">
													<span class="dashicons dashicons-edit"></span>
												</a>
												<a href="<?php echo esc_url( $del_url ); ?>" class="iaq-action-btn iaq-btn-delete iaq-confirm-delete" title="<?php esc_attr_e( 'Delete', 'indianagri-quiz-engine' ); ?>">
													<span class="dashicons dashicons-trash"></span>
												</a>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- RIGHT COLUMN: TOPICS -->
		<div class="iaq-col-panel">
			<!-- Add/Edit Topic Form -->
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2>
						<span class="dashicons dashicons-tag"></span> 
						<?php echo $edit_topic ? esc_html__( 'Edit Topic', 'indianagri-quiz-engine' ) : esc_html__( 'Add New Topic', 'indianagri-quiz-engine' ); ?>
					</h2>
				</div>
				<div class="iaq-card-body">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subjects-topics' ) ); ?>">
						<?php wp_nonce_field( 'iaq_save_topic_nonce' ); ?>
						<?php if ( $edit_topic ) : ?>
							<input type="hidden" name="id" value="<?php echo esc_attr( $edit_topic->id ); ?>">
						<?php endif; ?>

						<div class="iaq-form-group">
							<label for="topic-subject"><?php esc_html_e( 'Parent Subject', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<select name="subject_id" id="topic-subject" class="iaq-select iaq-full-width" required>
								<option value=""><?php esc_html_e( '— Select Parent Subject —', 'indianagri-quiz-engine' ); ?></option>
								<?php
								$selected_parent = $edit_topic ? $edit_topic->subject_id : 0;
								foreach ( $subjects as $subj ) :
									?>
									<option value="<?php echo esc_attr( $subj->id ); ?>" <?php selected( $selected_parent, $subj->id ); ?>>
										<?php echo esc_html( $subj->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="iaq-form-group">
							<label for="topic-name"><?php esc_html_e( 'Topic Name', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<input type="text" name="name" id="topic-name" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_topic ? $edit_topic->name : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., Weed Science & Management', 'indianagri-quiz-engine' ); ?>" required>
						</div>

						<div class="iaq-form-group">
							<label for="topic-slug"><?php esc_html_e( 'Slug (Optional)', 'indianagri-quiz-engine' ); ?></label>
							<input type="text" name="slug" id="topic-slug" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_topic ? $edit_topic->slug : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., weed-science', 'indianagri-quiz-engine' ); ?>">
						</div>

						<div class="iaq-form-actions">
							<button type="submit" name="iaq_save_topic" class="iaq-btn iaq-btn-primary">
								<span class="dashicons dashicons-saved"></span> <?php echo $edit_topic ? esc_html__( 'Update Topic', 'indianagri-quiz-engine' ) : esc_html__( 'Create Topic', 'indianagri-quiz-engine' ); ?>
							</button>
							<?php if ( $edit_topic ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-subjects-topics' ) ); ?>" class="iaq-btn iaq-btn-outline">
									<?php esc_html_e( 'Cancel', 'indianagri-quiz-engine' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</form>
				</div>
			</div>

			<!-- Topics List -->
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2><span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'All Topics', 'indianagri-quiz-engine' ); ?> (<?php echo count( $topics ); ?>)</h2>
				</div>
				<div class="iaq-card-body iaq-p0">
					<div class="iaq-scrollable-table">
						<table class="iaq-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Topic', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Subject', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'MCQs', 'indianagri-quiz-engine' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $topics ) ) : ?>
									<tr><td colspan="4" class="iaq-no-records"><?php esc_html_e( 'No topics registered yet.', 'indianagri-quiz-engine' ); ?></td></tr>
								<?php else : ?>
									<?php
									// Map subjects by ID for easy display.
									$subj_name_map = array();
									foreach ( $subjects as $s ) {
										$subj_name_map[ $s->id ] = $s->name;
									}
									foreach ( $topics as $t ) :
										?>
										<?php
										$del_topic_url = wp_nonce_url(
											admin_url( 'admin.php?page=iaq-subjects-topics&iaq_action=delete_topic&id=' . $t->id ),
											'iaq_delete_topic_' . $t->id
										);
										$edit_topic_url = admin_url( 'admin.php?page=iaq-subjects-topics&edit_topic=' . $t->id );
										$parent_name    = isset( $subj_name_map[ $t->subject_id ] ) ? $subj_name_map[ $t->subject_id ] : __( 'Unknown', 'indianagri-quiz-engine' );
										?>
										<tr>
											<td>
												<strong><?php echo esc_html( $t->name ); ?></strong>
											</td>
											<td>
												<span class="iaq-subject-badge"><?php echo esc_html( $parent_name ); ?></span>
											</td>
											<td>
												<span class="iaq-count-badge"><?php echo esc_html( $t->question_count ); ?></span>
											</td>
											<td>
												<div class="iaq-row-action-btns">
													<a href="<?php echo esc_url( $edit_topic_url ); ?>" class="iaq-action-btn iaq-btn-edit" title="<?php esc_attr_e( 'Edit', 'indianagri-quiz-engine' ); ?>">
														<span class="dashicons dashicons-edit"></span>
													</a>
													<a href="<?php echo esc_url( $del_topic_url ); ?>" class="iaq-action-btn iaq-btn-delete iaq-confirm-delete" title="<?php esc_attr_e( 'Delete', 'indianagri-quiz-engine' ); ?>">
														<span class="dashicons dashicons-trash"></span>
													</a>
												</div>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
