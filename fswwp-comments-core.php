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
 * @package           Fswwp_Comments_Core
 *
 * @wordpress-plugin
 * Plugin Name:       Comments Core
 * Plugin URI:        https://flowsyncweb.com
 * Description:       A lightweight, high-performance comment and feedback management system designed for seamless integration and developer-friendly customization.
 * Version:           1.0.7
 * Requires at least: 6.0
 * Author:            Flow Sync Web
 * Author URI:        https://flowsyncweb.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fswwp-comments-core
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.1 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'FSWWP_COMMENTS_CORE_VERSION', '1.0.7' );

/**
 * Plugin directory path and URL (with trailing slash).
 */
define( 'FSWWP_COMMENTS_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'FSWWP_COMMENTS_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Base URL for the license API (fswwp-license-checker REST endpoints).
 *
 * Override in wp-config.php if needed:
 *   define( 'FSWWP_LICENSE_API_URL', 'https://yoursite.com/wp-json/fswwp/v1/license' );
 */
if ( ! defined( 'FSWWP_LICENSE_API_URL' ) ) {
	define( 'FSWWP_LICENSE_API_URL', 'https://flowsyncweb.com/wp-json/fswwp/v1/license' );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-fswwp-comments-core-activator.php
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Required by register_activation_hook.
function activate_fswwp_comments_core() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-fswwp-comments-core-activator.php';
	Fswwp_Comments_Core_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-fswwp-comments-core-deactivator.php
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Required by register_deactivation_hook.
function deactivate_fswwp_comments_core() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-fswwp-comments-core-deactivator.php';
	Fswwp_Comments_Core_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_fswwp_comments_core' );
register_deactivation_hook( __FILE__, 'deactivate_fswwp_comments_core' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-fswwp-comments-core.php';

/**
 * FSWWP Update Checker — automatic updates from your own server.
 *
 * @see https://github.com/flowsyncweb/fswwp-update-checker
 */
require plugin_dir_path( __FILE__ ) . 'fswwp-update-checker/index.php';
$fswwp_update_checker = new FSWWP_Update_Checker_1_0(
	'https://api.flowsyncweb.com/api/releases/fswwp-comments-core',
	__FILE__
);

// Set the license page URL so admin notices link to the correct page.
$fswwp_update_checker->set_license_url(
	admin_url( 'edit.php?post_type=fswwp_review&page=fswwp-comments-core-license' )
);

// Inject license key so the update-checker sends it with every request.
// Without a valid key the API returns 401 and updates are blocked.
$fswwp_license_key = get_option( 'fswwp_license_key', '' );
if ( ! empty( $fswwp_license_key ) ) {
	error_log( '[FSWWP Comments Core] Bootstrap: setting license key: ' . substr( $fswwp_license_key, 0, 8 ) . '...' );
	$fswwp_update_checker->set_license( $fswwp_license_key );
} else {
	error_log( '[FSWWP Comments Core] Bootstrap: no license key found in options (fswwp_license_key is empty).' );
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.1
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Plugin bootstrap; contains plugin slug.
function run_fswwp_comments_core() {

	$plugin = new Fswwp_Comments_Core();
	$plugin->run();

}
run_fswwp_comments_core();
