<?php
/**
 * Uninstall routine.
 *
 * Runs only when the plugin is deleted from the plugins screen. Reviews are
 * user content, so they are kept by default. Deleting them requires the
 * FSWEB_COMMENTS_CORE_REMOVE_DATA constant to be true in wp-config.php.
 *
 * @package Fsweb_Comments_Core
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'fsweb_comments_core_form_status' );
delete_option( 'fsweb_comments_core_require_email' );
delete_option( 'fsweb_comments_core_version' );

if ( ! defined( 'FSWEB_COMMENTS_CORE_REMOVE_DATA' ) || ! FSWEB_COMMENTS_CORE_REMOVE_DATA ) {
	return;
}

$fsweb_cc_reviews = get_posts(
	array(
		'post_type'      => 'fsweb_review',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $fsweb_cc_reviews as $fsweb_cc_review_id ) {
	wp_delete_post( $fsweb_cc_review_id, true );
}

