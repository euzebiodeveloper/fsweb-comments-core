<?php
/**
 * FSWWP Update Checker — Entry Point
 *
 * Drop this entire folder into your plugin and require this file.
 *
 * Each library version has its own class name (FSWWP_Update_Checker_1_0,
 * FSWWP_Update_Checker_2_0, etc.), so different versions can coexist
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
//  Autoloader — maps FSWWP\UpdateChecker\V1 namespace to includes/
// ------------------------------------------------------------------//
require_once __DIR__ . '/includes/class-auto-loader.php';

$fswwp_autoloader = new \FSWWP\UpdateChecker\V1\Autoloader( __DIR__ . '/includes' );
$fswwp_autoloader->register();

// ------------------------------------------------------------------//
//  Main library class
// ------------------------------------------------------------------//
require_once __DIR__ . '/class-fswwp-update-checker.php';
