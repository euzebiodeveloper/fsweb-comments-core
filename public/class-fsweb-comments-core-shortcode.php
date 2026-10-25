<?php
/**
 * FSWEB Comments Core — [fsweb_reviews]
 *
 * Renders the published reviews in a grid or list.
 *
 * @package    Fsweb_Comments_Core
 * @subpackage Fsweb_Comments_Core/public
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Shortcode handler for [fsweb_reviews].
 *
 * Attributes:
 *   layout     — 'grid' (default) or 'list'
 *   count      — number of reviews (default 6, max 50)
 *   category   — category slug (comma separated, empty = all)
 *   order      — 'newest' (default), 'oldest', 'random', 'menu_order'
 *   show_image — 'yes' (default) or 'no'
 *   columns    — grid columns, 1 to 4 (default 3)
 */
class Fsweb_Comments_Core_Shortcode {

	/**
	 * Register the shortcode.
	 */
	public function __construct() {
		add_shortcode( 'fsweb_reviews', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array $atts User attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'layout'     => 'grid',
				'count'      => 6,
				'category'   => '',
				'order'      => 'newest',
				'show_image' => 'yes',
				'columns'    => 3,
				// Passed through to the add-on: a saved view brings its own
				// layout, skin and composition, and dropping these here meant
				// [fsweb_reviews id="default"] silently rendered the default
				// grid instead of the configured view.
				'id'         => '',
				'skin'       => '',
			),
			$atts,
			'fsweb_reviews'
		);

		$layout = 'list' === $atts['layout'] ? 'list' : 'grid';

		/**
		 * Short-circuit the review rendering.
		 *
		 * The PRO add-on uses this to render saved views, the slider layout
		 * and skins without duplicating the query.
		 *
		 * @since 1.0.0
		 * @param string $output Markup. Empty to continue with the default renderer.
		 * @param array  $atts   Shortcode attributes.
		 */
		$custom = apply_filters( 'fsweb_cc_reviews_pre_render', '', $atts );

		if ( '' !== $custom ) {
			return $custom;
		}

		$query = new WP_Query( $this->build_query( $atts ) );

		if ( ! $query->have_posts() ) {
			return '';
		}

		Fsweb_Comments_Core_Public::enqueue_styles();

		ob_start();

		/**
		 * Fires before the reviews loop.
		 *
		 * @since 1.0.0
		 * @param array    $atts  Shortcode attributes.
		 * @param WP_Query $query The reviews query.
		 */
		do_action( 'fsweb_cc_reviews_before_loop', $atts, $query );

		$template = $this->locate_template( $layout );

		// Templates read $reviews; keep the historical variable name working.
		$reviews = $query;

		if ( $template ) {
			include $template;
		}

		/**
		 * Fires after the reviews loop.
		 *
		 * @since 1.0.0
		 * @param array    $atts  Shortcode attributes.
		 * @param WP_Query $query The reviews query.
		 */
		do_action( 'fsweb_cc_reviews_after_loop', $atts, $query );

		$output = ob_get_clean();

		wp_reset_postdata();

		/**
		 * Filter the shortcode output before it is returned.
		 *
		 * @since 1.0.0
		 * @param string $output Rendered HTML.
		 * @param array  $atts   Shortcode attributes.
		 */
		$output = apply_filters( 'fsweb_cc_reviews_shortcode_output', $output, $atts );

		return $output;
	}

	/**
	 * Build the reviews query.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return array
	 */
	protected function build_query( $atts ) {
		$count = min( max( (int) $atts['count'], 1 ), 50 );

		$args = array(
			'post_type'      => 'fsweb_review',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'no_found_rows'  => true,
		);

		$category = trim( (string) $atts['category'] );
		if ( '' !== $category ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'fsweb_review_category',
					'field'    => 'slug',
					'terms'    => array_map( 'sanitize_title', explode( ',', $category ) ),
				),
			);
		}

		switch ( $atts['order'] ) {
			case 'oldest':
				$args['order']   = 'ASC';
				$args['orderby'] = 'date';
				break;
			case 'random':
				$args['orderby'] = 'rand';
				break;
			case 'menu_order':
				$args['orderby'] = 'menu_order';
				$args['order']   = 'ASC';
				break;
			default:
				$args['order']   = 'DESC';
				$args['orderby'] = 'date';
				break;
		}

		/**
		 * Filter the reviews query arguments.
		 *
		 * @since 1.0.0
		 * @param array $args Query arguments.
		 * @param array $atts Shortcode attributes.
		 */
		return (array) apply_filters( 'fsweb_cc_reviews_query_args', $args, $atts );
	}

	/**
	 * Locate a template, allowing theme overrides.
	 *
	 * @param string $layout Layout name.
	 * @return string|false
	 */
	protected function locate_template( $layout ) {
		$layout = sanitize_file_name( $layout );

		$located = locate_template( array( 'fsweb-reviews/' . $layout . '.php' ) );
		if ( $located ) {
			return $located;
		}

		$plugin_path = FSWEB_COMMENTS_CORE_DIR . 'public/templates/' . $layout . '.php';

		if ( file_exists( $plugin_path ) ) {
			return $plugin_path;
		}

		if ( 'grid' !== $layout ) {
			return $this->locate_template( 'grid' );
		}

		return false;
	}
}
