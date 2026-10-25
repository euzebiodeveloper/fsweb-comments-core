<?php
/**
 * FSWEB Reviews — Grid Template
 *
 * Variables available:
 *   $reviews — WP_Query with the published reviews
 *   $atts    — shortcode attributes
 *
 * @package Fsweb_Comments_Core
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$fsweb_cc_columns = min( 4, max( 1, (int) $atts['columns'] ) );
$fsweb_cc_show_image = 'yes' === $atts['show_image'];
?>
<div class="fsweb-reviews fsweb-reviews--grid" style="--fsweb-columns:<?php echo (int) $fsweb_cc_columns; ?>;">
	<?php
	while ( $reviews->have_posts() ) :
		$reviews->the_post();
		$fsweb_cc_rating = (int) get_post_meta( get_the_ID(), 'fsweb_rating', true );
		$fsweb_cc_date   = get_the_date();
		?>
		<article class="fsweb-review-card">
			<?php if ( $fsweb_cc_show_image && has_post_thumbnail() ) : ?>
				<div class="fsweb-review-image">
					<?php the_post_thumbnail( 'medium', array( 'class' => 'fsweb-review-avatar', 'loading' => 'lazy' ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $fsweb_cc_rating > 0 ) : ?>
				<div class="fsweb-review-stars-display" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: rating. */ __( 'Rated %d out of 5', 'fsweb-comments-core' ), $fsweb_cc_rating ) ); ?>">
					<?php
					for ( $i = 1; $i <= 5; $i++ ) {
						echo '<span class="fsweb-star' . ( $i <= $fsweb_cc_rating ? ' fsweb-star--filled' : '' ) . '" aria-hidden="true">&#9733;</span>';
					}
					?>
				</div>
			<?php endif; ?>

			<div class="fsweb-review-body">
				<?php if ( get_the_title() ) : ?>
					<h4 class="fsweb-review-name"><?php the_title(); ?></h4>
				<?php endif; ?>

				<div class="fsweb-review-content">
					<?php the_content(); ?>
				</div>

				<time class="fsweb-review-date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
					<?php echo esc_html( $fsweb_cc_date ); ?>
				</time>
			</div>
		</article>
		<?php
	endwhile;
	?>
</div>
