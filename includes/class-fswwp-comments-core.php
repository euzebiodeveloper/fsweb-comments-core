<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://flowsyncweb.com
 * @since      1.0.0
 *
 * @package    Fswwp_Comments_Core
 * @subpackage Fswwp_Comments_Core/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Fswwp_Comments_Core
 * @subpackage Fswwp_Comments_Core/includes
 * @author     Flow Sync Web <euzebio.batista@flowsyncweb.com>
 */
class Fswwp_Comments_Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Fswwp_Comments_Core_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'FSWWP_COMMENTS_CORE_VERSION' ) ) {
			$this->version = FSWWP_COMMENTS_CORE_VERSION;
		} else {
			$this->version = '1.0.8';
		}
		$this->plugin_name = 'fswwp-comments-core';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		// Register CPT, taxonomy, and shortcode on init.
		$this->loader->add_action( 'init', 'Fswwp_Comments_Core_Post_Types', 'register_cpt' );
		$this->loader->add_action( 'init', 'Fswwp_Comments_Core_Post_Types', 'register_taxonomy' );
		$this->loader->add_action( 'init', $this, 'init_shortcode' );

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Fswwp_Comments_Core_Loader. Orchestrates the hooks of the plugin.
	 * - Fswwp_Comments_Core_i18n. Defines internationalization functionality.
	 * - Fswwp_Comments_Core_Admin. Defines all hooks for the admin area.
	 * - Fswwp_Comments_Core_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fswwp-comments-core-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fswwp-comments-core-i18n.php';

		/**
		 * The class responsible for registering the Review custom post type
		 * and taxonomy.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-fswwp-comments-core-post-types.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-fswwp-comments-core-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-fswwp-comments-core-public.php';

		/**
		 * The class responsible for the [fswwp_reviews] shortcode.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-fswwp-comments-core-shortcode.php';

		/**
		 * The class responsible for the [fswwp_review_form] shortcode
		 * and AJAX review submission.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-fswwp-comments-core-form-shortcode.php';

		/**
		 * The class responsible for customising the Review edit screen
		 * (meta box + custom columns).
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-fswwp-comments-core-review-editor.php';

		$this->loader = new Fswwp_Comments_Core_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Fswwp_Comments_Core_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Fswwp_Comments_Core_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Fswwp_Comments_Core_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Submenu pages (Dashboard + License) under the Reviews CPT.
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );

		// AJAX — form builder save + delete.
		$this->loader->add_action( 'wp_ajax_fswwp_cc_save_form', $plugin_admin, 'ajax_save_form' );
		$this->loader->add_action( 'wp_ajax_fswwp_cc_delete_form', $plugin_admin, 'ajax_delete_form' );

		// AJAX — views builder save + delete.
		$this->loader->add_action( 'wp_ajax_fswwp_cc_save_view', $plugin_admin, 'ajax_save_view' );
		$this->loader->add_action( 'wp_ajax_fswwp_cc_delete_view', $plugin_admin, 'ajax_delete_view' );
		$this->loader->add_action( 'wp_ajax_fswwp_cc_get_form_components', $plugin_admin, 'ajax_get_form_components' );

		// AJAX — license activation / deactivation.
		$this->loader->add_action( 'wp_ajax_fswwp_activate_license', $plugin_admin, 'ajax_activate_license' );
		$this->loader->add_action( 'wp_ajax_fswwp_deactivate_license', $plugin_admin, 'ajax_deactivate_license' );
		$this->loader->add_action( 'wp_ajax_fswwp_validate_license', $plugin_admin, 'ajax_validate_license' );

		// Review Editor — meta box + custom columns (self-registers via constructor).
		new Fswwp_Comments_Core_Review_Editor();

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Fswwp_Comments_Core_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Fswwp_Comments_Core_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Instantiate the shortcode handler.
	 *
	 * Called on 'init' so the CPT is already registered.
	 *
	 * @since    1.0.0
	 */
	public function init_shortcode() {
		new Fswwp_Comments_Core_Shortcode();
		new Fswwp_Comments_Core_Form_Shortcode();
	}
}
