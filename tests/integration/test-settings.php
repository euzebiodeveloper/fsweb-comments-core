<?php
/**
 * Settings registration and sanitisation.
 *
 * @package Fsweb_Comments_Core
 */

/**
 * @covers Fsweb_Comments_Core_Admin::register_settings
 * @covers Fsweb_Comments_Core_Admin::sanitize_form_status
 */
class Fsweb_Cc_Test_Settings extends Fsweb_Cc_Test_Case {

	public function test_form_status_is_sanitised_to_open_or_closed() {
		$this->assertSame( 'closed', $this->admin()->sanitize_form_status( 'closed' ) );
		$this->assertSame( 'open', $this->admin()->sanitize_form_status( 'open' ) );
		$this->assertSame( 'open', $this->admin()->sanitize_form_status( 'garbage' ) );
		$this->assertSame( 'open', $this->admin()->sanitize_form_status( '' ) );
	}

	public function test_default_theme_is_sanitised_to_default_or_dark() {
		$this->assertSame( 'dark', $this->admin()->sanitize_default_theme( 'dark' ) );
		$this->assertSame( 'default', $this->admin()->sanitize_default_theme( 'default' ) );
		$this->assertSame( 'default', $this->admin()->sanitize_default_theme( 'neon' ) );
		$this->assertSame( 'default', $this->admin()->sanitize_default_theme( '' ) );
	}

	public function test_settings_are_registered_with_sanitizers() {
		$this->admin()->register_settings();

		$registered = get_registered_settings();

		$this->assertArrayHasKey( 'fsweb_comments_core_form_status', $registered );
		$this->assertArrayHasKey( 'fsweb_comments_core_require_email', $registered );
		$this->assertArrayHasKey( 'fsweb_comments_core_default_theme', $registered );
		$this->assertArrayHasKey( 'sanitize_callback', $registered['fsweb_comments_core_form_status'] );
		$this->assertSame( 'open', $registered['fsweb_comments_core_form_status']['default'] );
		$this->assertSame( '1', $registered['fsweb_comments_core_require_email']['default'] );
		$this->assertSame( 'default', $registered['fsweb_comments_core_default_theme']['default'] );
	}

	public function test_sanitize_option_uses_the_registered_callback() {
		$this->admin()->register_settings();

		$this->assertSame( 'closed', sanitize_option( 'fsweb_comments_core_form_status', 'closed' ) );
		$this->assertSame( 'open', sanitize_option( 'fsweb_comments_core_form_status', 'nonsense' ) );
	}

	public function test_option_controls_the_email_requirement() {
		$form = new Fsweb_Comments_Core_Form_Shortcode();

		update_option( 'fsweb_comments_core_require_email', '0' );
		$fields = $form->get_fields();
		$email  = wp_list_filter( $fields, array( 'id' => 'fsweb_email' ) );

		$this->assertFalse( (bool) reset( $email )['required'] );

		update_option( 'fsweb_comments_core_require_email', '1' );
		$fields = $form->get_fields();
		$email  = wp_list_filter( $fields, array( 'id' => 'fsweb_email' ) );

		$this->assertTrue( (bool) reset( $email )['required'] );
	}

	public function test_settings_screen_renders_options_and_pro_pills() {
		update_option( 'fsweb_comments_core_form_status', 'open' );

		$markup = $this->render( array( $this->admin(), 'render_settings_page' ) );

		$this->assertStringContainsString( 'fsweb-cc-wrap', $markup );
		$this->assertStringContainsString( 'name="fsweb_comments_core_form_status"', $markup );
		$this->assertStringContainsString( 'name="fsweb_comments_core_require_email"', $markup );
		$this->assertStringContainsString( 'name="fsweb_comments_core_default_theme"', $markup );
		$this->assertStringContainsString( 'value="dark"', $markup );
		$this->assertStringContainsString( 'fsweb-cc-pro-tag', $markup );
		$this->assertStringContainsString( 'fsweb-cc-locked', $markup );
	}
}
