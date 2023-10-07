<?php
/**
 * Tests for Attachment_Taxonomies_Core
 *
 * @package AttachmentTaxonomies\Tests
 * @author Felix Arntz <hello@felix-arntz.me>
 */

class Attachment_Taxonomies_Core_Tests extends WP_UnitTestCase {
	private $instance;

	public function set_up() {
		$this->instance = new Attachment_Taxonomies_Core(
			new Attachment_Taxonomies_Plugin_Env( TESTS_PLUGIN_DIR . '/attachment-taxonomies.php' )
		);
	}

	public function test_has_taxonomies() {
		$taxonomies = get_object_taxonomies( 'attachment', 'names' );

		$result = $this->instance->has_taxonomies();
		if ( 0 < count( $taxonomies ) ) {
			$this->assertTrue( $result );
		} else {
			$this->assertFalse( $result );
		}
	}

	public function test_get_taxonomies() {
		$taxonomies = get_object_taxonomies( 'attachment', 'names' );

		$result = $this->instance->get_taxonomies();
		$this->assertEqualSets( $taxonomies, $result );
	}

	public function test_get_terms_for_taxonomy() {
		$term1 = self::factory()->term->create( array( 'taxonomy' => 'attachment_category' ) );
		$term2 = self::factory()->term->create( array( 'taxonomy' => 'attachment_category' ) );

		$result = $this->instance->get_terms_for_taxonomy( 'attachment_category' );
		$this->assertCount( 2, $result );
		$this->assertEquals( $term1, $result[0]->term_id );
		$this->assertEquals( $term2, $result[1]->term_id );
	}
}
