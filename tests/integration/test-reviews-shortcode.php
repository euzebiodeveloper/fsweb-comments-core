<?php
/**
 * [fsweb_reviews] shortcode.
 *
 * @package Fsweb_Comments_Core
 */

/**
 * @covers Fsweb_Comments_Core_Shortcode
 */
class Fsweb_Cc_Test_Reviews_Shortcode extends Fsweb_Cc_Test_Case {

	public function test_renders_published_reviews_as_grid_by_default() {
		$this->create_review( array( 'post_title' => 'Alice' ) );
		$this->create_review( array( 'post_title' => 'Bob' ) );
		$this->create_review(
			array(
				'post_title'  => 'Pending',
				'post_status' => 'pending',
			)
		);

		$output = do_shortcode( '[fsweb_reviews]' );

		$this->assertStringContainsString( 'fsweb-reviews--grid', $output );
		$this->assertSame( 2, substr_count( $output, 'fsweb-review-card' ) );
		$this->assertStringNotContainsString( 'Pending', $output );
	}

	public function test_list_layout_uses_the_list_template() {
		$this->create_review();

		$output = do_shortcode( '[fsweb_reviews layout="list"]' );

		$this->assertStringContainsString( 'fsweb-reviews--list', $output );
		$this->assertStringContainsString( 'fsweb-review-list-item', $output );
		$this->assertStringNotContainsString( 'fsweb-review-card', $output );
	}

	public function test_count_attribute_limits_the_reviews() {
		for ( $index = 0; $index < 4; $index++ ) {
			$this->create_review( array( 'post_title' => 'Review ' . $index ) );
		}

		$output = do_shortcode( '[fsweb_reviews count="2"]' );

		$this->assertSame( 2, substr_count( $output, 'fsweb-review-card' ) );
	}

	public function test_columns_attribute_is_clamped_between_one_and_four() {
		$this->create_review();

		$output = do_shortcode( '[fsweb_reviews columns="9"]' );

		$this->assertStringContainsString( '--fsweb-columns:4', $output );
	}

	public function test_category_attribute_filters_the_query() {
		$in_category = $this->create_review( array( 'post_title' => 'Inside' ) );
		$other       = $this->create_review( array( 'post_title' => 'Outside' ) );

		wp_set_object_terms( $in_category, 'featured', 'fsweb_review_category' );
		wp_set_object_terms( $other, 'other', 'fsweb_review_category' );

		$output = do_shortcode( '[fsweb_reviews category="featured"]' );

		$this->assertStringContainsString( 'Inside', $output );
		$this->assertStringNotContainsString( 'Outside', $output );
	}

	public function test_show_image_no_removes_the_thumbnail() {
		$this->create_review();

		$output = do_shortcode( '[fsweb_reviews show_image="no"]' );

		$this->assertStringNotContainsString( 'fsweb-review-image', $output );
	}

	public function test_empty_result_returns_empty_string() {
		$this->assertSame( '', do_shortcode( '[fsweb_reviews]' ) );
	}

	public function test_pre_render_filter_short_circuits_the_output() {
		$this->create_review();

		add_filter(
			'fsweb_cc_reviews_pre_render',
			static function ( $output ) {
				return '<div class="pro-output">PRO</div>';
			}
		);

		$output = do_shortcode( '[fsweb_reviews]' );

		$this->assertStringContainsString( 'pro-output', $output );
		$this->assertStringNotContainsString( 'fsweb-reviews--grid', $output );
	}

	public function test_query_args_filter_receives_the_attributes() {
		$captured = array();

		add_filter(
			'fsweb_cc_reviews_query_args',
			static function ( $args ) use ( &$captured ) {
				$captured = $args;
				return $args;
			}
		);

		$this->create_review();
		do_shortcode( '[fsweb_reviews count="3" order="oldest"]' );

		$this->assertSame( 'fsweb_review', $captured['post_type'] );
		$this->assertSame( 3, $captured['posts_per_page'] );
		$this->assertSame( 'ASC', $captured['order'] );
	}

	public function test_output_filter_can_change_the_markup() {
		$this->create_review();

		add_filter(
			'fsweb_cc_reviews_shortcode_output',
			static function ( $output ) {
				return $output . '<span class="appended"></span>';
			}
		);

		$this->assertStringContainsString( 'appended', do_shortcode( '[fsweb_reviews]' ) );
	}

	public function test_loop_hooks_fire() {
		$before = 0;
		$after  = 0;

		add_action(
			'fsweb_cc_reviews_before_loop',
			static function () use ( &$before ) {
				$before++;
			}
		);
		add_action(
			'fsweb_cc_reviews_after_loop',
			static function () use ( &$after ) {
				$after++;
			}
		);

		$this->create_review();
		do_shortcode( '[fsweb_reviews]' );

		$this->assertSame( 1, $before );
		$this->assertSame( 1, $after );
	}
}
