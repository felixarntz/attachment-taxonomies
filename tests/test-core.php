<?php

class AT_Tests_Core extends WP_UnitTestCase {
	public function test_has_taxonomies() {
		$taxonomies = get_object_taxonomies( 'attachment', 'names' );

		$result = Attachment_Taxonomies_Core::instance()->has_taxonomies();
		if ( 0 < count( $taxonomies ) ) {
			$this->assertTrue( $result );
		} else {
			$this->assertFalse( $result );
		}
	}

	public function test_get_taxonomies() {
		$taxonomies = get_object_taxonomies( 'attachment', 'names' );

		$result = Attachment_Taxonomies_Core::instance()->get_taxonomies();
		$this->assertEqualSets( $taxonomies, $result );
	}

	public function test_get_terms_for_taxonomy() {
		$factory = method_exists( 'WP_UnitTestCase', 'factory' ) ? self::factory() : $this->factory;

		$term1 = $factory->term->create( array( 'taxonomy' => 'attachment_category' ) );
		$term2 = $factory->term->create( array( 'taxonomy' => 'attachment_category' ) );

		$result = Attachment_Taxonomies_Core::instance()->get_terms_for_taxonomy( 'attachment_category' );
		$this->assertCount( 2, $result );
		$this->assertEquals( $term1, $result[0]->term_id );
		$this->assertEquals( $term2, $result[1]->term_id );
	}
}
