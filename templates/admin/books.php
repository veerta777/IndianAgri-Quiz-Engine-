<?php
/**
 * Books Management Admin Template.
 * Allows administrators to add, edit, archive, and manage agricultural books,
 * upload cover images, associate subjects/examinations, and manage assigned practice sets.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$action    = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
$book_id   = isset( $_GET['book_id'] ) ? absint( $_GET['book_id'] ) : ( isset( $_GET['edit_id'] ) ? absint( $_GET['edit_id'] ) : 0 );
$edit_book = ( 'edit' === $action && $book_id ) ? IAQ_Practice_Sets::get_book( $book_id ) : null;

$subjects = IAQ_Question_Bank::get_subjects();
$exams    = IAQ_Examinations::get_examinations();

// If managing practice sets for a specific book:
if ( 'manage_sets' === $action && $book_id ) :
	$book             = IAQ_Practice_Sets::get_book( $book_id );
	$assigned_quizzes = IAQ_Practice_Sets::get_book_quizzes( $book_id, '' ); // Get all statuses for admin
	$all_quizzes      = IAQ_Quiz_Builder::get_quizzes( array( 'per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );

	// Filter out quizzes already assigned
	$assigned_ids = array_map( function( $q ) { return (int) $q->id; }, $assigned_quizzes );
	?>
	<div class="wrap iaq-admin-wrap">
		<div class="iaq-header-banner">
			<div class="iaq-header-title">
				<div class="iaq-header-badge"><?php esc_html_e( 'Practice by Book', 'indianagri-quiz-engine' ); ?></div>
				<h1><?php printf( esc_html__( 'Manage Practice Sets: %s', 'indianagri-quiz-engine' ), esc_html( $book ? $book->title : '' ) ); ?></h1>
				<p class="iaq-header-desc">
					<?php esc_html_e( 'Assign existing quizzes, create new sets, and configure the display sequence (Practice Set 1, 2, 3...) for this book.', 'indianagri-quiz-engine' ); ?>
				</p>
			</div>
			<div class="iaq-header-actions" style="margin-top:12px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-books' ) ); ?>" class="iaq-btn iaq-btn-outline">
					<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to All Books', 'indianagri-quiz-engine' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-add-quiz&quiz_type=practice&book_id=' . $book_id . ( $book && $book->subject_id ? '&subject_id=' . $book->subject_id : '' ) ) ); ?>" class="iaq-btn iaq-btn-primary">
					<span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'Create New Quiz for this Book', 'indianagri-quiz-engine' ); ?>
				</a>
			</div>
		</div>

		<div class="iaq-two-col-layout" style="display:grid;grid-template-columns:360px 1fr;gap:24px;margin-top:20px;">
			<!-- Assign Quiz Form Column -->
			<div class="iaq-form-column">
				<div class="iaq-card">
					<div class="iaq-card-header">
						<h2><span class="dashicons dashicons-paperclip"></span> <?php esc_html_e( 'Attach Existing Practice Set', 'indianagri-quiz-engine' ); ?></h2>
					</div>
					<div class="iaq-card-body">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-books&action=manage_sets&book_id=' . $book_id ) ); ?>">
							<?php wp_nonce_field( 'iaq_assign_book_quiz_nonce' ); ?>
							<input type="hidden" name="book_id" value="<?php echo esc_attr( $book_id ); ?>">

							<div class="iaq-form-group">
								<label for="assign-quiz-id"><?php esc_html_e( 'Select Quiz / Practice Set', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
								<select name="quiz_id" id="assign-quiz-id" class="iaq-select iaq-full-width" required>
									<option value=""><?php esc_html_e( '— Select a Practice Set —', 'indianagri-quiz-engine' ); ?></option>
									<?php foreach ( $all_quizzes as $q ) : ?>
										<?php if ( ! in_array( (int) $q->id, $assigned_ids, true ) ) : ?>
											<option value="<?php echo esc_attr( $q->id ); ?>">
												#<?php echo esc_html( $q->id ); ?>: <?php echo esc_html( $q->title ); ?> (<?php echo esc_html( $q->total_questions ); ?> Qs)
											</option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
								<span class="iaq-card-help"><?php esc_html_e( 'Reuses the existing quiz without duplicating questions or historical student records.', 'indianagri-quiz-engine' ); ?></span>
							</div>

							<div class="iaq-form-group">
								<label for="assign-sort-order"><?php esc_html_e( 'Display Position / Sort Order', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" min="1" name="sort_order" id="assign-sort-order" class="iaq-input iaq-full-width" value="<?php echo esc_attr( count( $assigned_quizzes ) + 1 ); ?>">
								<span class="iaq-card-help"><?php esc_html_e( 'Determines whether this appears as Practice Set 1, 2, 3...', 'indianagri-quiz-engine' ); ?></span>
							</div>

							<div style="margin-top:16px;">
								<button type="submit" name="iaq_assign_book_quiz" class="iaq-btn iaq-btn-primary iaq-full-width">
									<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Attach to Book', 'indianagri-quiz-engine' ); ?>
								</button>
							</div>
						</form>
					</div>
				</div>

				<!-- Book Details Summary -->
				<?php if ( $book ) : ?>
					<div class="iaq-card" style="margin-top:20px;">
						<div class="iaq-card-header">
							<h2><span class="dashicons dashicons-book-alt"></span> <?php esc_html_e( 'Book Summary', 'indianagri-quiz-engine' ); ?></h2>
						</div>
						<div class="iaq-card-body" style="font-size:13px;line-height:1.6;color:#475569;">
							<?php if ( ! empty( $book->cover_image_url ) ) : ?>
								<div style="text-align:center;margin-bottom:12px;">
									<img src="<?php echo esc_url( $book->cover_image_url ); ?>" alt="<?php echo esc_attr( $book->title ); ?>" style="max-height:120px;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.1);">
								</div>
							<?php endif; ?>
							<div><strong><?php esc_html_e( 'Author/Source:', 'indianagri-quiz-engine' ); ?></strong> <?php echo esc_html( $book->author ? $book->author : 'N/A' ); ?></div>
							<div><strong><?php esc_html_e( 'Subject:', 'indianagri-quiz-engine' ); ?></strong> <?php echo esc_html( $book->subject_name ? $book->subject_name : 'General' ); ?></div>
							<div><strong><?php esc_html_e( 'Examination:', 'indianagri-quiz-engine' ); ?></strong> <?php echo esc_html( $book->examination_name ? $book->examination_name : 'All' ); ?></div>
							<div><strong><?php esc_html_e( 'Access:', 'indianagri-quiz-engine' ); ?></strong> <?php echo 'paid' === $book->access_type ? '<span class="iaq-pill iaq-pill-paid">Paid</span>' : '<span class="iaq-pill iaq-pill-free">Free</span>'; ?></div>
							<div><strong><?php esc_html_e( 'Status:', 'indianagri-quiz-engine' ); ?></strong> <?php echo esc_html( ucfirst( $book->status ) ); ?></div>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<!-- Assigned Practice Sets List Column -->
			<div class="iaq-list-column">
				<div class="iaq-card">
					<div class="iaq-card-header" style="display:flex;justify-content:space-between;align-items:center;">
						<h2>
							<span class="dashicons dashicons-list-view"></span>
							<?php printf( esc_html__( 'Assigned Practice Sets (%d)', 'indianagri-quiz-engine' ), count( $assigned_quizzes ) ); ?>
						</h2>
					</div>
					<div class="iaq-card-body" style="padding:0;">
						<?php if ( empty( $assigned_quizzes ) ) : ?>
							<div style="padding:40px;text-align:center;color:#64748b;">
								<span class="dashicons dashicons-book" style="font-size:48px;width:48px;height:48px;color:#cbd5e1;margin-bottom:12px;"></span>
								<h3 style="margin:0 0 8px 0;color:#334155;"><?php esc_html_e( 'No Practice Sets Assigned Yet', 'indianagri-quiz-engine' ); ?></h3>
								<p style="margin:0;font-size:13px;"><?php esc_html_e( 'Use the form on the left to attach existing practice tests, or click "Create New Quiz" above.', 'indianagri-quiz-engine' ); ?></p>
							</div>
						<?php else : ?>
							<table class="iaq-admin-table" style="width:100%;border-collapse:collapse;">
								<thead>
									<tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;text-align:left;">
										<th style="padding:12px 16px;width:70px;"><?php esc_html_e( 'Display #', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;"><?php esc_html_e( 'Practice Set Title', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;width:90px;"><?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;width:80px;"><?php esc_html_e( 'Marks', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;width:90px;"><?php esc_html_e( 'Duration', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;width:80px;"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
										<th style="padding:12px 16px;text-align:right;width:140px;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $assigned_quizzes as $pq ) : ?>
										<tr style="border-bottom:1px solid #f1f5f9;">
											<td style="padding:12px 16px;font-weight:700;color:#117E3A;">
												<?php printf( esc_html__( 'Set %d', 'indianagri-quiz-engine' ), esc_html( $pq->practice_set_number ) ); ?>
											</td>
											<td style="padding:12px 16px;">
												<strong><?php echo esc_html( $pq->title ); ?></strong>
												<div style="font-size:11px;color:#64748b;margin-top:2px;">
													Quiz ID: #<?php echo esc_html( $pq->id ); ?>
													<?php if ( ! empty( $pq->subject_name ) ) : ?>
														&bull; <?php echo esc_html( $pq->subject_name ); ?>
													<?php endif; ?>
													&bull; <?php echo 'paid' === $pq->access_type ? '<span style="color:#d97706;">Paid</span>' : '<span style="color:#117E3A;">Free</span>'; ?>
												</div>
											</td>
											<td style="padding:12px 16px;color:#475569;">
												<?php echo esc_html( $pq->total_questions ); ?> Qs
											</td>
											<td style="padding:12px 16px;color:#475569;">
												<?php echo esc_html( $pq->total_marks ); ?>
											</td>
											<td style="padding:12px 16px;color:#475569;">
												<?php echo $pq->duration_minutes > 0 ? esc_html( $pq->duration_minutes ) . 'm' : esc_html__( 'Untimed', 'indianagri-quiz-engine' ); ?>
											</td>
											<td style="padding:12px 16px;">
												<span class="iaq-status-badge iaq-status-<?php echo esc_attr( $pq->status ); ?>">
													<?php echo esc_html( ucfirst( $pq->status ) ); ?>
												</span>
											</td>
											<td style="padding:12px 16px;text-align:right;">
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-quizzes&action=edit&id=' . $pq->id ) ); ?>" class="iaq-table-action" title="<?php esc_attr_e( 'Edit Quiz Settings', 'indianagri-quiz-engine' ); ?>">
													<span class="dashicons dashicons-edit"></span>
												</a>
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=iaq-books&action=manage_sets&book_id=' . $book_id . '&iaq_action=remove_book_quiz&quiz_id=' . $pq->id ), 'iaq_remove_book_quiz_' . $book_id . '_' . $pq->id ) ); ?>" class="iaq-table-action iaq-action-delete" title="<?php esc_attr_e( 'Detach from this book', 'indianagri-quiz-engine' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to remove this practice set from this book? The quiz and its questions will remain in the system.', 'indianagri-quiz-engine' ); ?>');">
													<span class="dashicons dashicons-no-alt"></span>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	return;
endif;

// Default View: Books Catalog Table + Add/Edit Form
$books = IAQ_Practice_Sets::get_books( array( 'status' => '' ) );
?>

<div class="wrap iaq-admin-wrap">
	<div class="iaq-header-banner">
		<div class="iaq-header-title">
			<div class="iaq-header-badge"><?php esc_html_e( 'IndianAgriExam.com', 'indianagri-quiz-engine' ); ?></div>
			<h1><?php esc_html_e( 'Practice Books Management', 'indianagri-quiz-engine' ); ?></h1>
			<p class="iaq-header-desc">
				<?php esc_html_e( 'Manage agricultural reference books, author attributions, cover artwork, and organize practice sets by book without modifying code.', 'indianagri-quiz-engine' ); ?>
			</p>
		</div>
	</div>

	<div class="iaq-two-col-layout" style="display:grid;grid-template-columns:360px 1fr;gap:24px;margin-top:20px;">
		<!-- Add / Edit Book Form Column -->
		<div class="iaq-form-column">
			<div class="iaq-card">
				<div class="iaq-card-header">
					<h2>
						<span class="dashicons dashicons-<?php echo $edit_book ? 'edit' : 'plus-alt'; ?>"></span>
						<?php echo $edit_book ? esc_html__( 'Edit Practice Book', 'indianagri-quiz-engine' ) : esc_html__( 'Add New Practice Book', 'indianagri-quiz-engine' ); ?>
					</h2>
				</div>
				<div class="iaq-card-body">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=iaq-books' ) ); ?>">
						<?php wp_nonce_field( 'iaq_save_book_nonce' ); ?>
						<?php if ( $edit_book ) : ?>
							<input type="hidden" name="id" value="<?php echo esc_attr( $edit_book->id ); ?>">
						<?php endif; ?>

						<div class="iaq-form-group">
							<label for="book-title"><?php esc_html_e( 'Book Title', 'indianagri-quiz-engine' ); ?> <span class="iaq-required">*</span></label>
							<input type="text" name="title" id="book-title" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_book ? $edit_book->title : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., A Competitive Book of Agriculture', 'indianagri-quiz-engine' ); ?>" required>
						</div>

						<div class="iaq-form-group">
							<label for="book-author"><?php esc_html_e( 'Author / Source', 'indianagri-quiz-engine' ); ?></label>
							<input type="text" name="author" id="book-author" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_book ? $edit_book->author : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., Nem Raj Sundha, Arun Katyayan', 'indianagri-quiz-engine' ); ?>">
						</div>

						<div class="iaq-form-group">
							<label for="book-slug"><?php esc_html_e( 'URL Slug', 'indianagri-quiz-engine' ); ?></label>
							<input type="text" name="slug" id="book-slug" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_book ? $edit_book->slug : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., nem-raj-sundha-agriculture', 'indianagri-quiz-engine' ); ?>">
						</div>

						<div class="iaq-form-group">
							<label for="book-cover"><?php esc_html_e( 'Cover Image URL', 'indianagri-quiz-engine' ); ?></label>
							<input type="url" name="cover_image_url" id="book-cover" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_book ? $edit_book->cover_image_url : '' ); ?>" placeholder="https://example.com/images/book-cover.jpg">
							<span class="iaq-card-help"><?php esc_html_e( 'Direct URL to book cover graphic. A handsome placeholder badge is rendered if blank.', 'indianagri-quiz-engine' ); ?></span>
						</div>

						<div class="iaq-form-group">
							<label for="book-subject"><?php esc_html_e( 'Associated Subject', 'indianagri-quiz-engine' ); ?></label>
							<select name="subject_id" id="book-subject" class="iaq-select iaq-full-width">
								<option value="0"><?php esc_html_e( '— General / Multiple Subjects —', 'indianagri-quiz-engine' ); ?></option>
								<?php foreach ( $subjects as $subj ) : ?>
									<option value="<?php echo esc_attr( $subj->id ); ?>" <?php selected( $edit_book ? $edit_book->subject_id : 0, $subj->id ); ?>>
										<?php echo esc_html( $subj->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="iaq-form-group">
							<label for="book-exam"><?php esc_html_e( 'Associated Examination', 'indianagri-quiz-engine' ); ?></label>
							<select name="examination_id" id="book-exam" class="iaq-select iaq-full-width">
								<option value="0"><?php esc_html_e( '— All Examinations / Competitive Exams —', 'indianagri-quiz-engine' ); ?></option>
								<?php foreach ( $exams as $ex ) : ?>
									<option value="<?php echo esc_attr( $ex->id ); ?>" <?php selected( $edit_book ? $edit_book->examination_id : 0, $ex->id ); ?>>
										<?php echo esc_html( $ex->name ); ?> (<?php echo esc_html( $ex->code ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
							<div class="iaq-form-group">
								<label for="book-access"><?php esc_html_e( 'Access Tier', 'indianagri-quiz-engine' ); ?></label>
								<select name="access_type" id="book-access" class="iaq-select iaq-full-width">
									<option value="free" <?php selected( $edit_book ? $edit_book->access_type : 'free', 'free' ); ?>><?php esc_html_e( 'Free', 'indianagri-quiz-engine' ); ?></option>
									<option value="paid" <?php selected( $edit_book ? $edit_book->access_type : '', 'paid' ); ?>><?php esc_html_e( 'Paid / Premium', 'indianagri-quiz-engine' ); ?></option>
								</select>
							</div>

							<div class="iaq-form-group">
								<label for="book-order"><?php esc_html_e( 'Display Order', 'indianagri-quiz-engine' ); ?></label>
								<input type="number" min="0" name="display_order" id="book-order" class="iaq-input iaq-full-width" value="<?php echo esc_attr( $edit_book ? $edit_book->display_order : 0 ); ?>">
							</div>
						</div>

						<div class="iaq-form-group">
							<label for="book-status"><?php esc_html_e( 'Publication Status', 'indianagri-quiz-engine' ); ?></label>
							<select name="status" id="book-status" class="iaq-select iaq-full-width">
								<option value="published" <?php selected( $edit_book ? $edit_book->status : 'published', 'published' ); ?>><?php esc_html_e( 'Published (Live)', 'indianagri-quiz-engine' ); ?></option>
								<option value="draft" <?php selected( $edit_book ? $edit_book->status : '', 'draft' ); ?>><?php esc_html_e( 'Draft (Hidden)', 'indianagri-quiz-engine' ); ?></option>
								<option value="archived" <?php selected( $edit_book ? $edit_book->status : '', 'archived' ); ?>><?php esc_html_e( 'Archived', 'indianagri-quiz-engine' ); ?></option>
							</select>
						</div>

						<div class="iaq-form-group">
							<label for="book-desc"><?php esc_html_e( 'Book Overview / Description', 'indianagri-quiz-engine' ); ?></label>
							<textarea name="description" id="book-desc" rows="3" class="iaq-textarea iaq-full-width" placeholder="<?php esc_attr_e( 'Brief summary of book chapters and exam focus...', 'indianagri-quiz-engine' ); ?>"><?php echo esc_textarea( $edit_book ? $edit_book->description : '' ); ?></textarea>
						</div>

						<div style="margin-top:20px;display:flex;gap:10px;">
							<button type="submit" name="iaq_save_book" class="iaq-btn iaq-btn-primary" style="flex:1;">
								<span class="dashicons dashicons-saved"></span> <?php echo $edit_book ? esc_html__( 'Update Book', 'indianagri-quiz-engine' ) : esc_html__( 'Save Book', 'indianagri-quiz-engine' ); ?>
							</button>
							<?php if ( $edit_book ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-books' ) ); ?>" class="iaq-btn iaq-btn-outline">
									<?php esc_html_e( 'Cancel', 'indianagri-quiz-engine' ); ?>
								</a>
							<?php endif; ?>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Books Table Column -->
		<div class="iaq-list-column">
			<div class="iaq-card">
				<div class="iaq-card-header" style="display:flex;justify-content:space-between;align-items:center;">
					<h2>
						<span class="dashicons dashicons-book"></span>
						<?php printf( esc_html__( 'Configured Books (%d)', 'indianagri-quiz-engine' ), count( $books ) ); ?>
					</h2>
				</div>
				<div class="iaq-card-body" style="padding:0;">
					<?php if ( empty( $books ) ) : ?>
						<div style="padding:40px;text-align:center;color:#64748b;">
							<span class="dashicons dashicons-book-alt" style="font-size:48px;width:48px;height:48px;color:#cbd5e1;margin-bottom:12px;"></span>
							<h3 style="margin:0 0 8px 0;color:#334155;"><?php esc_html_e( 'No Books Found', 'indianagri-quiz-engine' ); ?></h3>
							<p style="margin:0;font-size:13px;"><?php esc_html_e( 'Add your first reference book using the form on the left.', 'indianagri-quiz-engine' ); ?></p>
						</div>
					<?php else : ?>
						<table class="iaq-admin-table" style="width:100%;border-collapse:collapse;">
							<thead>
								<tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;text-align:left;">
									<th style="padding:12px 16px;width:54px;"><?php esc_html_e( 'Cover', 'indianagri-quiz-engine' ); ?></th>
									<th style="padding:12px 16px;"><?php esc_html_e( 'Book & Author', 'indianagri-quiz-engine' ); ?></th>
									<th style="padding:12px 16px;width:120px;"><?php esc_html_e( 'Practice Sets', 'indianagri-quiz-engine' ); ?></th>
									<th style="padding:12px 16px;width:80px;"><?php esc_html_e( 'Access', 'indianagri-quiz-engine' ); ?></th>
									<th style="padding:12px 16px;width:70px;"><?php esc_html_e( 'Order', 'indianagri-quiz-engine' ); ?></th>
									<th style="padding:12px 16px;width:80px;"><?php esc_html_e( 'Status', 'indianagri-quiz-engine' ); ?></th>
									<th style="padding:12px 16px;text-align:right;width:170px;"><?php esc_html_e( 'Actions', 'indianagri-quiz-engine' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $books as $b ) : ?>
									<tr style="border-bottom:1px solid #f1f5f9;">
										<td style="padding:12px 16px;">
											<?php if ( ! empty( $b->cover_image_url ) ) : ?>
												<img src="<?php echo esc_url( $b->cover_image_url ); ?>" alt="<?php echo esc_attr( $b->title ); ?>" style="width:36px;height:48px;object-fit:cover;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
											<?php else : ?>
												<div style="width:36px;height:48px;background:linear-gradient(135deg, #117E3A, #0d5f2c);color:#fff;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:18px;">
													<span class="dashicons dashicons-book-alt" style="font-size:18px;width:18px;height:18px;"></span>
												</div>
											<?php endif; ?>
										</td>
										<td style="padding:12px 16px;">
											<div style="font-weight:700;color:#0f172a;font-size:14px;">
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-books&action=manage_sets&book_id=' . $b->id ) ); ?>" style="color:#0f172a;text-decoration:none;">
													<?php echo esc_html( $b->title ); ?>
												</a>
											</div>
											<div style="font-size:12px;color:#64748b;margin-top:2px;">
												<?php if ( ! empty( $b->author ) ) : ?>
													<span><?php printf( esc_html__( 'By %s', 'indianagri-quiz-engine' ), esc_html( $b->author ) ); ?></span> &bull;
												<?php endif; ?>
												<?php echo esc_html( $b->subject_name ? $b->subject_name : 'General Agriculture' ); ?>
												<?php if ( ! empty( $b->examination_name ) ) : ?>
													&bull; <span class="iaq-pill iaq-pill-category" style="padding:2px 6px;font-size:10px;"><?php echo esc_html( $b->examination_name ); ?></span>
												<?php endif; ?>
											</div>
										</td>
										<td style="padding:12px 16px;">
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-books&action=manage_sets&book_id=' . $b->id ) ); ?>" class="iaq-pill" style="background:#f0fdf4;color:#117E3A;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
												<span class="dashicons dashicons-list-view" style="font-size:14px;width:14px;height:14px;"></span>
												<?php printf( esc_html__( '%d Sets', 'indianagri-quiz-engine' ), (int) $b->practice_sets_count ); ?>
											</a>
										</td>
										<td style="padding:12px 16px;">
											<?php echo 'paid' === $b->access_type ? '<span class="iaq-pill iaq-pill-paid">Paid</span>' : '<span class="iaq-pill iaq-pill-free">Free</span>'; ?>
										</td>
										<td style="padding:12px 16px;color:#64748b;font-weight:600;">
											<?php echo esc_html( $b->display_order ); ?>
										</td>
										<td style="padding:12px 16px;">
											<span class="iaq-status-badge iaq-status-<?php echo esc_attr( $b->status ); ?>">
												<?php echo esc_html( ucfirst( $b->status ) ); ?>
											</span>
										</td>
										<td style="padding:12px 16px;text-align:right;">
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-books&action=manage_sets&book_id=' . $b->id ) ); ?>" class="iaq-btn iaq-btn-outline" style="padding:4px 8px;font-size:12px;margin-right:4px;" title="<?php esc_attr_e( 'Manage Practice Sets', 'indianagri-quiz-engine' ); ?>">
												<?php esc_html_e( 'Manage Sets', 'indianagri-quiz-engine' ); ?>
											</a>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=iaq-books&action=edit&edit_id=' . $b->id ) ); ?>" class="iaq-table-action" title="<?php esc_attr_e( 'Edit Book', 'indianagri-quiz-engine' ); ?>">
												<span class="dashicons dashicons-edit"></span>
											</a>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=iaq-books&iaq_action=delete_book&id=' . $b->id ), 'iaq_delete_book_' . $b->id ) ); ?>" class="iaq-table-action iaq-action-delete" title="<?php esc_attr_e( 'Archive Book', 'indianagri-quiz-engine' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to archive this book? Historical student attempts will be safely preserved.', 'indianagri-quiz-engine' ); ?>');">
												<span class="dashicons dashicons-trash"></span>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
