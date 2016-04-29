<?php

class AT_Tests_Taxonomy extends WP_UnitTestCase {
	public function test_add_taxonomy() {
		$status = Attachment_Taxonomies::add_taxonomy( new Attachment_Existing_Taxonomy( 'post_tag' ), true );
		$this->assertTrue( $status );
	}

	public function test_get_taxonomy() {
		Attachment_Taxonomies::add_taxonomy( new Attachment_Existing_Taxonomy( 'post_tag' ), true );

		$tax = Attachment_Taxonomies::get_taxonomy( 'post_tag', true );
		assertInstanceOf( 'Attachment_Existing_Taxonomy' );
	}
}
