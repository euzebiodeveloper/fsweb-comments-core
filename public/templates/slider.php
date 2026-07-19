<?php
/**
 * FSWWP Reviews — Slider Template
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

<div class="fswwp-reviews fswwp-reviews--slider" data-fswwp-slider>
	<div class="fswwp-reviews-track">
		<?php while ( $reviews->have_posts() ) : $reviews->the_post(); ?>
			<div class="fswwp-review-slide">
				<div class="fswwp-review-card">
					<?php if ( $show_image && has_post_thumbnail() ) : ?>
						<div class="fswwp-review-image">
							<?php the_post_thumbnail( 'medium', array( 'class' => 'fswwp-review-avatar' ) ); ?>
						</div>
					<?php endif; ?>

					<div class="fswwp-review-body">
						<?php if ( get_the_title() ) : ?>
							<h4 class="fswwp-review-name"><?php the_title(); ?></h4>
						<?php endif; ?>

						<div class="fswwp-review-content">
							<?php the_content(); ?>
						</div>
					</div>
				</div>
			</div>
		<?php endwhile; ?>
	</div>

	<button type="button" class="fswwp-slider-prev" aria-label="<?php esc_attr_e( 'Previous', 'fswwp-comments-core' ); ?>">&#10094;</button>
	<button type="button" class="fswwp-slider-next" aria-label="<?php esc_attr_e( 'Next', 'fswwp-comments-core' ); ?>">&#10095;</button>

	<div class="fswwp-slider-dots"></div>
</div>
