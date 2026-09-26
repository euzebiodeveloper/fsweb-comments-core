<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://flowsyncweb.com
 * @since      1.0.0
 * @package    Fsweb_Comments_Core
 * @subpackage Fsweb_Comments_Core/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Fsweb_Comments_Core
 * @subpackage Fsweb_Comments_Core/admin
 * @author     Flow Sync Web <euzebio.batista@flowsyncweb.com>
 */
class Fsweb_Comments_Core_Admin {

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
			plugin_dir_url( __FILE__ ) . 'css/fsweb-comments-core-admin.css',
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
		$is_license_page = 'fsweb_review_page_fsweb-comments-core-license' === $hook_suffix;
		$is_builder_page = 'fsweb_review_page_fsweb-comments-core-builder' === $hook_suffix;

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/fsweb-comments-core-admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		if ( $is_license_page ) {
			wp_localize_script(
				$this->plugin_name,
				'FSWEB_CommentsCore_License',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'fsweb_license_action' ),
					'i18n'     => array(
						'confirm_deactivate' => __( 'Are you sure you want to deactivate this license?', 'fsweb-comments-core' ),
						'activating'         => __( 'Activating…', 'fsweb-comments-core' ),
						'deactivating'       => __( 'Deactivating…', 'fsweb-comments-core' ),
						'empty_key'          => __( 'Please enter a license key.', 'fsweb-comments-core' ),
					),
				)
			);
		}

		if ( $is_builder_page ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script(
				'fsweb-cc-builder',
				plugin_dir_url( __FILE__ ) . 'js/fsweb-comments-core-builder.js',
				array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable' ),
				$this->version,
				true
			);
		}

		if ( 'fsweb_review_page_fsweb-comments-core-views' === $hook_suffix ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-droppable' );
			wp_enqueue_script(
				'fsweb-cc-views',
				plugin_dir_url( __FILE__ ) . 'js/fsweb-comments-core-views.js',
				array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ),
				$this->version,
				true
			);
		}
	}

	// ------------------------------------------------------------------//
	//  Menu
	// ------------------------------------------------------------------//

	/**
	 * Add submenu pages under the Reviews CPT.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		$parent = 'edit.php?post_type=fsweb_review';

		// Dashboard (overview).
		add_submenu_page(
			$parent,
			__( 'Dashboard', 'fsweb-comments-core' ),
			__( 'Dashboard', 'fsweb-comments-core' ),
			'manage_options',
			'fsweb-comments-core',
			array( $this, 'render_main_page' )
		);

		// Form Builder.
		add_submenu_page(
			$parent,
			__( 'Form Builder', 'fsweb-comments-core' ),
			__( 'Form Builder', 'fsweb-comments-core' ),
			'manage_options',
			'fsweb-comments-core-builder',
			array( $this, 'render_builder_page' )
		);

		// Views Builder.
		add_submenu_page(
			$parent,
			__( 'Views Builder', 'fsweb-comments-core' ),
			__( 'Views Builder', 'fsweb-comments-core' ),
			'manage_options',
			'fsweb-comments-core-views',
			array( $this, 'render_views_page' )
		);

		// License.
		add_submenu_page(
			$parent,
			__( 'License', 'fsweb-comments-core' ),
			__( 'License', 'fsweb-comments-core' ),
			'manage_options',
			'fsweb-comments-core-license',
			array( $this, 'render_license_page' )
		);
	}

	/**
	 * Render the Dashboard page.
	 *
	 * @since    1.0.0
	 */
	public function render_main_page() {
		// Stats.
		$total_reviews = wp_count_posts( 'fsweb_review' );
		$total         = isset( $total_reviews->publish ) ? $total_reviews->publish : 0;

		$month_reviews = new \WP_Query( array(
			'post_type'      => 'fsweb_review',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'date_query'     => array(
				array( 'after' => 'first day of this month' ),
			),
			'no_found_rows'  => true,
		) );
		$month_count = $month_reviews->found_posts;

		$categories = wp_count_terms( array(
			'taxonomy' => 'fsweb_review_category',
			'hide_empty' => false,
		) );
		if ( is_wp_error( $categories ) ) {
			$categories = 0;
		}

		// Recent reviews.
		$recent = new \WP_Query( array(
			'post_type'      => 'fsweb_review',
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'no_found_rows'  => true,
		) );

		$license_status = get_option( 'fsweb_comments_core_license_status', 'inactive' );
		$license_url    = admin_url( 'admin.php?page=fsweb-comments-core-license&post_type=fsweb_review' );
		?>
		<div class="wrap fsweb-cc-wrap fsweb-cc-dashboard">
			<hr class="wp-header-end">
			<div class="fsweb-cc-hero">
				<h1><?php esc_html_e( 'Dashboard', 'fsweb-comments-core' ); ?></h1>
				<p><?php esc_html_e( 'Overview of your reviews, categories and license status.', 'fsweb-comments-core' ); ?></p>
			</div>

			<!-- Stats cards -->
			<div class="fsweb-cc-stats">
				<div class="fsweb-cc-stat-card">
					<span class="fsweb-cc-stat-number"><?php echo esc_html( $total ); ?></span>
					<span class="fsweb-cc-stat-label"><?php esc_html_e( 'Total Reviews', 'fsweb-comments-core' ); ?></span>
				</div>
				<div class="fsweb-cc-stat-card">
					<span class="fsweb-cc-stat-number"><?php echo esc_html( $month_count ); ?></span>
					<span class="fsweb-cc-stat-label"><?php esc_html_e( 'This Month', 'fsweb-comments-core' ); ?></span>
				</div>
				<div class="fsweb-cc-stat-card">
					<span class="fsweb-cc-stat-number"><?php echo esc_html( $categories ); ?></span>
					<span class="fsweb-cc-stat-label"><?php esc_html_e( 'Categories', 'fsweb-comments-core' ); ?></span>
				</div>
				<div class="fsweb-cc-stat-card fsweb-cc-stat-card--<?php echo esc_attr( $license_status ); ?>">
					<span class="fsweb-cc-stat-number">
						<?php echo 'active' === $license_status
							? esc_html__( 'Active', 'fsweb-comments-core' )
							: esc_html__( 'Inactive', 'fsweb-comments-core' ); ?>
					</span>
					<span class="fsweb-cc-stat-label"><?php esc_html_e( 'License', 'fsweb-comments-core' ); ?></span>
				</div>
			</div>

			<div class="fsweb-cc-dash-grid">
				<!-- Recent reviews -->
				<div class="fsweb-cc-dash-panel">
					<div class="fsweb-cc-dash-panel-header">
						<h2><?php esc_html_e( 'Recent Reviews', 'fsweb-comments-core' ); ?></h2>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=fsweb_review' ) ); ?>" class="button button-small">
							<?php esc_html_e( 'View All', 'fsweb-comments-core' ); ?>
						</a>
					</div>

					<?php if ( $recent->have_posts() ) : ?>
						<table class="wp-list-table widefat fixed striped fsweb-cc-recent-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Review', 'fsweb-comments-core' ); ?></th>
									<th><?php esc_html_e( 'Date', 'fsweb-comments-core' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php while ( $recent->have_posts() ) : $recent->the_post(); ?>
									<tr>
										<td>
											<strong>
												<a href="<?php echo esc_url( get_edit_post_link() ); ?>">
													<?php the_title(); ?>
												</a>
											</strong>
											<?php if ( has_post_thumbnail() ) : ?>
												<span class="fsweb-cc-has-thumb" title="<?php esc_attr_e( 'Has photo', 'fsweb-comments-core' ); ?>">📷</span>
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( get_the_date() ); ?></td>
									</tr>
								<?php endwhile; ?>
								<?php wp_reset_postdata(); ?>
							</tbody>
						</table>
					<?php else : ?>
						<p class="fsweb-cc-empty">
							<?php esc_html_e( 'No reviews yet. Create your first review to get started!', 'fsweb-comments-core' ); ?>
						</p>
					<?php endif; ?>
				</div>

				<!-- Quick actions -->
				<div class="fsweb-cc-dash-panel">
					<div class="fsweb-cc-dash-panel-header">
						<h2><?php esc_html_e( 'Quick Actions', 'fsweb-comments-core' ); ?></h2>
					</div>
					<div class="fsweb-cc-quick-actions">
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=fsweb_review' ) ); ?>" class="button button-primary button-large">
							<?php esc_html_e( 'Add New Review', 'fsweb-comments-core' ); ?>
						</a>
						<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=fsweb_review_category&post_type=fsweb_review' ) ); ?>" class="button button-large">
							<?php esc_html_e( 'Manage Categories', 'fsweb-comments-core' ); ?>
						</a>
						<a href="<?php echo esc_url( $license_url ); ?>" class="button button-large">
							<?php echo 'active' === $license_status
								? esc_html__( 'License Settings', 'fsweb-comments-core' )
								: esc_html__( 'Activate License', 'fsweb-comments-core' ); ?>
						</a>
					</div>

					<div class="fsweb-cc-shortcode-help">
						<h3><?php esc_html_e( 'Shortcodes', 'fsweb-comments-core' ); ?></h3>
						<p><?php esc_html_e( 'Use these shortcodes on any page or post:', 'fsweb-comments-core' ); ?></p>
						<p>
							<strong><?php esc_html_e( 'Display reviews:', 'fsweb-comments-core' ); ?></strong>
							<code>[fsweb_reviews id="view-id"]</code>
						</p>
						<p>
							<strong><?php esc_html_e( 'Review form:', 'fsweb-comments-core' ); ?></strong>
							<code>[fsweb_review_form id="form-id"]</code>
						</p>
						<p class="description">
							<?php esc_html_e( 'Create views and forms in Views Builder and Form Builder. Each gets a unique shortcode — just copy and paste.', 'fsweb-comments-core' ); ?>
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the license management page.
	 *
	 * The UI lives here, but activation/deactivation AJAX calls are
	 * handled by fsweb-license-checker (the license "API" plugin).
	 *
	 * @since    1.0.0
	 */
	public function render_license_page() {
		$license_key     = get_option( 'fsweb_comments_core_license_key', '' );
		$license_status  = get_option( 'fsweb_comments_core_license_status', 'inactive' );
		$license_expires = get_option( 'fsweb_comments_core_license_expires', '' );

		// Always sync with the license server on page load so the status
		// reflects reality — another site may have activated/deactivated
		// the same license key since the last local check.
		if ( ! empty( $license_key ) ) {
			$synced = $this->sync_license_status( $license_key );
			if ( $synced ) {
				$license_status  = $synced['status'];
				$license_expires = $synced['expires'];
			}
		}

		// Human readable label for the current status (scripts update it live).
		switch ( $license_status ) {
			case 'active':
				$status_label = __( 'Active', 'fsweb-comments-core' );
				break;
			case 'expired':
				$status_label = __( 'Expired', 'fsweb-comments-core' );
				break;
			case 'invalid':
				$status_label = __( 'Invalid', 'fsweb-comments-core' );
				break;
			case 'pending':
				$status_label = __( 'Pending', 'fsweb-comments-core' );
				break;
			default:
				$status_label = __( 'Inactive', 'fsweb-comments-core' );
				break;
		}

		$has_expiry  = ( 'active' === $license_status && ! empty( $license_expires ) );
		$show_verify = in_array( $license_status, array( 'expired', 'pending', 'invalid' ), true );
		$expires_on  = ! empty( $license_expires )
			? mysql2date( get_option( 'date_format' ), $license_expires )
			: __( 'Lifetime', 'fsweb-comments-core' );
		?>
		<div class="wrap fsweb-cc-wrap fsweb-cc-license-wrap">
			<hr class="wp-header-end">
			<div class="fsweb-cc-hero">
				<h1><?php esc_html_e( 'Comments Core — License', 'fsweb-comments-core' ); ?></h1>
				<p><?php esc_html_e( 'Manage your license key to receive automatic updates and premium support.', 'fsweb-comments-core' ); ?></p>
			</div>

			<div class="fsweb-cc-license-grid">
				<div class="fsweb-cc-card">
					<div class="fsweb-cc-card__header">
						<h2>
							<span class="dashicons dashicons-awards" aria-hidden="true"></span>
							<?php esc_html_e( 'License Key', 'fsweb-comments-core' ); ?>
						</h2>
					</div>
					<div class="fsweb-cc-card__body">
						<div class="fsweb-cc-grid">
							<div class="fsweb-cc-field fsweb-cc-field--full">
								<label for="fsweb-cc-license-key"><?php esc_html_e( 'License Key', 'fsweb-comments-core' ); ?></label>
								<input type="text" id="fsweb-cc-license-key" name="fsweb_comments_core_license_key" class="regular-text"
									value="<?php echo esc_attr( $license_key ); ?>"
									placeholder="<?php esc_attr_e( 'e.g. FSWEB-XXXX-XXXX-XXXX', 'fsweb-comments-core' ); ?>"
									<?php echo 'active' === $license_status ? 'readonly' : ''; ?>>
								<p class="description"><?php esc_html_e( 'Enter your license key to receive automatic updates and premium support.', 'fsweb-comments-core' ); ?></p>
							</div>
							<div class="fsweb-cc-field">
								<span class="fsweb-cc-field-label"><?php esc_html_e( 'Status', 'fsweb-comments-core' ); ?></span>
								<span id="fsweb-cc-license-status" class="fsweb-cc-status fsweb-cc-status--<?php echo esc_attr( sanitize_html_class( $license_status ) ); ?>"><?php echo esc_html( $status_label ); ?></span>
							</div>
							<div class="fsweb-cc-field" id="fsweb-cc-expires-row" style="<?php echo $has_expiry ? '' : 'display:none;'; ?>">
								<span class="fsweb-cc-field-label"><?php esc_html_e( 'Expires', 'fsweb-comments-core' ); ?></span>
								<span id="fsweb-cc-license-expires"><?php echo esc_html( $expires_on ); ?></span>
							</div>
						</div>

						<div class="submit fsweb-cc-license-actions">
							<?php
							/*
							 * The action buttons carry their Dashicons through CSS
							 * pseudo-elements: the license script replaces the button
							 * text while a request is in flight, which would wipe out a
							 * nested <span class="dashicons">.
							 */
							?>
							<button type="button" id="fsweb-cc-activate-license" class="button button-primary" style="<?php echo 'active' === $license_status ? 'display:none;' : ''; ?>"><?php esc_html_e( 'Activate', 'fsweb-comments-core' ); ?></button>
							<button type="button" id="fsweb-cc-deactivate-license" class="button button-secondary" style="<?php echo 'active' === $license_status ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Deactivate', 'fsweb-comments-core' ); ?></button>
							<button type="button" id="fsweb-cc-validate-license" class="button button-secondary" style="<?php echo $show_verify ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Verify License', 'fsweb-comments-core' ); ?></button>
							<span id="fsweb-cc-validate-spinner" class="spinner" style="display:none; float:none; margin-top:0;"></span>
						</div>
						<div id="fsweb-cc-license-message" class="notice" style="display:none;"></div>
					</div>
				</div>

				<div class="fsweb-cc-card">
					<div class="fsweb-cc-card__header">
						<h2>
							<span class="dashicons dashicons-editor-help" aria-hidden="true"></span>
							<?php esc_html_e( 'How it works', 'fsweb-comments-core' ); ?>
						</h2>
					</div>
					<div class="fsweb-cc-card__body">
						<ul class="fsweb-cc-help-list">
							<li>
								<span class="dashicons dashicons-yes" aria-hidden="true"></span>
								<span><?php esc_html_e( 'Activate: paste the key and we validate it on the FSWEB license server.', 'fsweb-comments-core' ); ?></span>
							</li>
							<li>
								<span class="dashicons dashicons-lock" aria-hidden="true"></span>
								<span><?php esc_html_e( 'Active: automatic updates and premium support are unlocked for this site.', 'fsweb-comments-core' ); ?></span>
							</li>
							<li>
								<span class="dashicons dashicons-search" aria-hidden="true"></span>
								<span><?php esc_html_e( 'Verify: re-checks the key with the server — use it after moving or restoring the site.', 'fsweb-comments-core' ); ?></span>
							</li>
							<li>
								<span class="dashicons dashicons-no" aria-hidden="true"></span>
								<span><?php esc_html_e( 'Deactivate: releases the key so you can use it on another site.', 'fsweb-comments-core' ); ?></span>
							</li>
						</ul>
						<p class="fsweb-cc-hint">
							<span class="dashicons dashicons-shield" aria-hidden="true"></span>
							<span><?php esc_html_e( 'The key is stored in this site\'s options and validated with the FSWEB license server every time you open this page.', 'fsweb-comments-core' ); ?></span>
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler — activate a license key.
	 *
	 * Calls the FSWEB license API to validate and activate the key.
	 *
	 * @since    1.0.0
	 */
	public function ajax_activate_license() {
		check_ajax_referer( 'fsweb_license_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fsweb-comments-core' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		// phpcs:enable

		if ( empty( $license_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a license key.', 'fsweb-comments-core' ) ) );
		}

		// If this exact key is already active locally, don't bother the server.
		$local_status = get_option( 'fsweb_comments_core_license_status', '' );
		$local_key    = get_option( 'fsweb_comments_core_license_key', '' );
		if ( 'active' === $local_status && $local_key === $license_key ) {
			wp_send_json_success( array(
				'message' => __( 'License is already active.', 'fsweb-comments-core' ),
				'status'  => 'active',
				'expires' => get_option( 'fsweb_comments_core_license_expires', '' ),
			) );
		}

		$api_url = FSWEB_LICENSE_API_URL . '/activate';

		$response = wp_remote_post( $api_url, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => wp_json_encode( array(
				'license_key' => $license_key,
				'domain'      => home_url(),
				'plugin_slug' => 'fsweb-comments-core',
			) ),
			'headers'   => array( 'Content-Type' => 'application/json' ),
		) );

		if ( is_wp_error( $response ) ) {
			update_option( 'fsweb_comments_core_license_key', $license_key );
			update_option( 'fsweb_comments_core_license_status', 'pending' );
			update_option( 'fsweb_comments_core_license_expires', '' );
			wp_send_json_error( array(
				'message' => __( 'Could not reach the license server. Your key has been saved and is pending validation.', 'fsweb-comments-core' ),
				'status'  => 'pending',
			) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 === $code && isset( $body['success'] ) && $body['success'] ) {
			update_option( 'fsweb_comments_core_license_key', $license_key );
			update_option( 'fsweb_comments_core_license_status', 'active' );
			update_option( 'fsweb_comments_core_license_expires', isset( $body['expires'] ) ? sanitize_text_field( $body['expires'] ) : '' );

			// A (re)activation means the previous key is no longer valid — drop
			// every update-checker cache so stale license/server errors recorded
			// under the old key are not served again.
			if ( function_exists( 'fsweb_comments_core_reset_update_checker_cache' ) ) {
				fsweb_comments_core_reset_update_checker_cache();
			}

			wp_send_json_success( array(
				'message' => __( 'License activated successfully!', 'fsweb-comments-core' ),
				'status'  => 'active',
				'expires' => isset( $body['expires'] ) ? $body['expires'] : '',
			) );
		}

		// Activation failed.
		update_option( 'fsweb_comments_core_license_key', $license_key );
		update_option( 'fsweb_comments_core_license_status', 'invalid' );
		update_option( 'fsweb_comments_core_license_expires', '' );
		$error_msg = isset( $body['message'] ) ? sanitize_text_field( $body['message'] ) : __( 'Invalid license key.', 'fsweb-comments-core' );
		wp_send_json_error( array(
			'message' => $error_msg,
			'status'  => 'invalid',
		) );
	}

	/**
	 * AJAX handler — deactivate a license key.
	 *
	 * Calls the FSWEB license API to deactivate the key.
	 *
	 * @since    1.0.0
	 */
	public function ajax_deactivate_license() {
		check_ajax_referer( 'fsweb_license_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fsweb-comments-core' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		// phpcs:enable

		$api_url = FSWEB_LICENSE_API_URL . '/deactivate';

		$response = wp_remote_post( $api_url, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => wp_json_encode( array(
				'license_key' => $license_key,
				'domain'      => home_url(),
				'plugin_slug' => 'fsweb-comments-core',
			) ),
			'headers'   => array( 'Content-Type' => 'application/json' ),
		) );

		// Always deactivate locally, even if the API call fails.
		update_option( 'fsweb_comments_core_license_status', 'pending' );
		update_option( 'fsweb_comments_core_license_expires', '' );

		if ( is_wp_error( $response ) ) {
			wp_send_json_success( array(
				'message' => __( 'License deactivated locally. The remote server could not be reached.', 'fsweb-comments-core' ),
				'status'  => 'pending',
			) );
		}

		wp_send_json_success( array(
			'message' => __( 'License deactivated successfully!', 'fsweb-comments-core' ),
			'status'  => 'pending',
		) );
	}

	/**
	 * AJAX handler — validate/sync license status with the remote server.
	 *
	 * Does NOT activate or deactivate — only fetches the real status
	 * from the license-checker and updates local wp_options to match.
	 *
	 * @since    1.1.0
	 */
	public function ajax_validate_license() {
		check_ajax_referer( 'fsweb_license_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fsweb-comments-core' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$input_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		// phpcs:enable

		// Use the key from the input field if provided, otherwise fall back to the saved key.
		$license_key = ! empty( $input_key ) ? $input_key : get_option( 'fsweb_comments_core_license_key', '' );

		if ( empty( $license_key ) ) {
			wp_send_json_error( array( 'message' => __( 'No license key saved. Please enter a key first.', 'fsweb-comments-core' ) ) );
		}

		$api_url = FSWEB_LICENSE_API_URL . '/validate?license_key=' . urlencode( $license_key ) . '&plugin_slug=fsweb-comments-core&domain=' . urlencode( home_url() );

		$response = wp_remote_get( $api_url, array(
			'timeout'   => 15,
			'sslverify' => false,
		) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array(
				'message' => __( 'Could not reach the license server.', 'fsweb-comments-core' ),
			) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $body || ! isset( $body['status'] ) ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid response from license server.', 'fsweb-comments-core' ),
			) );
		}

		$remote_status  = sanitize_text_field( $body['status'] );
		$remote_expires = isset( $body['expires'] ) ? sanitize_text_field( $body['expires'] ) : '';

		// Sync local options with remote truth.
		update_option( 'fsweb_comments_core_license_status', $remote_status );
		update_option( 'fsweb_comments_core_license_expires', $remote_expires );

		wp_send_json_success( array(
			'message' => $this->status_message( $remote_status, $remote_expires ),
			'status'  => $remote_status,
			'expires' => $remote_expires,
		) );
	}

	/**
	 * Human-readable message for a given license status.
	 *
	 * @since  1.1.0
	 * @param  string $status  License status.
	 * @param  string $expires Expiration date (optional).
	 * @return string
	 */
	private function status_message( $status, $expires = '' ) {
		switch ( $status ) {
			case 'active':
				return __( 'License is active and valid.', 'fsweb-comments-core' );
			case 'expired':
				return __( 'License has expired. Please renew to continue receiving updates.', 'fsweb-comments-core' );
			case 'invalid':
				return __( 'License key is invalid.', 'fsweb-comments-core' );
			case 'pending':
				return __( 'License is pending activation.', 'fsweb-comments-core' );
				return __( 'License was deactivated. Activate it again to resume updates.', 'fsweb-comments-core' );
			default:
				return __( 'License is not active.', 'fsweb-comments-core' );
		}
	}

	/**
	 * Sync local license status with the remote server.
	 *
	 * Called on page load so the UI always reflects reality — another site
	 * may have activated or deactivated the same license key since the
	 * last local check.
	 *
	 * @since  1.2.0
	 * @param  string $license_key The license key to check.
	 * @return array|null           Array with status/expires, or null on failure.
	 */
	private function sync_license_status( $license_key ) {
		$api_url = FSWEB_LICENSE_API_URL . '/validate?license_key=' . urlencode( $license_key ) . '&plugin_slug=fsweb-comments-core&domain=' . urlencode( home_url() );

		$response = wp_remote_get( $api_url, array(
			'timeout'   => 10,
			'sslverify' => false,
		) );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || ! isset( $body['status'] ) ) {
			return null;
		}

		$remote_status   = sanitize_text_field( $body['status'] );
		$remote_expires  = isset( $body['expires'] ) ? sanitize_text_field( $body['expires'] ) : '';

		update_option( 'fsweb_comments_core_license_status', $remote_status );
		update_option( 'fsweb_comments_core_license_expires', $remote_expires );

		return array(
			'status'  => $remote_status,
			'expires' => $remote_expires,
		);
	}

	/**
	 * Render the Form Builder page.
	 *
	 * Two modes:
	 *   - List (default) — shows saved templates with edit/copy/delete.
	 *   - Builder — when ?new=1 or ?form_id=xxx, shows the drag-and-drop builder.
	 *
	 * @since    1.0.0
	 */
	public function render_builder_page() {
		$forms   = get_option( 'fsweb_review_forms', array() );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET params for display routing only.
		$edit_id = isset( $_GET['form_id'] ) ? sanitize_key( wp_unslash( $_GET['form_id'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET params for display routing only.
		$is_new  = isset( $_GET['new'] ) && '1' === $_GET['new'];

		// --- Builder mode --------------------------------------------------
		if ( $is_new || '' !== $edit_id ) {
			$this->render_form_builder_editor( $forms, $edit_id, $is_new );
			return;
		}

		// --- List mode -----------------------------------------------------
		// Auto-create default form if none exist.
		if ( empty( $forms ) ) {
			$forms = $this->maybe_create_default_form();
		}

		$new_url = add_query_arg( array( 'new' => '1' ), remove_query_arg( 'form_id' ) );
		?>
		<div class="wrap fsweb-cc-wrap fsweb-cc-builder-wrap">
			<hr class="wp-header-end">
			<div class="fsweb-cc-hero">
				<h1><?php esc_html_e( 'Form Builder', 'fsweb-comments-core' ); ?></h1>
				<p><?php esc_html_e( 'Create and customize your review forms.', 'fsweb-comments-core' ); ?></p>
			</div>

			<div class="fsweb-cc-list-toolbar">
				<a href="<?php echo esc_url( $new_url ); ?>" class="button button-primary">
					<span class="dashicons dashicons-plus-alt" style="vertical-align:text-bottom;"></span>
					<?php esc_html_e( 'New Form', 'fsweb-comments-core' ); ?>
				</a>
			</div>

			<?php if ( ! empty( $forms ) ) : ?>
				<table class="wp-list-table widefat fixed striped fsweb-cc-templates-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Form Name', 'fsweb-comments-core' ); ?></th>
							<th><?php esc_html_e( 'Shortcode', 'fsweb-comments-core' ); ?></th>
							<th><?php esc_html_e( 'Fields', 'fsweb-comments-core' ); ?></th>
							<th style="width:100px;"></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $forms as $fid => $form ) :
								$field_count = isset( $form['fields'] ) ? count( $form['fields'] ) : 0;
								$is_multi    = ! empty( $form['multi_step'] );
								$step_count  = ( $is_multi && ! empty( $form['steps'] ) ) ? count( $form['steps'] ) : 0;
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $form['name'] ); ?></strong>
									<?php if ( $is_multi ) : ?>
										<span class="fsweb-cc-multi-badge">Multi-step</span>
									<?php endif; ?>
								</td>
								<td>
									<div class="fsweb-cc-shortcode-copy-wrap">
										<code class="fsweb-cc-shortcode-copy">[fsweb_review_form id="<?php echo esc_attr( $fid ); ?>"]</code>
										<button type="button" class="button button-small fsweb-cc-copy-btn" data-code="[fsweb_review_form id=&quot;<?php echo esc_attr( $fid ); ?>&quot;]" title="<?php esc_attr_e( 'Copy shortcode', 'fsweb-comments-core' ); ?>">
											<span class="dashicons dashicons-clipboard"></span>
										</button>
									</div>
								</td>
								<td><?php echo (int) ( $is_multi ? $step_count . ' steps' : $field_count . ' fields' ); ?></td>
								<td>
									<div class="fsweb-cc-action-buttons">
										<a href="<?php echo esc_url( add_query_arg( 'form_id', $fid, remove_query_arg( 'new' ) ) ); ?>" class="button button-small fsweb-cc-edit-btn" title="<?php esc_attr_e( 'Edit', 'fsweb-comments-core' ); ?>">
											<span class="dashicons dashicons-edit"></span>
										</a>
										<button type="button" class="button button-small fsweb-cc-delete-form" data-id="<?php echo esc_attr( $fid ); ?>" title="<?php esc_attr_e( 'Delete', 'fsweb-comments-core' ); ?>">
											<span class="dashicons dashicons-trash"></span>
										</button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="fsweb-cc-empty-state">
					<p><?php esc_html_e( 'No forms yet. Create your first form to start collecting reviews!', 'fsweb-comments-core' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the Form Builder editor (builder mode).
	 *
	 * @param array  $forms   All saved forms.
	 * @param string $edit_id Form ID being edited (empty for new).
	 * @param bool   $is_new  Whether this is a brand-new form.
	 */
	private function render_form_builder_editor( $forms, $edit_id, $is_new ) {
		$current = null;
		if ( '' !== $edit_id && isset( $forms[ $edit_id ] ) ) {
			$current = $forms[ $edit_id ];
		}

		// Fields: from saved form, or default template.
		if ( $current ) {
			$fields       = $current['fields'];
			$multi_step   = ! empty( $current['multi_step'] );
			$steps        = $multi_step && isset( $current['steps'] ) ? $current['steps'] : array();
		} elseif ( $is_new ) {
			$fields     = $this->default_form_fields();
			$multi_step = false;
			$steps      = array();
		} else {
			$fields     = array();
			$multi_step = false;
			$steps      = array();
		}

		$nonce    = wp_create_nonce( 'fsweb_builder_save' );
		$list_url = remove_query_arg( array( 'form_id', 'new' ) );

		$redirect_url   = $current && ! empty( $current['redirect_url'] ) ? $current['redirect_url'] : '';
		$redirect_delay = $current && isset( $current['redirect_delay'] ) ? max( 0, (int) $current['redirect_delay'] ) : 3;
		?>
		<div class="wrap fsweb-cc-wrap fsweb-cc-builder-wrap">
			<hr class="wp-header-end">
			<div class="fsweb-cc-hero">
				<h1><?php esc_html_e( 'Form Builder', 'fsweb-comments-core' ); ?></h1>
				<p><?php esc_html_e( 'Create and customize your review forms.', 'fsweb-comments-core' ); ?></p>
			</div>

			<!-- Back to list -->
			<p>
				<a href="<?php echo esc_url( $list_url ); ?>" class="button">
					<span class="dashicons dashicons-arrow-left-alt" style="vertical-align:text-bottom;"></span>
					<?php esc_html_e( 'Back to list', 'fsweb-comments-core' ); ?>
				</a>
			</p>

			<!-- Toolbar -->
			<div class="fsweb-cc-builder-toolbar" style="margin-bottom:16px;">
				<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;width:100%;">
					<input type="text" id="fsweb-cc-form-name" class="regular-text"
						value="<?php echo esc_attr( $current ? $current['name'] : '' ); ?>"
						placeholder="<?php esc_attr_e( 'Form name', 'fsweb-comments-core' ); ?>">
					<label id="fsweb-cc-multi-step-toggle" style="display:flex;align-items:center;gap:6px;cursor:pointer;white-space:nowrap;">
						<input type="checkbox" id="fsweb-cc-multi-step" <?php checked( $multi_step ); ?>>
						<?php esc_html_e( 'Multi-step', 'fsweb-comments-core' ); ?>
					</label>
					<button type="button" id="fsweb-cc-new-form" class="button">
						<?php esc_html_e( 'Clear & New', 'fsweb-comments-core' ); ?>
					</button>
					<button type="button" id="fsweb-cc-save-form" class="button button-primary">
						<?php esc_html_e( 'Save Form', 'fsweb-comments-core' ); ?>
					</button>
					<input type="hidden" id="fsweb-cc-form-id" value="<?php echo esc_attr( $edit_id ); ?>">
				</div>
			</div>

			<!-- Redirect after submission -->
			<div class="fsweb-cc-redirect-settings" style="display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start;margin-bottom:20px;background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:16px 20px;">
				<div style="flex:1;min-width:260px;">
					<label for="fsweb-cc-redirect-url" style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Redirect URL', 'fsweb-comments-core' ); ?></label>
					<input type="url" id="fsweb-cc-redirect-url" class="regular-text" value="<?php echo esc_attr( $redirect_url ); ?>" placeholder="https://…" style="width:100%;">
					<p class="description" style="margin-top:4px;"><?php esc_html_e( 'Leave empty to disable redirection after submission.', 'fsweb-comments-core' ); ?></p>
				</div>
				<div>
					<label for="fsweb-cc-redirect-delay" style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Redirect delay (seconds)', 'fsweb-comments-core' ); ?></label>
					<input type="number" id="fsweb-cc-redirect-delay" min="0" step="1" value="<?php echo esc_attr( $redirect_delay ); ?>" style="width:110px;">
				</div>
			</div>

			<div class="fsweb-cc-builder">
				<!-- Field palette -->
				<div class="fsweb-cc-builder-palette">
					<h3><?php esc_html_e( 'Available Fields', 'fsweb-comments-core' ); ?></h3>
					<div class="fsweb-cc-palette-items">
						<div class="fsweb-cc-palette-item" data-type="image">
							<span class="dashicons dashicons-format-image"></span>
							<?php esc_html_e( 'Image', 'fsweb-comments-core' ); ?>
						</div>
						<div class="fsweb-cc-palette-item" data-type="text">
							<span class="dashicons dashicons-editor-textcolor"></span>
							<?php esc_html_e( 'Text', 'fsweb-comments-core' ); ?>
						</div>
						<div class="fsweb-cc-palette-item" data-type="textarea">
							<span class="dashicons dashicons-editor-paragraph"></span>
							<?php esc_html_e( 'Textarea', 'fsweb-comments-core' ); ?>
						</div>
						<div class="fsweb-cc-palette-item" data-type="email">
							<span class="dashicons dashicons-email"></span>
							<?php esc_html_e( 'Email', 'fsweb-comments-core' ); ?>
						</div>
						<div class="fsweb-cc-palette-item" data-type="url">
							<span class="dashicons dashicons-admin-links"></span>
							<?php esc_html_e( 'URL', 'fsweb-comments-core' ); ?>
						</div>
						<div class="fsweb-cc-palette-item" data-type="date">
							<span class="dashicons dashicons-calendar"></span>
							<?php esc_html_e( 'Date', 'fsweb-comments-core' ); ?>
						</div>
						<div class="fsweb-cc-palette-item" data-type="select">
							<span class="dashicons dashicons-menu-alt"></span>
							<?php esc_html_e( 'Select', 'fsweb-comments-core' ); ?>
						</div>
						<div class="fsweb-cc-palette-item" data-type="rating">
							<span class="dashicons dashicons-star-filled"></span>
							<?php esc_html_e( 'Rating', 'fsweb-comments-core' ); ?>
						</div>
					</div>
				</div>

				<!-- Canvas -->
				<div class="fsweb-cc-builder-canvas">
					<div id="fsweb-cc-form-fields" class="fsweb-cc-fields-sortable" data-nonce="<?php echo esc_attr( $nonce ); ?>" style="<?php echo $multi_step ? 'display:none;' : ''; ?>">
						<?php foreach ( $fields as $i => $field ) : ?>
							<?php $this->render_field_card( $field, $i ); ?>
						<?php endforeach; ?>
					</div>

					<!-- Multi-step container -->
					<div id="fsweb-cc-steps-container" style="<?php echo $multi_step ? '' : 'display:none;'; ?>">
						<?php if ( $multi_step && ! empty( $steps ) ) : ?>
							<?php foreach ( $steps as $si => $step ) : ?>
								<div class="fsweb-cc-step" data-step-id="<?php echo esc_attr( $step['id'] ); ?>">
									<div class="fsweb-cc-step-header">
										<span class="dashicons dashicons-move fsweb-cc-step-handle"></span>
										<input type="text" class="fsweb-cc-step-label" value="<?php echo esc_attr( $step['label'] ); ?>" placeholder="<?php esc_attr_e( 'Step name', 'fsweb-comments-core' ); ?>">
										<span class="fsweb-cc-step-badge"><?php echo (int) ( $si + 1 ); ?></span>
										<button type="button" class="fsweb-cc-step-delete dashicons dashicons-trash" title="<?php esc_attr_e( 'Delete step', 'fsweb-comments-core' ); ?>"></button>
									</div>
									<div class="fsweb-cc-step-fields fsweb-cc-fields-sortable" data-step="<?php echo esc_attr( $step['id'] ); ?>">
										<?php foreach ( $step['fields'] as $fi => $field ) : ?>
											<?php $this->render_field_card( $field, $fi ); ?>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<div class="fsweb-cc-step" data-step-id="step_1">
								<div class="fsweb-cc-step-header">
									<span class="dashicons dashicons-move fsweb-cc-step-handle"></span>
									<input type="text" class="fsweb-cc-step-label" value="<?php esc_attr_e( 'Step 1', 'fsweb-comments-core' ); ?>" placeholder="<?php esc_attr_e( 'Step name', 'fsweb-comments-core' ); ?>">
									<span class="fsweb-cc-step-badge">1</span>
									<button type="button" class="fsweb-cc-step-delete dashicons dashicons-trash" title="<?php esc_attr_e( 'Delete step', 'fsweb-comments-core' ); ?>"></button>
								</div>
								<div class="fsweb-cc-step-fields fsweb-cc-fields-sortable" data-step="step_1"></div>
							</div>
						<?php endif; ?>
						<button type="button" id="fsweb-cc-add-step" class="button">+ Add Step</button>
					</div>

					<div id="fsweb-cc-builder-empty" style="<?php echo ! $multi_step && ! empty( $fields ) ? 'display:none;' : ( $multi_step ? 'display:none;' : '' ); ?>">
						<p><?php esc_html_e( 'Drag fields here from the palette on the left. Click a field to edit its properties.', 'fsweb-comments-core' ); ?></p>
					</div>
					<div id="fsweb-cc-builder-message" class="notice" style="display:none;"></div>
				</div>
			</div>
		</div>

		<!-- Field edit modal (rendered once, JS populates it) -->
		<div id="fsweb-cc-field-modal" class="fsweb-cc-modal" style="display:none;">
			<div class="fsweb-cc-modal-backdrop"></div>
			<div class="fsweb-cc-modal-content">
				<div class="fsweb-cc-modal-header">
					<h3><?php esc_html_e( 'Field Settings', 'fsweb-comments-core' ); ?></h3>
					<button type="button" class="fsweb-cc-modal-close dashicons dashicons-no-alt"></button>
				</div>
				<div class="fsweb-cc-modal-body">
					<div class="fsweb-cc-field-row">
						<label><?php esc_html_e( 'Field ID', 'fsweb-comments-core' ); ?></label>
						<input type="text" id="fsweb-cc-modal-field-id" class="regular-text">
						<p class="description"><?php esc_html_e( 'Unique identifier for this field. Used in shortcodes and templates.', 'fsweb-comments-core' ); ?></p>
					</div>
					<div class="fsweb-cc-field-row">
						<label><?php esc_html_e( 'Type', 'fsweb-comments-core' ); ?></label>
						<select id="fsweb-cc-modal-field-type">
							<option value="image"><?php esc_html_e( 'Image', 'fsweb-comments-core' ); ?></option>
							<option value="text"><?php esc_html_e( 'Text', 'fsweb-comments-core' ); ?></option>
							<option value="textarea"><?php esc_html_e( 'Textarea', 'fsweb-comments-core' ); ?></option>
							<option value="email"><?php esc_html_e( 'Email', 'fsweb-comments-core' ); ?></option>
							<option value="url"><?php esc_html_e( 'URL', 'fsweb-comments-core' ); ?></option>
							<option value="date"><?php esc_html_e( 'Date', 'fsweb-comments-core' ); ?></option>
							<option value="select"><?php esc_html_e( 'Select', 'fsweb-comments-core' ); ?></option>
							<option value="rating"><?php esc_html_e( 'Rating', 'fsweb-comments-core' ); ?></option>
						</select>
					</div>
					<div class="fsweb-cc-field-row">
						<label><?php esc_html_e( 'Label', 'fsweb-comments-core' ); ?></label>
						<input type="text" id="fsweb-cc-modal-field-label" class="regular-text">
					</div>
					<div class="fsweb-cc-field-row">
						<label><?php esc_html_e( 'Placeholder', 'fsweb-comments-core' ); ?></label>
						<input type="text" id="fsweb-cc-modal-field-placeholder" class="regular-text">
					</div>
					<div class="fsweb-cc-field-row fsweb-cc-modal-options-row" style="display:none;">
						<label><?php esc_html_e( 'Options', 'fsweb-comments-core' ); ?></label>
						<textarea id="fsweb-cc-modal-field-options" rows="4" placeholder="<?php esc_attr_e( 'One per line', 'fsweb-comments-core' ); ?>"></textarea>
						<p class="description"><?php esc_html_e( 'One option per line.', 'fsweb-comments-core' ); ?></p>
					</div>
					<div class="fsweb-cc-field-row">
						<label>
							<input type="checkbox" id="fsweb-cc-modal-field-required">
							<?php esc_html_e( 'Required', 'fsweb-comments-core' ); ?>
						</label>
					</div>
				</div>
				<div class="fsweb-cc-modal-footer">
					<button type="button" id="fsweb-cc-modal-save" class="button button-primary"><?php esc_html_e( 'Apply', 'fsweb-comments-core' ); ?></button>
					<button type="button" id="fsweb-cc-modal-cancel" class="button"><?php esc_html_e( 'Cancel', 'fsweb-comments-core' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Default form fields — pre-filled template for new forms.
	 *
	 * @return array
	 */
	private function default_form_fields() {
		return array(
			array(
				'id'          => 'fsweb_image',
				'type'        => 'image',
				'label'       => __( 'Image', 'fsweb-comments-core' ),
				'placeholder' => '',
				'required'    => false,
			),
			array(
				'id'          => 'fsweb_name',
				'type'        => 'text',
				'label'       => __( 'Name', 'fsweb-comments-core' ),
				'placeholder' => __( 'Insert your name', 'fsweb-comments-core' ),
				'required'    => true,
			),
			array(
				'id'          => 'fsweb_email',
				'type'        => 'email',
				'label'       => __( 'Email', 'fsweb-comments-core' ),
				'placeholder' => __( 'Insert your email', 'fsweb-comments-core' ),
				'required'    => true,
			),
			array(
				'id'          => 'fsweb_rating',
				'type'        => 'rating',
				'label'       => __( 'Rating', 'fsweb-comments-core' ),
				'placeholder' => '',
				'required'    => true,
			),
			array(
				'id'          => 'fsweb_description',
				'type'        => 'textarea',
				'label'       => __( 'Description', 'fsweb-comments-core' ),
				'placeholder' => __( 'Insert your review description', 'fsweb-comments-core' ),
				'required'    => true,
			),
		);
	}

	/**
	 * Render a single field card in the builder.
	 *
	 * @param array   $field Field data.
	 * @param integer $index Field index.
	 * @return void
	 */
	private function render_field_card( $field, $index ) {
		$type_labels = array(
			'image'    => __( 'Image', 'fsweb-comments-core' ),
			'text'     => __( 'Text', 'fsweb-comments-core' ),
			'textarea' => __( 'Textarea', 'fsweb-comments-core' ),
			'email'    => __( 'Email', 'fsweb-comments-core' ),
			'url'      => __( 'URL', 'fsweb-comments-core' ),
			'date'     => __( 'Date', 'fsweb-comments-core' ),
			'select'   => __( 'Select', 'fsweb-comments-core' ),
			'rating'   => __( 'Rating', 'fsweb-comments-core' ),
		);

		$type_icons = array(
			'image'    => 'dashicons-format-image',
			'text'     => 'dashicons-editor-textcolor',
			'textarea' => 'dashicons-editor-paragraph',
			'email'    => 'dashicons-email',
			'url'      => 'dashicons-admin-links',
			'date'     => 'dashicons-calendar',
			'select'   => 'dashicons-menu-alt',
			'rating'   => 'dashicons-star-filled',
		);

		$type_label = isset( $type_labels[ $field['type'] ] ) ? $type_labels[ $field['type'] ] : $field['type'];
		$type_icon  = isset( $type_icons[ $field['type'] ] ) ? $type_icons[ $field['type'] ] : 'dashicons-edit';
		$options    = isset( $field['options'] ) ? $field['options'] : '';
		?>
		<div class="fsweb-cc-field-card" data-id="<?php echo esc_attr( $field['id'] ); ?>">
			<div class="fsweb-cc-field-header">
				<span class="dashicons dashicons-move fsweb-cc-field-handle"></span>
				<span class="dashicons <?php echo esc_attr( $type_icon ); ?> fsweb-cc-field-icon"></span>
				<strong class="fsweb-cc-field-label-text"><?php echo esc_html( $field['label'] ); ?></strong>
				<span class="fsweb-cc-field-type-badge"><?php echo esc_html( $type_label ); ?></span>
				<button type="button" class="fsweb-cc-field-edit dashicons dashicons-edit" title="<?php esc_attr_e( 'Edit field', 'fsweb-comments-core' ); ?>"></button>
				<button type="button" class="fsweb-cc-field-delete dashicons dashicons-trash"></button>
			</div>
			<div class="fsweb-cc-field-body" style="display:none;">
				<div class="fsweb-cc-field-row">
					<label><?php esc_html_e( 'Type', 'fsweb-comments-core' ); ?></label>
					<select class="fsweb-cc-field-type">
						<?php foreach ( $type_labels as $val => $lbl ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $field['type'], $val ); ?>>
								<?php echo esc_html( $lbl ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="fsweb-cc-field-row">
					<label><?php esc_html_e( 'Label', 'fsweb-comments-core' ); ?></label>
					<input type="text" class="fsweb-cc-field-label" value="<?php echo esc_attr( $field['label'] ); ?>">
				</div>
				<div class="fsweb-cc-field-row">
					<label><?php esc_html_e( 'Placeholder', 'fsweb-comments-core' ); ?></label>
					<input type="text" class="fsweb-cc-field-placeholder" value="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>">
				</div>
				<div class="fsweb-cc-field-row fsweb-cc-field-options-row" style="<?php echo 'select' === $field['type'] ? '' : 'display:none;'; ?>">
					<label><?php esc_html_e( 'Options', 'fsweb-comments-core' ); ?></label>
					<textarea class="fsweb-cc-field-options" rows="3" placeholder="<?php esc_attr_e( 'One per line', 'fsweb-comments-core' ); ?>"><?php echo esc_textarea( $options ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One option per line.', 'fsweb-comments-core' ); ?></p>
				</div>
				<div class="fsweb-cc-field-row">
					<label>
						<input type="checkbox" class="fsweb-cc-field-required" <?php checked( ! empty( $field['required'] ) ); ?>>
						<?php esc_html_e( 'Required', 'fsweb-comments-core' ); ?>
					</label>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler — save form builder fields.
	 *
	 * @since    1.0.0
	 */
	public function ajax_save_form() {
		check_ajax_referer( 'fsweb_builder_save', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fsweb-comments-core' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON decoded & sanitized per-field below.
		$raw      = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : '[]';
		$name     = isset( $_POST['form_name'] ) ? sanitize_text_field( wp_unslash( $_POST['form_name'] ) ) : '';
		$form_id  = isset( $_POST['form_id'] ) ? sanitize_key( wp_unslash( $_POST['form_id'] ) ) : '';
		$multi    = isset( $_POST['multi_step'] ) && '1' === $_POST['multi_step'];
		$raw_steps = isset( $_POST['steps'] ) ? wp_unslash( $_POST['steps'] ) : '[]';
		$redirect_url   = isset( $_POST['redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_url'] ) ) : '';
		$redirect_delay = isset( $_POST['redirect_delay'] ) ? absint( $_POST['redirect_delay'] ) : 3;
		// phpcs:enable

		if ( '' === $name ) {
			$name = __( 'Untitled Form', 'fsweb-comments-core' );
		}

		// Sanitize helper for a single field.
		$valid_types = array( 'image', 'text', 'textarea', 'email', 'url', 'date', 'select', 'rating' );
		$sanitize_field = function( $field ) use ( $valid_types ) {
			return array(
				'id'          => sanitize_key( $field['id'] ),
				'type'        => in_array( $field['type'], $valid_types, true ) ? $field['type'] : 'text',
				'label'       => sanitize_text_field( $field['label'] ),
				'placeholder' => sanitize_text_field( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ),
				'required'    => ! empty( $field['required'] ),
				'options'     => isset( $field['options'] ) ? sanitize_textarea_field( $field['options'] ) : '',
			);
		};

		// Load existing forms.
		$forms = get_option( 'fsweb_review_forms', array() );

		// Generate new ID or use existing.
		if ( '' === $form_id || ! isset( $forms[ $form_id ] ) ) {
			$form_id = substr( wp_generate_password( 8, false, false ), 0, 8 );
		}

		$form_data = array(
			'name'           => $name,
			'multi_step'     => $multi,
			'redirect_url'   => $redirect_url,
			'redirect_delay' => max( 0, $redirect_delay ),
		);

		if ( $multi ) {
			// Multi-step: sanitize steps.
			$steps_data = json_decode( $raw_steps, true );
			if ( ! is_array( $steps_data ) ) {
				$steps_data = array();
			}
			$clean_steps = array();
			foreach ( $steps_data as $step ) {
				$clean_fields = array();
				if ( ! empty( $step['fields'] ) && is_array( $step['fields'] ) ) {
					foreach ( $step['fields'] as $f ) {
						$clean_fields[] = $sanitize_field( $f );
					}
				}
				$clean_steps[] = array(
					'id'     => sanitize_key( isset( $step['id'] ) ? $step['id'] : '' ),
					'label'  => sanitize_text_field( isset( $step['label'] ) ? $step['label'] : '' ),
					'fields' => $clean_fields,
				);
			}
			$form_data['steps'] = $clean_steps;
			// Also store flat fields for backwards compat.
			$all_fields = array();
			foreach ( $clean_steps as $s ) {
				foreach ( $s['fields'] as $f ) {
					$all_fields[] = $f;
				}
			}
			$form_data['fields'] = $all_fields;
		} else {
			// Normal: sanitize fields.
			$fields = json_decode( $raw, true );
			if ( ! is_array( $fields ) ) {
				$fields = array();
			}
			$clean = array();
			foreach ( $fields as $field ) {
				$clean[] = $sanitize_field( $field );
			}
			$form_data['fields'] = $clean;
		}

		$forms[ $form_id ] = $form_data;

		update_option( 'fsweb_review_forms', $forms );

		wp_send_json_success( array(
			'message'   => __( 'Form saved successfully!', 'fsweb-comments-core' ),
			'form_id'   => $form_id,
			'shortcode' => '[fsweb_review_form id="' . $form_id . '"]',
		) );
	}

	/**
	 * AJAX handler — delete a form.
	 *
	 * @since    1.0.0
	 */
	public function ajax_delete_form() {
		check_ajax_referer( 'fsweb_builder_save', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fsweb-comments-core' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$form_id = isset( $_POST['form_id'] ) ? sanitize_key( wp_unslash( $_POST['form_id'] ) ) : '';
		// phpcs:enable

		$forms = get_option( 'fsweb_review_forms', array() );
		unset( $forms[ $form_id ] );
		update_option( 'fsweb_review_forms', $forms );

		wp_send_json_success( array( 'message' => __( 'Form deleted.', 'fsweb-comments-core' ) ) );
	}

	// ------------------------------------------------------------------//
	//  Views Builder
	// ------------------------------------------------------------------//

	/**
	 * Render the Views Builder page.
	 *
	 * Two modes:
	 *   - List (default) — shows saved views with edit/copy/delete.
	 *   - Builder — when ?new=1 or ?view_id=xxx, shows the row/column builder.
	 *
	 * @since    1.0.0
	 */
	public function render_views_page() {
		$views   = get_option( 'fsweb_review_views', array() );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET params for display routing only.
		$edit_id = isset( $_GET['view_id'] ) ? sanitize_key( wp_unslash( $_GET['view_id'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET params for display routing only.
		$is_new  = isset( $_GET['new'] ) && '1' === $_GET['new'];

		// --- Builder mode --------------------------------------------------
		if ( $is_new || '' !== $edit_id ) {
			$this->render_views_builder_editor( $views, $edit_id, $is_new );
			return;
		}

		// --- List mode -----------------------------------------------------
		// Auto-create default view if none exist.
		if ( empty( $views ) ) {
			$views = $this->maybe_create_default_view();
		}

		$new_url = add_query_arg( array( 'new' => '1' ), remove_query_arg( 'view_id' ) );
		?>
		<div class="wrap fsweb-cc-wrap fsweb-cc-builder-wrap">
			<hr class="wp-header-end">
			<div class="fsweb-cc-hero">
				<h1><?php esc_html_e( 'Views Builder', 'fsweb-comments-core' ); ?></h1>
				<p><?php esc_html_e( 'Create and customize how reviews are displayed.', 'fsweb-comments-core' ); ?></p>
			</div>

			<div class="fsweb-cc-list-toolbar">
				<a href="<?php echo esc_url( $new_url ); ?>" class="button button-primary">
					<span class="dashicons dashicons-plus-alt" style="vertical-align:text-bottom;"></span>
					<?php esc_html_e( 'New View', 'fsweb-comments-core' ); ?>
				</a>
			</div>

			<?php if ( ! empty( $views ) ) : ?>
				<table class="wp-list-table widefat fixed striped fsweb-cc-templates-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'View Name', 'fsweb-comments-core' ); ?></th>
							<th><?php esc_html_e( 'Shortcode', 'fsweb-comments-core' ); ?></th>
							<th><?php esc_html_e( 'Layout', 'fsweb-comments-core' ); ?></th>
							<th><?php esc_html_e( 'Connected Form', 'fsweb-comments-core' ); ?></th>
							<th style="width:100px;"></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $views as $vid => $view ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $view['name'] ); ?></strong></td>
								<td>
									<div class="fsweb-cc-shortcode-copy-wrap">
										<code class="fsweb-cc-shortcode-copy">[fsweb_reviews id="<?php echo esc_attr( $vid ); ?>"]</code>
										<button type="button" class="button button-small fsweb-cc-copy-btn" data-code="[fsweb_reviews id=&quot;<?php echo esc_attr( $vid ); ?>&quot;]" title="<?php esc_attr_e( 'Copy shortcode', 'fsweb-comments-core' ); ?>">
											<span class="dashicons dashicons-clipboard"></span>
										</button>
									</div>
								</td>
								<td><?php echo esc_html( ucfirst( $view['layout'] ) ); ?></td>
								<td><?php echo isset( $view['form_id'] ) && ! empty( $view['form_id'] ) ? esc_html( $view['form_id'] ) : '—'; ?></td>
								<td>
									<div class="fsweb-cc-action-buttons">
										<a href="<?php echo esc_url( add_query_arg( 'view_id', $vid, remove_query_arg( 'new' ) ) ); ?>" class="button button-small fsweb-cc-edit-btn" title="<?php esc_attr_e( 'Edit', 'fsweb-comments-core' ); ?>">
											<span class="dashicons dashicons-edit"></span>
										</a>
										<button type="button" class="button button-small fsweb-cc-delete-view" data-id="<?php echo esc_attr( $vid ); ?>" title="<?php esc_attr_e( 'Delete', 'fsweb-comments-core' ); ?>">
											<span class="dashicons dashicons-trash"></span>
										</button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="fsweb-cc-empty-state">
					<p><?php esc_html_e( 'No views yet. Create your first view to display reviews!', 'fsweb-comments-core' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the Views Builder editor (builder mode).
	 *
	 * @param array  $views   All saved views.
	 * @param string $edit_id View ID being edited (empty for new).
	 * @param bool   $is_new  Whether this is a brand-new view.
	 */
	private function render_views_builder_editor( $views, $edit_id, $is_new ) {
		$forms = get_option( 'fsweb_review_forms', array() );

		$current = null;
		if ( '' !== $edit_id && isset( $views[ $edit_id ] ) ) {
			$current = $views[ $edit_id ];
		}

		// Determine connected form and available components.
		$connected_form_id = $current && isset( $current['form_id'] ) ? $current['form_id'] : '';
		$rows              = $current && isset( $current['rows'] ) ? $current['rows'] : array();

		// If new, build default rows from the first available form (or fallback).
		if ( $is_new && empty( $rows ) ) {
			if ( empty( $connected_form_id ) && ! empty( $forms ) ) {
				$first_form        = array_key_first( $forms );
				$connected_form_id = $first_form;
			}
			$rows = array( $this->default_view_row_from_form( $connected_form_id, $forms ) );
		} elseif ( empty( $rows ) ) {
			$rows = array( $this->default_view_row_from_form( $connected_form_id, $forms ) );
		}

		// Components available: from connected form, or fallback defaults.
		$available_components = $this->get_available_components( $connected_form_id, $forms );

		// Build field-label lookup from the connected form.
		$connected_form_fields = array();
		if ( $connected_form_id && isset( $forms[ $connected_form_id ]['fields'] ) ) {
			foreach ( $forms[ $connected_form_id ]['fields'] as $f ) {
				if ( ! empty( $f['label'] ) ) {
					$connected_form_fields[ $f['id'] ] = $f['label'];
				}
			}
		}

		$nonce    = wp_create_nonce( 'fsweb_views_save' );
		$list_url = remove_query_arg( array( 'view_id', 'new' ) );
		?>
		<div class="wrap fsweb-cc-wrap fsweb-cc-builder-wrap">
			<hr class="wp-header-end">
			<div class="fsweb-cc-hero">
				<h1><?php esc_html_e( 'Views Builder', 'fsweb-comments-core' ); ?></h1>
				<p><?php esc_html_e( 'Create and customize how reviews are displayed.', 'fsweb-comments-core' ); ?></p>
			</div>

			<!-- Back to list -->
			<p>
				<a href="<?php echo esc_url( $list_url ); ?>" class="button">
					<span class="dashicons dashicons-arrow-left-alt" style="vertical-align:text-bottom;"></span>
					<?php esc_html_e( 'Back to list', 'fsweb-comments-core' ); ?>
				</a>
			</p>

			<!-- Toolbar -->
			<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
				<input type="text" id="fsweb-cc-view-name" class="regular-text"
					value="<?php echo esc_attr( $current ? $current['name'] : '' ); ?>"
					placeholder="<?php esc_attr_e( 'View name', 'fsweb-comments-core' ); ?>">

				<label style="font-weight:600;font-size:13px;"><?php esc_html_e( 'Connected Form:', 'fsweb-comments-core' ); ?></label>
				<select id="fsweb-cc-view-form-id">
					<option value=""><?php esc_html_e( '— None —', 'fsweb-comments-core' ); ?></option>
					<?php foreach ( $forms as $fid => $form ) : ?>
						<option value="<?php echo esc_attr( $fid ); ?>" <?php selected( $connected_form_id, $fid ); ?>>
							<?php echo esc_html( $form['name'] . ' (' . $fid . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<select id="fsweb-cc-view-layout">
					<option value="grid" <?php selected( $current ? $current['layout'] : 'grid', 'grid' ); ?>>Grid</option>
					<option value="slider" <?php selected( $current ? $current['layout'] : '', 'slider' ); ?>>Slider</option>
					<option value="list" <?php selected( $current ? $current['layout'] : '', 'list' ); ?>>List</option>
				</select>
				<input type="number" id="fsweb-cc-view-count" class="small-text" min="1" max="50"
					value="<?php echo esc_attr( $current ? $current['count'] : 6 ); ?>" title="Count">
				<select id="fsweb-cc-view-order">
					<option value="newest" <?php selected( $current ? $current['order'] : 'newest', 'newest' ); ?>>Newest</option>
					<option value="oldest" <?php selected( $current ? $current['order'] : '', 'oldest' ); ?>>Oldest</option>
					<option value="random" <?php selected( $current ? $current['order'] : '', 'random' ); ?>>Random</option>
					<option value="menu_order" <?php selected( $current ? $current['order'] : '', 'menu_order' ); ?>>Menu order</option>
				</select>
				<button type="button" id="fsweb-cc-new-view" class="button"><?php esc_html_e( 'Clear & New', 'fsweb-comments-core' ); ?></button>
				<button type="button" id="fsweb-cc-save-view" class="button button-primary"><?php esc_html_e( 'Save View', 'fsweb-comments-core' ); ?></button>
				<input type="hidden" id="fsweb-cc-view-id" value="<?php echo esc_attr( $edit_id ); ?>">
			</div>

			<div class="fsweb-cc-builder" data-nonce="<?php echo esc_attr( $nonce ); ?>">
				<!-- Sidebar palette — only "Add Row" when form is connected (components already in layout) -->
				<div class="fsweb-cc-builder-palette">
					<h3><?php esc_html_e( 'Components', 'fsweb-comments-core' ); ?></h3>

					<?php if ( ! empty( $connected_form_id ) ) : ?>
						<p class="description" style="margin:0 0 8px;">
							<?php esc_html_e( 'Components from the connected form are already placed in the default layout. Drag them between columns or add more rows below.', 'fsweb-comments-core' ); ?>
						</p>
					<?php else : ?>
						<div class="fsweb-cc-palette-items" id="fsweb-cc-views-palette">
							<?php foreach ( $available_components as $comp ) : ?>
								<div class="fsweb-cc-palette-item" data-type="<?php echo esc_attr( $comp['type'] ); ?>" data-field-id="<?php echo esc_attr( $comp['id'] ); ?>">
									<span class="dashicons <?php echo esc_attr( $comp['icon'] ); ?>"></span>
									<?php echo esc_html( $comp['label'] ); ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<div style="margin-top:<?php echo ! empty( $connected_form_id ) ? '0' : '16px'; ?>;">
						<button type="button" id="fsweb-cc-add-row" class="button" style="width:100%;">
							<span class="dashicons dashicons-plus-alt fsweb-cc-btn-icon"></span>
							<?php esc_html_e( 'Add Row', 'fsweb-comments-core' ); ?>
						</button>
					</div>

					<?php
					$current_skin      = $current && isset( $current['skin'] ) ? $current['skin'] : 'default';
					$current_custom_css = $current && isset( $current['custom_css'] ) ? $current['custom_css'] : '';
					?>
					<div class="fsweb-cc-builder-styles" style="margin-top:20px;padding-top:16px;border-top:1px solid #dcdcde;">
						<h3 style="margin-top:0;"><?php esc_html_e( 'Style', 'fsweb-comments-core' ); ?></h3>
						<p class="description" style="margin:0 0 8px;">
							<?php esc_html_e( 'Choose a pre-built skin or write your own CSS.', 'fsweb-comments-core' ); ?>
						</p>
						<select id="fsweb-cc-view-skin" style="width:100%;margin-bottom:8px;">
							<option value="default" <?php selected( $current_skin, 'default' ); ?>><?php esc_html_e( 'Default', 'fsweb-comments-core' ); ?></option>
							<option value="dark" <?php selected( $current_skin, 'dark' ); ?>><?php esc_html_e( 'Dark', 'fsweb-comments-core' ); ?></option>
							<option value="minimal" <?php selected( $current_skin, 'minimal' ); ?>><?php esc_html_e( 'Minimal', 'fsweb-comments-core' ); ?></option>
							<option value="colorful" <?php selected( $current_skin, 'colorful' ); ?>><?php esc_html_e( 'Colorful', 'fsweb-comments-core' ); ?></option>
							<option value="flat" <?php selected( $current_skin, 'flat' ); ?>><?php esc_html_e( 'Flat', 'fsweb-comments-core' ); ?></option>
							<option value="custom" <?php selected( $current_skin, 'custom' ); ?>><?php esc_html_e( 'Custom CSS', 'fsweb-comments-core' ); ?></option>
						</select>
						<textarea id="fsweb-cc-view-custom-css"
							class="fsweb-cc-code-editor"
							rows="10"
							placeholder="<?php esc_attr_e( '/* Write your custom CSS here */', 'fsweb-comments-core' ); ?>"
							style="<?php echo 'custom' === $current_skin ? '' : 'display:none;'; ?>width:100%;font-family:monospace;font-size:12px;resize:vertical;"
						><?php echo esc_textarea( $current_custom_css ); ?></textarea>
						<p class="description" style="margin:4px 0 0;">
							<?php esc_html_e( 'Custom CSS has the highest priority and overrides all other styles, including component settings.', 'fsweb-comments-core' ); ?>
						</p>
					</div>
				</div>

				<!-- Canvas -->
				<div class="fsweb-cc-views-canvas">
					<div id="fsweb-cc-rows">
						<?php foreach ( $rows as $ri => $row ) : ?>
							<?php $this->render_view_row( $row, $ri, $connected_form_fields ); ?>
						<?php endforeach; ?>
					</div>
					<div id="fsweb-cc-view-empty" style="<?php echo ! empty( $rows ) ? 'display:none;' : ''; ?>">
						<p><?php esc_html_e( 'Click "Add Row" to start building your review card layout. Then drag components into the columns.', 'fsweb-comments-core' ); ?></p>
					</div>
					<div id="fsweb-cc-view-message" class="notice" style="display:none;"></div>
				</div>
			</div>

			<!-- Component Style Modal -->
			<div id="fsweb-cc-comp-modal" class="fsweb-cc-modal" style="display:none;">
				<div class="fsweb-cc-modal-backdrop"></div>
				<div class="fsweb-cc-modal-content">
					<div class="fsweb-cc-modal-header">
						<h3><?php esc_html_e( 'Component Settings', 'fsweb-comments-core' ); ?></h3>
						<button type="button" class="fsweb-cc-modal-close">&times;</button>
					</div>
					<div class="fsweb-cc-modal-body" id="fsweb-cc-comp-modal-body">
						<!-- Filled dynamically by JS -->
					</div>
					<div class="fsweb-cc-modal-footer">
						<button type="button" id="fsweb-cc-comp-apply" class="button button-primary"><?php esc_html_e( 'Apply', 'fsweb-comments-core' ); ?></button>
						<button type="button" id="fsweb-cc-comp-cancel" class="button"><?php esc_html_e( 'Cancel', 'fsweb-comments-core' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get available components for the Views palette from a connected form.
	 *
	 * @param string $form_id Connected form ID.
	 * @param array  $forms   All saved forms.
	 * @return array Components list with type, id, icon, label.
	 */
	private function get_available_components( $form_id, $forms ) {
		$type_icons = array(
			'image'    => 'dashicons-format-image',
			'text'     => 'dashicons-editor-textcolor',
			'textarea' => 'dashicons-editor-paragraph',
			'email'    => 'dashicons-email',
			'url'      => 'dashicons-admin-links',
			'date'     => 'dashicons-calendar',
			'select'   => 'dashicons-menu-alt',
			'rating'   => 'dashicons-star-filled',
		);

		// If connected to a form, return its fields as components.
		if ( ! empty( $form_id ) && isset( $forms[ $form_id ] ) ) {
			$components = array();
			foreach ( $forms[ $form_id ]['fields'] as $field ) {
				$components[] = array(
					'type'  => $field['type'],
					'id'    => $field['id'],
					'label' => $field['label'],
					'icon'  => isset( $type_icons[ $field['type'] ] ) ? $type_icons[ $field['type'] ] : 'dashicons-edit',
				);
			}
			return $components;
		}

		// Fallback — default components.
		return array(
			array( 'type' => 'image',    'id' => 'fsweb_image',    'label' => __( 'Image', 'fsweb-comments-core' ),    'icon' => 'dashicons-format-image' ),
			array( 'type' => 'name',     'id' => 'fsweb_name',     'label' => __( 'Name', 'fsweb-comments-core' ),     'icon' => 'dashicons-admin-users' ),
			array( 'type' => 'content',  'id' => 'fsweb_content',  'label' => __( 'Content', 'fsweb-comments-core' ),  'icon' => 'dashicons-editor-paragraph' ),
			array( 'type' => 'rating',   'id' => 'fsweb_rating',   'label' => __( 'Rating', 'fsweb-comments-core' ),   'icon' => 'dashicons-star-filled' ),
			array( 'type' => 'date',     'id' => 'fsweb_date',     'label' => __( 'Date', 'fsweb-comments-core' ),     'icon' => 'dashicons-calendar' ),
			array( 'type' => 'category', 'id' => 'fsweb_category', 'label' => __( 'Category', 'fsweb-comments-core' ), 'icon' => 'dashicons-category' ),
		);
	}

	/**
	 * Build a default view row from the connected form's fields.
	 *
	 * @param string $form_id Connected form ID.
	 * @param array  $forms   All saved forms.
	 * @return array Row with columns and components.
	 */
	private function default_view_row_from_form( $form_id, $forms ) {
		$components = $this->get_available_components( $form_id, $forms );

		// Left: image + name. Right: everything else.
		$left  = array();
		$right = array();

		foreach ( $components as $comp ) {
			if ( 'image' === $comp['type'] || ( 'text' === $comp['type'] && false !== stripos( $comp['id'], 'name' ) ) ) {
				$left[] = array( 'type' => $comp['type'], 'field_id' => $comp['id'] );
			} else {
				$right[] = array( 'type' => $comp['type'], 'field_id' => $comp['id'] );
			}
		}

		// If no image component, just put everything in one column.
		if ( empty( $left ) ) {
			return array(
				'columns' => array(
					array(
						'width'      => 100,
						'components' => $right,
					),
				),
			);
		}

		return array(
			'columns' => array(
				array(
					'width'      => 50,
					'components' => $left,
				),
				array(
					'width'      => 50,
					'components' => $right,
				),
			),
		);
	}

	/**
	 * Render a single row in the views builder.
	 */
	private function render_view_row( $row, $index, $field_labels = array() ) {
		$cols      = isset( $row['columns'] ) ? $row['columns'] : array();
		$col_count = count( $cols );
		?>
		<div class="fsweb-cc-view-row" data-row="<?php echo (int) $index; ?>">
			<div class="fsweb-cc-view-row-toolbar">
				<span class="dashicons dashicons-move fsweb-cc-row-handle"></span>
				<strong><?php esc_html_e( 'Row', 'fsweb-comments-core' ); ?> <?php echo (int) $index + 1; ?></strong>
				<button type="button" class="fsweb-cc-col-btn<?php echo 1 === $col_count ? ' active' : ''; ?>" data-cols="1">1 col</button>
				<button type="button" class="fsweb-cc-col-btn<?php echo 2 === $col_count ? ' active' : ''; ?>" data-cols="2">2 cols</button>
				<button type="button" class="fsweb-cc-col-btn<?php echo 3 === $col_count ? ' active' : ''; ?>" data-cols="3">3 cols</button>
				<button type="button" class="fsweb-cc-delete-row dashicons dashicons-trash"></button>
			</div>
			<div class="fsweb-cc-view-cols">
				<?php foreach ( $cols as $ci => $col ) : ?>
					<div class="fsweb-cc-view-col" data-col="<?php echo (int) $ci; ?>" style="flex:<?php echo isset( $col['width'] ) ? (int) $col['width'] : 100; ?>;">
						<div class="fsweb-cc-col-inner">
							<?php if ( isset( $col['components'] ) ) :
								foreach ( $col['components'] as $comp ) :
									$this->render_view_component( $comp, $field_labels );
								endforeach;
							endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a component inside a column.
	 */
	private function render_view_component( $comp, $field_labels = array() ) {
		$icons = array(
			'image'    => 'dashicons-format-image',
			'name'     => 'dashicons-admin-users',
			'content'  => 'dashicons-editor-paragraph',
			'text'     => 'dashicons-editor-textcolor',
			'textarea' => 'dashicons-editor-paragraph',
			'email'    => 'dashicons-email',
			'url'      => 'dashicons-admin-links',
			'date'     => 'dashicons-calendar',
			'select'   => 'dashicons-menu-alt',
			'rating'   => 'dashicons-star-filled',
			'category' => 'dashicons-category',
		);
		$labels = array(
			'image' => 'Image', 'name' => 'Name', 'content' => 'Content',
			'text' => 'Text', 'textarea' => 'Textarea', 'email' => 'Email',
			'url' => 'URL', 'date' => 'Date', 'select' => 'Select',
			'rating' => 'Rating', 'category' => 'Category',
		);
		$type     = $comp['type'];
		$field_id = isset( $comp['field_id'] ) ? $comp['field_id'] : '';
		$styles   = isset( $comp['styles'] ) ? $comp['styles'] : array();

		if ( $field_id && isset( $field_labels[ $field_id ] ) ) {
			$display_label = $field_labels[ $field_id ];
		} else {
			$display_label = isset( $labels[ $type ] ) ? $labels[ $type ] : ucfirst( $type );
		}
		$badge_text = isset( $labels[ $type ] ) ? $labels[ $type ] : $type;
		?>
		<div class="fsweb-cc-view-comp fsweb-cc-field-card" data-type="<?php echo esc_attr( $type ); ?>" data-field-id="<?php echo esc_attr( $field_id ); ?>" data-styles="<?php echo esc_attr( wp_json_encode( $styles ) ); ?>">
			<div class="fsweb-cc-field-header">
				<span class="dashicons dashicons-move fsweb-cc-field-handle"></span>
				<span class="dashicons <?php echo esc_attr( isset( $icons[ $type ] ) ? $icons[ $type ] : 'dashicons-edit' ); ?> fsweb-cc-field-icon"></span>
				<strong class="fsweb-cc-field-label-text"><?php echo esc_html( $display_label ); ?></strong>
				<span class="fsweb-cc-field-type-badge"><?php echo esc_html( $badge_text ); ?></span>
				<button type="button" class="fsweb-cc-field-edit dashicons dashicons-edit" title="<?php esc_attr_e( 'Edit styles', 'fsweb-comments-core' ); ?>"></button>
				<button type="button" class="fsweb-cc-field-delete dashicons dashicons-trash" title="<?php esc_attr_e( 'Remove', 'fsweb-comments-core' ); ?>"></button>
			</div>
			<div class="fsweb-cc-field-body" style="display:none;">
				<!-- Styles stored in data-styles attribute -->
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler — save a view.
	 *
	 * @since    1.0.0
	 */
	public function ajax_save_view() {
		check_ajax_referer( 'fsweb_views_save', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fsweb-comments-core' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON decoded & sanitized per-row below.
		$name    = isset( $_POST['view_name'] ) ? sanitize_text_field( wp_unslash( $_POST['view_name'] ) ) : '';
		$view_id = isset( $_POST['view_id'] ) ? sanitize_key( wp_unslash( $_POST['view_id'] ) ) : '';
		$layout  = isset( $_POST['layout'] ) ? sanitize_key( wp_unslash( $_POST['layout'] ) ) : 'grid';
		$count   = isset( $_POST['count'] ) ? min( max( (int) $_POST['count'], 1 ), 50 ) : 6;
		$order   = isset( $_POST['order'] ) ? sanitize_key( wp_unslash( $_POST['order'] ) ) : 'newest';
		$category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
		$form_id = isset( $_POST['form_id'] ) ? sanitize_key( wp_unslash( $_POST['form_id'] ) ) : '';
		$skin    = isset( $_POST['skin'] ) ? sanitize_key( wp_unslash( $_POST['skin'] ) ) : 'default';
		$custom_css = isset( $_POST['custom_css'] ) ? wp_strip_all_tags( wp_unslash( $_POST['custom_css'] ) ) : '';
		$raw_rows = isset( $_POST['rows'] ) ? wp_unslash( $_POST['rows'] ) : '[]';
		// phpcs:enable

		if ( '' === $name ) {
			$name = __( 'Untitled View', 'fsweb-comments-core' );
		}

		$valid_skins = array( 'default', 'dark', 'minimal', 'colorful', 'flat', 'custom' );
		if ( ! in_array( $skin, $valid_skins, true ) ) {
			$skin = 'default';
		}

		$rows = json_decode( $raw_rows, true );
		if ( ! is_array( $rows ) ) {
			$rows = array();
		}

		// Sanitize rows.
		$clean_rows = array();
		$valid_types = array( 'image', 'name', 'content', 'rating', 'date', 'category', 'text', 'textarea', 'email', 'url', 'select' );
		foreach ( $rows as $row ) {
			$clean_cols = array();
			if ( isset( $row['columns'] ) && is_array( $row['columns'] ) ) {
				$col_count = count( $row['columns'] );
				$col_width = $col_count > 0 ? floor( 100 / $col_count ) : 100;
				foreach ( $row['columns'] as $col ) {
					$clean_comps = array();
					if ( isset( $col['components'] ) && is_array( $col['components'] ) ) {
						foreach ( $col['components'] as $comp ) {
							if ( isset( $comp['type'] ) && in_array( $comp['type'], $valid_types, true ) ) {
								$clean_comp = array(
									'type'     => $comp['type'],
									'field_id' => isset( $comp['field_id'] ) ? sanitize_key( $comp['field_id'] ) : '',
								);

								// Sanitize styles.
								if ( isset( $comp['styles'] ) && is_array( $comp['styles'] ) ) {
									$clean_comp['styles'] = $this->sanitize_component_styles( $comp['styles'], $comp['type'] );
								}

								$clean_comps[] = $clean_comp;
							}
						}
					}
					$clean_cols[] = array(
						'width'      => $col_width,
						'components' => $clean_comps,
					);
				}
			}
			if ( ! empty( $clean_cols ) ) {
				$clean_rows[] = array( 'columns' => $clean_cols );
			}
		}

		if ( empty( $clean_rows ) ) {
			$forms = get_option( 'fsweb_review_forms', array() );
			$clean_rows = array( $this->default_view_row_from_form( $form_id, $forms ) );
		}

		$views = get_option( 'fsweb_review_views', array() );

		if ( '' === $view_id || ! isset( $views[ $view_id ] ) ) {
			$view_id = substr( wp_generate_password( 8, false, false ), 0, 8 );
		}

		$views[ $view_id ] = array(
			'name'       => $name,
			'layout'     => $layout,
			'count'      => $count,
			'order'      => $order,
			'category'   => $category,
			'form_id'    => $form_id,
			'skin'       => $skin,
			'custom_css' => 'custom' === $skin ? $custom_css : '',
			'rows'       => $clean_rows,
		);

		update_option( 'fsweb_review_views', $views );

		wp_send_json_success( array(
			'message'   => __( 'View saved successfully!', 'fsweb-comments-core' ),
			'view_id'   => $view_id,
			'shortcode' => '[fsweb_reviews id="' . $view_id . '"]',
		) );
	}

	/**
	 * AJAX handler — delete a view.
	 *
	 * @since    1.0.0
	 */
	public function ajax_delete_view() {
		check_ajax_referer( 'fsweb_views_save', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fsweb-comments-core' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$view_id = isset( $_POST['view_id'] ) ? sanitize_key( wp_unslash( $_POST['view_id'] ) ) : '';
		// phpcs:enable

		$views = get_option( 'fsweb_review_views', array() );
		unset( $views[ $view_id ] );
		update_option( 'fsweb_review_views', $views );

		wp_send_json_success( array( 'message' => __( 'View deleted.', 'fsweb-comments-core' ) ) );
	}

	/**
	 * AJAX handler — get components for a connected form.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_form_components() {
		check_ajax_referer( 'fsweb_views_save', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fsweb-comments-core' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$form_id = isset( $_POST['form_id'] ) ? sanitize_key( wp_unslash( $_POST['form_id'] ) ) : '';
		// phpcs:enable

		$forms = get_option( 'fsweb_review_forms', array() );
		$components = $this->get_available_components( $form_id, $forms );

		wp_send_json_success( array( 'components' => $components ) );
	}

	/**
	 * Auto-create a default form template if none exist.
	 *
	 * @since    1.0.0
	 * @return array The forms array (now containing the default).
	 */
	private function maybe_create_default_form() {
		$forms = get_option( 'fsweb_review_forms', array() );
		if ( ! empty( $forms ) ) {
			return $forms;
		}

		$default_id = 'default';
		$forms[ $default_id ] = array(
			'name'   => __( 'Default Form', 'fsweb-comments-core' ),
			'fields' => $this->default_form_fields(),
		);

		update_option( 'fsweb_review_forms', $forms );
		return $forms;
	}

	/**
	 * Auto-create a default view template if none exist.
	 *
	 * Connects to the default form (or first available form).
	 *
	 * @since    1.0.0
	 * @return array The views array (now containing the default).
	 */
	private function maybe_create_default_view() {
		$views = get_option( 'fsweb_review_views', array() );
		if ( ! empty( $views ) ) {
			return $views;
		}

		// Ensure a default form exists so the view can connect to it.
		$forms = $this->maybe_create_default_form();
		$form_id = 'default';
		if ( ! isset( $forms[ $form_id ] ) ) {
			$form_id = array_key_first( $forms );
		}

		$default_id = 'default';
		$views[ $default_id ] = array(
			'name'     => __( 'Default View', 'fsweb-comments-core' ),
			'layout'   => 'grid',
			'count'    => 6,
			'order'    => 'newest',
			'category' => '',
			'form_id'  => $form_id,
			'rows'     => array( $this->default_view_row_from_form( $form_id, $forms ) ),
		);

		update_option( 'fsweb_review_views', $views );
		return $views;
	}

	/**
	 * Sanitize component styles, allowing only safe CSS values.
	 *
	 * @since    1.0.0
	 * @param    array  $styles Raw style key-value pairs.
	 * @param    string $type   Component type (for allowed-property filtering).
	 * @return   array  Clean styles.
	 */
	private function sanitize_component_styles( $styles, $type ) {
		// Allowed CSS properties (camelCase as stored).
		$allowed_text = array(
			'text_align', 'font_weight', 'font_style', 'font_size',
			'color', 'background', 'margin', 'padding',
		);
		$allowed_image = array(
			'width', 'height', 'border_radius', 'object_fit', 'box_shadow',
		);
		$allowed_rating = array(
			'star_size', 'star_color', 'gap', 'text_align',
		);
		$allowed_badge = array(
			'text_align', 'font_size', 'color', 'font_style',
		);

		switch ( $type ) {
			case 'image':  $allowed = $allowed_image;  break;
			case 'rating': $allowed = $allowed_rating; break;
			case 'date':
			case 'category': $allowed = $allowed_badge; break;
			default:       $allowed = $allowed_text;   break;
		}

		$clean = array();
		foreach ( $styles as $key => $val ) {
			if ( ! in_array( $key, $allowed, true ) ) {
				continue;
			}
			$val = trim( $val );
			if ( '' === $val ) {
				continue;
			}
			// Basic safety: strip any dangerous characters.
			$val = preg_replace( '/[^a-zA-Z0-9#%\-_.,;:()\s]/', '', $val );
			$clean[ $key ] = $val;
		}

		return $clean;
	}
}
