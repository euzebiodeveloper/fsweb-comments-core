<?php
/**
 * [fsweb_review_form] shortcode and AJAX submission.
 *
 * @package Fsweb_Comments_Core
 */

/**
 * @covers Fsweb_Comments_Core_Form_Shortcode
 */
class Fsweb_Cc_Test_Form_Shortcode extends Fsweb_Cc_Test_Case {

	/**
	 * Form handler.
	 *
	 * @var Fsweb_Comments_Core_Form_Shortcode
	 */
	private $form;

	public function setUp(): void {
		parent::setUp();
		$this->form = new Fsweb_Comments_Core_Form_Shortcode();
	}

	public function tearDown(): void {
		unset( $_POST['fsweb_nonce'], $_REQUEST['fsweb_nonce'], $_POST['fsweb_field_fsweb_name'], $_POST['fsweb_field_fsweb_email'], $_POST['fsweb_field_fsweb_description'], $_POST['fsweb_field_fsweb_rating'] );
		parent::tearDown();
	}

	public function test_form_renders_nonce_and_default_fields() {
		$output = do_shortcode( '[fsweb_review_form]' );

		$this->assertStringContainsString( 'fsweb-review-form', $output );
		$this->assertStringContainsString( 'name="fsweb_nonce"', $output );
		$this->assertStringContainsString( 'name="fsweb_field_fsweb_name"', $output );
		$this->assertStringContainsString( 'name="fsweb_field_fsweb_email"', $output );
		$this->assertStringContainsString( 'name="fsweb_field_fsweb_description"', $output );
		$this->assertStringContainsString( 'fsweb_field_fsweb_rating', $output );
		$this->assertStringContainsString( 'Submit Review', $output );
	}

	public function test_title_and_button_attributes_are_rendered() {
		$output = do_shortcode( '[fsweb_review_form title="Leave a review" button="Send"]' );

		$this->assertStringContainsString( 'Leave a review', $output );
		$this->assertStringContainsString( 'Send', $output );
	}

	public function test_email_field_is_required_by_default() {
		$this->assertStringContainsString( 'name="fsweb_field_fsweb_email"', do_shortcode( '[fsweb_review_form]' ) );

		$output = do_shortcode( '[fsweb_review_form]' );
		$this->assertMatchesRegularExpression( '/<input[^>]*fsweb_field_fsweb_email[^>]*required/', $output );
	}

	public function test_email_field_is_optional_when_the_option_is_off() {
		update_option( 'fsweb_comments_core_require_email', '0' );

		$fields = $this->form->get_fields();
		$email  = wp_list_filter( $fields, array( 'id' => 'fsweb_email' ) );
		$email  = reset( $email );

		$this->assertFalse( (bool) $email['required'] );
	}

	public function test_closed_status_hides_the_form() {
		update_option( 'fsweb_comments_core_form_status', 'closed' );

		$output = do_shortcode( '[fsweb_review_form]' );

		$this->assertStringContainsString( 'fsweb-review-form-closed', $output );
		$this->assertStringNotContainsString( '<form', $output );
	}

	public function test_forms_open_filter_closes_the_form() {
		add_filter( 'fsweb_cc_forms_open', '__return_false' );

		$this->assertStringContainsString( 'fsweb-review-form-closed', do_shortcode( '[fsweb_review_form]' ) );
	}

	public function test_fields_filter_can_add_a_field() {
		add_filter(
			'fsweb_cc_form_fields',
			static function ( $fields ) {
				$fields[] = array(
					'id'       => 'fsweb_company',
					'type'     => 'text',
					'label'    => 'Company',
					'required' => false,
				);
				return $fields;
			}
		);

		$this->assertStringContainsString( 'fsweb_field_fsweb_company', do_shortcode( '[fsweb_review_form]' ) );
	}

	public function test_form_attributes_filter_is_printed_on_the_form_tag() {
		add_filter(
			'fsweb_cc_form_attributes',
			static function () {
				return 'data-custom="1"';
			}
		);

		$this->assertStringContainsString( 'data-custom="1"', do_shortcode( '[fsweb_review_form]' ) );
	}

	public function test_submission_creates_a_pending_review() {
		$_POST['fsweb_nonce']                  = wp_create_nonce( 'fsweb_review_submit' );
		$_REQUEST['fsweb_nonce']               = $_POST['fsweb_nonce'];
		$_POST['fsweb_field_fsweb_name']        = 'John Smith';
		$_POST['fsweb_field_fsweb_email']       = 'john@example.com';
		$_POST['fsweb_field_fsweb_description'] = 'Very good support.';
		$_POST['fsweb_field_fsweb_rating']      = '4';

		$response = $this->call_ajax( array( $this->form, 'ajax_submit' ) );

		$this->assertTrue( $response['success'] );

		$reviews = get_posts(
			array(
				'post_type'   => 'fsweb_review',
				'post_status' => 'pending',
				'fields'      => 'ids',
			)
		);

		$this->assertCount( 1, $reviews );
		$this->assertSame( 'John Smith', get_the_title( $reviews[0] ) );
		$this->assertSame( '4', get_post_meta( $reviews[0], 'fsweb_rating', true ) );
		$this->assertSame( 'john@example.com', get_post_meta( $reviews[0], 'fsweb_email', true ) );
	}

	public function test_submission_fires_the_submitted_action() {
		$captured = array();

		add_action(
			'fsweb_cc_review_submitted',
			static function ( $post_id, $data ) use ( &$captured ) {
				$captured = array( $post_id, $data );
			},
			10,
			2
		);

		$_POST['fsweb_nonce']                  = wp_create_nonce( 'fsweb_review_submit' );
		$_REQUEST['fsweb_nonce']               = $_POST['fsweb_nonce'];
		$_POST['fsweb_field_fsweb_name']        = 'John Smith';
		$_POST['fsweb_field_fsweb_email']       = 'john@example.com';
		$_POST['fsweb_field_fsweb_description'] = 'Very good support.';
		$_POST['fsweb_field_fsweb_rating']      = '5';

		$this->call_ajax( array( $this->form, 'ajax_submit' ) );

		$this->assertNotEmpty( $captured );
		$this->assertSame( 'John Smith', $captured[1]['name'] );
	}

	public function test_submission_rejects_invalid_payload() {
		$_POST['fsweb_nonce']                  = wp_create_nonce( 'fsweb_review_submit' );
		$_REQUEST['fsweb_nonce']               = $_POST['fsweb_nonce'];
		$_POST['fsweb_field_fsweb_name']        = '';
		$_POST['fsweb_field_fsweb_email']       = 'not-an-email';
		$_POST['fsweb_field_fsweb_description'] = '';
		$_POST['fsweb_field_fsweb_rating']      = '0';

		$response = $this->call_ajax( array( $this->form, 'ajax_submit' ) );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 0, count( get_posts( array( 'post_type' => 'fsweb_review', 'fields' => 'ids' ) ) ) );
	}

	public function test_submission_without_nonce_is_rejected() {
		$_POST['fsweb_field_fsweb_name']  = 'Hacker';
		$_POST['fsweb_field_fsweb_email'] = 'hacker@example.com';

		$this->catch_die();

		ob_start();
		try {
			$this->form->ajax_submit();
		} catch ( Fsweb_Cc_Die $e ) {
			$this->assertSame( '-1', $e->getMessage() );
		}
		ob_end_clean();
	}

	public function test_pro_can_take_over_the_submission_handler() {
		add_filter( 'fsweb_cc_form_handle_submission', '__return_false' );

		$_POST['fsweb_nonce']                  = wp_create_nonce( 'fsweb_review_submit' );
		$_REQUEST['fsweb_nonce']               = $_POST['fsweb_nonce'];
		$_POST['fsweb_field_fsweb_name']        = 'John Smith';
		$_POST['fsweb_field_fsweb_email']       = 'john@example.com';
		$_POST['fsweb_field_fsweb_description'] = 'Handled elsewhere.';
		$_POST['fsweb_field_fsweb_rating']      = '5';

		ob_start();
		$this->form->ajax_submit();
		ob_end_clean();

		$this->assertSame( 0, count( get_posts( array( 'post_type' => 'fsweb_review', 'fields' => 'ids' ) ) ) );
	}
}
