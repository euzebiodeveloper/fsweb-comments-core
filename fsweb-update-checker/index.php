<?php
/**
 * FSWEB Update Checker — Entry Point
 *
 * Drop this entire folder into your plugin and require this file.
 *
 * Each library version has its own class name (FSWEB_Update_Checker_1_0,
 * FSWEB_Update_Checker_2_0, etc.), so different versions can coexist
 * when different plugins ship different versions.
 *
 * @version 1.0.0
 * @link    https://flowsyncweb.com
 * @license GPL-2.0+
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

// ------------------------------------------------------------------//
//  Autoloader — maps FSWEB\UpdateChecker\V1 namespace to includes/
// ------------------------------------------------------------------//
require_once __DIR__ . '/includes/class-auto-loader.php';

$fsweb_autoloader = new \FSWEB\UpdateChecker\V1\Autoloader( __DIR__ . '/includes' );
$fsweb_autoloader->register();

// ------------------------------------------------------------------//
//  Main library class
// ------------------------------------------------------------------//
require_once __DIR__ . '/class-fsweb-update-checker.php';
