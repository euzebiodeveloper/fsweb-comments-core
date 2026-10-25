<?php

/**
 * The core plugin class.
 *
 * @link       https://flowsyncweb.com
 * @since      1.0.0
 * @package    Fsweb_Comments_Core
 * @subpackage Fsweb_Comments_Core/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Wires the plugin dependencies and hooks.
 *
 * @since 1.0.0
 */
class Fsweb_Comments_Core {

	/**
	 * Hook loader.
	 *
	 * @var Fsweb_Comments_Core_Loader
	 */
	protected $loader;

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Set the plugin identity and boot the hooks.
	 */
	public function __construct() {
		$this->version     = defined( 'FSWEB_COMMENTS_CORE_VERSION' ) ? FSWEB_COMMENTS_CORE_VERSION : '1.0.0';
		$this->plugin_name = 'fsweb-comments-core';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_init_hooks();
	}

	/**
	 * Load the required files.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once FSWEB_COMMENTS_CORE_DIR . 'includes/class-fsweb-comments-core-loader.php';
		require_once FSWEB_COMMENTS_CORE_DIR . 'includes/class-fsweb-comments-core-i18n.php';
		require_once FSWEB_COMMENTS_CORE_DIR . 'includes/class-fsweb-comments-core-post-types.php';
		require_once FSWEB_COMMENTS_CORE_DIR . 'admin/class-fsweb-comments-core-admin.php';
		require_once FSWEB_COMMENTS_CORE_DIR . 'admin/class-fsweb-comments-core-review-editor.php';
		require_once FSWEB_COMMENTS_CORE_DIR . 'public/class-fsweb-comments-core-public.php';
		require_once FSWEB_COMMENTS_CORE_DIR . 'public/class-fsweb-comments-core-shortcode.php';
		require_once FSWEB_COMMENTS_CORE_DIR . 'public/class-fsweb-comments-core-form-shortcode.php';

		$this->loader = new Fsweb_Comments_Core_Loader();
	}

	/**
	 * Register the text domain.
	 *
	 * @return void
	 */
	private function set_locale() {
		$plugin_i18n = new Fsweb_Comments_Core_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Fsweb_Comments_Core_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );

		// Review editor (meta box + columns) self-registers in the constructor.
		new Fsweb_Comments_Core_Review_Editor();
	}

	/**
	 * Register public hooks.
	 *
	 * @return void
	 */
	private function define_public_hooks() {
		$plugin_public = new Fsweb_Comments_Core_Public( $this->get_plugin_name(), $this->get_version() );

		// Assets are enqueued by the shortcodes, only when they are rendered.
		$this->loader->add_filter( 'fsweb_cc_reviews_shortcode_output', $plugin_public, 'filter_reviews_output', 10, 2 );
	}

	/**
	 * Register hooks that need the complete WordPress environment.
	 *
	 * @return void
	 */
	private function define_init_hooks() {
		$this->loader->add_action( 'init', 'Fsweb_Comments_Core_Post_Types', 'register_cpt' );
		$this->loader->add_action( 'init', 'Fsweb_Comments_Core_Post_Types', 'register_taxonomy' );
		$this->loader->add_action( 'init', $this, 'init_shortcodes' );
	}

	/**
	 * Instantiate the shortcode handlers.
	 *
	 * @return void
	 */
	public function init_shortcodes() {
		new Fsweb_Comments_Core_Shortcode();
		new Fsweb_Comments_Core_Form_Shortcode();
	}

	/**
	 * Run the loader.
	 *
	 * @return void
	 */
	public function run() {
		$this->loader->run();

		/**
		 * Fires after Comments Core has registered its hooks.
		 *
		 * The PRO add-on uses this to attach its own filters.
		 *
		 * @since 1.0.0
		 * @param Fsweb_Comments_Core $plugin The plugin instance.
		 */
		do_action( 'fsweb_comments_core_loaded', $this );
	}

	/**
	 * Plugin slug.
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Hook loader.
	 *
	 * @return Fsweb_Comments_Core_Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
}
