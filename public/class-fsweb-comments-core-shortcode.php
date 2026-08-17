<?php
/**
 * FSWEB Comments Core — Shortcode Handler
 *
 * Registers and renders the [fsweb_reviews] shortcode.
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
 *   layout    — 'grid' (default), 'slider', 'list'
 *   count     — number of reviews (default 6, max 50)
 *   category  — category slug (empty = all)
 *   order     — 'newest' (default), 'oldest', 'random', 'menu_order'
 *   show_image — 'yes' (default) or 'no'
 */
class Fsweb_Comments_Core_Shortcode {

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
		add_shortcode( 'fsweb_reviews', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode output.
	 *
	 * If 'id' is provided, loads a saved View from fsweb_review_views.
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
			'_skin'      => 'default',
			'_custom_css' => '',
		), $atts, 'fsweb_reviews' );

		// Block output if license is not active.
		if ( 'active' !== get_option( 'fsweb_license_status', 'inactive' ) ) {
			return '';
		}

		// If a saved view ID is provided, merge its config.
		if ( ! empty( $atts['id'] ) ) {
			$views = get_option( 'fsweb_review_views', array() );
			if ( isset( $views[ $atts['id'] ] ) ) {
				$view = $views[ $atts['id'] ];
				$atts['layout']     = $view['layout'];
				$atts['count']      = $view['count'];
				$atts['order']      = $view['order'];
				$atts['category']   = isset( $view['category'] ) ? $view['category'] : '';
				$atts['show_image'] = isset( $view['show_image'] ) && $view['show_image'] ? 'yes' : 'no';
				$atts['_rows']      = isset( $view['rows'] ) ? $view['rows'] : null;
				$atts['_skin']      = isset( $view['skin'] ) ? $view['skin'] : 'default';
				$atts['_custom_css'] = isset( $view['custom_css'] ) ? $view['custom_css'] : '';
			}
		}

		$count = min( max( (int) $atts['count'], 1 ), 50 );

		$query_args = array(
			'post_type'      => 'fsweb_review',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'no_found_rows'  => true,
		);

		// Category filter.
		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'fsweb_review_category',
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
			$this->enqueue_assets( $atts['layout'], $atts['_skin'], $atts['_custom_css'] );
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
			$template = FSWEB_COMMENTS_CORE_DIR . 'public/templates/dynamic.php';
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
		$template_name = "fsweb-reviews/{$layout}.php";
		$plugin_path   = FSWEB_COMMENTS_CORE_DIR . 'public/templates/' . $layout . '.php';

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
	 * @param string $layout     Layout name.
	 * @param string $skin       Skin name (default, dark, minimal, colorful, flat, custom).
	 * @param string $custom_css Custom CSS string (only used when skin=custom).
	 * @return void
	 */
	private function enqueue_assets( $layout, $skin = 'default', $custom_css = '' ) {
		$css_url = FSWEB_COMMENTS_CORE_URL . 'public/css/';
		$js_url  = FSWEB_COMMENTS_CORE_URL . 'public/js/';
		$version = FSWEB_COMMENTS_CORE_VERSION;

		// Base styles (always loaded).
		wp_enqueue_style(
			'fsweb-reviews-base',
			$css_url . 'fsweb-reviews.css',
			array(),
			$version
		);

		// Layout-specific CSS.
		$layout_css = FSWEB_COMMENTS_CORE_DIR . 'public/css/fsweb-reviews-' . $layout . '.css';
		if ( file_exists( $layout_css ) ) {
			wp_enqueue_style(
				'fsweb-reviews-' . $layout,
				$css_url . 'fsweb-reviews-' . $layout . '.css',
				array( 'fsweb-reviews-base' ),
				$version
			);
		}

		// Skin CSS — loaded after base + layout, so it overrides them.
		$valid_skins = array( 'dark', 'minimal', 'colorful', 'flat' );
		if ( in_array( $skin, $valid_skins, true ) ) {
			$skin_css = FSWEB_COMMENTS_CORE_DIR . 'public/css/fsweb-reviews-skin-' . $skin . '.css';
			if ( file_exists( $skin_css ) ) {
				$skin_handle = 'fsweb-reviews-skin';
				wp_enqueue_style(
					$skin_handle,
					$css_url . 'fsweb-reviews-skin-' . $skin . '.css',
					array( 'fsweb-reviews-base' ),
					$version
				);
			}
		}

		// Custom CSS — injected inline with highest priority.
		// Uses a dummy handle registered after everything else so it comes last.
		if ( 'custom' === $skin && ! empty( $custom_css ) ) {
			$custom_handle = 'fsweb-reviews-custom';
			wp_register_style( $custom_handle, false, array( 'fsweb-reviews-base' ), $version );
			wp_enqueue_style( $custom_handle );
			wp_add_inline_style( $custom_handle, $custom_css );
		}

		// Slider JS (Swiper-like minimal, only for slider layout).
		if ( 'slider' === $layout ) {
			wp_enqueue_script(
				'fsweb-reviews-slider',
				$js_url . 'fsweb-reviews-slider.js',
				array(),
				$version,
				true
			);
		}
	}
}
