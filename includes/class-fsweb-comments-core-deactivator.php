<?php

/**
 * Fired during plugin deactivation.
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
 * Deactivation routine.
 *
 * @since 1.0.0
 */
class Fsweb_Comments_Core_Deactivator {

	/**
	 * Nothing to clean up: the reviews stay in the database.
	 *
	 * @return void
	 */
	public static function deactivate() {
	}
}
