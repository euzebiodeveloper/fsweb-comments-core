<?php

/**
 * Define the internationalization functionality.
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
 * Loads the plugin text domain.
 *
 * @since 1.0.0
 */
class Fsweb_Comments_Core_i18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'fsweb-comments-core',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
