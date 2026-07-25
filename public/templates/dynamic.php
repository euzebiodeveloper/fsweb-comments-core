<?php
/**
 * FSWWP Reviews — Dynamic View Template
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

$wrap_class = 'fswwp-reviews';
if ( 'slider' === $layout ) {
	$wrap_class .= ' fswwp-reviews--slider';
} elseif ( 'list' === $layout ) {
	$wrap_class .= ' fswwp-reviews--list';
} else {
	$wrap_class .= ' fswwp-reviews--grid';
}
?>

<?php if ( 'slider' === $layout ) : ?>
<div class="<?php echo esc_attr( $wrap_class ); ?>" data-fswwp-slider>
	<div class="fswwp-reviews-track">
		<?php while ( $reviews->have_posts() ) : $reviews->the_post(); ?>
			<div class="fswwp-review-slide">
				<div class="fswwp-review-card">
					<?php render_rows( $rows, get_the_ID() ); ?>
				</div>
			</div>
		<?php endwhile; ?>
	</div>
	<button type="button" class="fswwp-slider-prev">&#10094;</button>
	<button type="button" class="fswwp-slider-next">&#10095;</button>
	<div class="fswwp-slider-dots"></div>
</div>

<?php else : ?>
<div class="<?php echo esc_attr( $wrap_class ); ?>">
	<?php while ( $reviews->have_posts() ) : $reviews->the_post(); ?>
		<div class="fswwp-review-card">
			<?php render_rows( $rows, get_the_ID() ); ?>
		</div>
	<?php endwhile; ?>
</div>
<?php endif; ?>

<?php
/**
 * Render rows for a single review card.
 */
function render_rows( $rows, $post_id ) {
	foreach ( $rows as $row ) {
		echo '<div class="fswwp-review-row" style="display:flex;gap:16px;">';
		if ( isset( $row['columns'] ) ) {
			$col_count = count( $row['columns'] );
			foreach ( $row['columns'] as $col ) {
				$w = isset( $col['width'] ) ? (int) $col['width'] : floor( 100 / $col_count );
				echo '<div class="fswwp-review-col" style="flex:' . (int) $w . ';">';
				if ( isset( $col['components'] ) ) {
					foreach ( $col['components'] as $comp ) {
						render_component( $comp, $post_id );
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
function build_comp_style( $comp ) {
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
function render_component( $comp, $post_id ) {
	$type     = $comp['type'];
	$field_id = isset( $comp['field_id'] ) ? $comp['field_id'] : '';

	switch ( $type ) {
		case 'image':
			if ( has_post_thumbnail( $post_id ) ) {
				$is = build_comp_style( $comp );
				echo '<div class="fswwp-review-image"' . $is . '>';
				echo get_the_post_thumbnail( $post_id, 'medium', array( 'class' => 'fswwp-review-avatar', 'style' => ( $is ? 'width:100%;height:auto;' : '' ) ) );
				echo '</div>';
			}
			break;

		case 'name':
			$title = get_the_title( $post_id );
			if ( $title ) {
				echo '<h4 class="fswwp-review-name"' . build_comp_style( $comp ) . '>' . esc_html( $title ) . '</h4>';
			}
			break;

		case 'content':
		case 'textarea':
			$post = get_post( $post_id );
			// For textarea, try post content first, then meta.
			$text = $post ? apply_filters( 'the_content', $post->post_content ) : '';
			if ( ! $text && $field_id ) {
				$text = get_post_meta( $post_id, $field_id, true );
			}
			if ( $text ) {
				echo '<div class="fswwp-review-content"' . build_comp_style( $comp ) . '>' . wp_kses_post( $text ) . '</div>';
			}
			break;

		case 'rating':
			$rating = $field_id ? get_post_meta( $post_id, $field_id, true ) : get_post_meta( $post_id, 'rating', true );
			if ( $rating ) {
				$stars = (int) $rating;
				echo '<div class="fswwp-review-stars-display"' . build_comp_style( $comp ) . '>';
				for ( $i = 1; $i <= 5; $i++ ) {
					echo '<span class="fswwp-star ' . ( $i <= $stars ? 'fswwp-star--filled' : '' ) . '">&#9733;</span>';
				}
				echo '</div>';
			}
			break;

		case 'date':
			echo '<span class="fswwp-review-date"' . build_comp_style( $comp ) . '>' . esc_html( get_the_date( '', $post_id ) ) . '</span>';
			break;

		case 'category':
			$cats = get_the_terms( $post_id, 'fswwp_review_category' );
			if ( $cats && ! is_wp_error( $cats ) ) {
				echo '<span class="fswwp-review-category"' . build_comp_style( $comp ) . '>';
				$names = array();
				foreach ( $cats as $cat ) {
					$names[] = esc_html( $cat->name );
				}
				echo implode( ', ', $names );
				echo '</span>';
			}
			break;

		case 'text':
			// Text fields: if this is the name field, read from post_title.
			if ( $field_id && ( false !== stripos( $field_id, 'name' ) ) ) {
				$title = get_the_title( $post_id );
				if ( $title ) {
					echo '<h4 class="fswwp-review-name"' . build_comp_style( $comp ) . '>' . esc_html( $title ) . '</h4>';
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
						echo '<a href="' . esc_url( $value ) . '" class="fswwp-review-url" target="_blank" rel="nofollow"' . build_comp_style( $comp ) . '>' . esc_html( $value ) . '</a>';
					} elseif ( 'email' === $type ) {
						echo '<a href="mailto:' . esc_attr( $value ) . '" class="fswwp-review-email"' . build_comp_style( $comp ) . '>' . esc_html( $value ) . '</a>';
					} else {
						echo '<span class="fswwp-review-meta"' . build_comp_style( $comp ) . '>' . esc_html( $value ) . '</span>';
					}
				}
			}
			break;
	}
}
