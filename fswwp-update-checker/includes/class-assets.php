<?php
/**
 * FSWWP Update Checker — Assets Manager
 *
 * Enqueues external CSS and JS files for the admin screens.
 * No inline styles or scripts — everything is in real files under assets/.
 *
 * @package   FSWWP_Update_Checker
 * @version   1.0.0
 * @link      https://flowsyncweb.com
 * @license   GPL-2.0+
 */

namespace FSWWP\UpdateChecker\V1;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Assets Manager — enqueues admin CSS/JS files.
 */
class Assets {

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Nonce action for AJAX.
	 *
	 * @var string
	 */
	private $nonce_action;

	/**
	 * URL to the assets directory (with trailing slash).
	 *
	 * @var string
	 */
	private $assets_url;

	/**
	 * Plugin version (for cache busting).
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @param string $slug         Plugin slug.
	 * @param string $nonce_action Nonce action string.
	 * @param string $assets_url   URL to the assets/ directory.
	 * @param string $version      Plugin version (cache busting).
	 */
	public function __construct( $slug, $nonce_action, $assets_url, $version = '1.0.0' ) {
		$this->slug         = $slug;
		$this->nonce_action = $nonce_action;
		$this->assets_url   = rtrim( $assets_url, '/\\' ) . '/';
		$this->version      = $version;
	}

	/**
	 * Enqueue admin styles and scripts.
	 *
	 * Hooked to `admin_enqueue_scripts`. Only loads on plugins.php
	 * and plugin-install.php screens.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'plugins.php', 'plugin-install.php' ), true ) ) {
			return;
		}

		if ( function_exists( 'add_thickbox' ) ) {
			add_thickbox();
		}

		wp_enqueue_style(
			'fswwp-update-checker-admin',
			$this->assets_url . 'css/admin.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			'fswwp-update-checker-admin',
			$this->assets_url . 'js/admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			'fswwp-update-checker-admin',
			'FSWWP_UpdateChecker_Rollback',
			array(
				'action'      => 'fswwp_install_version_' . $this->slug,
				'nonce'       => wp_create_nonce( $this->nonce_action ),
				'checkAction' => 'fswwp_check_updates_' . $this->slug,
				'checkNonce'  => wp_create_nonce( 'fswwp_check_updates_' . $this->slug ),
				'i18n'        => array(
					'installBtn'    => __( 'Install Selected Version', 'fswwp-update-checker' ),
					'installing'    => __( 'Installing…', 'fswwp-update-checker' ),
					'downloading'   => __( 'Downloading and installing version', 'fswwp-update-checker' ),
					'networkError'  => __( 'Network error. Please try again.', 'fswwp-update-checker' ),
					'checking'      => __( 'Checking for updates…', 'fswwp-update-checker' ),
					'noLicense'     => __( 'A valid license key is required to check for updates.', 'fswwp-update-checker' ),
					'latestVersion' => __( 'You are running the latest version.', 'fswwp-update-checker' ),
				),
			)
		);
	}
}
