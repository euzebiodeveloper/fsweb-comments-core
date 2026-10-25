<?php
/**
 * Creates the test database and installs WordPress into it.
 *
 * Never touches the site database: the credentials come from the environment
 * (or the defaults below) and wp-config.php is never loaded.
 *
 * Usage:
 *   php tests/bin/install-wp-tests.php
 *
 * Environment:
 *   WP_TEST_ROOT       WordPress core directory (defaults to the Local site).
 *   WP_TEST_DB         Database name (default fsweb_cc_test).
 *   WP_TEST_DB_USER    Database user (default root).
 *   WP_TEST_DB_PASSWORD Database password (default root).
 *   WP_TEST_DB_HOST    Database host (default localhost).
 *
 * @package Fsweb_Comments_Core
 */

$fsweb_cc_wp_root = getenv( 'WP_TEST_ROOT' );

if ( ! $fsweb_cc_wp_root ) {
	// Walk up from tests/bin until the WordPress bootstrap is found.
	$fsweb_cc_dir = __DIR__;
	for ( $fsweb_cc_depth = 0; $fsweb_cc_depth < 8; $fsweb_cc_depth++ ) {
		if ( file_exists( $fsweb_cc_dir . '/wp-settings.php' ) ) {
			$fsweb_cc_wp_root = $fsweb_cc_dir;
			break;
		}
		$fsweb_cc_dir = dirname( $fsweb_cc_dir );
	}
}

if ( ! $fsweb_cc_wp_root ) {
	fwrite( STDERR, "Set WP_TEST_ROOT to the WordPress directory.\n" );
	exit( 1 );
}

$fsweb_cc_wp_root = rtrim( $fsweb_cc_wp_root, '/\\' );

if ( ! file_exists( $fsweb_cc_wp_root . '/wp-settings.php' ) ) {
	fwrite( STDERR, "WordPress not found at {$fsweb_cc_wp_root}.\n" );
	exit( 1 );
}

define( 'DB_NAME', getenv( 'WP_TEST_DB' ) ?: 'fsweb_cc_test' );
define( 'DB_USER', getenv( 'WP_TEST_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TEST_DB_PASSWORD' ) ?: 'root' );
define( 'DB_HOST', getenv( 'WP_TEST_DB_HOST' ) ?: 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
$table_prefix = 'wp_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

define( 'AUTH_KEY', 'fsweb-cc-test-auth-key' );
define( 'SECURE_AUTH_KEY', 'fsweb-cc-test-secure-auth-key' );
define( 'LOGGED_IN_KEY', 'fsweb-cc-test-logged-in-key' );
define( 'NONCE_KEY', 'fsweb-cc-test-nonce-key' );
define( 'AUTH_SALT', 'fsweb-cc-test-auth-salt' );
define( 'SECURE_AUTH_SALT', 'fsweb-cc-test-secure-auth-salt' );
define( 'LOGGED_IN_SALT', 'fsweb-cc-test-logged-in-salt' );
define( 'NONCE_SALT', 'fsweb-cc-test-nonce-salt' );

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_USE_THEMES', false );
define( 'DISABLE_WP_CRON', true );
define( 'WP_INSTALLING', true );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $fsweb_cc_wp_root . '/' );
}

// Create the database when it does not exist yet.
$fsweb_cc_db = new mysqli( DB_HOST, DB_USER, DB_PASSWORD );
if ( $fsweb_cc_db->connect_error ) {
	fwrite( STDERR, 'Database connection failed: ' . $fsweb_cc_db->connect_error . "\n" );
	exit( 1 );
}
$fsweb_cc_db->query( 'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' );
$fsweb_cc_db->select_db( DB_NAME );
$fsweb_cc_db->close();

require_once ABSPATH . 'wp-settings.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

if ( ! is_blog_installed() ) {
	$fsweb_cc_result = wp_install( 'FSWEB Comments Core Tests', 'admin', 'admin@example.com', true, '', 'admin' );

	if ( is_wp_error( $fsweb_cc_result ) ) {
		fwrite( STDERR, 'WordPress install failed: ' . $fsweb_cc_result->get_error_message() . "\n" );
		exit( 1 );
	}

	update_option( 'blogdescription', 'Test site' );
	echo "WordPress installed in " . DB_NAME . "\n";
} else {
	echo "WordPress already installed in " . DB_NAME . "\n";
}

$fsweb_cc_plugin = 'fsweb-comments-core/fsweb-comments-core.php';
$fsweb_cc_active = (array) get_option( 'active_plugins', array() );

if ( ! in_array( $fsweb_cc_plugin, $fsweb_cc_active, true ) ) {
	$fsweb_cc_error = activate_plugin( $fsweb_cc_plugin );

	if ( is_wp_error( $fsweb_cc_error ) ) {
		fwrite( STDERR, 'Could not activate the plugin: ' . $fsweb_cc_error->get_error_message() . "\n" );
		exit( 1 );
	}

	echo "Plugin activated\n";
}

flush_rewrite_rules( false );

echo "Ready. Run: vendor/bin/phpunit (or php phpunit.phar)\n";
