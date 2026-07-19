<?php
/**
 * FSWWP Comments Core — Shortcode Handler
 *
 * Registers and renders the [fswwp_reviews] shortcode.
 *
 * @package    Fswwp_Comments_Core
 * @subpackage Fswwp_Comments_Core/public
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Shortcode handler for [fswwp_reviews].
 *
 * Attributes:
 *   layout    — 'grid' (default), 'slider', 'list'
 *   count     — number of reviews (default 6, max 50)
 *   category  — category slug (empty = all)
 *   order     — 'newest' (default), 'oldest', 'random', 'menu_order'
 *   show_image — 'yes' (default) or 'no'
 */
class Fswwp_Comments_Core_Shortcode {

	/**
	 * Whether assets have been enqueued.
	 *
	 * @var bool
	 */
	private static $assets_loaded = false;

	/**
	 * Constructor — registers the shortcode.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_shortcode( 'fswwp_reviews', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode output.
	 *
	 * If 'id' is provided, loads a saved View from fswwp_review_views.
	 * Otherwise, uses the inline attributes.
	 *
	 * @param array $atts User-defined attributes.
	 * @return string HTML output.
	 */
	public function render( $atts ) {
		$atts = shortcode_atts( array(
			'id'         => '',
			'layout'     => 'grid',
			'count'      => 6,
			'category'   => '',
			'order'      => 'newest',
			'show_image' => 'yes',
		), $atts, 'fswwp_reviews' );

		// If a saved view ID is provided, merge its config.
		if ( ! empty( $atts['id'] ) ) {
			$views = get_option( 'fswwp_review_views', array() );
			if ( isset( $views[ $atts['id'] ] ) ) {
				$view = $views[ $atts['id'] ];
				$atts['layout']     = $view['layout'];
				$atts['count']      = $view['count'];
				$atts['order']      = $view['order'];
				$atts['category']   = isset( $view['category'] ) ? $view['category'] : '';
				$atts['show_image'] = isset( $view['show_image'] ) && $view['show_image'] ? 'yes' : 'no';
				$atts['_rows']      = isset( $view['rows'] ) ? $view['rows'] : null;
			}
		}

		$count = min( max( (int) $atts['count'], 1 ), 50 );

		$query_args = array(
			'post_type'      => 'fswwp_review',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'no_found_rows'  => true,
		);

		// Category filter.
		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'fswwp_review_category',
					'field'    => 'slug',
					'terms'    => explode( ',', $atts['category'] ),
				),
			);
		}

		// Order.
		switch ( $atts['order'] ) {
			case 'oldest':
				$query_args['order']   = 'ASC';
				$query_args['orderby'] = 'date';
				break;
			case 'random':
				$query_args['orderby'] = 'rand';
				break;
			case 'menu_order':
				$query_args['orderby'] = 'menu_order';
				$query_args['order']   = 'ASC';
				break;
			default: // newest.
				$query_args['order']   = 'DESC';
				$query_args['orderby'] = 'date';
				break;
		}

		$reviews = new WP_Query( $query_args );

		if ( ! $reviews->have_posts() ) {
			return '';
		}

		// Enqueue assets on first shortcode render.
		if ( ! self::$assets_loaded ) {
			$this->enqueue_assets( $atts['layout'] );
			self::$assets_loaded = true;
		}

		// Load template.
		ob_start();
		$this->load_template( $atts, $reviews );
		$output = ob_get_clean();

		wp_reset_postdata();

		return $output;
	}

	// ------------------------------------------------------------------//
	//  Template loading
	// ------------------------------------------------------------------//

	/**
	 * Load the appropriate template file.
	 *
	 * Looks in: child theme → parent theme → plugin.
	 *
	 * @param array    $atts    Parsed shortcode attributes.
	 * @param WP_Query $reviews The reviews query.
	 * @return void
	 */
	private function load_template( $atts, $reviews ) {
		$layout     = sanitize_file_name( $atts['layout'] );
		$show_image = 'yes' === $atts['show_image'];
		$rows       = isset( $atts['_rows'] ) ? $atts['_rows'] : null;

		// If view has custom rows, use dynamic template.
		if ( $rows && is_array( $rows ) ) {
			$template = FSWWP_COMMENTS_CORE_DIR . 'public/templates/dynamic.php';
			if ( file_exists( $template ) ) {
				include $template;
				return;
			}
		}

		$template = $this->locate_template( $layout );

		if ( $template ) {
			include $template;
		}
	}

	/**
	 * Locate a template file, respecting theme overrides.
	 *
	 * @param string $layout Layout name (grid, slider, list).
	 * @return string|false Full path or false if not found.
	 */
	private function locate_template( $layout ) {
		$template_name = "fswwp-reviews/{$layout}.php";
		$plugin_path   = FSWWP_COMMENTS_CORE_DIR . 'public/templates/' . $layout . '.php';

		// Check child theme, then parent theme, then plugin.
		$located = locate_template( $template_name );

		if ( $located ) {
			return $located;
		}

		if ( file_exists( $plugin_path ) ) {
			return $plugin_path;
		}

		// Fallback to grid if requested layout doesn't exist.
		if ( 'grid' !== $layout ) {
			return $this->locate_template( 'grid' );
		}

		return false;
	}

	// ------------------------------------------------------------------//
	//  Assets
	// ------------------------------------------------------------------//

	/**
	 * Enqueue CSS and JS for the given layout.
	 *
	 * @param string $layout Layout name.
	 * @return void
	 */
	private function enqueue_assets( $layout ) {
		$css_url = FSWWP_COMMENTS_CORE_URL . 'public/css/';
		$js_url  = FSWWP_COMMENTS_CORE_URL . 'public/js/';
		$version = FSWWP_COMMENTS_CORE_VERSION;

		// Base styles (always loaded).
		wp_enqueue_style(
			'fswwp-reviews-base',
			$css_url . 'fswwp-reviews.css',
			array(),
			$version
		);

		// Layout-specific CSS.
		$layout_css = FSWWP_COMMENTS_CORE_DIR . 'public/css/fswwp-reviews-' . $layout . '.css';
		if ( file_exists( $layout_css ) ) {
			wp_enqueue_style(
				'fswwp-reviews-' . $layout,
				$css_url . 'fswwp-reviews-' . $layout . '.css',
				array( 'fswwp-reviews-base' ),
				$version
			);
		}

		// Slider JS (Swiper-like minimal, only for slider layout).
		if ( 'slider' === $layout ) {
			wp_enqueue_script(
				'fswwp-reviews-slider',
				$js_url . 'fswwp-reviews-slider.js',
				array(),
				$version,
				true
			);
		}
	}
}
