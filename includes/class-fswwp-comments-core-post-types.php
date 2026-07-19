<?php
/**
 * FSWWP Comments Core — Post Types & Taxonomies
 *
 * Registers the fswwp_review CPT and fswwp_review_category taxonomy.
 *
 * @package    Fswwp_Comments_Core
 * @subpackage Fswwp_Comments_Core/includes
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Post Types & Taxonomies registrar.
 */
class Fswwp_Comments_Core_Post_Types {

	/**
	 * Register the Review custom post type.
	 *
	 * @since    1.0.0
	 */
	public static function register_cpt() {
		$labels = array(
			'name'                  => _x( 'Reviews', 'Post type general name', 'fswwp-comments-core' ),
			'singular_name'         => _x( 'Review', 'Post type singular name', 'fswwp-comments-core' ),
			'menu_name'             => _x( 'Reviews', 'Admin menu text', 'fswwp-comments-core' ),
			'add_new'               => __( 'Add New', 'fswwp-comments-core' ),
			'add_new_item'          => __( 'Add New Review', 'fswwp-comments-core' ),
			'edit_item'             => __( 'Edit Review', 'fswwp-comments-core' ),
			'new_item'              => __( 'New Review', 'fswwp-comments-core' ),
			'view_item'             => __( 'View Review', 'fswwp-comments-core' ),
			'view_items'            => __( 'View Reviews', 'fswwp-comments-core' ),
			'search_items'          => __( 'Search Reviews', 'fswwp-comments-core' ),
			'not_found'             => __( 'No reviews found.', 'fswwp-comments-core' ),
			'not_found_in_trash'    => __( 'No reviews found in Trash.', 'fswwp-comments-core' ),
			'all_items'             => __( 'All Reviews', 'fswwp-comments-core' ),
			'archives'              => __( 'Review Archives', 'fswwp-comments-core' ),
			'attributes'            => __( 'Review Attributes', 'fswwp-comments-core' ),
			'insert_into_item'      => __( 'Insert into review', 'fswwp-comments-core' ),
			'uploaded_to_this_item' => __( 'Uploaded to this review', 'fswwp-comments-core' ),
			'filter_items_list'     => __( 'Filter reviews list', 'fswwp-comments-core' ),
			'items_list_navigation' => __( 'Reviews list navigation', 'fswwp-comments-core' ),
			'items_list'            => __( 'Reviews list', 'fswwp-comments-core' ),
			'item_published'        => __( 'Review published.', 'fswwp-comments-core' ),
			'item_updated'          => __( 'Review updated.', 'fswwp-comments-core' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-format-chat',
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'has_archive'         => false,
			'rewrite'             => array(
				'slug'       => 'review',
				'with_front' => false,
			),
			'capability_type'     => 'post',
			'menu_position'       => 30,
		);

		register_post_type( 'fswwp_review', $args );
	}

	/**
	 * Register the Review Category taxonomy.
	 *
	 * @since    1.0.0
	 */
	public static function register_taxonomy() {
		$labels = array(
			'name'              => _x( 'Categories', 'taxonomy general name', 'fswwp-comments-core' ),
			'singular_name'     => _x( 'Category', 'taxonomy singular name', 'fswwp-comments-core' ),
			'search_items'      => __( 'Search Categories', 'fswwp-comments-core' ),
			'all_items'         => __( 'All Categories', 'fswwp-comments-core' ),
			'parent_item'       => __( 'Parent Category', 'fswwp-comments-core' ),
			'parent_item_colon' => __( 'Parent Category:', 'fswwp-comments-core' ),
			'edit_item'         => __( 'Edit Category', 'fswwp-comments-core' ),
			'update_item'       => __( 'Update Category', 'fswwp-comments-core' ),
			'add_new_item'      => __( 'Add New Category', 'fswwp-comments-core' ),
			'new_item_name'     => __( 'New Category Name', 'fswwp-comments-core' ),
			'menu_name'         => __( 'Categories', 'fswwp-comments-core' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'review-category',
				'with_front' => false,
			),
		);

		register_taxonomy( 'fswwp_review_category', array( 'fswwp_review' ), $args );
	}
}
