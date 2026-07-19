<?php
/**
 * FSWWP Reviews — Grid Template
 *
 * Variables available:
 *   $reviews    — WP_Query result
 *   $show_image — bool
 *   $layout     — string, 'grid'
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="fswwp-reviews fswwp-reviews--grid">
	<?php while ( $reviews->have_posts() ) : $reviews->the_post(); ?>
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
	<?php endwhile; ?>
</div>
