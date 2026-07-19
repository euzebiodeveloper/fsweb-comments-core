<?php

/**
 * Fired during plugin activation
 *
 * @link       https://flowsyncweb.com
 * @since      1.0.0
 *
 * @package    Fswwp_Comments_Core
 * @subpackage Fswwp_Comments_Core/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Fswwp_Comments_Core
 * @subpackage Fswwp_Comments_Core/includes
 * @author     Flow Sync Web <euzebio.batista@flowsyncweb.com>
 */
class Fswwp_Comments_Core_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Ensure CPT and taxonomy are registered before flushing rewrites.
		Fswwp_Comments_Core_Post_Types::register_cpt();
		Fswwp_Comments_Core_Post_Types::register_taxonomy();
		flush_rewrite_rules();
	}

}
