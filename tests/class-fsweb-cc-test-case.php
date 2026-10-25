<?php
/**
 * Shared helpers for the integration tests.
 *
 * @package Fsweb_Comments_Core
 */

/**
 * Base test case: boots WordPress once and keeps the database clean between
 * tests without requiring the WordPress core test suite.
 */
abstract class Fsweb_Cc_Test_Case extends PHPUnit\Framework\TestCase {

	/**
	 * Plugin admin instance shared by the admin tests.
	 *
	 * @var Fsweb_Comments_Core_Admin|null
	 */
	protected static $admin = null;

	/**
	 * Reset the mutable state used by the plugin.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$user = get_user_by( 'login', 'admin' );
		if ( ! $user ) {
			$user_id = wp_insert_user(
				array(
					'user_login' => 'admin',
					'user_pass'  => 'admin',
					'user_email' => 'admin@example.com',
					'role'       => 'administrator',
				)
			);
			$user    = get_user_by( 'id', $user_id );
		}

		wp_set_current_user( $user->ID );
	}

	/**
	 * Clean reviews, options and filters between tests.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->delete_reviews();

		delete_option( 'fsweb_comments_core_form_status' );
		delete_option( 'fsweb_comments_core_require_email' );
		delete_option( 'fsweb_comments_core_license_status' );

		remove_all_filters( 'fsweb_cc_reviews_pre_render' );
		remove_all_filters( 'fsweb_cc_reviews_query_args' );
		remove_all_filters( 'fsweb_cc_reviews_shortcode_output' );
		remove_all_filters( 'fsweb_cc_form_fields' );
		remove_all_filters( 'fsweb_cc_form_attributes' );
		remove_all_filters( 'fsweb_cc_forms_open' );
		remove_all_filters( 'fsweb_cc_form_handle_submission' );

		// PHPUnit restores the global state between the bootstrap and the first
		// test, which drops the shortcode registry filled during init. Register
		// the plugin handlers again instead of re-running every init callback.
		if ( ! shortcode_exists( 'fsweb_reviews' ) ) {
			new Fsweb_Comments_Core_Shortcode();
			new Fsweb_Comments_Core_Form_Shortcode();
		}
	}

	/**
	 * Delete every review created by the tests.
	 */
	protected function delete_reviews() {
		$posts = get_posts(
			array(
				'post_type'      => 'fsweb_review',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Create a review.
	 *
	 * @param array $args Optional overrides.
	 * @return int Review ID.
	 */
	protected function create_review( $args = array() ) {
		$defaults = array(
			'post_type'    => 'fsweb_review',
			'post_status'  => 'publish',
			'post_title'   => 'Jane Doe',
			'post_content' => 'Great product, exactly what I needed.',
		);

		$post_id = wp_insert_post( array_merge( $defaults, $args ) );

		update_post_meta( $post_id, 'fsweb_name', $args['post_title'] ?? 'Jane Doe' );
		update_post_meta( $post_id, 'fsweb_rating', $args['rating'] ?? 5 );
		update_post_meta( $post_id, 'fsweb_email', 'jane@example.com' );

		return $post_id;
	}

	/**
	 * Admin instance used by the screen tests.
	 *
	 * @return Fsweb_Comments_Core_Admin
	 */
	protected function admin() {
		if ( ! self::$admin ) {
			self::$admin = new Fsweb_Comments_Core_Admin( 'fsweb-comments-core', FSWEB_COMMENTS_CORE_VERSION );
		}

		return self::$admin;
	}

	/**
	 * Render a callable that prints, returning the markup.
	 *
	 * @param callable $callback Renderer.
	 * @return string
	 */
	protected function render( $callback ) {
		ob_start();
		call_user_func( $callback );
		return ob_get_clean();
	}

	/**
	 * Replaces the wp_die handler so AJAX handlers can be asserted.
	 *
	 * @return void
	 */
	protected function catch_die() {
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		add_filter(
			'wp_die_ajax_handler',
			static function () {
				return static function ( $message ) {
					throw new Fsweb_Cc_Die( is_wp_error( $message ) ? $message->get_error_message() : (string) $message );
				};
			}
		);
	}

	/**
	 * Run an AJAX handler and return the captured JSON.
	 *
	 * @param callable $callback Handler.
	 * @return array Decoded JSON payload.
	 */
	protected function call_ajax( $callback ) {
		$this->catch_die();

		ob_start();
		try {
			call_user_func( $callback );
		} catch ( Fsweb_Cc_Die $e ) {
			// wp_die() ends the request; the payload was already printed.
			unset( $e );
		}
		$raw = ob_get_clean();

		$data = json_decode( $raw, true );
		$this->assertIsArray( $data, 'The AJAX handler did not print a JSON payload: ' . $raw );

		return $data;
	}
}

/**
 * Exception thrown in place of wp_die().
 */
class Fsweb_Cc_Die extends Exception {
}
