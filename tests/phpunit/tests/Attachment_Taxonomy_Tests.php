<?php
/**
 * Tests for Attachment_Taxonomy
 *
 * @package AttachmentTaxonomies\Tests
 * @author Felix Arntz <hello@felix-arntz.me>
 */

class Attachment_Taxonomy_Tests extends WP_UnitTestCase {
	public function test_register() {
		if ( function_exists( 'unregister_taxonomy' ) ) {
			unregister_taxonomy( 'attachment_tag' );
		} else {
			global $wp_taxonomies;

			unset( $wp_taxonomies['attachment_tag'] );
		}

		$tag = new Attachment_Tag();

		$tag->register();

		$tax = get_taxonomy( 'attachment_tag' );
		$this->assertObjectHasProperty( 'name', $tax );
	}

	public function test_unregister() {
		$tag = new Attachment_Tag();

		$tag->unregister();

		$tax = get_taxonomy( 'attachment_tag' );
		$this->assertFalse( $tax );
	}

	public function test_get_slug() {
		$tag = new Attachment_Tag();

		$slug = $tag->get_slug();
		$this->assertEquals( 'attachment_tag', $slug );
	}

	public function test_get_labels() {
		$tag = new Attachment_Tag();

		$labels = $tag->get_labels();
		$this->assertEmpty( $labels );
	}

	public function test_get_args() {
		$tag = new Attachment_Tag();

		$args = $tag->get_args();
		$this->assertArrayHasKey( 'labels', $args );
		$this->assertArrayHasKey( 'public', $args );
		$this->assertArrayHasKey( 'show_ui', $args );
		$this->assertArrayHasKey( 'show_in_menu', $args );
		$this->assertArrayHasKey( 'hierarchical', $args );
	}
}
