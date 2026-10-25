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
		if ( ! $this->is_pro_installed() ) {
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
	 * The version comes from the file timestamp so a changed stylesheet is
	 * never served from the browser cache.
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
	 * With the add-on installed it owns the overview and both builders, so the
	 * free edition only keeps the settings screen and the PRO page.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		$parent = 'edit.php?post_type=fsweb_review';

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

		add_submenu_page(
			$parent,
			__( 'Go PRO', 'fsweb-comments-core' ),
			'<span class="fsweb-cc-menu-pro">' . esc_html__( 'Go PRO', 'fsweb-comments-core' ) . '</span>',
			'manage_options',
			'fsweb-comments-core-go-pro',
			array( $this, 'render_go_pro_page' )
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
	 * Screens shown in the hero navigation.
	 *
	 * @param string $current Slug of the current screen.
	 * @return array
	 */
	private function nav_screens( $current ) {
		$screens = array();

		if ( 'fsweb-comments-core-go-pro' !== $current ) {
			$screens['fsweb-comments-core-go-pro'] = array(
				'label' => __( 'Go PRO', 'fsweb-comments-core' ),
				'icon'  => 'dashicons-star-filled',
			);
		}

		if ( ! $this->is_pro_installed() ) {
			$screens = array_merge(
				array(
					'fsweb-comments-core'         => array(
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
				),
				$screens
			);
		} else {
			$screens = array_merge(
				array(
					'fsweb-comments-core-pro' => array(
						'label' => __( 'Dashboard', 'fsweb-comments-core' ),
						'icon'  => 'dashicons-dashboard',
					),
				),
				$screens
			);
		}

		$screens['fsweb-comments-core-settings'] = array(
			'label' => __( 'Settings', 'fsweb-comments-core' ),
			'icon'  => 'dashicons-admin-generic',
		);

		return $screens;
	}

	/**
	 * Render the hero header shared by every screen.
	 *
	 * @param string $title       Screen title.
	 * @param string $description One-line description.
	 * @param string $current     Slug of the current screen.
	 * @return void
	 */
	private function render_hero( $title, $description, $current = '' ) {
		$screens = $this->nav_screens( $current );
		?>
		<div class="fsweb-cc-hero fsweb-cc-hero--split">
			<div class="fsweb-cc-hero__text">
				<h1><?php echo esc_html( $title ); ?></h1>
				<p><?php echo esc_html( $description ); ?></p>
			</div>
			<div class="fsweb-cc-hero__actions">
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
			</div>
		</div>
		<?php
	}

	/**
	 * PRO pill.
	 *
	 * @return void
	 */
	private function pro_tag() {
		if ( $this->is_pro_active() ) {
			return;
		}
		?>
		<a class="fsweb-cc-pro-tag" href="<?php echo esc_url( $this->page_url( 'fsweb-comments-core-go-pro' ) ); ?>">
			<?php esc_html_e( 'PRO', 'fsweb-comments-core' ); ?>
		</a>
		<?php
	}

	/**
	 * Render a locked option: label with the PRO pill and a muted value.
	 *
	 * @param string $label Option label.
	 * @param string $hint  Optional description.
	 * @return void
	 */
	private function render_locked_field( $label, $hint = '' ) {
		?>
		<tr class="fsweb-cc-locked-row">
			<th scope="row">
				<?php echo esc_html( $label ); ?>
				<?php $this->pro_tag(); ?>
			</th>
			<td>
				<span class="fsweb-cc-locked">
					<?php esc_html_e( 'Available in the PRO add-on', 'fsweb-comments-core' ); ?>
				</span>
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
				'type'              => 'string',
				'default'           => '1',
				'sanitize_callback' => array( $this, 'sanitize_require_email' ),
			)
		);
	}

	/**
	 * Sanitise the "require email" checkbox.
	 *
	 * The value is stored as '1' or '0': a boolean false would be dropped by
	 * update_option() when the option does not exist yet, and the default
	 * would silently win.
	 *
	 * @param mixed $value Raw value.
	 * @return string '1' when required, '0' otherwise.
	 */
	public function sanitize_require_email( $value ) {
		return ( '1' === $value || 1 === $value || true === $value || 'true' === $value ) ? '1' : '0';
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
			$this->render_hero(
				__( 'Comments Core', 'fsweb-comments-core' ),
				__( 'Collect reviews, moderate them and display them anywhere with a shortcode.', 'fsweb-comments-core' ),
				'fsweb-comments-core'
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
							<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=fsweb_review' ) ); ?>">
								<span class="dashicons dashicons-plus-alt" aria-hidden="true"></span>
								<?php esc_html_e( 'Add review', 'fsweb-comments-core' ); ?>
							</a>
						</p>
					</div>
				</div>
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
			<hr class="wp-header-end">
			<?php
			$this->render_hero(
				__( 'Form Builder', 'fsweb-comments-core' ),
				__( 'Build the review form field by field, with extra types and multiple steps.', 'fsweb-comments-core' ),
				'fsweb-comments-core-builder'
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
							$this->render_locked_field( __( 'Photo upload', 'fsweb-comments-core' ), __( 'Visitors attach a photo to the review.', 'fsweb-comments-core' ) );
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
							$this->render_locked_field( __( 'Multi-step form', 'fsweb-comments-core' ), __( 'Split the form into steps with a progress indicator.', 'fsweb-comments-core' ) );
							$this->render_locked_field( __( 'Redirect after submit', 'fsweb-comments-core' ), __( 'Send the visitor to a thank-you page after the review is accepted.', 'fsweb-comments-core' ) );
							$this->render_locked_field( __( 'Saved forms', 'fsweb-comments-core' ), __( 'Keep several forms and choose one per shortcode with [fsweb_review_form id="..."].', 'fsweb-comments-core' ) );
							?>
						</table>
					</div>
				</div>
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
			<hr class="wp-header-end">
			<?php
			$this->render_hero(
				__( 'Views Builder', 'fsweb-comments-core' ),
				__( 'Design the review card and pick the layout, the skin and the CSS for each view.', 'fsweb-comments-core' ),
				'fsweb-comments-core-views'
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
							<?php $this->render_locked_field( __( 'Slider', 'fsweb-comments-core' ), __( 'Swipe, keyboard and dot navigation, no external library.', 'fsweb-comments-core' ) ); ?>
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
							$this->render_locked_field( __( 'Skin', 'fsweb-comments-core' ), __( 'Dark, minimal, colorful and flat, ready to use.', 'fsweb-comments-core' ) );
							$this->render_locked_field( __( 'Custom CSS', 'fsweb-comments-core' ), __( 'Write the CSS of each view without touching the theme.', 'fsweb-comments-core' ) );
							$this->render_locked_field( __( 'Card composition', 'fsweb-comments-core' ), __( 'Choose which components appear in the card and in which order.', 'fsweb-comments-core' ) );
							?>
						</table>
					</div>
				</div>
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
		$require = '1' === get_option( 'fsweb_comments_core_require_email', '1' );
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

					<div class="fsweb-cc-card">
						<div class="fsweb-cc-card__header">
							<h2>
								<span class="dashicons dashicons-art" aria-hidden="true"></span>
								<?php esc_html_e( 'Appearance', 'fsweb-comments-core' ); ?>
							</h2>
						</div>
						<div class="fsweb-cc-card__body">
							<table class="form-table" role="presentation">
								<?php
								$this->render_locked_field( __( 'Default skin', 'fsweb-comments-core' ), __( 'Applies a ready-made skin to every view.', 'fsweb-comments-core' ) );
								$this->render_locked_field( __( 'Custom CSS', 'fsweb-comments-core' ), __( 'Extra CSS loaded after the plugin styles.', 'fsweb-comments-core' ) );
								?>
							</table>
							<p class="description">
								<?php esc_html_e( 'The free edition ships the default card. Skins and custom CSS come with the PRO add-on.', 'fsweb-comments-core' ); ?>
							</p>
						</div>
					</div>
				</div>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Go PRO screen.
	 *
	 * Single page with the full offer; every other screen only carries the
	 * small PRO pills next to the locked options.
	 *
	 * @return void
	 */
	public function render_go_pro_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap fsweb-cc-wrap">
			<hr class="wp-header-end">
			<?php
			$this->render_hero(
				__( 'Comments Core PRO', 'fsweb-comments-core' ),
				__( 'Unlock the builders, the slider, the skins and the automatic updates with a single license.', 'fsweb-comments-core' ),
				'fsweb-comments-core-go-pro'
			);
			?>

			<div class="fsweb-cc-grid">
				<div class="fsweb-cc-card fsweb-cc-card--pro">
					<div class="fsweb-cc-pro-card">
						<span class="fsweb-cc-pro-card__icon dashicons dashicons-star-filled" aria-hidden="true"></span>
						<div class="fsweb-cc-pro-card__text">
							<h2><?php esc_html_e( 'What the PRO add-on adds', 'fsweb-comments-core' ); ?></h2>
							<ul class="fsweb-cc-list">
								<li><?php esc_html_e( 'Form Builder: 8 field types, drag and drop order, multi-step forms', 'fsweb-comments-core' ); ?></li>
								<li><?php esc_html_e( 'Views Builder: rows, columns and components with per-view styles', 'fsweb-comments-core' ); ?></li>
								<li><?php esc_html_e( 'Slider layout with dot navigation', 'fsweb-comments-core' ); ?></li>
								<li><?php esc_html_e( 'Four ready-made skins plus custom CSS per view', 'fsweb-comments-core' ); ?></li>
								<li><?php esc_html_e( 'Front-end photo upload', 'fsweb-comments-core' ); ?></li>
								<li><?php esc_html_e( 'License activation and automatic updates', 'fsweb-comments-core' ); ?></li>
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

				<div class="fsweb-cc-card">
					<div class="fsweb-cc-card__header">
						<h2>
							<span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>
							<?php esc_html_e( 'How the license works', 'fsweb-comments-core' ); ?>
						</h2>
					</div>
					<div class="fsweb-cc-card__body">
						<ol class="fsweb-cc-list fsweb-cc-list--ordered">
							<li><?php esc_html_e( 'Install the free plugin (this one) and keep it active.', 'fsweb-comments-core' ); ?></li>
							<li><?php esc_html_e( 'Buy the license and download the PRO add-on ZIP.', 'fsweb-comments-core' ); ?></li>
							<li><?php esc_html_e( 'Install and activate the add-on — it appears under Reviews → Dashboard.', 'fsweb-comments-core' ); ?></li>
							<li><?php esc_html_e( 'Open Reviews → License and paste your key. The PRO pills disappear and every feature is unlocked.', 'fsweb-comments-core' ); ?></li>
						</ol>
						<p class="description">
							<?php esc_html_e( 'One key works for both editions: the add-on always validates the base slug fsweb-comments-core.', 'fsweb-comments-core' ); ?>
						</p>
					</div>
				</div>
			</div>
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
