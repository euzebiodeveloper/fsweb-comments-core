<?php
/**
 * FSWEB Reviews — Dynamic View Template
 *
 * Renders review cards based on the View's row/column/component structure.
 *
 * Variables:
 *   $reviews    — WP_Query result
 *   $layout     — string (grid, slider, list)
 *   $rows       — array of row definitions from the saved View
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-scoped variable, not a global.
$wrap_class = 'fsweb-reviews';
if ( 'slider' === $layout ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-scoped variable, not a global.
	$wrap_class .= ' fsweb-reviews--slider';
} elseif ( 'list' === $layout ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-scoped variable, not a global.
	$wrap_class .= ' fsweb-reviews--list';
} else {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-scoped variable, not a global.
	$wrap_class .= ' fsweb-reviews--grid';
}
?>

<?php if ( 'slider' === $layout ) : ?>
<div class="<?php echo esc_attr( $wrap_class ); ?>" data-fsweb-slider>
	<div class="fsweb-reviews-track">
		<?php while ( $reviews->have_posts() ) : $reviews->the_post(); ?>
			<div class="fsweb-review-slide">
				<div class="fsweb-review-card">
					<?php fsweb_render_rows( $rows, get_the_ID() ); ?>
				</div>
			</div>
		<?php endwhile; ?>
	</div>
	<button type="button" class="fsweb-slider-prev">&#10094;</button>
	<button type="button" class="fsweb-slider-next">&#10095;</button>
	<div class="fsweb-slider-dots"></div>
</div>

<?php else : ?>
<div class="<?php echo esc_attr( $wrap_class ); ?>">
	<?php while ( $reviews->have_posts() ) : $reviews->the_post(); ?>
		<div class="fsweb-review-card">
			<?php fsweb_render_rows( $rows, get_the_ID() ); ?>
		</div>
	<?php endwhile; ?>
</div>
<?php endif; ?>

<?php
/**
 * Render rows for a single review card.
 */
function fsweb_render_rows( $rows, $post_id ) {
	foreach ( $rows as $row ) {
		echo '<div class="fsweb-review-row" style="display:flex;gap:16px;">';
		if ( isset( $row['columns'] ) ) {
			$col_count = count( $row['columns'] );
			foreach ( $row['columns'] as $col ) {
				$w = isset( $col['width'] ) ? (int) $col['width'] : floor( 100 / $col_count );
				echo '<div class="fsweb-review-col" style="flex:' . (int) $w . ';">';
				if ( isset( $col['components'] ) ) {
					foreach ( $col['components'] as $comp ) {
						fsweb_render_component( $comp, $post_id );
					}
				}
				echo '</div>';
			}
		}
		echo '</div>';
	}
}

/**
 * Build inline style string from component styles array.
 */
function fsweb_build_comp_style( $comp ) {
	if ( empty( $comp['styles'] ) || ! is_array( $comp['styles'] ) ) {
		return '';
	}
	$map = array(
		'text_align'    => 'text-align',
		'font_weight'   => 'font-weight',
		'font_style'    => 'font-style',
		'font_size'     => 'font-size',
		'color'         => 'color',
		'background'    => 'background',
		'margin'        => 'margin',
		'padding'       => 'padding',
		'width'         => 'width',
		'height'        => 'height',
		'border_radius' => 'border-radius',
		'object_fit'    => 'object-fit',
		'box_shadow'    => 'box-shadow',
		'star_size'     => 'font-size',
		'star_color'    => 'color',
		'gap'           => 'gap',
	);
	$parts = array();
	foreach ( $comp['styles'] as $key => $val ) {
		if ( isset( $map[ $key ] ) && '' !== $val ) {
			$parts[] = $map[ $key ] . ':' . esc_attr( $val );
		}
	}
	return $parts ? ' style="' . implode( ';', $parts ) . '"' : '';
}

/**
 * Render a single component.
 */
function fsweb_render_component( $comp, $post_id ) {
	$type     = $comp['type'];
	$field_id = isset( $comp['field_id'] ) ? $comp['field_id'] : '';

	switch ( $type ) {
		case 'image':
			if ( has_post_thumbnail( $post_id ) ) {
				$is = fsweb_build_comp_style( $comp );
				echo '<div class="fsweb-review-image"' . wp_kses_post( $is ) . '>';
				echo get_the_post_thumbnail( $post_id, 'medium', array( 'class' => 'fsweb-review-avatar', 'style' => ( $is ? 'width:100%;height:auto;' : '' ) ) );
				echo '</div>';
			}
			break;

		case 'name':
			$title = get_the_title( $post_id );
			if ( $title ) {
				echo '<h4 class="fsweb-review-name"' . wp_kses_post( fsweb_build_comp_style( $comp ) ) . '>' . esc_html( $title ) . '</h4>';
			}
			break;

		case 'content':
		case 'textarea':
			$post = get_post( $post_id );
			// For textarea, try post content first, then meta.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook, not a custom hook.
			$text = $post ? apply_filters( 'the_content', $post->post_content ) : '';
			if ( ! $text && $field_id ) {
				$text = get_post_meta( $post_id, $field_id, true );
			}
			if ( $text ) {
				echo '<div class="fsweb-review-content"' . wp_kses_post( fsweb_build_comp_style( $comp ) ) . '>' . wp_kses_post( $text ) . '</div>';
			}
			break;

		case 'rating':
			$rating = $field_id ? get_post_meta( $post_id, $field_id, true ) : get_post_meta( $post_id, 'rating', true );
			if ( $rating ) {
				$stars = (int) $rating;
				echo '<div class="fsweb-review-stars-display"' . wp_kses_post( fsweb_build_comp_style( $comp ) ) . '>';
				for ( $i = 1; $i <= 5; $i++ ) {
					echo '<span class="fsweb-star ' . ( $i <= $stars ? 'fsweb-star--filled' : '' ) . '">&#9733;</span>';
				}
				echo '</div>';
			}
			break;

		case 'date':
			echo '<span class="fsweb-review-date"' . wp_kses_post( fsweb_build_comp_style( $comp ) ) . '>' . esc_html( get_the_date( '', $post_id ) ) . '</span>';
			break;

		case 'category':
			$cats = get_the_terms( $post_id, 'fsweb_review_category' );
			if ( $cats && ! is_wp_error( $cats ) ) {
				echo '<span class="fsweb-review-category"' . wp_kses_post( fsweb_build_comp_style( $comp ) ) . '>';
				$names = array();
				foreach ( $cats as $cat ) {
					$names[] = $cat->name;
				}
				echo esc_html( implode( ', ', $names ) );
				echo '</span>';
			}
			break;

		case 'text':
			// Text fields: if this is the name field, read from post_title.
			if ( $field_id && ( false !== stripos( $field_id, 'name' ) ) ) {
				$title = get_the_title( $post_id );
				if ( $title ) {
					echo '<h4 class="fsweb-review-name"' . wp_kses_post( fsweb_build_comp_style( $comp ) ) . '>' . esc_html( $title ) . '</h4>';
				}
				break;
			}
			// Otherwise fall through to generic meta handler.
		case 'email':
		case 'url':
		case 'select':
			if ( $field_id ) {
				$value = get_post_meta( $post_id, $field_id, true );
				if ( $value ) {
					if ( 'url' === $type ) {
						echo '<a href="' . esc_url( $value ) . '" class="fsweb-review-url" target="_blank" rel="nofollow"' . wp_kses_post( fsweb_build_comp_style( $comp ) ) . '>' . esc_html( $value ) . '</a>';
					} elseif ( 'email' === $type ) {
						echo '<a href="mailto:' . esc_attr( $value ) . '" class="fsweb-review-email"' . wp_kses_post( fsweb_build_comp_style( $comp ) ) . '>' . esc_html( $value ) . '</a>';
					} else {
						echo '<span class="fsweb-review-meta"' . wp_kses_post( fsweb_build_comp_style( $comp ) ) . '>' . esc_html( $value ) . '</span>';
					}
				}
			}
			break;
	}
}
