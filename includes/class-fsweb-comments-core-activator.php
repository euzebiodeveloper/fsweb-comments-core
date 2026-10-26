<?php

/**
 * Fired during plugin activation.
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
 * Activation routine.
 *
 * @since 1.0.0
 */
class Fsweb_Comments_Core_Activator {

	/**
	 * Register the post type and refresh rewrite rules.
	 *
	 * @return void
	 */
	public static function activate() {
		Fsweb_Comments_Core_Post_Types::register_cpt();
		Fsweb_Comments_Core_Post_Types::register_taxonomy();
		flush_rewrite_rules();
	}
}
