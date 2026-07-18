<?php
/**
 * FSWWP Update Checker — Update Manager
 *
 * Responsible for version comparison, building update data objects,
 * and providing plugin information for the WordPress update system.
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
 * Update Manager — builds update transient and plugins_api response objects.
 */
class Updates {

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
	 * Full path to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Metadata repository.
	 *
	 * @var Metadata
	 */
	private $metadata;

	/**
	 * Constructor.
	 *
	 * @param string   $plugin_basename Plugin basename.
	 * @param string   $slug            Plugin slug.
	 * @param string   $plugin_file     Full path to main plugin file.
	 * @param Metadata $metadata        Metadata repository instance.
	 */
	public function __construct( $plugin_basename, $slug, $plugin_file, Metadata $metadata ) {
		$this->plugin_basename = $plugin_basename;
		$this->slug            = $slug;
		$this->plugin_file     = $plugin_file;
		$this->metadata        = $metadata;
	}

	/**
	 * Inject update data into the site_transient_update_plugins.
	 *
	 * @param \stdClass $transient The update_plugins transient value.
	 * @return \stdClass
	 */
	public function inject_into_transient( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$meta = $this->metadata->get_cached();
		if ( ! $meta || empty( $meta->version ) ) {
			return $transient;
		}

		$current_version = $this->get_installed_version();
		if ( empty( $current_version ) ) {
			return $transient;
		}

		if ( version_compare( $meta->version, $current_version, '>' ) ) {
			$transient->response[ $this->plugin_basename ] = $this->build_update_object( $meta );
			if ( isset( $transient->no_update[ $this->plugin_basename ] ) ) {
				unset( $transient->no_update[ $this->plugin_basename ] );
			}
		} else {
			$transient->no_update[ $this->plugin_basename ] = $this->build_no_update_object( $meta );
			if ( isset( $transient->response[ $this->plugin_basename ] ) ) {
				unset( $transient->response[ $this->plugin_basename ] );
			}
		}

		return $transient;
	}

	/**
	 * Build the update object for the transient `response` array.
	 *
	 * @param object $meta Decoded JSON metadata.
	 * @return \stdClass
	 */
	public function build_update_object( $meta ) {
		$update                  = new \stdClass();
		$update->id              = $this->plugin_basename;
		$update->slug            = $this->slug;
		$update->plugin          = $this->plugin_basename;
		$update->new_version     = $meta->version;
		$update->url             = ! empty( $meta->homepage ) ? $meta->homepage : '';
		$update->package         = ! empty( $meta->download_url ) ? $meta->download_url : '';
		$update->requires        = ! empty( $meta->requires ) ? $meta->requires : '';
		$update->tested          = ! empty( $meta->tested ) ? $meta->tested : '';
		$update->requires_php    = ! empty( $meta->requires_php ) ? $meta->requires_php : '';
		$update->compatibility   = new \stdClass();
		$update->icons           = ! empty( $meta->icons ) ? (array) $meta->icons : array();
		$update->banners         = ! empty( $meta->banners ) ? (array) $meta->banners : array();
		$update->banners_rtl     = ! empty( $meta->banners_rtl ) ? (array) $meta->banners_rtl : array();

		if ( ! empty( $meta->translations ) ) {
			$update->translations = (array) $meta->translations;
		}

		return $update;
	}

	/**
	 * Build a "no_update" entry for the transient.
	 *
	 * @param object $meta Decoded JSON metadata.
	 * @return \stdClass
	 */
	public function build_no_update_object( $meta ) {
		$item                  = new \stdClass();
		$item->id              = $this->plugin_basename;
		$item->slug            = $this->slug;
		$item->plugin          = $this->plugin_basename;
		$item->new_version     = $meta->version;
		$item->url             = ! empty( $meta->homepage ) ? $meta->homepage : '';
		$item->package         = '';
		$item->requires        = ! empty( $meta->requires ) ? $meta->requires : '';
		$item->tested          = ! empty( $meta->tested ) ? $meta->tested : '';
		$item->requires_php    = ! empty( $meta->requires_php ) ? $meta->requires_php : '';
		$item->compatibility   = new \stdClass();
		$item->icons           = array();
		$item->banners         = array();
		$item->banners_rtl     = array();

		return $item;
	}

	/**
	 * Build the plugin_info object for the plugins_api response.
	 *
	 * @param object      $meta     Decoded JSON metadata.
	 * @param array|null  $sections Optional sections array.
	 * @param object|null $banners  Optional banners object.
	 * @param object|null $icons    Optional icons object.
	 * @return \stdClass
	 */
	public function build_plugin_info( $meta, $sections = null, $banners = null, $icons = null ) {
		$info                   = new \stdClass();
		$info->name             = ! empty( $meta->name ) ? $meta->name : $this->slug;
		$info->slug             = $this->slug;
		$info->version          = $meta->version;
		$info->author           = ! empty( $meta->author ) ? $meta->author : '';
		$info->author_profile   = ! empty( $meta->author_profile ) ? $meta->author_profile : '';
		$info->requires         = ! empty( $meta->requires ) ? $meta->requires : '';
		$info->tested           = ! empty( $meta->tested ) ? $meta->tested : '';
		$info->requires_php     = ! empty( $meta->requires_php ) ? $meta->requires_php : '';
		$info->homepage         = ! empty( $meta->homepage ) ? $meta->homepage : '';
		$info->download_link    = ! empty( $meta->download_url ) ? $meta->download_url : '';
		$info->last_updated     = ! empty( $meta->last_updated ) ? $meta->last_updated : '';

		if ( null !== $banners ) {
			$info->banners = (array) $banners;
		} elseif ( ! empty( $meta->banners ) ) {
			$info->banners = (array) $meta->banners;
		} else {
			$info->banners = array();
		}

		if ( null !== $icons ) {
			$info->icons = (array) $icons;
		} elseif ( ! empty( $meta->icons ) ) {
			$info->icons = (array) $meta->icons;
		} else {
			$info->icons = array();
		}

		$info->sections = ( null !== $sections && is_array( $sections ) )
			? $sections
			: array();

		return $info;
	}

	/**
	 * Check whether our slug matches the request.
	 *
	 * @param string $requested_slug The slug being requested.
	 * @return bool
	 */
	public function is_our_slug( $requested_slug ) {
		return $requested_slug === $this->slug
			|| $requested_slug === dirname( $this->plugin_basename );
	}

	/**
	 * Get the currently installed plugin version from plugin headers.
	 *
	 * @return string|null
	 */
	public function get_installed_version() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data( $this->plugin_file, false, false );

		return ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : null;
	}

	/**
	 * Get the plugin display name from plugin headers.
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$data = get_plugin_data( $this->plugin_file, false, false );

		return ! empty( $data['Name'] ) ? $data['Name'] : $this->slug;
	}

	/**
	 * Get the plugin basename.
	 *
	 * @return string
	 */
	public function get_plugin_basename() {
		return $this->plugin_basename;
	}

	/**
	 * Get the plugin slug.
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Build screenshots HTML from local plugin images.
	 *
	 * Scans {plugin_dir}/includes/assets/images/ for .webp, .png, .jpg, .jpeg
	 * files and returns the WordPress-expected <ol> markup.
	 *
	 * @return string HTML or empty string if no images found.
	 */
	public function build_local_screenshots() {
		$plugin_dir = plugin_dir_path( $this->plugin_file );
		$images_dir = $plugin_dir . 'includes/assets/images/';

		if ( ! is_dir( $images_dir ) ) {
			return '';
		}

		$plugin_url = plugin_dir_url( $this->plugin_file );
		$images_url = $plugin_url . 'includes/assets/images/';

		$extensions = array( 'webp', 'png', 'jpg', 'jpeg' );
		$files      = array();

		foreach ( $extensions as $ext ) {
			$found = glob( $images_dir . 'screenshot-*.' . $ext );
			if ( is_array( $found ) ) {
				$files = array_merge( $files, $found );
			}
		}

		if ( empty( $files ) ) {
			return '';
		}

		sort( $files );

		$html = '<ol>';
		foreach ( $files as $file ) {
			$filename = basename( $file );
			$alt      = pathinfo( $filename, PATHINFO_FILENAME );
			$url      = $images_url . $filename;

			$html .= sprintf(
				'<li><a href="%1$s" target="_blank"><img src="%1$s" alt="%2$s"></a></li>',
				esc_url( $url ),
				esc_attr( $alt )
			);
		}
		$html .= '</ol>';

		return $html;
	}
}
