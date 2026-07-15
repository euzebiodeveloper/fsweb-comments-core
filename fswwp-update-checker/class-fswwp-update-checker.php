<?php
/**
 * FSWWP Update Checker
 *
 * Self-contained library for checking plugin updates from a remote JSON endpoint.
 * Designed to be copied into any plugin — each version has a unique class name
 * so that different plugin versions can ship different library versions safely.
 *
 * @version 1.0.0
 * @link    https://flowsyncweb.com
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
		 * URL to the JSON metadata file.
		 *
		 * @var string
		 */
		private $metadata_url = '';

		/**
		 * Full path to the main plugin file.
		 *
		 * @var string
		 */
		private $plugin_file = '';

		/**
		 * Plugin basename relative to /plugins/ (e.g. 'my-plugin/my-plugin.php').
		 *
		 * @var string
		 */
		private $plugin_basename = '';

		/**
		 * Unique slug for this plugin.
		 *
		 * @var string
		 */
		private $slug = '';

		/**
		 * How often to check for updates (in hours).
		 *
		 * @var int
		 */
		private $check_period = 12;

		/**
		 * Where to store update metadata in wp_options.
		 *
		 * @var string
		 */
		private $option_name = '';

		/**
		 * Hook suffix used to namespace all filters/actions for this instance.
		 *
		 * @var string
		 */
		private $hook_suffix = '';

		/**
		 * Cached plugin display name (from headers).
		 *
		 * @var string
		 */
		private $plugin_name = '';

		/**
		 * Plugin directory path (with trailing slash).
		 *
		 * @var string
		 */
		private $plugin_dir_path = '';

		/**
		 * Nonce action for version rollback AJAX.
		 *
		 * @var string
		 */
		private $nonce_action = '';

		/**
		 * Full URL for the details endpoint (sections, changelog, etc.).
		 * Auto-derived from metadata_url by appending '/details'.
		 *
		 * @var string
		 */
		private $details_url = '';

		/**
		 * Constructor.
		 *
		 * @param string $metadata_url  URL to the JSON metadata endpoint.
		 * @param string $plugin_file   Full path to the main plugin file (__FILE__).
		 * @param string $slug          Optional slug. Defaults to the plugin filename without .php.
		 * @param int    $check_period  How often to check (hours). 0 disables automatic checks.
		 */
		public function __construct( $metadata_url, $plugin_file, $slug = '', $check_period = 12 ) {
			$this->metadata_url    = esc_url_raw( $metadata_url );
			$this->details_url     = esc_url_raw( rtrim( $metadata_url, '/' ) . '/details' );
			$this->plugin_file     = $plugin_file;
			$this->plugin_basename = plugin_basename( $plugin_file );
			$this->slug            = ! empty( $slug ) ? sanitize_title( $slug ) : basename( $this->plugin_basename, '.php' );
			$this->check_period    = max( 0, (int) $check_period );
			$this->option_name     = 'fswwp_updates_' . $this->slug;
			$this->hook_suffix     = $this->slug;
			$this->plugin_dir_path = plugin_dir_path( $plugin_file );
			$this->plugin_name     = $this->get_plugin_name();
			$this->nonce_action    = 'fswwp_rollback_' . $this->slug;

			// ------------------------------------------------------------------//
			//  Slug uniqueness guard — prevents two plugins sharing the same slug
			// ------------------------------------------------------------------//
			$slug_filter = 'fswwp_slug_in_use_' . $this->slug;
			$slug_used_by = apply_filters( $slug_filter, false );

			if ( $slug_used_by ) {
				// Translators: %1$s is the slug, %2$s is the path of the plugin that already uses it.
				wp_die(
					esc_html( sprintf(
						__( 'Plugin slug "%1$s" is already in use by %2$s. Each plugin must have a unique slug.', 'fswwp-update-checker' ),
						$this->slug,
						$slug_used_by
					) ),
					__( 'Update Checker Conflict', 'fswwp-update-checker' ),
					array( 'response' => 500 )
				);
			}
			add_filter( $slug_filter, array( $this, 'get_plugin_basename' ) );

			// ---------------------------------------------------------//
			//  Hooks
			// ---------------------------------------------------------//

			// Inject update data into the WordPress update transient.
			add_filter( 'site_transient_update_plugins', array( $this, 'inject_update' ) );

			// Intercept plugins_api() calls for plugin information (View Details).
			add_filter( 'plugins_api', array( $this, 'inject_plugin_info' ), 20, 3 );

			// Schedule the cron event for periodic checks.
			$this->schedule_cron();

			// Cleanup on plugin uninstall.
			add_action( 'uninstall_' . $this->plugin_basename, array( $this, 'cleanup' ) );

			// ---------------------------------------------------------//
			//  Admin hooks — only on the plugins screen
			// ---------------------------------------------------------//
			if ( is_admin() ) {
				// Links na segunda linha: View Details, Check for Updates,
				// Previous Versions (plugin_row_meta).
				add_filter(
					'plugin_row_meta',
					array( $this, 'add_plugin_row_links' ),
					10,
					2
				);

				// AJAX — thickbox com lista de versões anteriores.
				add_action(
					'wp_ajax_fswwp_previous_versions_' . $this->slug,
					array( $this, 'ajax_previous_versions' )
				);

				// AJAX — instala versão selecionada.
				add_action(
					'wp_ajax_fswwp_install_version_' . $this->slug,
					array( $this, 'ajax_install_version' )
				);

				// AJAX — verificação manual de atualizações.
				add_action(
					'wp_ajax_fswwp_check_updates_' . $this->slug,
					array( $this, 'ajax_check_for_updates' )
				);

				// Inline JS para interação do modal.
				add_action(
					'admin_enqueue_scripts',
					array( $this, 'enqueue_rollback_script' )
				);
			}
		}

		// ------------------------------------------------------------------//
		//  Public API
		// ------------------------------------------------------------------//

		/**
		 * Force an immediate update check. Returns the raw API response or null.
		 *
		 * @return object|null Decoded JSON metadata, or null on failure.
		 */
		public function check_for_updates() {
			$result = $this->fetch_metadata();

			if ( is_wp_error( $result ) ) {
				$this->log_error( 'fetch_metadata', $result->get_error_message() );
				return null;
			}

			$metadata = json_decode( wp_remote_retrieve_body( $result ) );

			if ( ! is_object( $metadata ) || empty( $metadata->version ) ) {
				$this->log_error( 'invalid_metadata', 'Response is missing version field or is not valid JSON.' );
				return null;
			}

			// Store metadata.
			update_option( $this->option_name, $metadata );
			update_option( $this->option_name . '_last_check', time() );

			return $metadata;
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
		 */
		public function cleanup() {
			$this->remove_hooks();
			$this->clear_cron();
			delete_option( $this->option_name );
			delete_option( $this->option_name . '_last_check' );
		}

		// ------------------------------------------------------------------//
		//  WordPress Hooks
		// ------------------------------------------------------------------//

		/**
		 * Inject update data into the site transient update_plugins.
		 *
		 * @param \stdClass $transient The update_plugins transient.
		 * @return \stdClass
		 */
		public function inject_update( $transient ) {
			if ( ! is_object( $transient ) ) {
				return $transient;
			}

			$metadata = $this->get_cached_metadata();

			if ( ! $metadata || empty( $metadata->version ) ) {
				return $transient;
			}

			// Get current installed version from plugin headers.
			$current_version = $this->get_installed_version();

			if ( empty( $current_version ) ) {
				return $transient;
			}

			// Only inject if remote version is newer.
			if ( version_compare( $metadata->version, $current_version, '>' ) ) {
				$update_data = $this->build_update_data( $metadata );

				// Set the update in the transient response.
				$transient->response[ $this->plugin_basename ] = $update_data;

				// Remove no_update entry if it exists.
				if ( isset( $transient->no_update[ $this->plugin_basename ] ) ) {
					unset( $transient->no_update[ $this->plugin_basename ] );
				}
			} else {
				// No update available — set no_update entry.
				$transient->no_update[ $this->plugin_basename ] = $this->build_no_update_data( $metadata );

				// Remove from response if present.
				if ( isset( $transient->response[ $this->plugin_basename ] ) ) {
					unset( $transient->response[ $this->plugin_basename ] );
				}
			}

			return $transient;
		}

		/**
		 * Intercept plugins_api() calls for plugin information.
		 *
		 * @param mixed  $result Default result.
		 * @param string $action API action ('plugin_information').
		 * @param object $args   Request arguments (must have ->slug).
		 * @return mixed Plugin info object or original result.
		 */
		public function inject_plugin_info( $result, $action, $args ) {
			if ( 'plugin_information' !== $action || empty( $args->slug ) ) {
				return $result;
			}

			// Check if this request is for our plugin.
			if ( $args->slug !== $this->slug && $args->slug !== dirname( $this->plugin_basename ) ) {
				return $result;
			}

			error_log( '[FSWWP Update Checker] === inject_plugin_info called ===' );
			error_log( '[FSWWP Update Checker] action: ' . $action . ' | slug: ' . $args->slug . ' | our slug: ' . $this->slug );

			// Busca o metadata base (version, download_url, etc.).
			$metadata = $this->get_cached_metadata();
			error_log( '[FSWWP Update Checker] cached metadata: ' . ( $metadata ? 'YES (version ' . $metadata->version . ')' : 'NONE' ) );

			if ( ! $metadata ) {
				error_log( '[FSWWP Update Checker] will fetch fresh metadata from: ' . $this->metadata_url );
				$metadata = $this->check_for_updates();
				error_log( '[FSWWP Update Checker] fresh metadata: ' . ( $metadata ? 'OK version ' . $metadata->version : 'FAILED' ) );
			}
			if ( ! $metadata ) {
				error_log( '[FSWWP Update Checker] aborting — no metadata available' );
				return $result;
			}

			// Busca dados completos do endpoint de detalhes (sections, banners, icons).
			error_log( '[FSWWP Update Checker] fetching details from endpoint' );
			$details = $this->fetch_details();
			$sections = $details ? $details->sections : null;
			$banners  = $details ? $details->banners : null;
			$icons    = $details ? $details->icons : null;
			error_log( '[FSWWP Update Checker] details result — sections: '
				. ( $sections ? 'OK (' . count( $sections ) . ')' : 'null' )
				. ' | banners: ' . ( $banners ? 'OK' : 'null' )
				. ' | icons: ' . ( $icons ? 'OK' : 'null' ) );

			$info = $this->build_plugin_info( $metadata, $sections, $banners, $icons );
			error_log( '[FSWWP Update Checker] returning plugin info — name: ' . $info->name . ' | version: ' . $info->version . ' | sections: ' . count( $info->sections ) );
			return $info;
		}

		/**
		 * Cron callback — triggered by wp_cron to check for updates.
		 */
		public function cron_check() {
			$this->check_for_updates();
		}

		// ------------------------------------------------------------------//
		//  Scheduling (WP-Cron)
		// ------------------------------------------------------------------//

		/**
		 * Schedule the cron event for periodic checks.
		 */
		private function schedule_cron() {
			if ( $this->check_period <= 0 ) {
				return; // Automatic checks disabled.
			}

			$hook = 'fswwp_check_updates_' . $this->hook_suffix;

			if ( ! wp_next_scheduled( $hook ) ) {
				$interval = $this->check_period * HOUR_IN_SECONDS;
				wp_schedule_event( time(), 'fswwp_every_' . $this->check_period . 'hours', $hook );

				// Register the custom schedule if it doesn't exist.
				add_filter( 'cron_schedules', array( $this, 'register_cron_schedule' ) );
			}

			add_action( $hook, array( $this, 'cron_check' ) );
		}

		/**
		 * Register a custom cron schedule for this instance's check period.
		 *
		 * @param array $schedules Existing cron schedules.
		 * @return array
		 */
		public function register_cron_schedule( $schedules ) {
			$key = 'fswwp_every_' . $this->check_period . 'hours';

			if ( ! isset( $schedules[ $key ] ) ) {
				$schedules[ $key ] = array(
					'interval' => $this->check_period * HOUR_IN_SECONDS,
					/* translators: %d: number of hours */
					'display'  => sprintf( __( 'Every %d hours', 'fswwp-update-checker' ), $this->check_period ),
				);
			}

			return $schedules;
		}

		/**
		 * Remove all hooks added by this instance.
		 */
		private function remove_hooks() {
			remove_filter( 'site_transient_update_plugins', array( $this, 'inject_update' ) );
			remove_filter( 'plugins_api', array( $this, 'inject_plugin_info' ), 20 );
			remove_action( 'fswwp_check_updates_' . $this->hook_suffix, array( $this, 'cron_check' ) );

			// Admin hooks.
			remove_filter(
				'plugin_row_meta',
				array( $this, 'add_plugin_row_links' ),
				10
			);
			remove_action(
				'wp_ajax_fswwp_previous_versions_' . $this->slug,
				array( $this, 'ajax_previous_versions' )
			);
			remove_action(
				'wp_ajax_fswwp_install_version_' . $this->slug,
				array( $this, 'ajax_install_version' )
			);
			remove_action(
				'wp_ajax_fswwp_check_updates_' . $this->slug,
				array( $this, 'ajax_check_for_updates' )
			);
			remove_action(
				'admin_enqueue_scripts',
				array( $this, 'enqueue_rollback_script' )
			);
		}

		/**
		 * Clear the cron event.
		 */
		private function clear_cron() {
			$hook = 'fswwp_check_updates_' . $this->hook_suffix;
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}

		// ------------------------------------------------------------------//
		//  Helpers
		// ------------------------------------------------------------------//

		/**
		 * Fetch metadata JSON from the remote URL.
		 *
		 * @return array|\WP_Error
		 */
		private function fetch_metadata() {
			$args = apply_filters(
				'fswwp_update_checker_http_args',
				array(
					'timeout'    => 10,
					'headers'    => array(
						'Accept' => 'application/json',
					),
					'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
				),
				$this->slug
			);

			$response = wp_remote_get( $this->metadata_url, $args );

			if ( is_wp_error( $response ) ) {
				error_log( '[FSWWP Update Checker] HTTP error: ' . $response->get_error_message() );
			} else {
				$code = wp_remote_retrieve_response_code( $response );
				$body = wp_remote_retrieve_body( $response );
				error_log( '[FSWWP Update Checker] HTTP ' . $code . ' from ' . $this->metadata_url );
				error_log( '[FSWWP Update Checker] Response body (first 500 chars): ' . substr( $body, 0, 500 ) );
			}

			return $response;
		}

		/**
		 * Get cached metadata from wp_options.
		 *
		 * @return object|null
		 */
		private function get_cached_metadata() {
			$metadata = get_option( $this->option_name );
			return is_object( $metadata ) ? $metadata : null;
		}

		/**
		 * Get the currently installed plugin version.
		 *
		 * @return string|null
		 */
		private function get_installed_version() {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugin_data = get_plugin_data( $this->plugin_file, false, false );

			return ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : null;
		}

		/**
		 * Build an update object compatible with the update_plugins transient (response).
		 *
		 * @param object $metadata Decoded JSON metadata.
		 * @return \stdClass
		 */
		private function build_update_data( $metadata ) {
			$update = new \stdClass();
			$update->id          = $this->plugin_basename;
			$update->slug        = $this->slug;
			$update->plugin      = $this->plugin_basename;
			$update->new_version = $metadata->version;
			$update->url         = ! empty( $metadata->homepage ) ? $metadata->homepage : '';
			$update->package     = ! empty( $metadata->download_url ) ? $metadata->download_url : '';
			$update->requires    = ! empty( $metadata->requires ) ? $metadata->requires : '';
			$update->tested      = ! empty( $metadata->tested ) ? $metadata->tested : '';
			$update->requires_php = ! empty( $metadata->requires_php ) ? $metadata->requires_php : '';
			$update->compatibility = new \stdClass();

			// Icons.
			if ( ! empty( $metadata->icons ) ) {
				$update->icons = (array) $metadata->icons;
			} else {
				$update->icons = array();
			}

			// Banners.
			if ( ! empty( $metadata->banners ) ) {
				$update->banners = (array) $metadata->banners;
			} else {
				$update->banners = array();
			}

			// Banners RTL.
			if ( ! empty( $metadata->banners_rtl ) ) {
				$update->banners_rtl = (array) $metadata->banners_rtl;
			} else {
				$update->banners_rtl = array();
			}

			// Translations.
			if ( ! empty( $metadata->translations ) ) {
				$update->translations = (array) $metadata->translations;
			}

			return $update;
		}

		/**
		 * Build a "no_update" entry for the transient.
		 *
		 * @param object $metadata Decoded JSON metadata.
		 * @return \stdClass
		 */
		private function build_no_update_data( $metadata ) {
			$item = new \stdClass();
			$item->id            = $this->plugin_basename;
			$item->slug          = $this->slug;
			$item->plugin        = $this->plugin_basename;
			$item->new_version   = $metadata->version;
			$item->url           = ! empty( $metadata->homepage ) ? $metadata->homepage : '';
			$item->package       = '';
			$item->requires      = ! empty( $metadata->requires ) ? $metadata->requires : '';
			$item->tested        = ! empty( $metadata->tested ) ? $metadata->tested : '';
			$item->requires_php  = ! empty( $metadata->requires_php ) ? $metadata->requires_php : '';
			$item->compatibility = new \stdClass();
			$item->icons         = array();
			$item->banners       = array();
			$item->banners_rtl   = array();
			return $item;
		}

		/**
		 * Build a plugin info object for the plugins_api response.
		 *
		 * @param object      $metadata Decoded JSON metadata.
		 * @param array|null  $sections Optional. Sections from details endpoint.
		 * @param object|null $banners  Optional. Banners from details endpoint.
		 * @param object|null $icons    Optional. Icons from details endpoint.
		 * @return \stdClass
		 */
		private function build_plugin_info( $metadata, $sections = null, $banners = null, $icons = null ) {
			$info = new \stdClass();
			$info->name           = ! empty( $metadata->name ) ? $metadata->name : $this->slug;
			$info->slug           = $this->slug;
			$info->version        = $metadata->version;
			$info->author         = ! empty( $metadata->author ) ? $metadata->author : '';
			$info->author_profile = ! empty( $metadata->author_profile ) ? $metadata->author_profile : '';
			$info->requires       = ! empty( $metadata->requires ) ? $metadata->requires : '';
			$info->tested         = ! empty( $metadata->tested ) ? $metadata->tested : '';
			$info->requires_php   = ! empty( $metadata->requires_php ) ? $metadata->requires_php : '';
			$info->homepage       = ! empty( $metadata->homepage ) ? $metadata->homepage : '';
			$info->download_link  = ! empty( $metadata->download_url ) ? $metadata->download_url : '';
			$info->last_updated   = ! empty( $metadata->last_updated ) ? $metadata->last_updated : '';

			// Banners: details endpoint first, then metadata fallback.
			if ( $banners !== null ) {
				$info->banners = (array) $banners;
			} elseif ( ! empty( $metadata->banners ) ) {
				$info->banners = (array) $metadata->banners;
			} else {
				$info->banners = array();
			}

			// Icons: details endpoint first, then metadata fallback.
			if ( $icons !== null ) {
				$info->icons = (array) $icons;
			} elseif ( ! empty( $metadata->icons ) ) {
				$info->icons = (array) $metadata->icons;
			} else {
				$info->icons = array();
			}

			// Sections: use $sections from details endpoint first,
			// fallback to $metadata->sections, then default.
			if ( $sections !== null && is_array( $sections ) ) {
				$info->sections = $sections;
			} elseif ( ! empty( $metadata->sections ) ) {
				$info->sections = (array) $metadata->sections;
			} else {
				$info->sections = array(
					/* translators: %s: plugin name */
					'description' => sprintf( __( '<p>Plugin %s</p>', 'fswwp-update-checker' ), $info->name ),
				);
			}

			return $info;
		}

		/**
		 * Fetch sections, banners e icons do endpoint de detalhes.
		 *
		 * Retorna um objeto com { sections, banners, icons } extraído do
		 * JSON do endpoint /details. Qualquer campo pode ser null se
		 * não existir na resposta.
		 *
		 * @return \stdClass|null { sections: array|null, banners: object|null, icons: object|null }
		 */
		private function fetch_details() {
			error_log( '[FSWWP Update Checker] fetch_details — calling: ' . $this->details_url );

			$response = wp_remote_get( $this->details_url, array(
				'timeout'    => 10,
				'headers'    => array( 'Accept' => 'application/json' ),
				'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
			) );

			if ( is_wp_error( $response ) ) {
				error_log( '[FSWWP Update Checker] details endpoint WP_Error: ' . $response->get_error_message() );
				return null;
			}

			$code     = wp_remote_retrieve_response_code( $response );
			$raw_body = wp_remote_retrieve_body( $response );
			error_log( '[FSWWP Update Checker] details endpoint HTTP ' . $code . ' — body length: ' . strlen( $raw_body ) . ' chars' );
			error_log( '[FSWWP Update Checker] details raw body (first 800 chars): ' . substr( $raw_body, 0, 800 ) );

			if ( $code !== 200 ) {
				error_log( '[FSWWP Update Checker] details endpoint returned non-200, aborting' );
				return null;
			}

			$body = json_decode( $raw_body );
			if ( ! is_object( $body ) ) {
				error_log( '[FSWWP Update Checker] details response is not valid JSON' );
				return null;
			}

			error_log( '[FSWWP Update Checker] details JSON decoded OK — keys: ' . implode( ', ', array_keys( (array) $body ) ) );

			$result = new \stdClass();
			$result->sections = null;
			$result->banners  = ! empty( $body->banners ) ? $body->banners : null;
			$result->icons    = ! empty( $body->icons ) ? $body->icons : null;

			// Sections podem vir num objeto aninhado ou como campos soltos.
			if ( ! empty( $body->sections ) ) {
				$result->sections = (array) $body->sections;
			} else {
				$flat_sections = array();
				foreach ( array( 'description', 'changelog', 'installation', 'faq', 'screenshots' ) as $key ) {
					if ( ! empty( $body->$key ) ) {
						$flat_sections[ $key ] = $body->$key;
					}
				}
				if ( ! empty( $flat_sections ) ) {
					$result->sections = $flat_sections;
				}
			}

			error_log( '[FSWWP Update Checker] details parsed — sections: '
				. ( $result->sections ? 'OK (' . count( $result->sections ) . ')' : 'null' )
				. ' | banners: ' . ( $result->banners ? 'OK' : 'null' )
				. ' | icons: ' . ( $result->icons ? 'OK' : 'null' ) );

			return $result;
		}

		// ------------------------------------------------------------------//
		//  Version Rollback — Previous Versions
		// ------------------------------------------------------------------//

		/**
		 * Get the plugin display name from plugin headers.
		 *
		 * @return string
		 */
		private function get_plugin_name() {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$data = get_plugin_data( $this->plugin_file, false, false );
			return ! empty( $data['Name'] ) ? $data['Name'] : $this->slug;
		}

		/**
		 * Add custom links to the plugin's second row (plugin_row_meta).
		 *
		 * Adds: View Details | Check for Updates | Previous Versions
		 *
		 * @param array  $links Existing meta links.
		 * @param string $file  Plugin basename.
		 * @return array
		 */
		public function add_plugin_row_links( $links, $file ) {
			if ( $file !== $this->plugin_basename ) {
				return $links;
			}

			// NOTA: "View Details" não é adicionado aqui porque o WordPress
			// já adiciona automaticamente (traduzido pelo locale do site).
			// Um JS inline troca o texto "Ver detalhes" para "View Details".

			// --- Check for Updates (AJAX manual check) ---
			$check_url = admin_url(
				'admin-ajax.php?action=fswwp_check_updates_' . $this->slug
			);
			$links['fswwp-check'] = sprintf(
				'<a href="%s" class="fswwp-check-updates" data-plugin="%s">%s</a>',
				esc_url( $check_url ),
				esc_attr( $this->plugin_basename ),
				__( 'Check for Updates', 'fswwp-update-checker' )
			);

			// --- Previous Versions (thickbox) ---
			$prev_url = admin_url(
				'admin-ajax.php?action=fswwp_previous_versions_' . $this->slug
				. '&TB_iframe=true&width=772&height=550'
			);
			$links['fswwp-versions'] = sprintf(
				'<a href="%s" class="thickbox" title="%s">%s</a>',
				esc_url( $prev_url ),
				esc_attr(
					sprintf(
						/* translators: %s: plugin name */
						__( '%s — Previous Versions', 'fswwp-update-checker' ),
						$this->plugin_name
					)
				),
				__( 'Previous Versions', 'fswwp-update-checker' )
			);

			return $links;
		}

		/**
		 * AJAX handler — returns the thickbox HTML with the version list.
		 *
		 * No nonce check here — thickbox/iframe requests don't reliably
		 * send URL nonces. Only capability is verified (read-only output).
		 * The install action (write) has its own nonce check.
		 */
		public function ajax_previous_versions() {
			if ( ! current_user_can( 'update_plugins' ) ) {
				wp_die( -1 );
			}

			// Fetch fresh metadata to get the latest versions list.
			$metadata = $this->check_for_updates();

			if ( ! $metadata ) {
				// Try cached metadata.
				$metadata = $this->get_cached_metadata();
			}

			$current_version = $this->get_installed_version();

			// Build versions array from metadata.
			$versions = array();
			if ( $metadata && ! empty( $metadata->versions ) && is_array( $metadata->versions ) ) {
				$versions = $metadata->versions;
			} else {
				// Fallback: only the latest version is known.
				if ( $metadata && ! empty( $metadata->version ) ) {
					$versions[] = array(
						'version'      => $metadata->version,
						'download_url' => ! empty( $metadata->download_url ) ? $metadata->download_url : '',
						'date'         => ! empty( $metadata->last_updated ) ? $metadata->last_updated : '',
						'notes'        => '',
					);
				}
			}

			// Sort descending (newest first).
			usort( $versions, function ( $a, $b ) {
				return version_compare(
					is_object( $a ) ? $a->version : $a['version'],
					is_object( $b ) ? $b->version : $b['version'],
					'<'
				) ? 1 : -1;
			} );

			?>
			<!DOCTYPE html>
			<html><head>
				<title><?php echo esc_html( $this->plugin_name ); ?> — <?php esc_html_e( 'Previous Versions', 'fswwp-update-checker' ); ?></title>
				<?php wp_admin_css( 'common' ); ?>
				<?php wp_admin_css( 'forms' ); ?>
				<style>
					body { margin: 15px; }
					.current-version { background: #f0f8e8 !important; }
					.current-label { color: #46b450; font-weight: 600; }
					.rollback-table { margin: 15px 0; }
					.rollback-table td,
					.rollback-table th { vertical-align: middle; }
					#fswwp-install-result { margin-top: 15px; padding: 10px; display: none; }
					#fswwp-install-result.success { display: block; background: #ecf7ed; border-left: 4px solid #46b450; }
					#fswwp-install-result.error { display: block; background: #fbeaea; border-left: 4px solid #dc3232; }
					.rollback-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; }
				</style>
			</head><body>
				<h2><?php echo esc_html( $this->plugin_name ); ?> — <?php esc_html_e( 'Previous Versions', 'fswwp-update-checker' ); ?></h2>
				<p><?php esc_html_e( 'Select a version to install. The current version is highlighted in green.', 'fswwp-update-checker' ); ?></p>

				<?php if ( empty( $versions ) ) : ?>
					<p><?php esc_html_e( 'No version history available.', 'fswwp-update-checker' ); ?></p>
				<?php else : ?>
					<table class="wp-list-table widefat striped rollback-table">
						<thead>
							<tr>
								<th width="50"><?php esc_html_e( 'Select', 'fswwp-update-checker' ); ?></th>
								<th><?php esc_html_e( 'Version', 'fswwp-update-checker' ); ?></th>
								<th><?php esc_html_e( 'Date', 'fswwp-update-checker' ); ?></th>
								<th><?php esc_html_e( 'Notes', 'fswwp-update-checker' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $versions as $ver ) : ?>
								<?php
								$ver_obj   = is_object( $ver ) ? $ver : (object) $ver;
								$is_current = $current_version && version_compare( $ver_obj->version, $current_version, '=' );
								?>
								<tr class="<?php echo $is_current ? 'current-version' : ''; ?>">
									<td>
										<input
											type="radio"
											name="fswwp_version"
											value="<?php echo esc_url( $ver_obj->download_url ); ?>"
											data-version="<?php echo esc_attr( $ver_obj->version ); ?>"
											<?php disabled( $is_current ); ?>>
									</td>
									<td>
										<strong><?php echo esc_html( $ver_obj->version ); ?></strong>
										<?php if ( $is_current ) : ?>
											<span class="current-label"> — <?php esc_html_e( 'installed', 'fswwp-update-checker' ); ?></span>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( ! empty( $ver_obj->date ) ? $ver_obj->date : '' ); ?></td>
									<td><?php echo esc_html( ! empty( $ver_obj->notes ) ? $ver_obj->notes : '' ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<div class="rollback-footer">
						<div>
							<button class="button button-primary" id="fswwp-install-version" disabled>
								<?php esc_html_e( 'Install Selected Version', 'fswwp-update-checker' ); ?>
							</button>
						</div>
						<div>
							<button class="button" onclick="self.parent.tb_remove();">
								<?php esc_html_e( 'Close', 'fswwp-update-checker' ); ?>
							</button>
						</div>
					</div>

					<div id="fswwp-install-result"></div>
				<?php endif; ?>
			</body></html>
			<?php
			wp_die();
		}

		/**
		 * AJAX handler — installs the selected version via Plugin_Upgrader.
		 */
		public function ajax_install_version() {
			check_ajax_referer( $this->nonce_action );

			if ( ! current_user_can( 'update_plugins' ) ) {
				wp_send_json_error( array(
					'message' => __( 'You do not have permission to install plugins.', 'fswwp-update-checker' ),
				) );
			}

			if ( empty( $_POST['download_url'] ) ) {
				wp_send_json_error( array(
					'message' => __( 'No download URL provided.', 'fswwp-update-checker' ),
				) );
			}

			$download_url = esc_url_raw( $_POST['download_url'] );
			$was_active   = is_plugin_active( $this->plugin_basename );

			// Include required WordPress upgrade classes.
			if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
				require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			// 1. Deactivate the plugin if it is active.
			if ( $was_active ) {
				deactivate_plugins( $this->plugin_basename, true );
			}

			// 2. Remove current plugin files.
			$deleted = delete_plugins( array( $this->plugin_basename ) );
			if ( is_wp_error( $deleted ) ) {
				// Re-activate if we deactivated but couldn't delete.
				if ( $was_active ) {
					activate_plugin( $this->plugin_basename );
				}
				wp_send_json_error( array(
					'message' => $deleted->get_error_message(),
				) );
			}

			// 3. Install the selected version.
			$skin     = new \WP_Ajax_Upgrader_Skin();
			$upgrader = new \Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $download_url );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array(
					'message' => $result->get_error_message(),
				) );
			}

			if ( ! $result ) {
				wp_send_json_error( array(
					'message' => __( 'Installation failed. Check filesystem permissions.', 'fswwp-update-checker' ),
				) );
			}

			// 4. Re-activate if it was active before.
			if ( $was_active ) {
				$activated = activate_plugin( $this->plugin_basename );
				if ( is_wp_error( $activated ) ) {
					wp_send_json_error( array(
						'message' => sprintf(
							/* translators: 1: error message */
							__( 'Version installed but could not re-activate: %s', 'fswwp-update-checker' ),
							$activated->get_error_message()
						),
					) );
				}
			}

			wp_send_json_success( array(
				'message' => __( 'Version installed successfully!', 'fswwp-update-checker' ),
				'version' => ! empty( $_POST['version'] ) ? sanitize_text_field( $_POST['version'] ) : '',
			) );
		}

		/**
		 * Enqueue inline JavaScript for the version selector thickbox.
		 *
		 * Only enqueued on the plugins.php admin page.
		 *
		 * @param string $hook_suffix Current admin page.
		 */
		public function enqueue_rollback_script( $hook_suffix ) {
			if ( 'plugins.php' !== $hook_suffix ) {
				return;
			}

			// Ensure thickbox is loaded.
			if ( function_exists( 'add_thickbox' ) ) {
				add_thickbox();
			}

			$ajax_action = 'fswwp_install_version_' . $this->slug;
			$nonce       = wp_create_nonce( $this->nonce_action );

			wp_add_inline_style(
				'common',
				'
				.plugin-title .fswwp-version-selector { margin-left: 8px; }
				#TB_window.thickbox-loading .wrap { opacity: 0.5; }
			'
			);

			wp_add_inline_script(
				'common',
				'
				jQuery( function( $ ) {
					var $doc = $( document );

					// Renomeia "Ver detalhes" → "View Details" (WP traduz
					// conforme locale, mas queremos manter tudo em inglês).
					$doc.find( "a.thickbox.open-plugin-details-modal" ).each( function() {
						var $el = $( this );
						if ( $el.text().trim() !== "View Details" ) {
							$el.text( "View Details" );
						}
					} );

					// Enable install button when a non-current version is selected.
					$doc.on( "change", "input[name=\"fswwp_version\"]", function() {
						$( "#fswwp-install-version" ).prop( "disabled", false );
					} );

					// Handle install button click.
					$doc.on( "click", "#fswwp-install-version", function() {
						var $btn     = $( this );
						var $checked = $( "input[name=\"fswwp_version\"]:checked" );
						if ( ! $checked.length ) return;

						var downloadUrl = $checked.val();
						var version     = $checked.data( "version" );
						var $result     = $( "#fswwp-install-result" );

						$btn.prop( "disabled", true ).text( "Installing…" );
						$result
							.removeClass( "success error" )
							.hide()
							.html( "<p>Downloading and installing version " + version + "…</p>" )
							.show();

						$.post( ajaxurl, {
							action:        "' . $ajax_action . '",
							_ajax_nonce:   "' . $nonce . '",
							download_url:  downloadUrl,
							version:       version
						}, function( response ) {
							if ( response.success ) {
								$result
									.removeClass( "error" )
									.addClass( "success" )
									.html( "<p>" + response.data.message + "</p>" );

								// Reload the plugins page after a short delay
								// so the version number updates in the UI.
								setTimeout( function() {
									self.parent.tb_remove();
									window.location.reload();
								}, 1500 );
							} else {
								$btn.prop( "disabled", false ).text( "Install Selected Version" );
								$result
									.removeClass( "success" )
									.addClass( "error" )
									.html( "<p>" + response.data.message + "</p>" );
							}
						}, "json" ).fail( function() {
							$btn.prop( "disabled", false ).text( "Install Selected Version" );
							$result
								.removeClass( "success" )
								.addClass( "error" )
								.html( "<p>Network error. Please try again.</p>" );
						} );
					} );
				} );
			'
			);
		}

		/**
		 * Log errors internally. Override or extend as needed.
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 */
		private function log_error( $code, $message ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// Translators: %1$s is the error code, %2$s is the error message.
				trigger_error(
					esc_html( sprintf(
						__( '[FSWWP Update Checker - %1$s] %2$s', 'fswwp-update-checker' ),
						$code,
						$message
					) ),
					E_USER_WARNING
				);
			}
		}
	}
}
