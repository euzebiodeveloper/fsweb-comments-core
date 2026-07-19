<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://flowsyncweb.com
 * @since      1.0.0
 * @package    Fswwp_Comments_Core
 * @subpackage Fswwp_Comments_Core/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Fswwp_Comments_Core
 * @subpackage Fswwp_Comments_Core/admin
 * @author     Flow Sync Web <euzebio.batista@flowsyncweb.com>
 */
class Fswwp_Comments_Core_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of this plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	// ------------------------------------------------------------------//
	//  Assets
	// ------------------------------------------------------------------//

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string    $hook_suffix    Current admin page hook.
	 */
	public function enqueue_styles( $hook_suffix ) {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/fswwp-comments-core-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string    $hook_suffix    Current admin page hook.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		$is_license_page = 'comments-core_page_fswwp-comments-core-license' === $hook_suffix;

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/fswwp-comments-core-admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		if ( $is_license_page ) {
			wp_localize_script(
				$this->plugin_name,
				'FSWWP_CommentsCore_License',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					// Same nonce action as fswwp-license-checker so AJAX
					// calls are accepted by its handlers.
					'nonce'    => wp_create_nonce( 'fswwp_license_action' ),
					'i18n'     => array(
						'confirm_deactivate' => __( 'Are you sure you want to deactivate this license?', 'fswwp-comments-core' ),
						'activating'         => __( 'Activating…', 'fswwp-comments-core' ),
						'deactivating'       => __( 'Deactivating…', 'fswwp-comments-core' ),
						'empty_key'          => __( 'Please enter a license key.', 'fswwp-comments-core' ),
					),
				)
			);
		}
	}

	// ------------------------------------------------------------------//
	//  Menu
	// ------------------------------------------------------------------//

	/**
	 * Add the main Comments Core admin menu.
	 *
	 * @since    1.0.0
	 */
	public function add_main_menu() {
		add_menu_page(
			__( 'Comments Core', 'fswwp-comments-core' ),
			__( 'Comments Core', 'fswwp-comments-core' ),
			'manage_options',
			'fswwp-comments-core',
			array( $this, 'render_main_page' ),
			'dashicons-testimonial',
			30
		);
	}

	/**
	 * Add the License submenu.
	 *
	 * @since    1.0.0
	 */
	public function add_license_submenu() {
		add_submenu_page(
			'fswwp-comments-core',
			__( 'License', 'fswwp-comments-core' ),
			__( 'License', 'fswwp-comments-core' ),
			'manage_options',
			'fswwp-comments-core-license',
			array( $this, 'render_license_page' )
		);
	}

	/**
	 * Render the main plugin page.
	 *
	 * @since    1.0.0
	 */
	public function render_main_page() {
		$license_status = get_option( 'fswwp_license_status', 'inactive' );
		$license_url    = admin_url( 'admin.php?page=fswwp-comments-core-license' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Comments Core', 'fswwp-comments-core' ); ?></h1>
			<div class="fswwp-cc-welcome">
				<p><?php esc_html_e( 'Welcome to Comments Core — a lightweight, high-performance comment and feedback management system.', 'fswwp-comments-core' ); ?></p>
				<?php if ( 'active' === $license_status ) : ?>
					<div class="notice notice-success inline" style="margin:12px 0;">
						<p><?php esc_html_e( 'Your license is active. You are receiving automatic updates and premium support.', 'fswwp-comments-core' ); ?></p>
					</div>
				<?php else : ?>
					<div class="notice notice-warning inline" style="margin:12px 0;">
						<p><?php esc_html_e( 'No active license. Activate your license to receive automatic updates.', 'fswwp-comments-core' ); ?></p>
						<p><a href="<?php echo esc_url( $license_url ); ?>" class="button button-primary"><?php esc_html_e( 'Activate License', 'fswwp-comments-core' ); ?></a></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the license management page.
	 *
	 * The UI lives here, but activation/deactivation AJAX calls are
	 * handled by fswwp-license-checker (the license "API" plugin).
	 *
	 * @since    1.0.0
	 */
	public function render_license_page() {
		$license_key     = get_option( 'fswwp_license_key', '' );
		$license_status  = get_option( 'fswwp_license_status', 'inactive' );
		$license_expires = get_option( 'fswwp_license_expires', '' );

		?>
		<div class="wrap fswwp-cc-license-wrap">
			<h1><?php esc_html_e( 'Comments Core — License', 'fswwp-comments-core' ); ?></h1>

			<div class="fswwp-cc-license-card">
				<p class="description">
					<?php esc_html_e( 'Enter your license key to receive automatic updates and premium support.', 'fswwp-comments-core' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="fswwp-cc-license-key"><?php esc_html_e( 'License Key', 'fswwp-comments-core' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="fswwp-cc-license-key"
								name="fswwp_license_key"
								class="regular-text"
								value="<?php echo esc_attr( $license_key ); ?>"
								placeholder="<?php esc_attr_e( 'e.g. FSWWP-XXXX-XXXX-XXXX', 'fswwp-comments-core' ); ?>"
								<?php echo 'active' === $license_status ? 'readonly' : ''; ?>
							>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status', 'fswwp-comments-core' ); ?></th>
						<td>
							<span id="fswwp-cc-license-status" class="fswwp-cc-status fswwp-cc-status--<?php echo esc_attr( $license_status ); ?>">
								<?php
								switch ( $license_status ) {
									case 'active':
										esc_html_e( 'Active', 'fswwp-comments-core' );
										break;
									case 'expired':
										esc_html_e( 'Expired', 'fswwp-comments-core' );
										break;
									case 'invalid':
										esc_html_e( 'Invalid', 'fswwp-comments-core' );
										break;
									case 'pending':
										esc_html_e( 'Pending', 'fswwp-comments-core' );
										break;
									default:
										esc_html_e( 'Inactive', 'fswwp-comments-core' );
										break;
								}
								?>
							</span>
						</td>
					</tr>
					<tr id="fswwp-cc-expires-row" style="<?php echo 'active' === $license_status && ! empty( $license_expires ) ? '' : 'display:none;'; ?>">
						<th scope="row"><?php esc_html_e( 'Expires', 'fswwp-comments-core' ); ?></th>
						<td>
							<span id="fswwp-cc-license-expires">
								<?php
								if ( ! empty( $license_expires ) ) {
									echo esc_html( mysql2date( get_option( 'date_format' ), $license_expires ) );
								} else {
									esc_html_e( 'Lifetime', 'fswwp-comments-core' );
								}
								?>
							</span>
						</td>
					</tr>
				</table>

				<p class="submit">
					<?php if ( 'active' === $license_status ) : ?>
						<button type="button" id="fswwp-cc-deactivate-license" class="button button-secondary">
							<?php esc_html_e( 'Deactivate', 'fswwp-comments-core' ); ?>
						</button>
					<?php else : ?>
						<button type="button" id="fswwp-cc-activate-license" class="button button-primary">
							<?php esc_html_e( 'Activate', 'fswwp-comments-core' ); ?>
						</button>
					<?php endif; ?>
				</p>

				<div id="fswwp-cc-license-message" class="notice" style="display:none;"></div>
			</div>
		</div>
		<?php
	}
}
