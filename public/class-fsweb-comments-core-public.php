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
			$this->version
		);

		wp_register_style(
			'fsweb-review-form',
			FSWEB_COMMENTS_CORE_URL . 'public/css/fsweb-review-form.css',
			array( 'fsweb-reviews-base' ),
			$this->version
		);
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
			$this->version,
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
		return apply_filters( 'fsweb_cc_reviews_html', $output, $atts );
	}
}
