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
	 * Two modes:
	 *   - List (default) — shows saved templates with edit/copy/delete.
	 *   - Builder — when ?new=1 or ?form_id=xxx, shows the drag-and-drop builder.
	 *
	 * @since    1.0.0
	 */
	public function render_builder_page() {
		$forms   = get_option( 'fswwp_review_forms', array() );
		$edit_id = isset( $_GET['form_id'] ) ? sanitize_key( wp_unslash( $_GET['form_id'] ) ) : '';
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
		<div class="wrap fswwp-cc-builder-wrap">
			<h1><?php esc_html_e( 'Form Builder', 'fswwp-comments-core' ); ?></h1>

			<div class="fswwp-cc-list-toolbar">
				<a href="<?php echo esc_url( $new_url ); ?>" class="button button-primary">
					<span class="dashicons dashicons-plus-alt" style="vertical-align:text-bottom;"></span>
					<?php esc_html_e( 'New Form', 'fswwp-comments-core' ); ?>
				</a>
			</div>

			<?php if ( ! empty( $forms ) ) : ?>
				<table class="wp-list-table widefat fixed striped fswwp-cc-templates-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Form Name', 'fswwp-comments-core' ); ?></th>
							<th><?php esc_html_e( 'Shortcode', 'fswwp-comments-core' ); ?></th>
							<th><?php esc_html_e( 'Fields', 'fswwp-comments-core' ); ?></th>
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
										<span class="fswwp-cc-multi-badge">Multi-step</span>
									<?php endif; ?>
								</td>
								<td>
									<div class="fswwp-cc-shortcode-copy-wrap">
										<code class="fswwp-cc-shortcode-copy">[fswwp_review_form id="<?php echo esc_attr( $fid ); ?>"]</code>
										<button type="button" class="button button-small fswwp-cc-copy-btn" data-code="[fswwp_review_form id=&quot;<?php echo esc_attr( $fid ); ?>&quot;]" title="<?php esc_attr_e( 'Copy shortcode', 'fswwp-comments-core' ); ?>">
											<span class="dashicons dashicons-clipboard"></span>
										</button>
									</div>
								</td>
								<td><?php echo (int) ( $is_multi ? $step_count . ' steps' : $field_count . ' fields' ); ?></td>
								<td>
									<div class="fswwp-cc-action-buttons">
										<a href="<?php echo esc_url( add_query_arg( 'form_id', $fid, remove_query_arg( 'new' ) ) ); ?>" class="button button-small fswwp-cc-edit-btn" title="<?php esc_attr_e( 'Edit', 'fswwp-comments-core' ); ?>">
											<span class="dashicons dashicons-edit"></span>
										</a>
										<button type="button" class="button button-small fswwp-cc-delete-form" data-id="<?php echo esc_attr( $fid ); ?>" title="<?php esc_attr_e( 'Delete', 'fswwp-comments-core' ); ?>">
											<span class="dashicons dashicons-trash"></span>
										</button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="fswwp-cc-empty-state">
					<p><?php esc_html_e( 'No forms yet. Create your first form to start collecting reviews!', 'fswwp-comments-core' ); ?></p>
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

		$nonce    = wp_create_nonce( 'fswwp_builder_save' );
		$list_url = remove_query_arg( array( 'form_id', 'new' ) );
		?>
		<div class="wrap fswwp-cc-builder-wrap">
			<h1><?php esc_html_e( 'Form Builder', 'fswwp-comments-core' ); ?></h1>

			<!-- Back to list -->
			<p>
				<a href="<?php echo esc_url( $list_url ); ?>" class="button">
					<span class="dashicons dashicons-arrow-left-alt" style="vertical-align:text-bottom;"></span>
					<?php esc_html_e( 'Back to list', 'fswwp-comments-core' ); ?>
				</a>
			</p>

			<!-- Toolbar -->
			<div class="fswwp-cc-builder-toolbar" style="margin-bottom:16px;">
				<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
					<input type="text" id="fswwp-cc-form-name" class="regular-text"
						value="<?php echo esc_attr( $current ? $current['name'] : '' ); ?>"
						placeholder="<?php esc_attr_e( 'Form name', 'fswwp-comments-core' ); ?>">
					<label id="fswwp-cc-multi-step-toggle" style="display:flex;align-items:center;gap:6px;cursor:pointer;white-space:nowrap;">
						<input type="checkbox" id="fswwp-cc-multi-step" <?php checked( $multi_step ); ?>>
						<?php esc_html_e( 'Multi-step', 'fswwp-comments-core' ); ?>
					</label>
					<button type="button" id="fswwp-cc-new-form" class="button">
						<?php esc_html_e( 'Clear & New', 'fswwp-comments-core' ); ?>
					</button>
					<button type="button" id="fswwp-cc-save-form" class="button button-primary">
						<?php esc_html_e( 'Save Form', 'fswwp-comments-core' ); ?>
					</button>
					<input type="hidden" id="fswwp-cc-form-id" value="<?php echo esc_attr( $edit_id ); ?>">
				</div>
			</div>

			<div class="fswwp-cc-builder">
				<!-- Field palette -->
				<div class="fswwp-cc-builder-palette">
					<h3><?php esc_html_e( 'Available Fields', 'fswwp-comments-core' ); ?></h3>
					<div class="fswwp-cc-palette-items">
						<div class="fswwp-cc-palette-item" data-type="image">
							<span class="dashicons dashicons-format-image"></span>
							<?php esc_html_e( 'Image', 'fswwp-comments-core' ); ?>
						</div>
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
						<div class="fswwp-cc-palette-item" data-type="date">
							<span class="dashicons dashicons-calendar"></span>
							<?php esc_html_e( 'Date', 'fswwp-comments-core' ); ?>
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
					<div id="fswwp-cc-form-fields" class="fswwp-cc-fields-sortable" data-nonce="<?php echo esc_attr( $nonce ); ?>" style="<?php echo $multi_step ? 'display:none;' : ''; ?>">
						<?php foreach ( $fields as $i => $field ) : ?>
							<?php $this->render_field_card( $field, $i ); ?>
						<?php endforeach; ?>
					</div>

					<!-- Multi-step container -->
					<div id="fswwp-cc-steps-container" style="<?php echo $multi_step ? '' : 'display:none;'; ?>">
						<?php if ( $multi_step && ! empty( $steps ) ) : ?>
							<?php foreach ( $steps as $si => $step ) : ?>
								<div class="fswwp-cc-step" data-step-id="<?php echo esc_attr( $step['id'] ); ?>">
									<div class="fswwp-cc-step-header">
										<span class="dashicons dashicons-move fswwp-cc-step-handle"></span>
										<input type="text" class="fswwp-cc-step-label" value="<?php echo esc_attr( $step['label'] ); ?>" placeholder="<?php esc_attr_e( 'Step name', 'fswwp-comments-core' ); ?>">
										<span class="fswwp-cc-step-badge"><?php echo (int) ( $si + 1 ); ?></span>
										<button type="button" class="fswwp-cc-step-delete dashicons dashicons-trash" title="<?php esc_attr_e( 'Delete step', 'fswwp-comments-core' ); ?>"></button>
									</div>
									<div class="fswwp-cc-step-fields fswwp-cc-fields-sortable" data-step="<?php echo esc_attr( $step['id'] ); ?>">
										<?php foreach ( $step['fields'] as $fi => $field ) : ?>
											<?php $this->render_field_card( $field, $fi ); ?>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<div class="fswwp-cc-step" data-step-id="step_1">
								<div class="fswwp-cc-step-header">
									<span class="dashicons dashicons-move fswwp-cc-step-handle"></span>
									<input type="text" class="fswwp-cc-step-label" value="<?php esc_attr_e( 'Step 1', 'fswwp-comments-core' ); ?>" placeholder="<?php esc_attr_e( 'Step name', 'fswwp-comments-core' ); ?>">
									<span class="fswwp-cc-step-badge">1</span>
									<button type="button" class="fswwp-cc-step-delete dashicons dashicons-trash" title="<?php esc_attr_e( 'Delete step', 'fswwp-comments-core' ); ?>"></button>
								</div>
								<div class="fswwp-cc-step-fields fswwp-cc-fields-sortable" data-step="step_1"></div>
							</div>
						<?php endif; ?>
						<button type="button" id="fswwp-cc-add-step" class="button">+ Add Step</button>
					</div>

					<div id="fswwp-cc-builder-empty" style="<?php echo ! $multi_step && ! empty( $fields ) ? 'display:none;' : ( $multi_step ? 'display:none;' : '' ); ?>">
						<p><?php esc_html_e( 'Drag fields here from the palette on the left. Click a field to edit its properties.', 'fswwp-comments-core' ); ?></p>
					</div>
					<div id="fswwp-cc-builder-message" class="notice" style="display:none;"></div>
				</div>
			</div>
		</div>

		<!-- Field edit modal (rendered once, JS populates it) -->
		<div id="fswwp-cc-field-modal" class="fswwp-cc-modal" style="display:none;">
			<div class="fswwp-cc-modal-backdrop"></div>
			<div class="fswwp-cc-modal-content">
				<div class="fswwp-cc-modal-header">
					<h3><?php esc_html_e( 'Field Settings', 'fswwp-comments-core' ); ?></h3>
					<button type="button" class="fswwp-cc-modal-close dashicons dashicons-no-alt"></button>
				</div>
				<div class="fswwp-cc-modal-body">
					<div class="fswwp-cc-field-row">
						<label><?php esc_html_e( 'Field ID', 'fswwp-comments-core' ); ?></label>
						<input type="text" id="fswwp-cc-modal-field-id" class="regular-text">
						<p class="description"><?php esc_html_e( 'Unique identifier for this field. Used in shortcodes and templates.', 'fswwp-comments-core' ); ?></p>
					</div>
					<div class="fswwp-cc-field-row">
						<label><?php esc_html_e( 'Type', 'fswwp-comments-core' ); ?></label>
						<select id="fswwp-cc-modal-field-type">
							<option value="image"><?php esc_html_e( 'Image', 'fswwp-comments-core' ); ?></option>
							<option value="text"><?php esc_html_e( 'Text', 'fswwp-comments-core' ); ?></option>
							<option value="textarea"><?php esc_html_e( 'Textarea', 'fswwp-comments-core' ); ?></option>
							<option value="email"><?php esc_html_e( 'Email', 'fswwp-comments-core' ); ?></option>
							<option value="url"><?php esc_html_e( 'URL', 'fswwp-comments-core' ); ?></option>
							<option value="date"><?php esc_html_e( 'Date', 'fswwp-comments-core' ); ?></option>
							<option value="select"><?php esc_html_e( 'Select', 'fswwp-comments-core' ); ?></option>
							<option value="rating"><?php esc_html_e( 'Rating', 'fswwp-comments-core' ); ?></option>
						</select>
					</div>
					<div class="fswwp-cc-field-row">
						<label><?php esc_html_e( 'Label', 'fswwp-comments-core' ); ?></label>
						<input type="text" id="fswwp-cc-modal-field-label" class="regular-text">
					</div>
					<div class="fswwp-cc-field-row">
						<label><?php esc_html_e( 'Placeholder', 'fswwp-comments-core' ); ?></label>
						<input type="text" id="fswwp-cc-modal-field-placeholder" class="regular-text">
					</div>
					<div class="fswwp-cc-field-row fswwp-cc-modal-options-row" style="display:none;">
						<label><?php esc_html_e( 'Options', 'fswwp-comments-core' ); ?></label>
						<textarea id="fswwp-cc-modal-field-options" rows="4" placeholder="<?php esc_attr_e( 'One per line', 'fswwp-comments-core' ); ?>"></textarea>
						<p class="description"><?php esc_html_e( 'One option per line.', 'fswwp-comments-core' ); ?></p>
					</div>
					<div class="fswwp-cc-field-row">
						<label>
							<input type="checkbox" id="fswwp-cc-modal-field-required">
							<?php esc_html_e( 'Required', 'fswwp-comments-core' ); ?>
						</label>
					</div>
				</div>
				<div class="fswwp-cc-modal-footer">
					<button type="button" id="fswwp-cc-modal-save" class="button button-primary"><?php esc_html_e( 'Apply', 'fswwp-comments-core' ); ?></button>
					<button type="button" id="fswwp-cc-modal-cancel" class="button"><?php esc_html_e( 'Cancel', 'fswwp-comments-core' ); ?></button>
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
				'id'          => 'fswwp_image',
				'type'        => 'image',
				'label'       => __( 'Image', 'fswwp-comments-core' ),
				'placeholder' => '',
				'required'    => false,
			),
			array(
				'id'          => 'fswwp_name',
				'type'        => 'text',
				'label'       => __( 'Name', 'fswwp-comments-core' ),
				'placeholder' => __( 'Insert your name', 'fswwp-comments-core' ),
				'required'    => true,
			),
			array(
				'id'          => 'fswwp_email',
				'type'        => 'email',
				'label'       => __( 'Email', 'fswwp-comments-core' ),
				'placeholder' => __( 'Insert your email', 'fswwp-comments-core' ),
				'required'    => true,
			),
			array(
				'id'          => 'fswwp_rating',
				'type'        => 'rating',
				'label'       => __( 'Rating', 'fswwp-comments-core' ),
				'placeholder' => '',
				'required'    => true,
			),
			array(
				'id'          => 'fswwp_description',
				'type'        => 'textarea',
				'label'       => __( 'Description', 'fswwp-comments-core' ),
				'placeholder' => __( 'Insert your review description', 'fswwp-comments-core' ),
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
			'image'    => __( 'Image', 'fswwp-comments-core' ),
			'text'     => __( 'Text', 'fswwp-comments-core' ),
			'textarea' => __( 'Textarea', 'fswwp-comments-core' ),
			'email'    => __( 'Email', 'fswwp-comments-core' ),
			'url'      => __( 'URL', 'fswwp-comments-core' ),
			'date'     => __( 'Date', 'fswwp-comments-core' ),
			'select'   => __( 'Select', 'fswwp-comments-core' ),
			'rating'   => __( 'Rating', 'fswwp-comments-core' ),
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
		<div class="fswwp-cc-field-card" data-id="<?php echo esc_attr( $field['id'] ); ?>">
			<div class="fswwp-cc-field-header">
				<span class="dashicons dashicons-move fswwp-cc-field-handle"></span>
				<span class="dashicons <?php echo esc_attr( $type_icon ); ?> fswwp-cc-field-icon"></span>
				<strong class="fswwp-cc-field-label-text"><?php echo esc_html( $field['label'] ); ?></strong>
				<span class="fswwp-cc-field-type-badge"><?php echo esc_html( $type_label ); ?></span>
				<button type="button" class="fswwp-cc-field-edit dashicons dashicons-edit" title="<?php esc_attr_e( 'Edit field', 'fswwp-comments-core' ); ?>"></button>
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
		$raw      = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : '[]';
		$name     = isset( $_POST['form_name'] ) ? sanitize_text_field( wp_unslash( $_POST['form_name'] ) ) : '';
		$form_id  = isset( $_POST['form_id'] ) ? sanitize_key( wp_unslash( $_POST['form_id'] ) ) : '';
		$multi    = isset( $_POST['multi_step'] ) && '1' === $_POST['multi_step'];
		$raw_steps = isset( $_POST['steps'] ) ? wp_unslash( $_POST['steps'] ) : '[]';
		// phpcs:enable

		if ( '' === $name ) {
			$name = __( 'Untitled Form', 'fswwp-comments-core' );
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
		$forms = get_option( 'fswwp_review_forms', array() );

		// Generate new ID or use existing.
		if ( '' === $form_id || ! isset( $forms[ $form_id ] ) ) {
			$form_id = substr( wp_generate_password( 8, false, false ), 0, 8 );
		}

		$form_data = array(
			'name'       => $name,
			'multi_step' => $multi,
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
	 * Two modes:
	 *   - List (default) — shows saved views with edit/copy/delete.
	 *   - Builder — when ?new=1 or ?view_id=xxx, shows the row/column builder.
	 *
	 * @since    1.0.0
	 */
	public function render_views_page() {
		$views   = get_option( 'fswwp_review_views', array() );
		$edit_id = isset( $_GET['view_id'] ) ? sanitize_key( wp_unslash( $_GET['view_id'] ) ) : '';
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
		<div class="wrap fswwp-cc-builder-wrap">
			<h1><?php esc_html_e( 'Views Builder', 'fswwp-comments-core' ); ?></h1>

			<div class="fswwp-cc-list-toolbar">
				<a href="<?php echo esc_url( $new_url ); ?>" class="button button-primary">
					<span class="dashicons dashicons-plus-alt" style="vertical-align:text-bottom;"></span>
					<?php esc_html_e( 'New View', 'fswwp-comments-core' ); ?>
				</a>
			</div>

			<?php if ( ! empty( $views ) ) : ?>
				<table class="wp-list-table widefat fixed striped fswwp-cc-templates-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'View Name', 'fswwp-comments-core' ); ?></th>
							<th><?php esc_html_e( 'Shortcode', 'fswwp-comments-core' ); ?></th>
							<th><?php esc_html_e( 'Layout', 'fswwp-comments-core' ); ?></th>
							<th><?php esc_html_e( 'Connected Form', 'fswwp-comments-core' ); ?></th>
							<th style="width:100px;"></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $views as $vid => $view ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $view['name'] ); ?></strong></td>
								<td>
									<div class="fswwp-cc-shortcode-copy-wrap">
										<code class="fswwp-cc-shortcode-copy">[fswwp_reviews id="<?php echo esc_attr( $vid ); ?>"]</code>
										<button type="button" class="button button-small fswwp-cc-copy-btn" data-code="[fswwp_reviews id=&quot;<?php echo esc_attr( $vid ); ?>&quot;]" title="<?php esc_attr_e( 'Copy shortcode', 'fswwp-comments-core' ); ?>">
											<span class="dashicons dashicons-clipboard"></span>
										</button>
									</div>
								</td>
								<td><?php echo esc_html( ucfirst( $view['layout'] ) ); ?></td>
								<td><?php echo isset( $view['form_id'] ) && ! empty( $view['form_id'] ) ? esc_html( $view['form_id'] ) : '—'; ?></td>
								<td>
									<div class="fswwp-cc-action-buttons">
										<a href="<?php echo esc_url( add_query_arg( 'view_id', $vid, remove_query_arg( 'new' ) ) ); ?>" class="button button-small fswwp-cc-edit-btn" title="<?php esc_attr_e( 'Edit', 'fswwp-comments-core' ); ?>">
											<span class="dashicons dashicons-edit"></span>
										</a>
										<button type="button" class="button button-small fswwp-cc-delete-view" data-id="<?php echo esc_attr( $vid ); ?>" title="<?php esc_attr_e( 'Delete', 'fswwp-comments-core' ); ?>">
											<span class="dashicons dashicons-trash"></span>
										</button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="fswwp-cc-empty-state">
					<p><?php esc_html_e( 'No views yet. Create your first view to display reviews!', 'fswwp-comments-core' ); ?></p>
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
		$forms = get_option( 'fswwp_review_forms', array() );

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

		$nonce    = wp_create_nonce( 'fswwp_views_save' );
		$list_url = remove_query_arg( array( 'view_id', 'new' ) );
		?>
		<div class="wrap fswwp-cc-builder-wrap">
			<h1><?php esc_html_e( 'Views Builder', 'fswwp-comments-core' ); ?></h1>

			<!-- Back to list -->
			<p>
				<a href="<?php echo esc_url( $list_url ); ?>" class="button">
					<span class="dashicons dashicons-arrow-left-alt" style="vertical-align:text-bottom;"></span>
					<?php esc_html_e( 'Back to list', 'fswwp-comments-core' ); ?>
				</a>
			</p>

			<!-- Toolbar -->
			<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
				<input type="text" id="fswwp-cc-view-name" class="regular-text"
					value="<?php echo esc_attr( $current ? $current['name'] : '' ); ?>"
					placeholder="<?php esc_attr_e( 'View name', 'fswwp-comments-core' ); ?>">

				<label style="font-weight:600;font-size:13px;"><?php esc_html_e( 'Connected Form:', 'fswwp-comments-core' ); ?></label>
				<select id="fswwp-cc-view-form-id">
					<option value=""><?php esc_html_e( '— None —', 'fswwp-comments-core' ); ?></option>
					<?php foreach ( $forms as $fid => $form ) : ?>
						<option value="<?php echo esc_attr( $fid ); ?>" <?php selected( $connected_form_id, $fid ); ?>>
							<?php echo esc_html( $form['name'] . ' (' . $fid . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</select>

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
				<button type="button" id="fswwp-cc-new-view" class="button"><?php esc_html_e( 'Clear & New', 'fswwp-comments-core' ); ?></button>
				<button type="button" id="fswwp-cc-save-view" class="button button-primary"><?php esc_html_e( 'Save View', 'fswwp-comments-core' ); ?></button>
				<input type="hidden" id="fswwp-cc-view-id" value="<?php echo esc_attr( $edit_id ); ?>">
			</div>

			<div class="fswwp-cc-builder" data-nonce="<?php echo esc_attr( $nonce ); ?>">
				<!-- Sidebar palette — only "Add Row" when form is connected (components already in layout) -->
				<div class="fswwp-cc-builder-palette">
					<h3><?php esc_html_e( 'Components', 'fswwp-comments-core' ); ?></h3>

					<?php if ( ! empty( $connected_form_id ) ) : ?>
						<p class="description" style="margin:0 0 8px;">
							<?php esc_html_e( 'Components from the connected form are already placed in the default layout. Drag them between columns or add more rows below.', 'fswwp-comments-core' ); ?>
						</p>
					<?php else : ?>
						<div class="fswwp-cc-palette-items" id="fswwp-cc-views-palette">
							<?php foreach ( $available_components as $comp ) : ?>
								<div class="fswwp-cc-palette-item" data-type="<?php echo esc_attr( $comp['type'] ); ?>" data-field-id="<?php echo esc_attr( $comp['id'] ); ?>">
									<span class="dashicons <?php echo esc_attr( $comp['icon'] ); ?>"></span>
									<?php echo esc_html( $comp['label'] ); ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<div style="margin-top:<?php echo ! empty( $connected_form_id ) ? '0' : '16px'; ?>;">
						<button type="button" id="fswwp-cc-add-row" class="button" style="width:100%;">
							<span class="dashicons dashicons-plus-alt fswwp-cc-btn-icon"></span>
							<?php esc_html_e( 'Add Row', 'fswwp-comments-core' ); ?>
						</button>
					</div>
				</div>

				<!-- Canvas -->
				<div class="fswwp-cc-views-canvas">
					<div id="fswwp-cc-rows">
						<?php foreach ( $rows as $ri => $row ) : ?>
							<?php $this->render_view_row( $row, $ri, $connected_form_fields ); ?>
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
			array( 'type' => 'image',    'id' => 'fswwp_image',    'label' => __( 'Image', 'fswwp-comments-core' ),    'icon' => 'dashicons-format-image' ),
			array( 'type' => 'name',     'id' => 'fswwp_name',     'label' => __( 'Name', 'fswwp-comments-core' ),     'icon' => 'dashicons-admin-users' ),
			array( 'type' => 'content',  'id' => 'fswwp_content',  'label' => __( 'Content', 'fswwp-comments-core' ),  'icon' => 'dashicons-editor-paragraph' ),
			array( 'type' => 'rating',   'id' => 'fswwp_rating',   'label' => __( 'Rating', 'fswwp-comments-core' ),   'icon' => 'dashicons-star-filled' ),
			array( 'type' => 'date',     'id' => 'fswwp_date',     'label' => __( 'Date', 'fswwp-comments-core' ),     'icon' => 'dashicons-calendar' ),
			array( 'type' => 'category', 'id' => 'fswwp_category', 'label' => __( 'Category', 'fswwp-comments-core' ), 'icon' => 'dashicons-category' ),
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
						<div class="fswwp-cc-col-inner">
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

		// Use field label when available, otherwise fall back to type name.
		if ( $field_id && isset( $field_labels[ $field_id ] ) ) {
			$display_label = $field_labels[ $field_id ];
		} else {
			$display_label = isset( $labels[ $type ] ) ? $labels[ $type ] : ucfirst( $type );
		}
		?>
		<div class="fswwp-cc-view-comp" data-type="<?php echo esc_attr( $type ); ?>" data-field-id="<?php echo esc_attr( $field_id ); ?>">
			<span class="dashicons <?php echo esc_attr( isset( $icons[ $type ] ) ? $icons[ $type ] : 'dashicons-edit' ); ?>"></span>
			<span><?php echo esc_html( $display_label ); ?></span>
			<button type="button" class="fswwp-cc-comp-delete dashicons dashicons-no-alt"></button>
		</div>
		<?php
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
		$form_id = isset( $_POST['form_id'] ) ? sanitize_key( wp_unslash( $_POST['form_id'] ) ) : '';
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
								$clean_comps[] = array(
									'type'     => $comp['type'],
									'field_id' => isset( $comp['field_id'] ) ? sanitize_key( $comp['field_id'] ) : '',
								);
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
			$forms = get_option( 'fswwp_review_forms', array() );
			$clean_rows = array( $this->default_view_row_from_form( $form_id, $forms ) );
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
			'form_id'  => $form_id,
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

	/**
	 * AJAX handler — get components for a connected form.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_form_components() {
		check_ajax_referer( 'fswwp_views_save', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fswwp-comments-core' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$form_id = isset( $_POST['form_id'] ) ? sanitize_key( wp_unslash( $_POST['form_id'] ) ) : '';
		// phpcs:enable

		$forms = get_option( 'fswwp_review_forms', array() );
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
		$forms = get_option( 'fswwp_review_forms', array() );
		if ( ! empty( $forms ) ) {
			return $forms;
		}

		$default_id = 'default';
		$forms[ $default_id ] = array(
			'name'   => __( 'Default Form', 'fswwp-comments-core' ),
			'fields' => $this->default_form_fields(),
		);

		update_option( 'fswwp_review_forms', $forms );
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
		$views = get_option( 'fswwp_review_views', array() );
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
			'name'     => __( 'Default View', 'fswwp-comments-core' ),
			'layout'   => 'grid',
			'count'    => 6,
			'order'    => 'newest',
			'category' => '',
			'form_id'  => $form_id,
			'rows'     => array( $this->default_view_row_from_form( $form_id, $forms ) ),
		);

		update_option( 'fswwp_review_views', $views );
		return $views;
	}
}
