<?php
/**
 * FSWEB Reviews — Slider Template
 *
 * The default layout: a row of cards, one page at a time, arrows and dots.
 * The structure is the same one the PRO add-on renders, so installing or
 * removing the add-on never changes the page format under the visitor.
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

$fsweb_cc_show_image = 'yes' === $atts['show_image'];
$fsweb_cc_slides     = min( 4, max( 1, (int) $atts['slides'] ) );
$fsweb_cc_theme      = isset( $atts['theme'] ) ? $atts['theme'] : 'default';
?>
<div class="fsweb-reviews fsweb-reviews--slider fsweb-reviews--theme-<?php echo esc_attr( $fsweb_cc_theme ); ?>" data-fsweb-slider data-fsweb-slides="<?php echo esc_attr( (string) $fsweb_cc_slides ); ?>">
	<div class="fsweb-slider-stage">
		<div class="fsweb-slider-viewport">
			<div class="fsweb-reviews-track">
				<?php
				while ( $reviews->have_posts() ) :
					$reviews->the_post();
					$fsweb_cc_rating = (int) get_post_meta( get_the_ID(), 'fsweb_rating', true );
					?>
					<div class="fsweb-review-slide">
						<article class="fsweb-review-card<?php echo $fsweb_cc_show_image ? '' : ' fsweb-review-card--stacked'; ?>">
							<div class="fsweb-review-card__aside">
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

								<?php if ( get_the_title() ) : ?>
									<h4 class="fsweb-review-name"><?php the_title(); ?></h4>
								<?php endif; ?>
							</div>

							<div class="fsweb-review-card__main">
								<?php if ( $fsweb_cc_rating > 0 ) : ?>
									<div class="fsweb-review-stars-display" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: rating. */ __( 'Rated %d out of 5', 'fsweb-comments-core' ), $fsweb_cc_rating ) ); ?>">
										<?php
										for ( $fsweb_cc_index = 1; $fsweb_cc_index <= 5; $fsweb_cc_index++ ) {
											echo '<span class="fsweb-star' . ( $fsweb_cc_index <= $fsweb_cc_rating ? ' fsweb-star--filled' : '' ) . '" aria-hidden="true">&#9733;</span>';
										}
										?>
									</div>
								<?php endif; ?>

								<div class="fsweb-review-content">
									<?php the_content(); ?>
								</div>

								<time class="fsweb-review-date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
									<?php echo esc_html( get_the_date() ); ?>
								</time>
							</div>
						</article>
					</div>
					<?php
				endwhile;
				?>
			</div>
		</div>

		<button type="button" class="fsweb-slider-prev" aria-label="<?php esc_attr_e( 'Previous review', 'fsweb-comments-core' ); ?>"><?php echo Fsweb_Comments_Core_Public::arrow_icon( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></button>
		<button type="button" class="fsweb-slider-next" aria-label="<?php esc_attr_e( 'Next review', 'fsweb-comments-core' ); ?>"><?php echo Fsweb_Comments_Core_Public::arrow_icon( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG. ?></button>
	</div>

	<div class="fsweb-slider-dots"></div>
</div>
