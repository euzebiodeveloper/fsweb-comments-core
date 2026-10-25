<?php
/**
 * Hooks the PRO add-on relies on.
 *
 * @package Fsweb_Comments_Core
 */

/**
 * @coversNothing
 */
class Fsweb_Cc_Test_Pro_Handover extends Fsweb_Cc_Test_Case {

	public function test_loaded_action_fires_for_the_add_on() {
		$this->assertGreaterThan( 0, did_action( 'fsweb_comments_core_loaded' ) );
	}

	public function test_documented_filters_are_applied_by_the_plugin() {
		$seen = array();

		add_filter(
			'fsweb_cc_reviews_query_args',
			static function ( $args ) use ( &$seen ) {
				$seen[] = 'query_args';
				return $args;
			}
		);
		add_filter(
			'fsweb_cc_reviews_pre_render',
			static function ( $output ) use ( &$seen ) {
				$seen[] = 'pre_render';
				return $output;
			}
		);
		add_filter(
			'fsweb_cc_reviews_shortcode_output',
			static function ( $output ) use ( &$seen ) {
				$seen[] = 'shortcode_output';
				return $output;
			},
			10,
			2
		);
		add_filter(
			'fsweb_cc_form_fields',
			static function ( $fields ) use ( &$seen ) {
				$seen[] = 'form_fields';
				return $fields;
			}
		);
		add_filter(
			'fsweb_cc_form_attributes',
			static function ( $attributes ) use ( &$seen ) {
				$seen[] = 'form_attributes';
				return $attributes;
			}
		);

		$this->create_review();
		do_shortcode( '[fsweb_reviews]' );
		do_shortcode( '[fsweb_review_form]' );

		$this->assertContains( 'query_args', $seen );
		$this->assertContains( 'pre_render', $seen );
		$this->assertContains( 'shortcode_output', $seen );
		$this->assertContains( 'form_fields', $seen );
		$this->assertContains( 'form_attributes', $seen );
	}

	public function test_shortcode_passes_the_view_attributes_to_the_add_on() {
		$seen = array();

		add_filter(
			'fsweb_cc_reviews_pre_render',
			static function ( $output, $atts ) use ( &$seen ) {
				$seen = $atts;
				return $output;
			},
			10,
			2
		);

		do_shortcode( '[fsweb_reviews id="default" skin="dark"]' );

		// Dropping these here made [fsweb_reviews id="default"] silently fall
		// back to the grid instead of the saved view (slider, skins).
		$this->assertSame( 'default', $seen['id'] );
		$this->assertSame( 'dark', $seen['skin'] );
	}

	public function test_forms_open_filter_is_applied_on_submission() {
		$called = 0;

		add_filter(
			'fsweb_cc_forms_open',
			static function ( $open ) use ( &$called ) {
				$called++;
				return $open;
			}
		);

		$form = new Fsweb_Comments_Core_Form_Shortcode();
		$_POST['fsweb_nonce']                  = wp_create_nonce( 'fsweb_review_submit' );
		$_REQUEST['fsweb_nonce']               = $_POST['fsweb_nonce'];
		$_POST['fsweb_field_fsweb_name']        = 'John Smith';
		$_POST['fsweb_field_fsweb_email']       = 'john@example.com';
		$_POST['fsweb_field_fsweb_description'] = 'Nice.';
		$_POST['fsweb_field_fsweb_rating']      = '5';

		$this->call_ajax( array( $form, 'ajax_submit' ) );

		unset( $_POST['fsweb_nonce'], $_REQUEST['fsweb_nonce'], $_POST['fsweb_field_fsweb_name'], $_POST['fsweb_field_fsweb_email'], $_POST['fsweb_field_fsweb_description'], $_POST['fsweb_field_fsweb_rating'] );

		$this->assertGreaterThan( 0, $called );
	}

	public function test_pro_url_constant_points_to_the_product_page() {
		$this->assertTrue( defined( 'FSWEB_COMMENTS_CORE_PRO_URL' ) );
		$this->assertStringStartsWith( 'http', FSWEB_COMMENTS_CORE_PRO_URL );
	}

	public function test_shortcodes_are_registered() {
		$this->assertTrue(
			shortcode_exists( 'fsweb_reviews' ),
			sprintf(
				'db=%s init=%d loaded=%d tags=%s',
				DB_NAME,
				did_action( 'init' ),
				did_action( 'fsweb_comments_core_loaded' ),
				implode( ',', array_keys( (array) $GLOBALS['shortcode_tags'] ) )
			)
		);
		$this->assertTrue( shortcode_exists( 'fsweb_review_form' ) );
	}

	public function test_plugin_constants_are_defined() {
		$this->assertTrue( defined( 'FSWEB_COMMENTS_CORE_VERSION' ) );
		$this->assertTrue( defined( 'FSWEB_COMMENTS_CORE_SLUG' ) );
		$this->assertTrue( defined( 'FSWEB_COMMENTS_CORE_DIR' ) );
		$this->assertTrue( defined( 'FSWEB_COMMENTS_CORE_URL' ) );
		$this->assertSame( 'fsweb-comments-core', FSWEB_COMMENTS_CORE_SLUG );
	}
}
