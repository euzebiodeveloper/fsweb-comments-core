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
		$is_license_page = 'fswwp_review_page_fswwp-comments-core-license' === $hook_suffix;
		$is_builder_page = 'fswwp_review_page_fswwp-comments-core-builder' === $hook_suffix;

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

		if ( $is_builder_page ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script(
				'fswwp-cc-builder',
				plugin_dir_url( __FILE__ ) . 'js/fswwp-comments-core-builder.js',
				array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable' ),
				$this->version,
				true
			);
		}

		if ( 'fswwp_review_page_fswwp-comments-core-views' === $hook_suffix ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-droppable' );
			wp_enqueue_script(
				'fswwp-cc-views',
				plugin_dir_url( __FILE__ ) . 'js/fswwp-comments-core-views.js',
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
		$parent = 'edit.php?post_type=fswwp_review';

		// Dashboard (overview).
		add_submenu_page(
			$parent,
			__( 'Dashboard', 'fswwp-comments-core' ),
			__( 'Dashboard', 'fswwp-comments-core' ),
			'manage_options',
			'fswwp-comments-core',
			array( $this, 'render_main_page' )
		);

		// License.
		add_submenu_page(
			$parent,
			__( 'License', 'fswwp-comments-core' ),
			__( 'License', 'fswwp-comments-core' ),
			'manage_options',
			'fswwp-comments-core-license',
			array( $this, 'render_license_page' )
		);

		// Form Builder.
		add_submenu_page(
			$parent,
			__( 'Form Builder', 'fswwp-comments-core' ),
			__( 'Form Builder', 'fswwp-comments-core' ),
			'manage_options',
			'fswwp-comments-core-builder',
			array( $this, 'render_builder_page' )
		);

		// Views Builder.
		add_submenu_page(
			$parent,
			__( 'Views Builder', 'fswwp-comments-core' ),
			__( 'Views Builder', 'fswwp-comments-core' ),
			'manage_options',
			'fswwp-comments-core-views',
			array( $this, 'render_views_page' )
		);
	}

	/**
	 * Render the Dashboard page.
	 *
	 * @since    1.0.0
	 */
	public function render_main_page() {
		// Stats.
		$total_reviews = wp_count_posts( 'fswwp_review' );
		$total         = isset( $total_reviews->publish ) ? $total_reviews->publish : 0;

		$month_reviews = new \WP_Query( array(
			'post_type'      => 'fswwp_review',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'date_query'     => array(
				array( 'after' => 'first day of this month' ),
			),
			'no_found_rows'  => true,
		) );
		$month_count = $month_reviews->found_posts;

		$categories = wp_count_terms( array(
			'taxonomy' => 'fswwp_review_category',
			'hide_empty' => false,
		) );
		if ( is_wp_error( $categories ) ) {
			$categories = 0;
		}

		// Recent reviews.
		$recent = new \WP_Query( array(
			'post_type'      => 'fswwp_review',
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'no_found_rows'  => true,
		) );

		$license_status = get_option( 'fswwp_license_status', 'inactive' );
		$license_url    = admin_url( 'admin.php?page=fswwp-comments-core-license&post_type=fswwp_review' );
		?>
		<div class="wrap fswwp-cc-dashboard">
			<h1><?php esc_html_e( 'Dashboard', 'fswwp-comments-core' ); ?></h1>

			<!-- Stats cards -->
			<div class="fswwp-cc-stats">
				<div class="fswwp-cc-stat-card">
					<span class="fswwp-cc-stat-number"><?php echo esc_html( $total ); ?></span>
					<span class="fswwp-cc-stat-label"><?php esc_html_e( 'Total Reviews', 'fswwp-comments-core' ); ?></span>
				</div>
				<div class="fswwp-cc-stat-card">
					<span class="fswwp-cc-stat-number"><?php echo esc_html( $month_count ); ?></span>
					<span class="fswwp-cc-stat-label"><?php esc_html_e( 'This Month', 'fswwp-comments-core' ); ?></span>
				</div>
				<div class="fswwp-cc-stat-card">
					<span class="fswwp-cc-stat-number"><?php echo esc_html( $categories ); ?></span>
					<span class="fswwp-cc-stat-label"><?php esc_html_e( 'Categories', 'fswwp-comments-core' ); ?></span>
				</div>
				<div class="fswwp-cc-stat-card fswwp-cc-stat-card--<?php echo esc_attr( $license_status ); ?>">
					<span class="fswwp-cc-stat-number">
						<?php echo 'active' === $license_status
							? esc_html__( 'Active', 'fswwp-comments-core' )
							: esc_html__( 'Inactive', 'fswwp-comments-core' ); ?>
					</span>
					<span class="fswwp-cc-stat-label"><?php esc_html_e( 'License', 'fswwp-comments-core' ); ?></span>
				</div>
			</div>

			<div class="fswwp-cc-dash-grid">
				<!-- Recent reviews -->
				<div class="fswwp-cc-dash-panel">
					<div class="fswwp-cc-dash-panel-header">
						<h2><?php esc_html_e( 'Recent Reviews', 'fswwp-comments-core' ); ?></h2>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=fswwp_review' ) ); ?>" class="button button-small">
							<?php esc_html_e( 'View All', 'fswwp-comments-core' ); ?>
						</a>
					</div>

					<?php if ( $recent->have_posts() ) : ?>
						<table class="wp-list-table widefat fixed striped fswwp-cc-recent-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Review', 'fswwp-comments-core' ); ?></th>
									<th><?php esc_html_e( 'Date', 'fswwp-comments-core' ); ?></th>
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
												<span class="fswwp-cc-has-thumb" title="<?php esc_attr_e( 'Has photo', 'fswwp-comments-core' ); ?>">📷</span>
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( get_the_date() ); ?></td>
									</tr>
								<?php endwhile; ?>
								<?php wp_reset_postdata(); ?>
							</tbody>
						</table>
					<?php else : ?>
						<p class="fswwp-cc-empty">
							<?php esc_html_e( 'No reviews yet. Create your first review to get started!', 'fswwp-comments-core' ); ?>
						</p>
					<?php endif; ?>
				</div>

				<!-- Quick actions -->
				<div class="fswwp-cc-dash-panel">
					<div class="fswwp-cc-dash-panel-header">
						<h2><?php esc_html_e( 'Quick Actions', 'fswwp-comments-core' ); ?></h2>
					</div>
					<div class="fswwp-cc-quick-actions">
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=fswwp_review' ) ); ?>" class="button button-primary button-large">
							<?php esc_html_e( 'Add New Review', 'fswwp-comments-core' ); ?>
						</a>
						<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=fswwp_review_category&post_type=fswwp_review' ) ); ?>" class="button button-large">
							<?php esc_html_e( 'Manage Categories', 'fswwp-comments-core' ); ?>
						</a>
						<a href="<?php echo esc_url( $license_url ); ?>" class="button button-large">
							<?php echo 'active' === $license_status
								? esc_html__( 'License Settings', 'fswwp-comments-core' )
								: esc_html__( 'Activate License', 'fswwp-comments-core' ); ?>
						</a>
					</div>

					<div class="fswwp-cc-shortcode-help">
						<h3><?php esc_html_e( 'Shortcodes', 'fswwp-comments-core' ); ?></h3>
						<p><?php esc_html_e( 'Use these shortcodes on any page or post:', 'fswwp-comments-core' ); ?></p>
						<p>
							<strong><?php esc_html_e( 'Display reviews:', 'fswwp-comments-core' ); ?></strong>
							<code>[fswwp_reviews id="view-id"]</code>
						</p>
						<p>
							<strong><?php esc_html_e( 'Review form:', 'fswwp-comments-core' ); ?></strong>
							<code>[fswwp_review_form id="form-id"]</code>
						</p>
						<p class="description">
							<?php esc_html_e( 'Create views and forms in Views Builder and Form Builder. Each gets a unique shortcode — just copy and paste.', 'fswwp-comments-core' ); ?>
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
							<input type="text" id="fswwp-cc-license-key" name="fswwp_license_key" class="regular-text"
								value="<?php echo esc_attr( $license_key ); ?>"
								placeholder="<?php esc_attr_e( 'e.g. FSWWP-XXXX-XXXX-XXXX', 'fswwp-comments-core' ); ?>"
								<?php echo 'active' === $license_status ? 'readonly' : ''; ?>>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status', 'fswwp-comments-core' ); ?></th>
						<td>
							<span id="fswwp-cc-license-status" class="fswwp-cc-status fswwp-cc-status--<?php echo esc_attr( $license_status ); ?>">
								<?php
								switch ( $license_status ) {
									case 'active':  esc_html_e( 'Active', 'fswwp-comments-core' );  break;
									case 'expired': esc_html_e( 'Expired', 'fswwp-comments-core' ); break;
									case 'invalid': esc_html_e( 'Invalid', 'fswwp-comments-core' ); break;
									case 'pending': esc_html_e( 'Pending', 'fswwp-comments-core' ); break;
									default:        esc_html_e( 'Inactive', 'fswwp-comments-core' ); break;
								}
								?>
							</span>
						</td>
					</tr>
					<tr id="fswwp-cc-expires-row" style="<?php echo 'active' === $license_status && ! empty( $license_expires ) ? '' : 'display:none;'; ?>">
						<th scope="row"><?php esc_html_e( 'Expires', 'fswwp-comments-core' ); ?></th>
						<td><span id="fswwp-cc-license-expires"><?php echo ! empty( $license_expires ) ? esc_html( mysql2date( get_option( 'date_format' ), $license_expires ) ) : esc_html__( 'Lifetime', 'fswwp-comments-core' ); ?></span></td>
					</tr>
				</table>

				<p class="submit">
					<?php if ( 'active' === $license_status ) : ?>
						<button type="button" id="fswwp-cc-deactivate-license" class="button button-secondary"><?php esc_html_e( 'Deactivate', 'fswwp-comments-core' ); ?></button>
					<?php else : ?>
						<button type="button" id="fswwp-cc-activate-license" class="button button-primary"><?php esc_html_e( 'Activate', 'fswwp-comments-core' ); ?></button>
					<?php endif; ?>
				</p>
				<div id="fswwp-cc-license-message" class="notice" style="display:none;"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Form Builder page.
	 *
	 * @since    1.0.0
	 */
	public function render_builder_page() {
		$forms  = get_option( 'fswwp_review_forms', array() );
		$edit_id = isset( $_GET['form_id'] ) ? sanitize_key( wp_unslash( $_GET['form_id'] ) ) : '';

		// Find the form being edited, or pick the first one.
		$current = null;
		if ( '' !== $edit_id && isset( $forms[ $edit_id ] ) ) {
			$current = $forms[ $edit_id ];
		} elseif ( ! empty( $forms ) ) {
			$current = reset( $forms );
		}

		$fields = $current ? $current['fields'] : array();
		$nonce  = wp_create_nonce( 'fswwp_builder_save' );
		?>
		<div class="wrap fswwp-cc-builder-wrap">
			<h1><?php esc_html_e( 'Form Builder', 'fswwp-comments-core' ); ?></h1>

			<!-- Saved forms list -->
			<?php if ( ! empty( $forms ) ) : ?>
				<div class="fswwp-cc-forms-list">
					<h3><?php esc_html_e( 'Your Forms', 'fswwp-comments-core' ); ?></h3>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Form Name', 'fswwp-comments-core' ); ?></th>
								<th><?php esc_html_e( 'Shortcode', 'fswwp-comments-core' ); ?></th>
								<th><?php esc_html_e( 'Fields', 'fswwp-comments-core' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $forms as $fid => $form ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( add_query_arg( 'form_id', $fid ) ); ?>">
											<strong><?php echo esc_html( $form['name'] ); ?></strong>
										</a>
									</td>
									<td>
										<code class="fswwp-cc-shortcode-copy" title="<?php esc_attr_e( 'Click to copy', 'fswwp-comments-core' ); ?>">[fswwp_review_form id="<?php echo esc_attr( $fid ); ?>"]</code>
									</td>
									<td><?php echo count( $form['fields'] ); ?></td>
									<td>
										<button type="button" class="button button-small fswwp-cc-delete-form" data-id="<?php echo esc_attr( $fid ); ?>">
											<?php esc_html_e( 'Delete', 'fswwp-comments-core' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<!-- Form name + new form -->
			<div class="fswwp-cc-builder-toolbar" style="margin-bottom:16px;">
				<div style="display:flex;align-items:center;gap:12px;">
					<input type="text" id="fswwp-cc-form-name" class="regular-text"
						value="<?php echo esc_attr( $current ? $current['name'] : '' ); ?>"
						placeholder="<?php esc_attr_e( 'Form name', 'fswwp-comments-core' ); ?>">
					<button type="button" id="fswwp-cc-new-form" class="button">
						<?php esc_html_e( 'New Form', 'fswwp-comments-core' ); ?>
					</button>
					<button type="button" id="fswwp-cc-save-form" class="button button-primary">
						<?php esc_html_e( 'Save Form', 'fswwp-comments-core' ); ?>
					</button>
					<input type="hidden" id="fswwp-cc-form-id" value="<?php echo esc_attr( $edit_id ); ?>">
				</div>
				<div id="fswwp-cc-shortcode-output" style="margin-top:8px;<?php echo $current ? '' : 'display:none;'; ?>">
					<?php esc_html_e( 'Shortcode:', 'fswwp-comments-core' ); ?>
					<code id="fswwp-cc-shortcode-text"><?php echo $current ? esc_html( '[fswwp_review_form id="' . $edit_id . '"]' ) : ''; ?></code>
					<span class="description" style="margin-left:8px;"><?php esc_html_e( '(copy and paste into any page)', 'fswwp-comments-core' ); ?></span>
				</div>
			</div>

			<div class="fswwp-cc-builder">
				<!-- Field palette -->
				<div class="fswwp-cc-builder-palette">
					<h3><?php esc_html_e( 'Available Fields', 'fswwp-comments-core' ); ?></h3>
					<div class="fswwp-cc-palette-items">
						<div class="fswwp-cc-palette-item" data-type="text">
							<span class="dashicons dashicons-editor-textcolor"></span>
							<?php esc_html_e( 'Text', 'fswwp-comments-core' ); ?>
						</div>
						<div class="fswwp-cc-palette-item" data-type="textarea">
							<span class="dashicons dashicons-editor-paragraph"></span>
							<?php esc_html_e( 'Textarea', 'fswwp-comments-core' ); ?>
						</div>
						<div class="fswwp-cc-palette-item" data-type="email">
							<span class="dashicons dashicons-email"></span>
							<?php esc_html_e( 'Email', 'fswwp-comments-core' ); ?>
						</div>
						<div class="fswwp-cc-palette-item" data-type="url">
							<span class="dashicons dashicons-admin-links"></span>
							<?php esc_html_e( 'URL', 'fswwp-comments-core' ); ?>
						</div>
						<div class="fswwp-cc-palette-item" data-type="select">
							<span class="dashicons dashicons-menu-alt"></span>
							<?php esc_html_e( 'Select', 'fswwp-comments-core' ); ?>
						</div>
						<div class="fswwp-cc-palette-item" data-type="rating">
							<span class="dashicons dashicons-star-filled"></span>
							<?php esc_html_e( 'Rating', 'fswwp-comments-core' ); ?>
						</div>
					</div>
				</div>

				<!-- Canvas -->
				<div class="fswwp-cc-builder-canvas">
					<div id="fswwp-cc-form-fields" class="fswwp-cc-fields-sortable" data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<?php foreach ( $fields as $i => $field ) : ?>
							<?php $this->render_field_card( $field, $i ); ?>
						<?php endforeach; ?>
					</div>
					<div id="fswwp-cc-builder-empty" style="<?php echo ! empty( $fields ) ? 'display:none;' : ''; ?>">
						<p><?php esc_html_e( 'Drag fields here from the palette on the left.', 'fswwp-comments-core' ); ?></p>
					</div>
					<div id="fswwp-cc-builder-message" class="notice" style="display:none;"></div>
				</div>
			</div>
		</div>
		<?php
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
			'text'     => __( 'Text', 'fswwp-comments-core' ),
			'textarea' => __( 'Textarea', 'fswwp-comments-core' ),
			'email'    => __( 'Email', 'fswwp-comments-core' ),
			'url'      => __( 'URL', 'fswwp-comments-core' ),
			'select'   => __( 'Select', 'fswwp-comments-core' ),
			'rating'   => __( 'Rating', 'fswwp-comments-core' ),
		);

		$type_label = isset( $type_labels[ $field['type'] ] ) ? $type_labels[ $field['type'] ] : $field['type'];
		$options    = isset( $field['options'] ) ? $field['options'] : '';
		?>
		<div class="fswwp-cc-field-card" data-id="<?php echo esc_attr( $field['id'] ); ?>">
			<div class="fswwp-cc-field-header">
				<span class="dashicons dashicons-move fswwp-cc-field-handle"></span>
				<strong class="fswwp-cc-field-label-text"><?php echo esc_html( $field['label'] ); ?></strong>
				<span class="fswwp-cc-field-type-badge"><?php echo esc_html( $type_label ); ?></span>
				<button type="button" class="fswwp-cc-field-toggle dashicons dashicons-arrow-down-alt2"></button>
				<button type="button" class="fswwp-cc-field-delete dashicons dashicons-trash"></button>
			</div>
			<div class="fswwp-cc-field-body" style="display:none;">
				<div class="fswwp-cc-field-row">
					<label><?php esc_html_e( 'Type', 'fswwp-comments-core' ); ?></label>
					<select class="fswwp-cc-field-type">
						<?php foreach ( $type_labels as $val => $lbl ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $field['type'], $val ); ?>>
								<?php echo esc_html( $lbl ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="fswwp-cc-field-row">
					<label><?php esc_html_e( 'Label', 'fswwp-comments-core' ); ?></label>
					<input type="text" class="fswwp-cc-field-label" value="<?php echo esc_attr( $field['label'] ); ?>">
				</div>
				<div class="fswwp-cc-field-row">
					<label><?php esc_html_e( 'Placeholder', 'fswwp-comments-core' ); ?></label>
					<input type="text" class="fswwp-cc-field-placeholder" value="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>">
				</div>
				<div class="fswwp-cc-field-row fswwp-cc-field-options-row" style="<?php echo 'select' === $field['type'] ? '' : 'display:none;'; ?>">
					<label><?php esc_html_e( 'Options', 'fswwp-comments-core' ); ?></label>
					<textarea class="fswwp-cc-field-options" rows="3" placeholder="<?php esc_attr_e( 'One per line', 'fswwp-comments-core' ); ?>"><?php echo esc_textarea( $options ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One option per line.', 'fswwp-comments-core' ); ?></p>
				</div>
				<div class="fswwp-cc-field-row">
					<label>
						<input type="checkbox" class="fswwp-cc-field-required" <?php checked( ! empty( $field['required'] ) ); ?>>
						<?php esc_html_e( 'Required', 'fswwp-comments-core' ); ?>
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
		check_ajax_referer( 'fswwp_builder_save', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fswwp-comments-core' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$raw    = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : '[]';
		$name   = isset( $_POST['form_name'] ) ? sanitize_text_field( wp_unslash( $_POST['form_name'] ) ) : '';
		$form_id = isset( $_POST['form_id'] ) ? sanitize_key( wp_unslash( $_POST['form_id'] ) ) : '';
		// phpcs:enable

		$fields = json_decode( $raw, true );
		if ( ! is_array( $fields ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'fswwp-comments-core' ) ) );
		}

		if ( '' === $name ) {
			$name = __( 'Untitled Form', 'fswwp-comments-core' );
		}

		// Sanitize fields.
		$clean = array();
		foreach ( $fields as $field ) {
			$clean[] = array(
				'id'          => sanitize_key( $field['id'] ),
				'type'        => in_array( $field['type'], array( 'text', 'textarea', 'email', 'url', 'select', 'rating' ), true ) ? $field['type'] : 'text',
				'label'       => sanitize_text_field( $field['label'] ),
				'placeholder' => sanitize_text_field( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ),
				'required'    => ! empty( $field['required'] ),
				'options'     => isset( $field['options'] ) ? sanitize_textarea_field( $field['options'] ) : '',
			);
		}

		// Load existing forms.
		$forms = get_option( 'fswwp_review_forms', array() );

		// Generate new ID or use existing.
		if ( '' === $form_id || ! isset( $forms[ $form_id ] ) ) {
			$form_id = substr( wp_generate_password( 8, false, false ), 0, 8 );
		}

		$forms[ $form_id ] = array(
			'name'   => $name,
			'fields' => $clean,
		);

		update_option( 'fswwp_review_forms', $forms );

		wp_send_json_success( array(
			'message'   => __( 'Form saved successfully!', 'fswwp-comments-core' ),
			'form_id'   => $form_id,
			'shortcode' => '[fswwp_review_form id="' . $form_id . '"]',
		) );
	}

	/**
	 * AJAX handler — delete a form.
	 *
	 * @since    1.0.0
	 */
	public function ajax_delete_form() {
		check_ajax_referer( 'fswwp_builder_save', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fswwp-comments-core' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$form_id = isset( $_POST['form_id'] ) ? sanitize_key( wp_unslash( $_POST['form_id'] ) ) : '';
		// phpcs:enable

		$forms = get_option( 'fswwp_review_forms', array() );
		unset( $forms[ $form_id ] );
		update_option( 'fswwp_review_forms', $forms );

		wp_send_json_success( array( 'message' => __( 'Form deleted.', 'fswwp-comments-core' ) ) );
	}

	// ------------------------------------------------------------------//
	//  Views Builder
	// ------------------------------------------------------------------//

	/**
	 * Render the Views Builder page.
	 *
	 * @since    1.0.0
	 */
	public function render_views_page() {
		$views   = get_option( 'fswwp_review_views', array() );
		$edit_id = isset( $_GET['view_id'] ) ? sanitize_key( wp_unslash( $_GET['view_id'] ) ) : '';

		$current = null;
		if ( '' !== $edit_id && isset( $views[ $edit_id ] ) ) {
			$current = $views[ $edit_id ];
		} elseif ( ! empty( $views ) ) {
			$current = reset( $views );
		}

		$rows = $current && isset( $current['rows'] ) ? $current['rows'] : array();
		$nonce = wp_create_nonce( 'fswwp_views_save' );

		// Default row if none.
		if ( empty( $rows ) ) {
			$rows = array( $this->default_row() );
		}
		?>
		<div class="wrap fswwp-cc-builder-wrap">
			<h1><?php esc_html_e( 'Views Builder', 'fswwp-comments-core' ); ?></h1>

			<!-- Saved views list -->
			<?php if ( ! empty( $views ) ) : ?>
				<div class="fswwp-cc-forms-list">
					<h3><?php esc_html_e( 'Your Views', 'fswwp-comments-core' ); ?></h3>
					<table class="wp-list-table widefat fixed striped">
						<thead><tr>
							<th><?php esc_html_e( 'Name', 'fswwp-comments-core' ); ?></th>
							<th><?php esc_html_e( 'Shortcode', 'fswwp-comments-core' ); ?></th>
							<th><?php esc_html_e( 'Layout', 'fswwp-comments-core' ); ?></th>
							<th></th>
						</tr></thead>
						<tbody>
							<?php foreach ( $views as $vid => $view ) : ?>
								<tr>
									<td><a href="<?php echo esc_url( add_query_arg( 'view_id', $vid ) ); ?>"><strong><?php echo esc_html( $view['name'] ); ?></strong></a></td>
									<td><code class="fswwp-cc-shortcode-copy">[fswwp_reviews id="<?php echo esc_attr( $vid ); ?>"]</code></td>
									<td><?php echo esc_html( ucfirst( $view['layout'] ) ); ?></td>
									<td><button type="button" class="button button-small fswwp-cc-delete-view" data-id="<?php echo esc_attr( $vid ); ?>"><?php esc_html_e( 'Delete', 'fswwp-comments-core' ); ?></button></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<!-- Toolbar -->
			<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
				<input type="text" id="fswwp-cc-view-name" class="regular-text"
					value="<?php echo esc_attr( $current ? $current['name'] : '' ); ?>"
					placeholder="<?php esc_attr_e( 'View name', 'fswwp-comments-core' ); ?>">
				<select id="fswwp-cc-view-layout">
					<option value="grid" <?php selected( $current ? $current['layout'] : 'grid', 'grid' ); ?>>Grid</option>
					<option value="slider" <?php selected( $current ? $current['layout'] : '', 'slider' ); ?>>Slider</option>
					<option value="list" <?php selected( $current ? $current['layout'] : '', 'list' ); ?>>List</option>
				</select>
				<input type="number" id="fswwp-cc-view-count" class="small-text" min="1" max="50"
					value="<?php echo esc_attr( $current ? $current['count'] : 6 ); ?>" title="Count">
				<select id="fswwp-cc-view-order">
					<option value="newest" <?php selected( $current ? $current['order'] : 'newest', 'newest' ); ?>>Newest</option>
					<option value="oldest" <?php selected( $current ? $current['order'] : '', 'oldest' ); ?>>Oldest</option>
					<option value="random" <?php selected( $current ? $current['order'] : '', 'random' ); ?>>Random</option>
					<option value="menu_order" <?php selected( $current ? $current['order'] : '', 'menu_order' ); ?>>Menu order</option>
				</select>
				<button type="button" id="fswwp-cc-new-view" class="button"><?php esc_html_e( 'New', 'fswwp-comments-core' ); ?></button>
				<button type="button" id="fswwp-cc-save-view" class="button button-primary"><?php esc_html_e( 'Save View', 'fswwp-comments-core' ); ?></button>
				<input type="hidden" id="fswwp-cc-view-id" value="<?php echo esc_attr( $edit_id ); ?>">
				<span id="fswwp-cc-view-shortcode-output" style="<?php echo $current ? '' : 'display:none;'; ?>">
					<code id="fswwp-cc-view-shortcode-text"><?php echo $current ? esc_html( '[fswwp_reviews id="' . $edit_id . '"]' ) : ''; ?></code>
				</span>
			</div>

			<div class="fswwp-cc-builder" data-nonce="<?php echo esc_attr( $nonce ); ?>">
				<!-- Component palette -->
				<div class="fswwp-cc-builder-palette">
					<h3><?php esc_html_e( 'Components', 'fswwp-comments-core' ); ?></h3>
					<div class="fswwp-cc-palette-items">
						<div class="fswwp-cc-palette-item" data-type="image"><span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'Image', 'fswwp-comments-core' ); ?></div>
						<div class="fswwp-cc-palette-item" data-type="name"><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'Name', 'fswwp-comments-core' ); ?></div>
						<div class="fswwp-cc-palette-item" data-type="content"><span class="dashicons dashicons-editor-paragraph"></span> <?php esc_html_e( 'Content', 'fswwp-comments-core' ); ?></div>
						<div class="fswwp-cc-palette-item" data-type="rating"><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Rating', 'fswwp-comments-core' ); ?></div>
						<div class="fswwp-cc-palette-item" data-type="date"><span class="dashicons dashicons-calendar"></span> <?php esc_html_e( 'Date', 'fswwp-comments-core' ); ?></div>
						<div class="fswwp-cc-palette-item" data-type="category"><span class="dashicons dashicons-category"></span> <?php esc_html_e( 'Category', 'fswwp-comments-core' ); ?></div>
					</div>

					<div style="margin-top:16px;">
						<button type="button" id="fswwp-cc-add-row" class="button" style="width:100%;">
							<span class="dashicons dashicons-plus-alt" style="vertical-align:middle;"></span>
							<?php esc_html_e( 'Add Row', 'fswwp-comments-core' ); ?>
						</button>
					</div>
				</div>

				<!-- Canvas -->
				<div class="fswwp-cc-views-canvas">
					<div id="fswwp-cc-rows">
						<?php foreach ( $rows as $ri => $row ) : ?>
							<?php $this->render_view_row( $row, $ri ); ?>
						<?php endforeach; ?>
					</div>
					<div id="fswwp-cc-view-empty" style="<?php echo ! empty( $rows ) ? 'display:none;' : ''; ?>">
						<p><?php esc_html_e( 'Click "Add Row" to start building your review card layout. Then drag components into the columns.', 'fswwp-comments-core' ); ?></p>
					</div>
					<div id="fswwp-cc-view-message" class="notice" style="display:none;"></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single row in the views builder.
	 */
	private function render_view_row( $row, $index ) {
		$cols      = isset( $row['columns'] ) ? $row['columns'] : array();
		$col_count = count( $cols );
		?>
		<div class="fswwp-cc-view-row" data-row="<?php echo (int) $index; ?>">
			<div class="fswwp-cc-view-row-toolbar">
				<span class="dashicons dashicons-move fswwp-cc-row-handle"></span>
				<strong><?php esc_html_e( 'Row', 'fswwp-comments-core' ); ?> <?php echo (int) $index + 1; ?></strong>
				<button type="button" class="fswwp-cc-col-btn<?php echo 1 === $col_count ? ' active' : ''; ?>" data-cols="1">1 col</button>
				<button type="button" class="fswwp-cc-col-btn<?php echo 2 === $col_count ? ' active' : ''; ?>" data-cols="2">2 cols</button>
				<button type="button" class="fswwp-cc-col-btn<?php echo 3 === $col_count ? ' active' : ''; ?>" data-cols="3">3 cols</button>
				<button type="button" class="fswwp-cc-delete-row dashicons dashicons-trash"></button>
			</div>
			<div class="fswwp-cc-view-cols">
				<?php foreach ( $cols as $ci => $col ) : ?>
					<div class="fswwp-cc-view-col" data-col="<?php echo (int) $ci; ?>" style="flex:<?php echo isset( $col['width'] ) ? (int) $col['width'] : 100; ?>;">
						<div class="fswwp-cc-col-inner fswwp-cc-col-sortable">
							<?php if ( isset( $col['components'] ) ) :
								foreach ( $col['components'] as $comp ) :
									$this->render_view_component( $comp );
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
	private function render_view_component( $comp ) {
		$icons = array(
			'image'    => 'dashicons-format-image',
			'name'     => 'dashicons-admin-users',
			'content'  => 'dashicons-editor-paragraph',
			'rating'   => 'dashicons-star-filled',
			'date'     => 'dashicons-calendar',
			'category' => 'dashicons-category',
		);
		$labels = array(
			'image' => 'Image', 'name' => 'Name', 'content' => 'Content',
			'rating' => 'Rating', 'date' => 'Date', 'category' => 'Category',
		);
		$type = $comp['type'];
		?>
		<div class="fswwp-cc-view-comp" data-type="<?php echo esc_attr( $type ); ?>">
			<span class="dashicons <?php echo esc_attr( $icons[ $type ] ); ?>"></span>
			<span><?php echo esc_html( $labels[ $type ] ); ?></span>
			<button type="button" class="fswwp-cc-comp-delete dashicons dashicons-no-alt"></button>
		</div>
		<?php
	}

	/**
	 * Default row with 2 columns (image left, text right).
	 */
	private function default_row() {
		return array(
			'columns' => array(
				array(
					'width'      => 40,
					'components' => array(
						array( 'type' => 'image' ),
					),
				),
				array(
					'width'      => 60,
					'components' => array(
						array( 'type' => 'name' ),
						array( 'type' => 'content' ),
						array( 'type' => 'rating' ),
					),
				),
			),
		);
	}

	/**
	 * AJAX handler — save a view.
	 *
	 * @since    1.0.0
	 */
	public function ajax_save_view() {
		check_ajax_referer( 'fswwp_views_save', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fswwp-comments-core' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$name    = isset( $_POST['view_name'] ) ? sanitize_text_field( wp_unslash( $_POST['view_name'] ) ) : '';
		$view_id = isset( $_POST['view_id'] ) ? sanitize_key( wp_unslash( $_POST['view_id'] ) ) : '';
		$layout  = isset( $_POST['layout'] ) ? sanitize_key( wp_unslash( $_POST['layout'] ) ) : 'grid';
		$count   = isset( $_POST['count'] ) ? min( max( (int) $_POST['count'], 1 ), 50 ) : 6;
		$order   = isset( $_POST['order'] ) ? sanitize_key( wp_unslash( $_POST['order'] ) ) : 'newest';
		$category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
		$raw_rows = isset( $_POST['rows'] ) ? wp_unslash( $_POST['rows'] ) : '[]';
		// phpcs:enable

		if ( '' === $name ) {
			$name = __( 'Untitled View', 'fswwp-comments-core' );
		}

		$rows = json_decode( $raw_rows, true );
		if ( ! is_array( $rows ) ) {
			$rows = array();
		}

		// Sanitize rows.
		$clean_rows = array();
		$valid_types = array( 'image', 'name', 'content', 'rating', 'date', 'category' );
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
								$clean_comps[] = array( 'type' => $comp['type'] );
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
			$clean_rows = array( $this->default_row() );
		}

		$views = get_option( 'fswwp_review_views', array() );

		if ( '' === $view_id || ! isset( $views[ $view_id ] ) ) {
			$view_id = substr( wp_generate_password( 8, false, false ), 0, 8 );
		}

		$views[ $view_id ] = array(
			'name'     => $name,
			'layout'   => $layout,
			'count'    => $count,
			'order'    => $order,
			'category' => $category,
			'rows'     => $clean_rows,
		);

		update_option( 'fswwp_review_views', $views );

		wp_send_json_success( array(
			'message'   => __( 'View saved successfully!', 'fswwp-comments-core' ),
			'view_id'   => $view_id,
			'shortcode' => '[fswwp_reviews id="' . $view_id . '"]',
		) );
	}

	/**
	 * AJAX handler — delete a view.
	 *
	 * @since    1.0.0
	 */
	public function ajax_delete_view() {
		check_ajax_referer( 'fswwp_views_save', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fswwp-comments-core' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$view_id = isset( $_POST['view_id'] ) ? sanitize_key( wp_unslash( $_POST['view_id'] ) ) : '';
		// phpcs:enable

		$views = get_option( 'fswwp_review_views', array() );
		unset( $views[ $view_id ] );
		update_option( 'fswwp_review_views', $views );

		wp_send_json_success( array( 'message' => __( 'View deleted.', 'fswwp-comments-core' ) ) );
	}
}
