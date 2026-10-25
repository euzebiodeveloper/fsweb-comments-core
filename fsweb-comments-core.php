<?php

/**
 * The plugin bootstrap file.
 *
 * This file is read by WordPress to generate the plugin information in the
 * plugin admin area. It also loads the dependencies, registers activation and
 * deactivation hooks, and starts the plugin.
 *
 * @link              https://flowsyncweb.com
 * @since             1.0.0
 * @package           Fsweb_Comments_Core
 *
 * @wordpress-plugin
 * Plugin Name:       FSWEB Comments Core
 * Plugin URI:        https://github.com/euzebiodeveloper/fsweb-comments-core
 * Description:       Lightweight review and feedback management for WordPress: collect reviews with star ratings, moderate them and display them anywhere with a shortcode.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Flow Sync Web
 * Author URI:        https://flowsyncweb.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fsweb-comments-core
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'FSWEB_COMMENTS_CORE_VERSION', '1.0.0' );

/**
 * Plugin slug. Used for the text domain and admin screens.
 */
define( 'FSWEB_COMMENTS_CORE_SLUG', 'fsweb-comments-core' );

/**
 * Plugin directory path and URL (with trailing slash).
 */
define( 'FSWEB_COMMENTS_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'FSWEB_COMMENTS_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Landing page of the PRO add-on.
 *
 * The free edition never contains premium code: the Form Builder, Views
 * Builder, skins, slider and automatic updates live in a separate add-on.
 * Override in wp-config.php to point at another page.
 */
if ( ! defined( 'FSWEB_COMMENTS_CORE_PRO_URL' ) ) {
	define( 'FSWEB_COMMENTS_CORE_PRO_URL', 'https://flowsyncweb.com/produto/comments-core-pro/' );
}

/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 */
function activate_fsweb_comments_core() {
	require_once FSWEB_COMMENTS_CORE_DIR . 'includes/class-fsweb-comments-core-activator.php';
	Fsweb_Comments_Core_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function deactivate_fsweb_comments_core() {
	require_once FSWEB_COMMENTS_CORE_DIR . 'includes/class-fsweb-comments-core-deactivator.php';
	Fsweb_Comments_Core_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_fsweb_comments_core' );
register_deactivation_hook( __FILE__, 'deactivate_fsweb_comments_core' );

/**
 * The core plugin class.
 */
require FSWEB_COMMENTS_CORE_DIR . 'includes/class-fsweb-comments-core.php';

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function run_fsweb_comments_core() {
	$plugin = new Fsweb_Comments_Core();
	$plugin->run();
}
run_fsweb_comments_core();
