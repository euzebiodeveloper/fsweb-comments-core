<?php
/**
 * PHPUnit bootstrap.
 *
 * Boots a real WordPress install against a dedicated test database, so the
 * plugin runs with its own hooks, database and template loader instead of a
 * mocked environment.
 *
 * The test site is created by tests/bin/install-wp-tests.sh. WordPress core is
 * the one already on disk; only the database is different, which keeps the
 * suite fast and never touches the site data.
 *
 * Environment variables (all optional):
 *   WP_TEST_ROOT       WordPress core directory.
 *   WP_TEST_DB         Test database name (default fsweb_cc_test).
 *   WP_TEST_DB_USER    Database user.
 *   WP_TEST_DB_PASSWORD Database password.
 *   WP_TEST_DB_HOST    Database host.
 *
 * @package Fsweb_Comments_Core
 */

$fsweb_cc_wp_root = getenv( 'WP_TEST_ROOT' );
if ( ! $fsweb_cc_wp_root ) {
	$fsweb_cc_wp_root = '/home/euzebio/Local Sites/flowsyncweb/app/public';
}

$fsweb_cc_wp_root = rtrim( $fsweb_cc_wp_root, '/\\' );

if ( ! file_exists( $fsweb_cc_wp_root . '/wp-settings.php' ) ) {
	fwrite(
		STDERR,
		"WordPress not found at {$fsweb_cc_wp_root}.\n" .
		"Run tests/bin/install-wp-tests.sh or set WP_TEST_ROOT.\n"
	);
	exit( 1 );
}

// The database is defined here on purpose: wp-config.php is never loaded, so a
// wrong path can never wipe the site database.
define( 'DB_NAME', getenv( 'WP_TEST_DB' ) ?: 'fsweb_cc_test' );
define( 'DB_USER', getenv( 'WP_TEST_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TEST_DB_PASSWORD' ) ?: 'root' );
define( 'DB_HOST', getenv( 'WP_TEST_DB_HOST' ) ?: 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wp_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required by wp-settings.php.

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
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'WP_AUTO_UPDATE_CORE', false );
define( 'FSWEB_COMMENTS_CORE_REMOVE_DATA', false );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $fsweb_cc_wp_root . '/' );
}

require_once ABSPATH . 'wp-settings.php';

if ( ! class_exists( 'Fsweb_Comments_Core' ) ) {
	fwrite(
		STDERR,
		"FSWEB Comments Core is not active in the test database.\n" .
		"Activate it: php tests/bin/install-wp-tests.sh --activate\n"
	);
	exit( 1 );
}

// Admin helpers used by the screen tests.
require_once ABSPATH . 'wp-admin/includes/template.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
require_once ABSPATH . 'wp-admin/includes/post.php';

require_once __DIR__ . '/class-fsweb-cc-test-case.php';
