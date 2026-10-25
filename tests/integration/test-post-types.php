<?php
/**
 * Review post type and taxonomy.
 *
 * @package Fsweb_Comments_Core
 */

/**
 * @covers Fsweb_Comments_Core_Post_Types
 */
class Fsweb_Cc_Test_Post_Types extends Fsweb_Cc_Test_Case {

	public function test_review_post_type_is_registered() {
		$this->assertTrue( post_type_exists( 'fsweb_review' ) );

		$object = get_post_type_object( 'fsweb_review' );

		$this->assertTrue( $object->public );
		$this->assertFalse( $object->publicly_queryable );
		$this->assertFalse( $object->has_archive );
		$this->assertTrue( $object->show_in_menu );
		$this->assertSame( 'review', $object->rewrite['slug'] );
	}

	public function test_review_post_type_supports_title_thumbnail_and_order() {
		$this->assertTrue( post_type_supports( 'fsweb_review', 'title' ) );
		$this->assertTrue( post_type_supports( 'fsweb_review', 'thumbnail' ) );
		$this->assertTrue( post_type_supports( 'fsweb_review', 'page-attributes' ) );
	}

	public function test_review_taxonomy_is_registered_for_reviews() {
		$this->assertTrue( taxonomy_exists( 'fsweb_review_category' ) );

		$taxonomy = get_taxonomy( 'fsweb_review_category' );

		$this->assertTrue( $taxonomy->hierarchical );
		$this->assertTrue( $taxonomy->show_admin_column );
		$this->assertContains( 'fsweb_review', $taxonomy->object_type );
		$this->assertSame( 'review-category', $taxonomy->rewrite['slug'] );
	}
}
