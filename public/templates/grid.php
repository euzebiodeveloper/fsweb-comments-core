<?php
/**
 * FSWEB Reviews — Grid Template
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

<div class="fsweb-reviews fsweb-reviews--grid">
	<?php while ( $reviews->have_posts() ) : $reviews->the_post(); ?>
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
	<?php endwhile; ?>
</div>
