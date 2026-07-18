<?php
/**
 * FSWWP Update Checker — Metadata Repository
 *
 * Responsible for fetching plugin metadata from a remote JSON endpoint,
 * caching it in wp_options, and validating the response.
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
 * Metadata Repository — fetch, cache and validate remote plugin metadata.
 */
class Metadata {

	/**
	 * Remote JSON metadata URL.
	 *
	 * @var string
	 */
	private $metadata_url;

	/**
	 * Full URL for the details endpoint.
	 *
	 * @var string
	 */
	private $details_url;

	/**
	 * wp_options key for cached metadata.
	 *
	 * @var string
	 */
	private $option_name;

	/**
	 * Plugin slug (used for HTTP args filter).
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Constructor.
	 *
	 * @param string $metadata_url Remote JSON metadata URL.
	 * @param string $slug         Plugin slug.
	 * @param string $option_name  wp_options key for caching.
	 */
	public function __construct( $metadata_url, $slug, $option_name ) {
		$this->metadata_url = esc_url_raw( $metadata_url );
		$this->details_url  = esc_url_raw( rtrim( $metadata_url, '/' ) . '/details' );
		$this->slug         = $slug;
		$this->option_name  = $option_name;
	}

	/**
	 * Force-fetch metadata from the remote endpoint and cache it.
	 *
	 * @return object|null Decoded JSON metadata, or null on failure.
	 */
	public function fetch_and_cache() {
		$response = $this->fetch( $this->metadata_url );

		if ( is_wp_error( $response ) ) {
			$this->log( 'fetch_error', $response->get_error_message() );
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			$this->log( 'http_' . $code, 'Metadata endpoint returned HTTP ' . $code );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! is_object( $body ) || empty( $body->version ) ) {
			$this->log( 'invalid_metadata', 'Response is missing version field or is not valid JSON.' );
			return null;
		}

		update_option( $this->option_name, $body );
		update_option( $this->option_name . '_last_check', time() );

		return $body;
	}

	/**
	 * Get cached metadata from wp_options.
	 *
	 * @return object|null
	 */
	public function get_cached() {
		$metadata = get_option( $this->option_name );
		return is_object( $metadata ) ? $metadata : null;
	}

	/**
	 * Delete cached metadata, details, and last-check timestamp.
	 *
	 * @return void
	 */
	public function clear_cache() {
		delete_option( $this->option_name );
		delete_option( $this->option_name . '_last_check' );
		delete_option( $this->option_name . '_details' );
	}

	/**
	 * Fetch details from the /details endpoint (sections, banners, icons).
	 *
	 * @param bool $force Bypass cache and force a fresh fetch.
	 * @return object|null Object with `sections`, `banners`, `icons` — or null on failure.
	 */
	public function fetch_details( $force = false ) {
		$cache_key = $this->option_name . '_details';

		if ( ! $force ) {
			$cached = get_option( $cache_key );
			if ( is_object( $cached ) ) {
				return $cached;
			}
		}

		$response = $this->fetch( $this->details_url );

		if ( is_wp_error( $response ) ) {
			$this->log( 'details_fetch_error', $response->get_error_message() );
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			$this->log( 'details_http_' . $code, 'Details endpoint returned HTTP ' . $code );
			return null;
		}

		$raw_body = wp_remote_retrieve_body( $response );
		$body     = json_decode( $raw_body );

		if ( ! is_object( $body ) ) {
			$this->log( 'details_invalid_json', 'Details response is not valid JSON.' );
			return null;
		}

		$result           = new \stdClass();
		$result->sections = null;
		$result->banners  = ! empty( $body->banners ) ? $body->banners : null;
		$result->icons    = ! empty( $body->icons ) ? $body->icons : null;

		// Sections may come as a nested object or as top-level fields.
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

		// Cache the result for 1 hour (avoids HTTP on every tab click).
		update_option( $cache_key, $result );

		return $result;
	}

	/**
	 * Get the metadata URL.
	 *
	 * @return string
	 */
	public function get_metadata_url() {
		return $this->metadata_url;
	}

	// ------------------------------------------------------------------//
	//  Private helpers
	// ------------------------------------------------------------------//

	/**
	 * Perform an HTTP GET request to a URL.
	 *
	 * @param string $url URL to fetch.
	 * @return array|\WP_Error
	 */
	private function fetch( $url ) {
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

		return wp_remote_get( $url, $args );
	}

	/**
	 * Log errors when WP_DEBUG is enabled.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @return void
	 */
	private function log( $code, $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error(
				esc_html(
					sprintf(
						/* translators: 1: error code, 2: error message */
						__( '[FSWWP Update Checker - %1$s] %2$s', 'fswwp-update-checker' ),
						$code,
						$message
					)
				),
				E_USER_WARNING
			);
		}
	}
}
