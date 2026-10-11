<?php
/**
 * Frontend: Quiz List Grid.
 * Displayed via shortcode [iaq_quiz_list].
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$types = IAQ_Quiz_Builder::get_quiz_types();
?>

<div class="iaq-quiz-grid-wrapper">
	<?php if ( empty( $quizzes ) ) : ?>
		<div class="iaq-notice-box iaq-notice-info">
			<?php esc_html_e( 'No published examinations currently available in this category.', 'indianagri-quiz-engine' ); ?>
		</div>
	<?php else : ?>
		<div class="iaq-quiz-grid">
			<?php foreach ( $quizzes as $q ) : ?>
				<?php
				$type_meta = isset( $types[ $q->quiz_type ] ) ? $types[ $q->quiz_type ] : array( 'label' => ucfirst( $q->quiz_type ), 'badge_color' => '#117E3A' );
				?>
				<div class="iaq-grid-card">
					<div class="iaq-card-top">
						<div class="iaq-badges-line">
							<span class="iaq-pill iaq-pill-category"><?php echo esc_html( $q->exam_category ); ?></span>
							<span class="iaq-pill iaq-pill-type" style="background:<?php echo esc_attr( $type_meta['badge_color'] ); ?>15;color:<?php echo esc_attr( $type_meta['badge_color'] ); ?>;">
								<?php echo esc_html( $type_meta['label'] ); ?>
							</span>
							<?php if ( 'paid' === $q->access_type ) : ?>
								<span class="iaq-pill iaq-pill-paid"><?php esc_html_e( 'Paid', 'indianagri-quiz-engine' ); ?></span>
							<?php else : ?>
								<span class="iaq-pill iaq-pill-free"><?php esc_html_e( 'Free', 'indianagri-quiz-engine' ); ?></span>
							<?php endif; ?>
						</div>

						<h3 class="iaq-grid-title">
							<?php echo esc_html( $q->title ); ?>
						</h3>

						<?php if ( ! empty( $q->description ) ) : ?>
							<p class="iaq-grid-excerpt">
								<?php echo esc_html( wp_trim_words( $q->description, 16 ) ); ?>
							</p>
						<?php endif; ?>
					</div>

					<div class="iaq-card-meta-bar">
						<div class="iaq-meta-item">
							<span class="iaq-meta-label"><?php esc_html_e( 'Questions', 'indianagri-quiz-engine' ); ?></span>
							<span class="iaq-meta-val"><?php echo esc_html( $q->total_questions ); ?></span>
						</div>
						<div class="iaq-meta-item">
							<span class="iaq-meta-label"><?php esc_html_e( 'Marks', 'indianagri-quiz-engine' ); ?></span>
							<span class="iaq-meta-val"><?php echo esc_html( $q->total_marks ); ?></span>
						</div>
						<div class="iaq-meta-item">
							<span class="iaq-meta-label"><?php esc_html_e( 'Duration', 'indianagri-quiz-engine' ); ?></span>
							<span class="iaq-meta-val">
								<?php echo ( $q->duration_minutes > 0 ) ? esc_html( $q->duration_minutes ) . 'm' : esc_html__( 'Untimed', 'indianagri-quiz-engine' ); ?>
							</span>
						</div>
					</div>

					<div class="iaq-card-footer">
						<?php $target_id = isset( $q->id ) ? (int) $q->id : ( isset( $q->quiz_id ) ? (int) $q->quiz_id : 0 ); ?>
						<a href="<?php echo esc_url( add_query_arg( 'iaq_quiz_id', $target_id, get_permalink() ) ); ?>" class="iaq-btn-take-quiz">
							<?php esc_html_e( 'View & Attempt Test', 'indianagri-quiz-engine' ); ?> &rarr;
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
