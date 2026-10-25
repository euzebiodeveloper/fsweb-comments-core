<?php
/**
 * Admin screens: hero, navigation and PRO pills.
 *
 * @package Fsweb_Comments_Core
 */

/**
 * @covers Fsweb_Comments_Core_Admin
 */
class Fsweb_Cc_Test_Admin_Screens extends Fsweb_Cc_Test_Case {

	public function test_overview_renders_inside_the_wrapped_layout() {
		$markup = $this->render( array( $this->admin(), 'render_overview_page' ) );

		$this->assertStringContainsString( 'class="wrap fsweb-cc-wrap"', $markup );
		$this->assertStringContainsString( 'fsweb-cc-hero', $markup );
		$this->assertStringContainsString( 'fsweb-cc-stat-card', $markup );
		$this->assertStringContainsString( '[fsweb_reviews', $markup );
	}

	public function test_overview_shows_the_review_counters() {
		$this->create_review();
		$this->create_review( array( 'post_status' => 'pending' ) );

		$markup = $this->render( array( $this->admin(), 'render_overview_page' ) );

		$this->assertStringContainsString( 'Published reviews', $markup );
		$this->assertStringContainsString( 'Awaiting moderation', $markup );
	}

	public function test_hero_navigation_lists_every_screen() {
		$markup = $this->render( array( $this->admin(), 'render_overview_page' ) );

		$this->assertStringContainsString( 'page=fsweb-comments-core-builder', $markup );
		$this->assertStringContainsString( 'page=fsweb-comments-core-views', $markup );
		$this->assertStringContainsString( 'page=fsweb-comments-core-settings', $markup );
		$this->assertStringContainsString( 'page=fsweb-comments-core-go-pro', $markup );
	}

	public function test_current_screen_button_is_marked() {
		$markup = $this->render( array( $this->admin(), 'render_overview_page' ) );

		$this->assertStringContainsString( 'aria-current="page"', $markup );
	}

	public function test_premium_options_carry_a_pro_pill_linking_to_the_offer() {
		foreach ( array( 'render_builder_page', 'render_views_page', 'render_settings_page' ) as $screen ) {
			$markup = $this->render( array( $this->admin(), $screen ) );

			$this->assertStringContainsString( 'fsweb-cc-pro-tag', $markup, $screen . ' has no PRO pill' );
			$this->assertStringContainsString( 'page=fsweb-comments-core-go-pro', $markup, $screen . ' pill does not link to the offer' );
		}
	}

	public function test_locked_options_are_plain_text_not_fake_controls() {
		$markup = $this->render( array( $this->admin(), 'render_builder_page' ) );

		$this->assertStringContainsString( 'fsweb-cc-locked', $markup );
		$this->assertStringNotContainsString( '<select disabled', $markup );
	}

	public function test_go_pro_page_holds_the_offer_and_the_cta() {
		$markup = $this->render( array( $this->admin(), 'render_go_pro_page' ) );

		$this->assertStringContainsString( 'fsweb-cc-pro-card', $markup );
		$this->assertStringContainsString( 'button-primary', $markup );
		$this->assertStringContainsString( esc_url( FSWEB_COMMENTS_CORE_PRO_URL ), $markup );
	}

	public function test_only_the_go_pro_page_prints_the_upsell_card() {
		$settings = $this->render( array( $this->admin(), 'render_settings_page' ) );
		$offer    = $this->render( array( $this->admin(), 'render_go_pro_page' ) );

		$this->assertStringNotContainsString( 'fsweb-cc-pro-card', $settings );
		$this->assertStringContainsString( 'fsweb-cc-pro-card', $offer );
	}

	public function test_users_without_capability_see_nothing() {
		$subscriber = wp_insert_user(
			array(
				'user_login' => 'subscriber_' . wp_rand( 1000, 9999 ),
				'user_pass'  => 'secret',
				'user_email' => 'sub' . wp_rand( 1000, 9999 ) . '@example.com',
				'role'       => 'subscriber',
			)
		);

		wp_set_current_user( $subscriber );

		$this->assertSame( '', $this->render( array( $this->admin(), 'render_overview_page' ) ) );
		$this->assertSame( '', $this->render( array( $this->admin(), 'render_go_pro_page' ) ) );

		wp_set_current_user( 1 );
	}

	public function test_pro_is_inactive_without_the_add_on() {
		$this->assertFalse( $this->admin()->is_pro_active() );
	}

	public function test_license_active_without_the_add_on_keeps_the_pills() {
		update_option( 'fsweb_comments_core_license_status', 'active' );

		$this->assertFalse( $this->admin()->is_pro_active() );
		$this->assertStringContainsString( 'fsweb-cc-pro-tag', $this->render( array( $this->admin(), 'render_builder_page' ) ) );
	}

}
