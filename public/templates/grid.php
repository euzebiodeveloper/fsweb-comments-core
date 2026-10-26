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
$fsweb_cc_theme = isset( $atts['theme'] ) ? $atts['theme'] : 'default';
?>
<div class="fsweb-reviews fsweb-reviews--grid fsweb-reviews--theme-<?php echo esc_attr( $fsweb_cc_theme ); ?>" data-fsweb-columns="<?php echo esc_attr( (string) $fsweb_cc_columns ); ?>">
	<?php
	while ( $reviews->have_posts() ) :
		$reviews->the_post();
		$fsweb_cc_rating = (int) get_post_meta( get_the_ID(), 'fsweb_rating', true );
		$fsweb_cc_date   = get_the_date();
		?>
		<article class="fsweb-review-card">
			<?php if ( $fsweb_cc_show_image ) : ?>
				<div class="fsweb-review-image">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'medium', array( 'class' => 'fsweb-review-avatar', 'loading' => 'lazy' ) ); ?>
					<?php else : ?>
						<?php // A review without a photo still needs an anchor in the card. ?>
						<?php echo '<span class="fsweb-review-avatar fsweb-review-avatar--placeholder" aria-hidden="true"><svg viewBox="0 0 24 24" width="40" height="40" fill="currentColor" focusable="false"><path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm0 2c-4.14 0-7.5 2.24-7.5 5v1h15v-1c0-2.76-3.36-5-7.5-5Z"/></svg></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup. ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $fsweb_cc_rating > 0 ) : ?>
				<div class="fsweb-review-stars-display" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: rating. */ __( 'Rated %d out of 5', 'fsweb-comments-core' ), $fsweb_cc_rating ) ); ?>">
					<?php
					for ( $fsweb_cc_index = 1; $fsweb_cc_index <= 5; $fsweb_cc_index++ ) {
						echo '<span class="fsweb-star' . ( $fsweb_cc_index <= $fsweb_cc_rating ? ' fsweb-star--filled' : '' ) . '" aria-hidden="true">&#9733;</span>';
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
