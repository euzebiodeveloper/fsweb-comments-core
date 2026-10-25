<?php

/**
 * The admin-specific functionality of the plugin.
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

		wp_enqueue_style(
			$this->plugin_name,
			FSWEB_COMMENTS_CORE_URL . 'admin/css/fsweb-comments-core-admin.css',
			array(),
			$this->version
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

		wp_enqueue_script(
			$this->plugin_name,
			FSWEB_COMMENTS_CORE_URL . 'admin/js/fsweb-comments-core-admin.js',
			array(),
			$this->version,
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

	/**
	 * Register the submenu pages.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		$parent = 'edit.php?post_type=fsweb_review';

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
			__( 'Settings', 'fsweb-comments-core' ),
			__( 'Settings', 'fsweb-comments-core' ),
			'manage_options',
			'fsweb-comments-core-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			$parent,
			__( 'Go PRO', 'fsweb-comments-core' ),
			'<span class="fsweb-menu-pro">' . esc_html__( 'Go PRO', 'fsweb-comments-core' ) . '</span>',
			'manage_options',
			'fsweb-comments-core-go-pro',
			array( $this, 'render_pro_page' )
		);
	}

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

		add_settings_section(
			'fsweb_comments_core_main',
			__( 'Review form', 'fsweb-comments-core' ),
			array( $this, 'render_section_description' ),
			'fsweb-comments-core-settings'
		);

		add_settings_field(
			'fsweb_comments_core_form_status',
			__( 'Submissions', 'fsweb-comments-core' ),
			array( $this, 'render_form_status_field' ),
			'fsweb-comments-core-settings',
			'fsweb_comments_core_main'
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

	/**
	 * Settings section description.
	 *
	 * @return void
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Control how visitors can submit reviews.', 'fsweb-comments-core' ) . '</p>';
	}

	/**
	 * Submissions toggle.
	 *
	 * @return void
	 */
	public function render_form_status_field() {
		$status = get_option( 'fsweb_comments_core_form_status', 'open' );
		?>
		<select name="fsweb_comments_core_form_status" id="fsweb_comments_core_form_status">
			<option value="open" <?php selected( $status, 'open' ); ?>><?php esc_html_e( 'Open — accept new reviews', 'fsweb-comments-core' ); ?></option>
			<option value="closed" <?php selected( $status, 'closed' ); ?>><?php esc_html_e( 'Closed — hide the form', 'fsweb-comments-core' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Overview page.
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
		?>
		<div class="wrap fsweb-admin-wrap">
			<h1><?php esc_html_e( 'Comments Core', 'fsweb-comments-core' ); ?></h1>
			<p class="fsweb-admin-lead">
				<?php esc_html_e( 'Collect reviews, moderate them and display them anywhere with a shortcode.', 'fsweb-comments-core' ); ?>
			</p>

			<div class="fsweb-admin-cards">
				<div class="fsweb-admin-card">
					<span class="fsweb-admin-card-value"><?php echo esc_html( (string) $publish ); ?></span>
					<span class="fsweb-admin-card-label"><?php esc_html_e( 'Published reviews', 'fsweb-comments-core' ); ?></span>
				</div>
				<div class="fsweb-admin-card">
					<span class="fsweb-admin-card-value"><?php echo esc_html( (string) $pending ); ?></span>
					<span class="fsweb-admin-card-label"><?php esc_html_e( 'Awaiting moderation', 'fsweb-comments-core' ); ?></span>
				</div>
				<div class="fsweb-admin-card">
					<span class="fsweb-admin-card-value"><?php echo esc_html( '' === $average ? '—' : $average ); ?></span>
					<span class="fsweb-admin-card-label"><?php esc_html_e( 'Average rating', 'fsweb-comments-core' ); ?></span>
				</div>
			</div>

			<div class="fsweb-admin-grid">
				<div class="fsweb-admin-panel">
					<h2><?php esc_html_e( 'Shortcodes', 'fsweb-comments-core' ); ?></h2>
					<p><?php esc_html_e( 'Display your reviews:', 'fsweb-comments-core' ); ?></p>
					<code class="fsweb-admin-code">[fsweb_reviews layout="grid" count="6" columns="3"]</code>
					<p><?php esc_html_e( 'Show the submission form:', 'fsweb-comments-core' ); ?></p>
					<code class="fsweb-admin-code">[fsweb_review_form]</code>
					<p class="description">
						<?php esc_html_e( 'Attributes: layout (grid or list), count, category, order (newest, oldest, random, menu_order), show_image (yes or no), columns (1 to 4).', 'fsweb-comments-core' ); ?>
					</p>
				</div>

				<div class="fsweb-admin-panel fsweb-admin-panel--pro">
					<h2><?php esc_html_e( 'Go PRO', 'fsweb-comments-core' ); ?></h2>
					<ul class="fsweb-admin-list">
						<li><?php esc_html_e( 'Drag and drop Form Builder with extra field types', 'fsweb-comments-core' ); ?></li>
						<li><?php esc_html_e( 'Views Builder: design each review card with dynamic tags', 'fsweb-comments-core' ); ?></li>
						<li><?php esc_html_e( 'Slider layout and ready-made skins', 'fsweb-comments-core' ); ?></li>
						<li><?php esc_html_e( 'Custom CSS per view', 'fsweb-comments-core' ); ?></li>
						<li><?php esc_html_e( 'Photo upload, automatic updates and priority support', 'fsweb-comments-core' ); ?></li>
					</ul>
					<a class="button button-primary" href="<?php echo esc_url( FSWEB_COMMENTS_CORE_PRO_URL ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'See the PRO edition', 'fsweb-comments-core' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap fsweb-admin-wrap">
			<h1><?php esc_html_e( 'Comments Core settings', 'fsweb-comments-core' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'fsweb_comments_core_settings' );
				do_settings_sections( 'fsweb-comments-core-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * PRO landing page inside the admin.
	 *
	 * @return void
	 */
	public function render_pro_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap fsweb-admin-wrap">
			<h1><?php esc_html_e( 'Comments Core PRO', 'fsweb-comments-core' ); ?></h1>
			<p class="fsweb-admin-lead">
				<?php esc_html_e( 'The PRO edition is an add-on: install it alongside this free plugin to unlock the builders, layouts and skins.', 'fsweb-comments-core' ); ?>
			</p>

			<div class="fsweb-admin-panel fsweb-admin-panel--pro">
				<ul class="fsweb-admin-list">
					<li><?php esc_html_e( 'Form Builder — reorder fields, add rating, date, select and file inputs, split the form into steps', 'fsweb-comments-core' ); ?></li>
					<li><?php esc_html_e( 'Views Builder — compose the review card with dynamic tags and per-view settings', 'fsweb-comments-core' ); ?></li>
					<li><?php esc_html_e( 'Slider layout with keyboard and touch support', 'fsweb-comments-core' ); ?></li>
					<li><?php esc_html_e( 'Skins (dark, minimal, colorful, flat) and custom CSS', 'fsweb-comments-core' ); ?></li>
					<li><?php esc_html_e( 'Automatic updates delivered from our server', 'fsweb-comments-core' ); ?></li>
				</ul>
				<a class="button button-primary" href="<?php echo esc_url( FSWEB_COMMENTS_CORE_PRO_URL ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Get Comments Core PRO', 'fsweb-comments-core' ); ?>
				</a>
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
