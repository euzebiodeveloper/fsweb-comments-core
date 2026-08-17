<?php
/**
 * FSWEB Reviews — Slider Template
 *
 * Variables available:
 *   $reviews    — WP_Query result
 *   $show_image — bool
 *   $layout     — string, 'slider'
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="fsweb-reviews fsweb-reviews--slider" data-fsweb-slider>
	<div class="fsweb-reviews-track">
		<?php while ( $reviews->have_posts() ) : $reviews->the_post(); ?>
			<div class="fsweb-review-slide">
				<div class="fsweb-review-card">
					<?php if ( $show_image && has_post_thumbnail() ) : ?>
						<div class="fsweb-review-image">
							<?php the_post_thumbnail( 'medium', array( 'class' => 'fsweb-review-avatar' ) ); ?>
						</div>
					<?php endif; ?>

					<div class="fsweb-review-body">
						<?php if ( get_the_title() ) : ?>
							<h4 class="fsweb-review-name"><?php the_title(); ?></h4>
						<?php endif; ?>

						<div class="fsweb-review-content">
							<?php the_content(); ?>
						</div>
					</div>
				</div>
			</div>
		<?php endwhile; ?>
	</div>

	<button type="button" class="fsweb-slider-prev" aria-label="<?php esc_attr_e( 'Previous', 'fsweb-comments-core' ); ?>">&#10094;</button>
	<button type="button" class="fsweb-slider-next" aria-label="<?php esc_attr_e( 'Next', 'fsweb-comments-core' ); ?>">&#10095;</button>

	<div class="fsweb-slider-dots"></div>
</div>
