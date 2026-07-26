<?php
/**
 * FSWWP Update Checker — Main Plugin Class
 *
 * Thin facade that wires dependencies and registers WordPress hooks.
 * All logic is delegated to focused classes following SOLID principles.
 *
 * Designed to be copied into any plugin — each version has a unique
 * class name so that different plugin versions can ship different
 * library versions safely.
 *
 * @version 1.0.0
 * @link    https://flowsyncweb.com
 * @license GPL-2.0+
 */

// Only guard is for the SAME version — prevents fatal if two plugins ship the same version.
if ( ! class_exists( 'FSWWP_Update_Checker_1_0', false ) ) {

	/**
	 * FSWWP_Update_Checker_1_0 — plugin update checker for custom update servers.
	 *
	 * Version 1.0 of the library. When releasing breaking changes, create a new
	 * class like FSWWP_Update_Checker_2_0 — both will coexist in memory and each
	 * plugin instantiates the version it bundled.
	 *
	 * Usage:
	 *   require_once plugin_dir_path( __FILE__ ) . 'fswwp-update-checker/index.php';
	 *   new FSWWP_Update_Checker_1_0( 'https://api.example.com/updates/plugin-slug.json', __FILE__ );
	 */
	class FSWWP_Update_Checker_1_0 {

		/**
		 * Plugin basename (e.g. 'my-plugin/my-plugin.php').
		 *
		 * @var string
		 */
		private $plugin_basename;

		/**
		 * Plugin slug.
		 *
		 * @var string
		 */
		private $slug;

		/**
		 * Hook suffix for namespacing hooks.
		 *
		 * @var string
		 */
		private $hook_suffix;

		/**
		 * Metadata repository.
		 *
		 * @var \FSWWP\UpdateChecker\V1\Metadata
		 */
		private $metadata;

		/**
		 * Update manager.
		 *
		 * @var \FSWWP\UpdateChecker\V1\Updates
		 */
		private $updates;

		/**
		 * Cron manager.
		 *
		 * @var \FSWWP\UpdateChecker\V1\Cron
		 */
		private $cron;

		/**
		 * Admin UI.
		 *
		 * @var \FSWWP\UpdateChecker\V1\AdminUi
		 */
		private $admin_ui;

		/**
		 * Rollback handler.
		 *
		 * @var \FSWWP\UpdateChecker\V1\Rollback
		 */
		private $rollback;

		/**
		 * Assets manager.
		 *
		 * @var \FSWWP\UpdateChecker\V1\Assets
		 */
		private $assets;

		/**
		 * License page URL — shown in admin notices.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		private $license_url = '';

		/**
		 * Option name for metadata cache.
		 *
		 * @var string
		 */
		private $option_name;

		/**
		 * Constructor.
		 *
		 * @param string $metadata_url  URL to the JSON metadata endpoint.
		 * @param string $plugin_file   Full path to the main plugin file (__FILE__).
		 * @param string $slug          Optional slug. Defaults to the plugin filename without .php.
		 * @param int    $check_period  How often to check (hours). 0 disables automatic checks.
		 */
		public function __construct( $metadata_url, $plugin_file, $slug = '', $check_period = 12 ) {
			$this->plugin_basename = plugin_basename( $plugin_file );
			$this->slug            = ! empty( $slug )
				? sanitize_title( $slug )
				: basename( $this->plugin_basename, '.php' );
			$this->hook_suffix     = $this->slug;
			$this->option_name     = 'fswwp_updates_' . $this->slug;

			// Resolve plugin directory URL for assets.
			$plugin_dir_url = plugin_dir_url( $plugin_file );
			$assets_url     = $plugin_dir_url . 'fswwp-update-checker/assets/';

			// ------------------------------------------------------------------//
			//  Wire dependencies
			// ------------------------------------------------------------------//
			$this->metadata = new \FSWWP\UpdateChecker\V1\Metadata(
				$metadata_url,
				$this->slug,
				$this->option_name
			);

			$this->updates = new \FSWWP\UpdateChecker\V1\Updates(
				$this->plugin_basename,
				$this->slug,
				$plugin_file,
				$this->metadata
			);

			$this->cron = new \FSWWP\UpdateChecker\V1\Cron(
				$check_period,
				$this->hook_suffix,
				array( $this, 'cron_check' )
			);

			$plugin_name = $this->updates->get_plugin_name();

			$this->admin_ui = new \FSWWP\UpdateChecker\V1\AdminUi(
				$this->slug,
				$this->plugin_basename,
				$plugin_name,
				$assets_url,
				$this->metadata,
				$this->updates
			);

			$this->rollback = new \FSWWP\UpdateChecker\V1\Rollback(
				$this->slug,
				$this->plugin_basename,
				$this->metadata,
				$this->updates,
				$this->admin_ui
			);

			$nonce_action = $this->rollback->get_nonce_action();

			$this->assets = new \FSWWP\UpdateChecker\V1\Assets(
				$this->slug,
				$nonce_action,
				$assets_url,
				'1.0.0'
			);

			// ------------------------------------------------------------------//
			//  Slug uniqueness guard
			// ------------------------------------------------------------------//
			$slug_filter  = 'fswwp_slug_in_use_' . $this->slug;
			$slug_used_by = apply_filters( $slug_filter, false );

			if ( $slug_used_by ) {
				// Translators: %1$s is the slug, %2$s is the path of the plugin that already uses it.
				wp_die(
					esc_html(
						sprintf(
							__( 'Plugin slug "%1$s" is already in use by %2$s. Each plugin must have a unique slug.', 'fswwp-update-checker' ),
							$this->slug,
							$slug_used_by
						)
					),
					esc_html__( 'Update Checker Conflict', 'fswwp-update-checker' ),
					array( 'response' => 500 )
				);
			}
			add_filter( $slug_filter, array( $this, 'get_plugin_basename' ) );

			// ------------------------------------------------------------------//
			//  Register WordPress hooks
			// ------------------------------------------------------------------//

			// Inject update data into the WordPress update transient.
			add_filter( 'site_transient_update_plugins', array( $this, 'inject_update' ) );

			// Intercept plugins_api() calls for plugin information.
			add_filter( 'plugins_api', array( $this, 'inject_plugin_info' ), 20, 3 );

			// Schedule cron for periodic checks.
			$this->cron->schedule();

			// Cleanup on plugin uninstall.
			add_action( 'uninstall_' . $this->plugin_basename, array( $this, 'cleanup' ) );

			// Admin-only hooks.
			if ( is_admin() ) {
				// Plugin row links.
				add_filter(
					'plugin_row_meta',
					array( $this, 'add_plugin_row_links' ),
					10,
					2
				);

				// AJAX — previous versions thickbox.
				add_action(
					'wp_ajax_fswwp_previous_versions_' . $this->slug,
					array( $this, 'ajax_previous_versions' )
				);

				// AJAX — install selected version.
				add_action(
					'wp_ajax_fswwp_install_version_' . $this->slug,
					array( $this, 'ajax_install_version' )
				);

				// AJAX — manual update check.
				add_action(
					'wp_ajax_fswwp_check_updates_' . $this->slug,
					array( $this, 'ajax_check_for_updates' )
				);

				// Enqueue admin assets.
				add_action(
					'admin_enqueue_scripts',
					array( $this, 'enqueue_admin_assets' )
				);

				// Admin notice for license errors.
				add_action( 'admin_notices', array( $this, 'license_admin_notice' ) );
			}
		}

		// ------------------------------------------------------------------//
		//  Public API
		// ------------------------------------------------------------------//

		/**
		 * Set the license key so every update request includes it.
		 *
		 * Also stores the installed plugin version for the POST body.
		 * Call this before the first cron or transient check fires.
		 *
		 * @param string $license_key The license key.
		 * @return void
		 */
		public function set_license( $license_key ) {
			$this->metadata->set_license( $license_key );
			$this->metadata->set_plugin_version( $this->updates->get_installed_version() );
		}

		/**
		 * Set the license page URL shown in admin notices.
		 *
		 * @since 1.0.0
		 * @param string $url Full admin URL to the license settings page.
		 * @return void
		 */
		public function set_license_url( $url ) {
			$this->license_url = esc_url_raw( $url );
		}

		/**
		 * Get the last license error, if any.
		 *
		 * @return string 'invalid_key', 'forbidden', or empty string.
		 */
		public function get_license_error() {
			return $this->metadata->get_last_license_error();
		}

		/**
		 * Force an immediate update check.
		 *
		 * @return object|null Decoded JSON metadata, or null on failure.
		 */
		public function check_for_updates() {
			return $this->metadata->fetch_and_cache();
		}

		/**
		 * Get the plugin basename (used by the slug uniqueness filter).
		 *
		 * @return string
		 */
		public function get_plugin_basename() {
			return $this->plugin_basename;
		}

		/**
		 * Remove all hooks and scheduled crons. Called on plugin uninstall.
		 *
		 * @return void
		 */
		public function cleanup() {
			$this->remove_hooks();
			$this->cron->clear();
			$this->metadata->clear_cache();
		}

		// ------------------------------------------------------------------//
		//  WordPress Hook Callbacks (thin delegators)
		// ------------------------------------------------------------------//

		/**
		 * Inject update data into the site_transient_update_plugins.
		 *
		 * Blocks updates when a license error is recorded (no key, invalid key,
		 * domain mismatch, or expired license).
		 *
		 * @param \stdClass $transient The update_plugins transient value.
		 * @return \stdClass
		 */
		public function inject_update( $transient ) {
			$license_error = $this->metadata->get_last_license_error();
			if ( ! empty( $license_error ) ) {
				return $transient;
			}

			return $this->updates->inject_into_transient( $transient );
		}

		/**
		 * Intercept plugins_api() calls for plugin information.
		 *
		 * Everything comes from local data (plugin headers + README.txt +
		 * local icon/banner/screenshot assets). No API call — "View Details"
		 * always reflects the *installed* version.
		 *
		 * @param mixed  $result Default result.
		 * @param string $action API action.
		 * @param object $args   Request arguments.
		 * @return mixed Plugin info object or original result.
		 */
		public function inject_plugin_info( $result, $action, $args ) {
			if ( 'plugin_information' !== $action || empty( $args->slug ) ) {
				return $result;
			}

			if ( ! $this->updates->is_our_slug( $args->slug ) ) {
				return $result;
			}

			return $this->updates->build_local_plugin_info();
		}

		/**
		 * Cron callback — triggered by wp_cron to check for updates.
		 *
		 * @return void
		 */
		public function cron_check() {
			$this->metadata->fetch_and_cache();
		}

		/**
		 * Add custom links to the plugin's second row.
		 *
		 * @param array  $links Existing meta links.
		 * @param string $file  Plugin basename.
		 * @return array
		 */
		public function add_plugin_row_links( $links, $file ) {
			return $this->admin_ui->add_row_links( $links, $file );
		}

		/**
		 * AJAX handler — renders the previous versions thickbox.
		 *
		 * @return void
		 */
		public function ajax_previous_versions() {
			$this->rollback->ajax_previous_versions();
		}

		/**
		 * AJAX handler — installs a selected version.
		 *
		 * @return void
		 */
		public function ajax_install_version() {
			$this->rollback->ajax_install_version();
		}

		/**
		 * AJAX handler — performs a manual update check.
		 *
		 * @return void
		 */
		public function ajax_check_for_updates() {
			$this->rollback->ajax_check_for_updates();
		}

		/**
		 * Enqueue admin CSS and JS assets.
		 *
		 * @param string $hook_suffix Current admin page hook suffix.
		 * @return void
		 */
		public function enqueue_admin_assets( $hook_suffix ) {
			$this->assets->enqueue( $hook_suffix );
		}

		/**
		 * Admin notice — warns the user about license problems.
		 *
		 * Rendered via admin_notices hook when the license check returns
		 * 401 (invalid key) or 403 (domain mismatch / expired).
		 *
		 * @return void
		 */
		public function license_admin_notice() {
			$error = $this->metadata->get_last_license_error();

			if ( empty( $error ) ) {
				return;
			}

			$plugin_name = $this->updates->get_plugin_name();

			if ( 'invalid_key' === $error ) {
				$message = sprintf(
					/* translators: 1: plugin name, 2: license page URL */
					__(
						'%1$s is not activated. %2$sPlease activate your license%3$s to receive automatic updates.',
						'fswwp-update-checker'
					),
					'<strong>' . esc_html( $plugin_name ) . '</strong>',
					$this->license_url ? '<a href="' . esc_url( $this->license_url ) . '">' : '',
					$this->license_url ? '</a>' : ''
				);
			} else {
				$message = sprintf(
					/* translators: 1: plugin name, 2: license page URL */
					__(
						'%1$s: Automatic updates are disabled — your license may be expired or assigned to a different domain. %2$sCheck your license status%3$s to resume updates.',
						'fswwp-update-checker'
					),
					'<strong>' . esc_html( $plugin_name ) . '</strong>',
					$this->license_url ? '<a href="' . esc_url( $this->license_url ) . '">' : '',
					$this->license_url ? '</a>' : ''
				);
			}

			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				wp_kses(
					$message,
					array(
						'strong' => array(),
						'a'      => array( 'href' => array() ),
					)
				)
			);
		}

		// ------------------------------------------------------------------//
		//  Cleanup helpers
		// ------------------------------------------------------------------//

		/**
		 * Remove all WordPress hooks registered by this instance.
		 *
		 * @return void
		 */
		private function remove_hooks() {
			remove_filter( 'site_transient_update_plugins', array( $this, 'inject_update' ) );
			remove_filter( 'plugins_api', array( $this, 'inject_plugin_info' ), 20 );
			remove_action( 'fswwp_check_updates_' . $this->hook_suffix, array( $this, 'cron_check' ) );
			remove_filter( 'plugin_row_meta', array( $this, 'add_plugin_row_links' ), 10 );
			remove_action( 'wp_ajax_fswwp_previous_versions_' . $this->slug, array( $this, 'ajax_previous_versions' ) );
			remove_action( 'wp_ajax_fswwp_install_version_' . $this->slug, array( $this, 'ajax_install_version' ) );
			remove_action( 'wp_ajax_fswwp_check_updates_' . $this->slug, array( $this, 'ajax_check_for_updates' ) );
			remove_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			remove_action( 'admin_notices', array( $this, 'license_admin_notice' ) );
			remove_filter( 'cron_schedules', array( $this->cron, 'register_schedule' ) );
		}
	}
}
