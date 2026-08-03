<?php
/**
 * FSWWP Update Checker — Admin UI
 *
 * Responsible for rendering plugin row links and the "Previous Versions"
 * thickbox HTML. Does NOT inject inline <style> — CSS is served via
 * enqueued stylesheets.
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
 * Admin UI — plugin row links and thickbox rendering.
 */
class AdminUi {

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Plugin display name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * URL to the assets directory (with trailing slash).
	 *
	 * @var string
	 */
	private $assets_url;

	/**
	 * Metadata repository.
	 *
	 * @var Metadata
	 */
	private $metadata;

	/**
	 * Update manager.
	 *
	 * @var Updates
	 */
	private $updates;

	/**
	 * Constructor.
	 *
	 * @param string   $slug            Plugin slug.
	 * @param string   $plugin_basename Plugin basename.
	 * @param string   $plugin_name     Plugin display name.
	 * @param string   $assets_url      URL to the assets/ directory.
	 * @param Metadata $metadata        Metadata repository.
	 * @param Updates  $updates         Update manager.
	 */
	public function __construct( $slug, $plugin_basename, $plugin_name, $assets_url, Metadata $metadata, Updates $updates ) {
		$this->slug            = $slug;
		$this->plugin_basename = $plugin_basename;
		$this->plugin_name     = $plugin_name;
		$this->assets_url      = rtrim( $assets_url, '/\\' ) . '/';
		$this->metadata        = $metadata;
		$this->updates         = $updates;
	}

	/**
	 * Add custom links to the plugin's second row (plugin_row_meta filter).
	 *
	 * @param array  $links Existing meta links.
	 * @param string $file  Plugin basename.
	 * @return array
	 */
	public function add_row_links( $links, $file ) {
		if ( $file !== $this->plugin_basename ) {
			return $links;
		}

		// "View Details" — thickbox link.
		$view_url = add_query_arg(
			array(
				'tab'       => 'plugin-information',
				'plugin'    => dirname( $this->plugin_basename ),
				'TB_iframe' => 'true',
				'width'     => 772,
				'height'    => 550,
			),
			admin_url( 'plugin-install.php' )
		);
		$view_link = sprintf(
			'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s">%s</a>',
			esc_url( $view_url ),
			/* translators: %s: plugin name */
			esc_attr( sprintf( __( 'View details for %s', 'fswwp-update-checker' ), $this->plugin_name ) ),
			esc_html__( 'View Details', 'fswwp-update-checker' )
		);

		// Replace any existing "View Details" link.
		$has_view_details = false;
		foreach ( $links as $k => $v ) {
			if ( is_string( $v ) && false !== strpos( $v, 'open-plugin-details-modal' ) ) {
				$links[ $k ]      = $view_link;
				$has_view_details = true;
				break;
			}
		}
		if ( ! $has_view_details ) {
			$links['view-details'] = $view_link;
		}

		// "Check for Updates" — AJAX manual check (JS intercepts the click).
		$links['fswwp-check'] = sprintf(
			'<a href="#" class="fswwp-check-updates" data-action="%s" data-nonce="%s">%s</a>',
			esc_attr( 'fswwp_check_updates_' . $this->slug ),
			esc_attr( wp_create_nonce( 'fswwp_check_updates_' . $this->slug ) ),
			esc_html__( 'Check for Updates', 'fswwp-update-checker' )
		);

		// "Previous Versions" — thickbox modal.
		$prev_url = add_query_arg(
			array(
				'action'    => 'fswwp_previous_versions_' . $this->slug,
				'TB_iframe' => 'true',
				'width'     => 772,
				'height'    => 550,
			),
			admin_url( 'admin-ajax.php' )
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
			esc_html__( 'Previous Versions', 'fswwp-update-checker' )
		);

		return $links;
	}

	/**
	 * Render the "Previous Versions" thickbox HTML.
	 *
	 * @param array  $versions        List of version objects/arrays.
	 * @param string $current_version Currently installed version.
	 * @return void
	 */
	public function render_versions_modal( $versions, $current_version ) {
		?>
		<!DOCTYPE html>
		<html><head>
			<title>
				<?php echo esc_html( $this->plugin_name ); ?>
				&mdash;
				<?php esc_html_e( 'Previous Versions', 'fswwp-update-checker' ); ?>
			</title>
			<?php wp_admin_css( 'common' ); ?>
			<?php wp_admin_css( 'forms' ); ?>
			<?php wp_enqueue_style( 'fswwp-update-checker-admin' ); ?>
			<?php wp_print_styles( 'fswwp-update-checker-admin' ); ?>
		</head><body class="fswwp-rollback-body">
			<h2>
				<?php echo esc_html( $this->plugin_name ); ?>
				&mdash;
				<?php esc_html_e( 'Previous Versions', 'fswwp-update-checker' ); ?>
			</h2>
			<p>
				<?php esc_html_e( 'Select a version to install. The current version is highlighted in green.', 'fswwp-update-checker' ); ?>
			</p>

			<?php if ( empty( $versions ) ) : ?>
				<p><?php esc_html_e( 'No version history available.', 'fswwp-update-checker' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat striped rollback-table">
					<thead>
						<tr>
							<th width="50"><?php esc_html_e( 'Select', 'fswwp-update-checker' ); ?></th>
							<th><?php esc_html_e( 'Version', 'fswwp-update-checker' ); ?></th>
							<th><?php esc_html_e( 'Date', 'fswwp-update-checker' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $versions as $ver ) : ?>
							<?php
							$ver_obj    = is_object( $ver ) ? $ver : (object) $ver;
							$is_current = $current_version
								&& version_compare( $ver_obj->version, $current_version, '=' );
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
										<span class="current-label">
											&mdash; <?php esc_html_e( 'installed', 'fswwp-update-checker' ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $this->format_date( $ver_obj->date ) ); ?></td>
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
	}

	/**
	 * Render a "License Required" thickbox modal.
	 *
	 * Shown when the user attempts to access Previous Versions without
	 * a valid license key.
	 *
	 * @return void
	 */
	public function render_license_required_modal() {
		?>
		<!DOCTYPE html>
		<html><head>
			<title>
				<?php echo esc_html( $this->plugin_name ); ?>
				&mdash;
				<?php esc_html_e( 'License Required', 'fswwp-update-checker' ); ?>
			</title>
			<?php wp_admin_css( 'common' ); ?>
			<?php wp_admin_css( 'forms' ); ?>
			<?php wp_enqueue_style( 'fswwp-update-checker-admin' ); ?>
			<?php wp_print_styles( 'fswwp-update-checker-admin' ); ?>
		</head><body class="fswwp-rollback-body">
			<h2>
				<?php echo esc_html( $this->plugin_name ); ?>
				&mdash;
				<?php esc_html_e( 'License Required', 'fswwp-update-checker' ); ?>
			</h2>
			<p>
				<?php esc_html_e( 'A valid license key is required to access previous versions.', 'fswwp-update-checker' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Please activate your license on the plugin settings page to unlock this feature.', 'fswwp-update-checker' ); ?>
			</p>
			<div class="rollback-footer">
				<button class="button" onclick="self.parent.tb_remove();">
					<?php esc_html_e( 'Close', 'fswwp-update-checker' ); ?>
				</button>
			</div>
		</body></html>
		<?php
	}

	/**
	 * Format a date string for display.
	 *
	 * Converts ISO 8601 or other parseable dates to a human-readable format.
	 * Falls back to the original string if parsing fails.
	 *
	 * @param string $raw_date Raw date string (e.g. "2026-07-14T02:48:02Z").
	 * @return string
	 */
	private function format_date( $raw_date ) {
		if ( empty( $raw_date ) ) {
			return '&mdash;';
		}

		$timestamp = strtotime( $raw_date );

		if ( false === $timestamp ) {
			return esc_html( $raw_date );
		}

		return esc_html(
			wp_date(
				/* translators: date format for version release date */
				__( 'F j, Y', 'fswwp-update-checker' ),
				$timestamp
			)
		);
	}
}
