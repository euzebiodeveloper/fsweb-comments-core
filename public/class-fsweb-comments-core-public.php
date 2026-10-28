<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://flowsyncweb.com
 * @since      1.0.0
 * @package    Fsweb_Comments_Core
 * @subpackage Fsweb_Comments_Core/public
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Public-facing functionality.
 *
 * Assets are registered here and only enqueued by the shortcodes that need
 * them, so pages without reviews stay untouched.
 *
 * @since 1.0.0
 */
class Fsweb_Comments_Core_Public {

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Whether the front-end assets were already enqueued.
	 *
	 * @var bool
	 */
	private static $enqueued = false;

	/**
	 * Set the plugin identity.
	 *
	 * @param string $plugin_name Plugin slug.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the public stylesheet.
	 *
	 * @return void
	 */
	public function register_styles() {
		wp_register_style(
			'fsweb-reviews-base',
			FSWEB_COMMENTS_CORE_URL . 'public/css/fsweb-reviews.css',
			array(),
			$this->asset_version( 'public/css/fsweb-reviews.css' )
		);

		wp_register_style(
			'fsweb-reviews-slider',
			FSWEB_COMMENTS_CORE_URL . 'public/css/fsweb-reviews-slider.css',
			array( 'fsweb-reviews-base' ),
			$this->asset_version( 'public/css/fsweb-reviews-slider.css' )
		);

		wp_register_style(
			'fsweb-reviews-theme-dark',
			FSWEB_COMMENTS_CORE_URL . 'public/css/fsweb-reviews-theme-dark.css',
			array( 'fsweb-reviews-base' ),
			$this->asset_version( 'public/css/fsweb-reviews-theme-dark.css' )
		);

		wp_register_style(
			'fsweb-review-form',
			FSWEB_COMMENTS_CORE_URL . 'public/css/fsweb-review-form.css',
			array( 'fsweb-reviews-base' ),
			$this->asset_version( 'public/css/fsweb-review-form.css' )
		);
	}

	/**
	 * Cache-busting version for a file, falling back to the plugin version.
	 *
	 * @param string $relative Path relative to the plugin root.
	 * @return string
	 */
	private function asset_version( $relative ) {
		$path = FSWEB_COMMENTS_CORE_DIR . $relative;

		if ( file_exists( $path ) ) {
			return (string) filemtime( $path );
		}

		return $this->version;
	}

	/**
	 * Register the public scripts.
	 *
	 * @return void
	 */
	public function register_scripts() {
		wp_register_script(
			'fsweb-review-form',
			FSWEB_COMMENTS_CORE_URL . 'public/js/fsweb-review-form.js',
			array(),
			$this->asset_version( 'public/js/fsweb-review-form.js' ),
			true
		);

		wp_register_script(
			'fsweb-reviews-slider',
			FSWEB_COMMENTS_CORE_URL . 'public/js/fsweb-reviews-slider.js',
			array(),
			$this->asset_version( 'public/js/fsweb-reviews-slider.js' ),
			true
		);

		wp_localize_script(
			'fsweb-review-form',
			'fswebReviews',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'fsweb_review_submit' ),
				'i18n'    => array(
					'submitting' => __( 'Sending…', 'fsweb-comments-core' ),
					'success'    => __( 'Thank you! Your review was submitted and is awaiting moderation.', 'fsweb-comments-core' ),
					'error'      => __( 'We could not send your review. Please try again.', 'fsweb-comments-core' ),
					'required'   => __( 'Please fill in the required fields.', 'fsweb-comments-core' ),
				),
			)
		);
	}

	/**
	 * Enqueue the base styles once.
	 *
	 * @return void
	 */
	public static function enqueue_styles() {
		if ( self::$enqueued ) {
			return;
		}

		wp_enqueue_style( 'fsweb-reviews-base' );
		self::$enqueued = true;
	}

	/**
	 * Enqueue the assets a layout needs.
	 *
	 * The slider is the default layout, so its sheet and script load on every
	 * page that renders reviews; the dark theme only loads when it is picked.
	 *
	 * @param string $layout Layout name.
	 * @param string $theme  Theme name: `default` or `dark`.
	 * @return void
	 */
	public static function enqueue_layout_assets( $layout, $theme = 'default' ) {
		self::enqueue_styles();

		if ( 'slider' === $layout ) {
			wp_enqueue_style( 'fsweb-reviews-slider' );
			wp_enqueue_script( 'fsweb-reviews-slider' );
		}

		if ( 'dark' === $theme ) {
			wp_enqueue_style( 'fsweb-reviews-theme-dark' );
		}
	}

	/**
	 * Chevron used by the slider arrows.
	 *
	 * An inline SVG has its own box, so the theme's line-height and font
	 * metrics cannot push the glyph off center the way they did with a text
	 * arrow.
	 *
	 * @param string $direction Either `prev` or `next`.
	 * @return string SVG markup.
	 */
	public static function arrow_icon( $direction ) {
		$path = 'prev' === $direction ? 'M15 5 8 12l7 7' : 'M9 5l7 7-7 7';

		return '<svg class="fsweb-slider-arrow-icon" viewBox="0 0 24 24" width="16" height="16"'
			. ' fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"'
			. ' stroke-linejoin="round" aria-hidden="true" focusable="false">'
			. '<path d="' . esc_attr( $path ) . '"/></svg>';
	}

	/**
	 * Enqueue the form styles and script.
	 *
	 * @return void
	 */
	public static function enqueue_form_assets() {
		self::enqueue_styles();
		wp_enqueue_style( 'fsweb-review-form' );
		wp_enqueue_script( 'fsweb-review-form' );
	}

	/**
	 * Allowed HTML for markup returned by the extension filters.
	 *
	 * The post allowlist covers regular content; the SVG entries keep the
	 * avatar placeholder and slider arrow icons that the layouts print.
	 *
	 * @return array
	 */
	public static function allowed_reviews_markup() {
		$allowed = wp_kses_allowed_html( 'post' );

		$icon_attributes = array(
			'class'               => true,
			'fill'                => true,
			'width'               => true,
			'height'              => true,
			'viewbox'             => true,
			'focusable'           => true,
			'role'                => true,
			'xmlns'               => true,
			'preserveaspectratio' => true,
			'stroke'              => true,
			'stroke-width'        => true,
			'stroke-linecap'      => true,
			'stroke-linejoin'     => true,
			'stroke-miterlimit'   => true,
			'overflow'            => true,
		);

		$shape_attributes = array(
			'd'                => true,
			'fill'             => true,
			'fill-opacity'     => true,
			'fill-rule'        => true,
			'clip-rule'        => true,
			'clip-path'        => true,
			'stroke'           => true,
			'stroke-width'     => true,
			'stroke-linecap'   => true,
			'stroke-linejoin'  => true,
			'stroke-miterlimit' => true,
			'stroke-opacity'   => true,
			'opacity'          => true,
			'transform'        => true,
			'transform-origin' => true,
			'cx'               => true,
			'cy'               => true,
			'r'                => true,
			'x'                => true,
			'y'                => true,
			'width'            => true,
			'height'           => true,
			'points'           => true,
			'href'             => true,
			'xlink:href'       => true,
		);

		$allowed['svg']   = $icon_attributes;
		$allowed['g']     = $icon_attributes;
		$allowed['defs']  = $icon_attributes;
		$allowed['title'] = array();

		foreach ( array( 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'use' ) as $tag ) {
			$allowed[ $tag ] = $shape_attributes;
		}

		return $allowed;
	}

	/**
	 * Sanitize markup returned by extension filters.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	public static function kses_reviews_markup( $html ) {
		return wp_kses( (string) $html, self::allowed_reviews_markup() );
	}

	/**
	 * Allowed HTML for custom form field markup.
	 *
	 * The field renderer is filterable, so only form controls and the
	 * wrappers used by the built-in renderer are permitted.
	 *
	 * @return array
	 */
	public static function allowed_form_field_markup() {
		$attributes = array(
			'class'            => true,
			'id'               => true,
			'name'             => true,
			'type'             => true,
			'value'            => true,
			'placeholder'      => true,
			'required'         => true,
			'checked'          => true,
			'selected'         => true,
			'disabled'         => true,
			'readonly'         => true,
			'multiple'         => true,
			'accept'           => true,
			'min'              => true,
			'max'              => true,
			'step'             => true,
			'rows'             => true,
			'cols'             => true,
			'maxlength'        => true,
			'pattern'          => true,
			'autocomplete'     => true,
			'for'              => true,
			'title'            => true,
			'data-*'           => true,
			'aria-label'       => true,
			'aria-labelledby'  => true,
			'aria-describedby' => true,
			'aria-hidden'      => true,
			'role'             => true,
		);

		$allowed = array();

		foreach ( array( 'div', 'span', 'p', 'label', 'input', 'select', 'textarea', 'option', 'optgroup', 'button', 'small', 'strong', 'em' ) as $tag ) {
			$allowed[ $tag ] = $attributes;
		}

		return $allowed;
	}

	/**
	 * Filter the rendered reviews markup.
	 *
	 * Gives the PRO add-on a single place to inject its layouts, skins and
	 * dynamic views without touching this class.
	 *
	 * @param string $output Rendered HTML.
	 * @param array  $atts   Shortcode attributes.
	 * @return string
	 */
	public function filter_reviews_output( $output, $atts ) {
		/**
		 * Filter the final markup of [fsweb_reviews].
		 *
		 * @since 1.0.0
		 * @param string $output Rendered HTML.
		 * @param array  $atts   Shortcode attributes.
		 */
		return self::kses_reviews_markup( apply_filters( 'fsweb_cc_reviews_html', $output, $atts ) );
	}
}
