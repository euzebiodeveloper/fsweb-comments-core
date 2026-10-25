<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * The free edition renders every screen of the product. Premium options are
 * visible but inert, marked with a PRO pill that links to the product page.
 * When the PRO add-on is installed and licensed, those pills disappear and the
 * add-on takes over the screens it owns.
 *
 * @link       https://flowsyncweb.com
 * @since      1.0.0
 * @package    Fsweb_Comments_Core
 * @subpackage Fsweb_Comments_Core/admin
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Admin screens, settings and assets.
 *
 * @since 1.0.0
 */
class Fsweb_Comments_Core_Admin {

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Set the plugin identity.
	 *
	 * @param string $plugin_name Plugin slug.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	// ------------------------------------------------------------------//
	//  PRO state
	// ------------------------------------------------------------------//

	/**
	 * Whether the PRO add-on is installed and licensed.
	 *
	 * While it is not, the premium options stay visible but locked.
	 *
	 * @return bool
	 */
	public function is_pro_active() {
		if ( ! defined( 'FSWEB_COMMENTS_CORE_PRO_VERSION' ) ) {
			return false;
		}

		return 'active' === get_option( 'fsweb_comments_core_license_status', 'inactive' );
	}

	/**
	 * Whether the PRO add-on is installed at all.
	 *
	 * @return bool
	 */
	private function is_pro_installed() {
		return defined( 'FSWEB_COMMENTS_CORE_PRO_VERSION' );
	}

	/**
	 * URL of the product page.
	 *
	 * @return string
	 */
	private function pro_url() {
		return defined( 'FSWEB_COMMENTS_CORE_PRO_URL' ) ? FSWEB_COMMENTS_CORE_PRO_URL : 'https://flowsyncweb.com/';
	}

	// ------------------------------------------------------------------//
	//  Assets
	// ------------------------------------------------------------------//

	/**
	 * Enqueue the admin stylesheet on our screens.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue_styles( $hook_suffix ) {
		if ( ! $this->is_plugin_screen( $hook_suffix ) ) {
			return;
		}

		$stylesheet = FSWEB_COMMENTS_CORE_DIR . 'admin/css/fsweb-comments-core-admin.css';

		wp_enqueue_style(
			$this->plugin_name,
			FSWEB_COMMENTS_CORE_URL . 'admin/css/fsweb-comments-core-admin.css',
			array(),
			file_exists( $stylesheet ) ? (string) filemtime( $stylesheet ) : $this->version
		);
	}

	/**
	 * Enqueue the admin script on our screens.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( ! $this->is_plugin_screen( $hook_suffix ) ) {
			return;
		}

		$script = FSWEB_COMMENTS_CORE_DIR . 'admin/js/fsweb-comments-core-admin.js';

		wp_enqueue_script(
			$this->plugin_name,
			FSWEB_COMMENTS_CORE_URL . 'admin/js/fsweb-comments-core-admin.js',
			array(),
			file_exists( $script ) ? (string) filemtime( $script ) : $this->version,
			true
		);
	}

	/**
	 * Whether the current screen belongs to the plugin.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return bool
	 */
	private function is_plugin_screen( $hook_suffix ) {
		if ( false !== strpos( (string) $hook_suffix, 'fsweb-comments-core' ) ) {
			return true;
		}

		$screen = get_current_screen();

		return $screen && 'fsweb_review' === $screen->post_type;
	}

	// ------------------------------------------------------------------//
	//  Menu
	// ------------------------------------------------------------------//

	/**
	 * Register the submenu pages.
	 *
	 * The PRO add-on registers the builder and views screens with the same
	 * slugs at a later priority, replacing these previews.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		$parent = 'edit.php?post_type=fsweb_review';

		// With the add-on installed it owns the overview and both builders, so the
		// free edition only keeps the settings screen to avoid duplicate menus.
		if ( ! $this->is_pro_installed() ) {
			add_submenu_page(
				$parent,
				__( 'Overview', 'fsweb-comments-core' ),
				__( 'Overview', 'fsweb-comments-core' ),
				'manage_options',
				'fsweb-comments-core',
				array( $this, 'render_overview_page' )
			);

			add_submenu_page(
				$parent,
				__( 'Form Builder', 'fsweb-comments-core' ),
				__( 'Form Builder', 'fsweb-comments-core' ),
				'manage_options',
				'fsweb-comments-core-builder',
				array( $this, 'render_builder_page' )
			);

			add_submenu_page(
				$parent,
				__( 'Views Builder', 'fsweb-comments-core' ),
				__( 'Views Builder', 'fsweb-comments-core' ),
				'manage_options',
				'fsweb-comments-core-views',
				array( $this, 'render_views_page' )
			);
		}

		add_submenu_page(
			$parent,
			__( 'Settings', 'fsweb-comments-core' ),
			__( 'Settings', 'fsweb-comments-core' ),
			'manage_options',
			'fsweb-comments-core-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * URL of one of our screens.
	 *
	 * @param string $slug Screen slug.
	 * @return string
	 */
	private function page_url( $slug ) {
		return admin_url( 'edit.php?post_type=fsweb_review&page=' . $slug );
	}

	// ------------------------------------------------------------------//
	//  Shared UI
	// ------------------------------------------------------------------//

	/**
	 * Render the hero header shared by every screen.
	 *
	 * @param string $title       Screen title.
	 * @param string $description One-line description.
	 * @param string $current     Slug of the current screen.
	 * @param array  $actions     Extra buttons: array( 'label', 'url', 'icon', 'primary' ).
	 * @return void
	 */
	private function render_hero( $title, $description, $current = '', $actions = array() ) {
		$overview = $this->is_pro_installed() ? 'fsweb-comments-core-pro' : 'fsweb-comments-core';

		$screens = array(
			$overview => array(
				'label' => __( 'Overview', 'fsweb-comments-core' ),
				'icon'  => 'dashicons-dashboard',
			),
			'fsweb-comments-core-builder' => array(
				'label' => __( 'Form Builder', 'fsweb-comments-core' ),
				'icon'  => 'dashicons-feedback',
			),
			'fsweb-comments-core-views'   => array(
				'label' => __( 'Views Builder', 'fsweb-comments-core' ),
				'icon'  => 'dashicons-layout',
			),
			'fsweb-comments-core-settings' => array(
				'label' => __( 'Settings', 'fsweb-comments-core' ),
				'icon'  => 'dashicons-admin-generic',
			),
		);
		?>
		<div class="fsweb-cc-hero fsweb-cc-hero--split">
			<div class="fsweb-cc-hero__text">
				<h1><?php echo esc_html( $title ); ?></h1>
				<p><?php echo esc_html( $description ); ?></p>
			</div>
			<div class="fsweb-cc-hero__actions">
				<?php foreach ( $actions as $action ) : ?>
					<a class="button<?php echo ! empty( $action['primary'] ) ? ' button-primary' : ''; ?>"
						href="<?php echo esc_url( $action['url'] ); ?>"
						<?php echo ! empty( $action['target'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
						<?php if ( ! empty( $action['icon'] ) ) : ?>
							<span class="dashicons <?php echo esc_attr( $action['icon'] ); ?>" aria-hidden="true"></span>
						<?php endif; ?>
						<?php echo esc_html( $action['label'] ); ?>
					</a>
				<?php endforeach; ?>

				<?php if ( '' !== $current ) : ?>
					<?php foreach ( $screens as $slug => $screen ) : ?>
						<?php if ( $slug === $current ) : ?>
							<span class="button" aria-current="page">
								<span class="dashicons <?php echo esc_attr( $screen['icon'] ); ?>" aria-hidden="true"></span>
								<?php echo esc_html( $screen['label'] ); ?>
							</span>
						<?php else : ?>
							<a class="button" href="<?php echo esc_url( $this->page_url( $slug ) ); ?>">
								<span class="dashicons <?php echo esc_attr( $screen['icon'] ); ?>" aria-hidden="true"></span>
								<?php echo esc_html( $screen['label'] ); ?>
							</a>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * PRO pill.
	 *
	 * @param bool $locked Whether to print it.
	 * @return void
	 */
	private function pro_tag( $locked = true ) {
		if ( ! $locked ) {
			return;
		}
		?>
		<span class="fsweb-cc-pro-tag"><?php esc_html_e( 'PRO', 'fsweb-comments-core' ); ?></span>
		<?php
	}

	/**
	 * Invitation card shown while PRO is not licensed.
	 *
	 * @param string $title   Card title.
	 * @param string $lead    Lead paragraph.
	 * @param array  $bullets Feature list.
	 * @return void
	 */
	private function render_pro_card( $title, $lead, $bullets ) {
		if ( $this->is_pro_active() ) {
			return;
		}
		?>
		<div class="fsweb-cc-card fsweb-cc-card--pro">
			<div class="fsweb-cc-pro-card">
				<span class="fsweb-cc-pro-card__icon dashicons dashicons-star-filled" aria-hidden="true"></span>
				<div class="fsweb-cc-pro-card__text">
					<h2>
						<?php echo esc_html( $title ); ?>
						<?php $this->pro_tag(); ?>
					</h2>
					<p><?php echo esc_html( $lead ); ?></p>
					<ul class="fsweb-cc-list">
						<?php foreach ( $bullets as $bullet ) : ?>
							<li><?php echo esc_html( $bullet ); ?></li>
						<?php endforeach; ?>
					</ul>
					<p>
						<a class="button button-primary" href="<?php echo esc_url( $this->pro_url() ); ?>" target="_blank" rel="noopener noreferrer">
							<span class="dashicons dashicons-external" aria-hidden="true"></span>
							<?php esc_html_e( 'Get Comments Core PRO', 'fsweb-comments-core' ); ?>
						</a>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a disabled control with the PRO pill.
	 *
	 * @param string $label Control label.
	 * @param string $type  Control type: text, select, checkbox, color.
	 * @param string $hint  Optional description.
	 * @return void
	 */
	private function render_locked_field( $label, $type = 'text', $hint = '' ) {
		?>
		<tr class="fsweb-cc-locked-row">
			<th scope="row">
				<?php echo esc_html( $label ); ?>
				<?php $this->pro_tag(); ?>
			</th>
			<td>
				<?php if ( 'checkbox' === $type ) : ?>
					<label>
						<input type="checkbox" disabled>
						<?php esc_html_e( 'Available in the PRO add-on', 'fsweb-comments-core' ); ?>
					</label>
				<?php elseif ( 'select' === $type ) : ?>
					<select disabled>
						<option><?php esc_html_e( 'Available in the PRO add-on', 'fsweb-comments-core' ); ?></option>
					</select>
				<?php elseif ( 'textarea' === $type ) : ?>
					<textarea class="large-text" rows="3" disabled placeholder="<?php esc_attr_e( 'Custom CSS becomes available with the PRO add-on.', 'fsweb-comments-core' ); ?>"></textarea>
				<?php else : ?>
					<input type="text" class="regular-text" disabled placeholder="<?php esc_attr_e( 'Available in the PRO add-on', 'fsweb-comments-core' ); ?>">
				<?php endif; ?>

				<?php if ( '' !== $hint ) : ?>
					<p class="description"><?php echo esc_html( $hint ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	// ------------------------------------------------------------------//
	//  Settings API
	// ------------------------------------------------------------------//

	/**
	 * Register the settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'fsweb_comments_core_settings',
			'fsweb_comments_core_form_status',
			array(
				'type'              => 'string',
				'default'           => 'open',
				'sanitize_callback' => array( $this, 'sanitize_form_status' ),
			)
		);

		register_setting(
			'fsweb_comments_core_settings',
			'fsweb_comments_core_require_email',
			array(
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);
	}

	/**
	 * Sanitise the form status option.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public function sanitize_form_status( $value ) {
		return 'closed' === $value ? 'closed' : 'open';
	}

	// ------------------------------------------------------------------//
	//  Screens
	// ------------------------------------------------------------------//

	/**
	 * Overview screen.
	 *
	 * @return void
	 */
	public function render_overview_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$counts  = wp_count_posts( 'fsweb_review' );
		$pending = isset( $counts->pending ) ? (int) $counts->pending : 0;
		$publish = isset( $counts->publish ) ? (int) $counts->publish : 0;
		$average = $this->get_average_rating();
		$status  = get_option( 'fsweb_comments_core_form_status', 'open' );
		?>
		<div class="wrap fsweb-cc-wrap">
			<hr class="wp-header-end">
			<?php
			?>
		<div class="wrap fsweb-cc-wrap">
			<?php
			$this->render_hero(
				__( 'Comments Core', 'fsweb-comments-core' ),
				__( 'Collect reviews, moderate them and display them anywhere with a shortcode.', 'fsweb-comments-core' ),
				'fsweb-comments-core',
				array(
					array(
						'label' => __( 'Add review', 'fsweb-comments-core' ),
						'url'   => admin_url( 'post-new.php?post_type=fsweb_review' ),
						'icon'  => 'dashicons-plus-alt',
					),
					array(
						'label'   => __( 'Go PRO', 'fsweb-comments-core' ),
						'url'     => $this->pro_url(),
						'icon'    => 'dashicons-star-filled',
						'primary' => true,
						'target'  => true,
					),
				)
			);
			?>

			<div class="fsweb-cc-stats">
				<div class="fsweb-cc-stat-card">
					<span class="fsweb-cc-stat-label"><?php esc_html_e( 'Published reviews', 'fsweb-comments-core' ); ?></span>
					<span class="fsweb-cc-stat-value"><?php echo esc_html( (string) $publish ); ?></span>
				</div>
				<div class="fsweb-cc-stat-card">
					<span class="fsweb-cc-stat-label"><?php esc_html_e( 'Awaiting moderation', 'fsweb-comments-core' ); ?></span>
					<span class="fsweb-cc-stat-value"><?php echo esc_html( (string) $pending ); ?></span>
				</div>
				<div class="fsweb-cc-stat-card">
					<span class="fsweb-cc-stat-label"><?php esc_html_e( 'Average rating', 'fsweb-comments-core' ); ?></span>
					<span class="fsweb-cc-stat-value"><?php echo esc_html( '' === $average ? '—' : $average ); ?></span>
				</div>
				<div class="fsweb-cc-stat-card">
					<span class="fsweb-cc-stat-label"><?php esc_html_e( 'Submissions', 'fsweb-comments-core' ); ?></span>
					<span class="fsweb-cc-pill <?php echo 'open' === $status ? 'is-on' : 'is-off'; ?>">
						<?php echo 'open' === $status ? esc_html__( 'Open', 'fsweb-comments-core' ) : esc_html__( 'Closed', 'fsweb-comments-core' ); ?>
					</span>
				</div>
			</div>

			<div class="fsweb-cc-grid">
				<div class="fsweb-cc-card">
					<div class="fsweb-cc-card__header">
						<h2>
							<span class="dashicons dashicons-shortcode" aria-hidden="true"></span>
							<?php esc_html_e( 'Shortcodes', 'fsweb-comments-core' ); ?>
						</h2>
					</div>
					<div class="fsweb-cc-card__body">
						<p><?php esc_html_e( 'Display your reviews:', 'fsweb-comments-core' ); ?></p>
						<code class="fsweb-cc-code">[fsweb_reviews layout="grid" count="6" columns="3"]</code>
						<p><?php esc_html_e( 'Show the submission form:', 'fsweb-comments-core' ); ?></p>
						<code class="fsweb-cc-code">[fsweb_review_form]</code>
						<p class="description">
							<?php esc_html_e( 'Attributes: layout (grid or list), count, category, order (newest, oldest, random, menu_order), show_image (yes or no), columns (1 to 4).', 'fsweb-comments-core' ); ?>
						</p>
					</div>
				</div>

				<div class="fsweb-cc-card">
					<div class="fsweb-cc-card__header">
						<h2>
							<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
							<?php esc_html_e( 'Included in the free edition', 'fsweb-comments-core' ); ?>
						</h2>
					</div>
					<div class="fsweb-cc-card__body">
						<ul class="fsweb-cc-list">
							<li><?php esc_html_e( 'Reviews post type with categories', 'fsweb-comments-core' ); ?></li>
							<li><?php esc_html_e( 'Star ratings and photo (featured image)', 'fsweb-comments-core' ); ?></li>
							<li><?php esc_html_e( 'Grid and list layouts', 'fsweb-comments-core' ); ?></li>
							<li><?php esc_html_e( 'Submission form with moderation queue', 'fsweb-comments-core' ); ?></li>
							<li><?php esc_html_e( 'Theme template overrides', 'fsweb-comments-core' ); ?></li>
						</ul>
						<p>
							<a class="button" href="<?php echo esc_url( $this->page_url( 'fsweb-comments-core-settings' ) ); ?>">
								<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
								<?php esc_html_e( 'Open settings', 'fsweb-comments-core' ); ?>
							</a>
						</p>
					</div>
				</div>

				<?php
				$this->render_pro_card(
					__( 'Comments Core PRO', 'fsweb-comments-core' ),
					__( 'The free edition covers the whole flow: reviews, ratings, moderation and shortcodes. The PRO add-on gives you the visual builders and the extra layouts.', 'fsweb-comments-core' ),
					array(
						__( 'Drag and drop Form Builder with 8 field types and multi-step forms', 'fsweb-comments-core' ),
						__( 'Views Builder: compose the review card with rows, columns and components', 'fsweb-comments-core' ),
						__( 'Slider layout and ready-made skins (dark, minimal, colorful, flat)', 'fsweb-comments-core' ),
						__( 'Custom CSS per view and front-end photo upload', 'fsweb-comments-core' ),
						__( 'License activation and automatic updates', 'fsweb-comments-core' ),
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Form Builder screen — unlocked by the PRO add-on.
	 *
	 * @return void
	 */
	public function render_builder_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap fsweb-cc-wrap">
			<?php
			$this->render_hero(
				__( 'Form Builder', 'fsweb-comments-core' ),
				__( 'Build the review form field by field, with extra types and multiple steps.', 'fsweb-comments-core' ),
				'fsweb-comments-core-builder',
				array(
					array(
						'label'   => __( 'Get the PRO add-on', 'fsweb-comments-core' ),
						'url'     => $this->pro_url(),
						'icon'    => 'dashicons-star-filled',
						'primary' => true,
						'target'  => true,
					),
				)
			);
			?>

			<div class="fsweb-cc-grid">
				<div class="fsweb-cc-card">
					<div class="fsweb-cc-card__header">
						<h2>
							<span class="dashicons dashicons-feedback" aria-hidden="true"></span>
							<?php esc_html_e( 'Fields', 'fsweb-comments-core' ); ?>
						</h2>
					</div>
					<div class="fsweb-cc-card__body">
						<table class="form-table fsweb-cc-table" role="presentation">
							<tr>
								<th scope="row"><?php esc_html_e( 'Name', 'fsweb-comments-core' ); ?></th>
								<td><span class="fsweb-cc-pill is-on"><?php esc_html_e( 'Included', 'fsweb-comments-core' ); ?></span></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Email', 'fsweb-comments-core' ); ?></th>
								<td><span class="fsweb-cc-pill is-on"><?php esc_html_e( 'Included', 'fsweb-comments-core' ); ?></span></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Rating (stars)', 'fsweb-comments-core' ); ?></th>
								<td><span class="fsweb-cc-pill is-on"><?php esc_html_e( 'Included', 'fsweb-comments-core' ); ?></span></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Review text', 'fsweb-comments-core' ); ?></th>
								<td><span class="fsweb-cc-pill is-on"><?php esc_html_e( 'Included', 'fsweb-comments-core' ); ?></span></td>
							</tr>
							<?php
							$this->render_locked_field( __( 'Date', 'fsweb-comments-core' ) );
							$this->render_locked_field( __( 'Select', 'fsweb-comments-core' ) );
							$this->render_locked_field( __( 'URL', 'fsweb-comments-core' ) );
							$this->render_locked_field( __( 'Photo upload', 'fsweb-comments-core' ), 'text', __( 'Visitors attach a photo to the review.', 'fsweb-comments-core' ) );
							?>
						</table>
						<p class="description">
							<?php esc_html_e( 'In the free edition the form always has name, email, rating and review. The PRO add-on lets you reorder, remove and add fields.', 'fsweb-comments-core' ); ?>
						</p>
					</div>
				</div>

				<div class="fsweb-cc-card">
					<div class="fsweb-cc-card__header">
						<h2>
							<span class="dashicons dashicons-randomize" aria-hidden="true"></span>
							<?php esc_html_e( 'Flow', 'fsweb-comments-core' ); ?>
						</h2>
					</div>
					<div class="fsweb-cc-card__body">
						<table class="form-table fsweb-cc-table" role="presentation">
							<?php
							$this->render_locked_field( __( 'Multi-step form', 'fsweb-comments-core' ), 'checkbox', __( 'Split the form into steps with a progress indicator.', 'fsweb-comments-core' ) );
							$this->render_locked_field( __( 'Redirect after submit', 'fsweb-comments-core' ), 'text', __( 'Send the visitor to a thank-you page after the review is accepted.', 'fsweb-comments-core' ) );
							$this->render_locked_field( __( 'Saved forms', 'fsweb-comments-core' ), 'select', __( 'Keep several forms and choose one per shortcode with [fsweb_review_form id="..."] .', 'fsweb-comments-core' ) );
							?>
						</table>
					</div>
				</div>

				<?php
				$this->render_pro_card(
					__( 'Unlock the Form Builder', 'fsweb-comments-core' ),
					__( 'Install the PRO add-on on top of this plugin to design the form without code.', 'fsweb-comments-core' ),
					array(
						__( 'Drag and drop the field order', 'fsweb-comments-core' ),
						__( '8 field types: text, email, URL, textarea, rating, select, date and file', 'fsweb-comments-core' ),
						__( 'Multi-step forms with progress indicator', 'fsweb-comments-core' ),
						__( 'Several saved forms, one per shortcode', 'fsweb-comments-core' ),
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Views Builder screen — unlocked by the PRO add-on.
	 *
	 * @return void
	 */
	public function render_views_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap fsweb-cc-wrap">
			<?php
			$this->render_hero(
				__( 'Views Builder', 'fsweb-comments-core' ),
				__( 'Design the review card and pick the layout, the skin and the CSS for each view.', 'fsweb-comments-core' ),
				'fsweb-comments-core-views',
				array(
					array(
						'label'   => __( 'Get the PRO add-on', 'fsweb-comments-core' ),
						'url'     => $this->pro_url(),
						'icon'    => 'dashicons-star-filled',
						'primary' => true,
						'target'  => true,
					),
				)
			);
			?>

			<div class="fsweb-cc-grid">
				<div class="fsweb-cc-card">
					<div class="fsweb-cc-card__header">
						<h2>
							<span class="dashicons dashicons-layout" aria-hidden="true"></span>
							<?php esc_html_e( 'Layout', 'fsweb-comments-core' ); ?>
						</h2>
					</div>
					<div class="fsweb-cc-card__body">
						<table class="form-table fsweb-cc-table" role="presentation">
							<tr>
								<th scope="row"><?php esc_html_e( 'Grid', 'fsweb-comments-core' ); ?></th>
								<td><span class="fsweb-cc-pill is-on"><?php esc_html_e( 'Included', 'fsweb-comments-core' ); ?></span></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'List', 'fsweb-comments-core' ); ?></th>
								<td><span class="fsweb-cc-pill is-on"><?php esc_html_e( 'Included', 'fsweb-comments-core' ); ?></span></td>
							</tr>
							<?php $this->render_locked_field( __( 'Slider', 'fsweb-comments-core' ), 'text', __( 'Swipe, keyboard and dot navigation, no external library.', 'fsweb-comments-core' ) ); ?>
						</table>
						<p class="description">
							<?php esc_html_e( 'In the free edition the shortcode renders the grid and list layouts. The slider is part of the PRO add-on.', 'fsweb-comments-core' ); ?>
						</p>
					</div>
				</div>

				<div class="fsweb-cc-card">
					<div class="fsweb-cc-card__header">
						<h2>
							<span class="dashicons dashicons-art" aria-hidden="true"></span>
							<?php esc_html_e( 'Appearance', 'fsweb-comments-core' ); ?>
						</h2>
					</div>
					<div class="fsweb-cc-card__body">
						<table class="form-table fsweb-cc-table" role="presentation">
							<?php
							$this->render_locked_field( __( 'Skin', 'fsweb-comments-core' ), 'select', __( 'Dark, minimal, colorful and flat, ready to use.', 'fsweb-comments-core' ) );
							$this->render_locked_field( __( 'Custom CSS', 'fsweb-comments-core' ), 'textarea', __( 'Write the CSS of each view without touching the theme.', 'fsweb-comments-core' ) );
							$this->render_locked_field( __( 'Card composition', 'fsweb-comments-core' ), 'select', __( 'Choose which components appear in the card and in which order.', 'fsweb-comments-core' ) );
							?>
						</table>
					</div>
				</div>

				<?php
				$this->render_pro_card(
					__( 'Unlock the Views Builder', 'fsweb-comments-core' ),
					__( 'Install the PRO add-on to compose the card visually and reuse the view in any page.', 'fsweb-comments-core' ),
					array(
						__( 'Rows, columns and components with per-view styles', 'fsweb-comments-core' ),
						__( 'Slider layout with dot navigation', 'fsweb-comments-core' ),
						__( 'Four ready-made skins plus custom CSS', 'fsweb-comments-core' ),
						__( 'Saved views used with [fsweb_reviews id="..."]', 'fsweb-comments-core' ),
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Settings screen.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status  = get_option( 'fsweb_comments_core_form_status', 'open' );
		$require = (bool) get_option( 'fsweb_comments_core_require_email', true );
		?>
		<div class="wrap fsweb-cc-wrap">
			<hr class="wp-header-end">
			<?php
			$this->render_hero(
				__( 'Settings', 'fsweb-comments-core' ),
				__( 'Control the submissions and the appearance of the reviews.', 'fsweb-comments-core' ),
				'fsweb-comments-core-settings'
			);
			?>
			<form method="post" action="options.php">
				<?php settings_fields( 'fsweb_comments_core_settings' ); ?>

				<div class="fsweb-cc-grid">
					<div class="fsweb-cc-card">
						<div class="fsweb-cc-card__header">
							<h2>
								<span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
								<?php esc_html_e( 'Review form', 'fsweb-comments-core' ); ?>
							</h2>
						</div>
						<div class="fsweb-cc-card__body">
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row">
										<label for="fsweb_comments_core_form_status"><?php esc_html_e( 'Submissions', 'fsweb-comments-core' ); ?></label>
									</th>
									<td>
										<select name="fsweb_comments_core_form_status" id="fsweb_comments_core_form_status">
											<option value="open" <?php selected( $status, 'open' ); ?>><?php esc_html_e( 'Open — accept new reviews', 'fsweb-comments-core' ); ?></option>
											<option value="closed" <?php selected( $status, 'closed' ); ?>><?php esc_html_e( 'Closed — hide the form', 'fsweb-comments-core' ); ?></option>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Email', 'fsweb-comments-core' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="fsweb_comments_core_require_email" value="1" <?php checked( $require ); ?>>
											<?php esc_html_e( 'Require the visitor email', 'fsweb-comments-core' ); ?>
										</label>
										<p class="description"><?php esc_html_e( 'The email stays private and is only used to contact the author.', 'fsweb-comments-core' ); ?></p>
									</td>
								</tr>
							</table>
						</div>
					</div>

					<?php if ( ! $this->is_pro_active() ) : ?>
					<div class="fsweb-cc-card">
						<div class="fsweb-cc-card__header">
							<h2>
								<span class="dashicons dashicons-art" aria-hidden="true"></span>
								<?php esc_html_e( 'Appearance', 'fsweb-comments-core' ); ?>
								<?php $this->pro_tag(); ?>
							</h2>
						</div>
						<div class="fsweb-cc-card__body">
							<table class="form-table" role="presentation">
								<?php
								$this->render_locked_field( __( 'Default skin', 'fsweb-comments-core' ), 'select', __( 'Applies a ready-made skin to every view.', 'fsweb-comments-core' ) );
								$this->render_locked_field( __( 'Custom CSS', 'fsweb-comments-core' ), 'textarea', __( 'Extra CSS loaded after the plugin styles.', 'fsweb-comments-core' ) );
								?>
							</table>
							<p class="description">
								<?php esc_html_e( 'The free edition ships the default card. Skins and custom CSS come with the PRO add-on.', 'fsweb-comments-core' ); ?>
							</p>
						</div>
					</div>
					<?php endif; ?>

					<?php
					$this->render_pro_card(
						__( 'Go further with PRO', 'fsweb-comments-core' ),
						__( 'One license unlocks the builders, the skins and the automatic updates.', 'fsweb-comments-core' ),
						array(
							__( 'Form Builder and Views Builder', 'fsweb-comments-core' ),
							__( 'Slider layout, skins and custom CSS', 'fsweb-comments-core' ),
							__( 'Front-end photo upload', 'fsweb-comments-core' ),
							__( 'Automatic updates and priority support', 'fsweb-comments-core' ),
						)
					);
					?>
				</div>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Average rating of the published reviews.
	 *
	 * @return string Formatted with one decimal, or empty string.
	 */
	private function get_average_rating() {
		$ids = get_posts(
			array(
				'post_type'      => 'fsweb_review',
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		if ( empty( $ids ) ) {
			return '';
		}

		$total = 0;
		$rated = 0;

		foreach ( $ids as $id ) {
			$rating = (int) get_post_meta( $id, 'fsweb_rating', true );
			if ( $rating > 0 ) {
				$total += $rating;
				$rated++;
			}
		}

		if ( 0 === $rated ) {
			return '';
		}

		return number_format_i18n( $total / $rated, 1 );
	}
}
