<?php
/**
 * Tests for Attachment_Taxonomies
 *
 * @package AttachmentTaxonomies\Tests
 * @author Felix Arntz <hello@felix-arntz.me>
 */

class Attachment_Taxonomies_Tests extends WP_UnitTestCase {
	public function test_add_taxonomy() {
		$status = Attachment_Taxonomies::instance()->add_taxonomy( new Attachment_Existing_Taxonomy( 'post_tag' ), true );
		$this->assertTrue( $status );
	}

	public function test_get_taxonomy() {
		Attachment_Taxonomies::instance()->add_taxonomy( new Attachment_Existing_Taxonomy( 'post_tag' ), true );

		$tax = Attachment_Taxonomies::instance()->get_taxonomy( 'post_tag', true );
		$this->assertInstanceOf( 'Attachment_Existing_Taxonomy', $tax );

		$tax = Attachment_Taxonomies::instance()->get_taxonomy( 'attachment_category' );
		$this->assertInstanceOf( 'Attachment_Category', $tax );
	}

	public function test_remove_taxonomy() {
		$status = Attachment_Taxonomies::instance()->remove_taxonomy( 'attachment_category' );
		$this->assertTrue( $status );
	}
}
