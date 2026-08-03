<?php

/**
 * Fired during plugin activation
 *
 * @link       https://flowsyncweb.com
 * @since      1.0.0
 *
 * @package    Fswwp_Comments_Core
 * @subpackage Fswwp_Comments_Core/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Fswwp_Comments_Core
 * @subpackage Fswwp_Comments_Core/includes
 * @author     Flow Sync Web <euzebio.batista@flowsyncweb.com>
 */
class Fswwp_Comments_Core_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Ensure CPT and taxonomy are registered before flushing rewrites.
		Fswwp_Comments_Core_Post_Types::register_cpt();
		Fswwp_Comments_Core_Post_Types::register_taxonomy();
		flush_rewrite_rules();

		// Create default templates if they don't exist yet.
		self::create_default_templates();
	}

	/**
	 * Create default form and view templates on fresh install.
	 *
	 * @since    1.0.0
	 */
	private static function create_default_templates() {
		// Only create if options don't already exist.
		$forms = get_option( 'fswwp_review_forms', array() );
		if ( empty( $forms ) ) {
			$default_fields = array(
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

			$forms['default'] = array(
				'name'   => __( 'Default Form', 'fswwp-comments-core' ),
				'fields' => $default_fields,
			);
			update_option( 'fswwp_review_forms', $forms );
		}

		$views = get_option( 'fswwp_review_views', array() );
		if ( empty( $views ) ) {
			$default_row = array(
				'columns' => array(
					array(
						'width'      => 40,
						'components' => array(
							array( 'type' => 'image', 'field_id' => 'fswwp_image' ),
						),
					),
					array(
						'width'      => 60,
						'components' => array(
							array( 'type' => 'text',     'field_id' => 'fswwp_name' ),
							array( 'type' => 'email',    'field_id' => 'fswwp_email' ),
							array( 'type' => 'rating',   'field_id' => 'fswwp_rating' ),
							array( 'type' => 'textarea', 'field_id' => 'fswwp_description' ),
						),
					),
				),
			);

			$views['default'] = array(
				'name'     => __( 'Default View', 'fswwp-comments-core' ),
				'layout'   => 'grid',
				'count'    => 6,
				'order'    => 'newest',
				'category' => '',
				'form_id'  => 'default',
				'rows'     => array( $default_row ),
			);
			update_option( 'fswwp_review_views', $views );
		}
	}

}
