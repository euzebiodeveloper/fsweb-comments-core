<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://flowsyncweb.com
 * @since             1.0.0
 * @package           Fsweb_Comments_Core
 *
 * @wordpress-plugin
 * Plugin Name:       Comments Core
 * Plugin URI:        https://flowsyncweb.com
 * Description:       A lightweight, high-performance comment and feedback management system designed for seamless integration and developer-friendly customization.
 * Version:           1.0.0
 * Requires at least: 6.0
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
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'FSWEB_COMMENTS_CORE_VERSION', '1.0.0' );

/**
 * Plugin directory path and URL (with trailing slash).
 */
define( 'FSWEB_COMMENTS_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'FSWEB_COMMENTS_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Base URL for the license API (fsweb-license-checker REST endpoints).
 *
 * Override in wp-config.php if needed:
 *   define( 'FSWEB_LICENSE_API_URL', 'https://yoursite.com/wp-json/fsweb/v1/license' );
 */
if ( ! defined( 'FSWEB_LICENSE_API_URL' ) ) {
	define( 'FSWEB_LICENSE_API_URL', 'https://flowsyncweb.com/wp-json/fsweb/v1/license' );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-fsweb-comments-core-activator.php
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Required by register_activation_hook.
function activate_fsweb_comments_core() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-fsweb-comments-core-activator.php';
	Fsweb_Comments_Core_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-fsweb-comments-core-deactivator.php
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Required by register_deactivation_hook.
function deactivate_fsweb_comments_core() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-fsweb-comments-core-deactivator.php';
	Fsweb_Comments_Core_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_fsweb_comments_core' );
register_deactivation_hook( __FILE__, 'deactivate_fsweb_comments_core' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-fsweb-comments-core.php';

/**
 * FSWEB Update Checker — automatic updates from your own server.
 *
 * @see https://github.com/flowsyncweb/fsweb-update-checker
 */
require plugin_dir_path( __FILE__ ) . 'fsweb-update-checker/index.php';
$fsweb_update_checker = new FSWEB_Update_Checker_1_0(
	'https://api.flowsyncweb.com/api/releases/fsweb-comments-core',
	__FILE__
);

// Set the license page URL so admin notices link to the correct page.
$fsweb_update_checker->set_license_url(
	admin_url( 'edit.php?post_type=fsweb_review&page=fsweb-comments-core-license' )
);

// Inject license key so the update-checker sends it with every request.
// Without a valid key the API returns 401 and updates are blocked.
$fsweb_license_key = get_option( 'fsweb_license_key', '' );
if ( ! empty( $fsweb_license_key ) ) {
	$fsweb_update_checker->set_license( $fsweb_license_key );
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Plugin bootstrap; contains plugin slug.
function run_fsweb_comments_core() {

	$plugin = new Fsweb_Comments_Core();
	$plugin->run();

}
run_fsweb_comments_core();
