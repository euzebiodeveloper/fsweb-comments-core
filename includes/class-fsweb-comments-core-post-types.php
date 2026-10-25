<?php
/**
 * FSWEB Comments Core — Post Types & Taxonomies
 *
 * Registers the fsweb_review CPT and the fsweb_review_category taxonomy.
 *
 * @package    Fsweb_Comments_Core
 * @subpackage Fsweb_Comments_Core/includes
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Post Types & Taxonomies registrar.
 */
class Fsweb_Comments_Core_Post_Types {

	/**
	 * Register the Review custom post type.
	 *
	 * @return void
	 */
	public static function register_cpt() {
		$labels = array(
			'name'                  => _x( 'Reviews', 'Post type general name', 'fsweb-comments-core' ),
			'singular_name'         => _x( 'Review', 'Post type singular name', 'fsweb-comments-core' ),
			'menu_name'             => _x( 'Reviews', 'Admin menu text', 'fsweb-comments-core' ),
			'add_new'               => __( 'Add New', 'fsweb-comments-core' ),
			'add_new_item'          => __( 'Add New Review', 'fsweb-comments-core' ),
			'edit_item'             => __( 'Edit Review', 'fsweb-comments-core' ),
			'new_item'              => __( 'New Review', 'fsweb-comments-core' ),
			'view_item'             => __( 'View Review', 'fsweb-comments-core' ),
			'view_items'            => __( 'View Reviews', 'fsweb-comments-core' ),
			'search_items'          => __( 'Search Reviews', 'fsweb-comments-core' ),
			'not_found'             => __( 'No reviews found.', 'fsweb-comments-core' ),
			'not_found_in_trash'    => __( 'No reviews found in Trash.', 'fsweb-comments-core' ),
			'all_items'             => __( 'All Reviews', 'fsweb-comments-core' ),
			'archives'              => __( 'Review Archives', 'fsweb-comments-core' ),
			'attributes'            => __( 'Review Attributes', 'fsweb-comments-core' ),
			'insert_into_item'      => __( 'Insert into review', 'fsweb-comments-core' ),
			'uploaded_to_this_item' => __( 'Uploaded to this review', 'fsweb-comments-core' ),
			'filter_items_list'     => __( 'Filter reviews list', 'fsweb-comments-core' ),
			'items_list_navigation' => __( 'Reviews list navigation', 'fsweb-comments-core' ),
			'items_list'            => __( 'Reviews list', 'fsweb-comments-core' ),
			'item_published'        => __( 'Review published.', 'fsweb-comments-core' ),
			'item_updated'          => __( 'Review updated.', 'fsweb-comments-core' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-format-chat',
			'hierarchical'        => false,
			'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
			'has_archive'         => false,
			'rewrite'             => array(
				'slug'       => 'review',
				'with_front' => false,
			),
			'capability_type'     => 'post',
			'menu_position'       => 30,
		);

		register_post_type( 'fsweb_review', $args );
	}

	/**
	 * Register the Review Category taxonomy.
	 *
	 * @return void
	 */
	public static function register_taxonomy() {
		$labels = array(
			'name'              => _x( 'Categories', 'taxonomy general name', 'fsweb-comments-core' ),
			'singular_name'     => _x( 'Category', 'taxonomy singular name', 'fsweb-comments-core' ),
			'search_items'      => __( 'Search Categories', 'fsweb-comments-core' ),
			'all_items'         => __( 'All Categories', 'fsweb-comments-core' ),
			'parent_item'       => __( 'Parent Category', 'fsweb-comments-core' ),
			'parent_item_colon' => __( 'Parent Category:', 'fsweb-comments-core' ),
			'edit_item'         => __( 'Edit Category', 'fsweb-comments-core' ),
			'update_item'       => __( 'Update Category', 'fsweb-comments-core' ),
			'add_new_item'      => __( 'Add New Category', 'fsweb-comments-core' ),
			'new_item_name'     => __( 'New Category Name', 'fsweb-comments-core' ),
			'menu_name'         => __( 'Categories', 'fsweb-comments-core' ),
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

		register_taxonomy( 'fsweb_review_category', array( 'fsweb_review' ), $args );
	}
}
