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

	public function test_get_all_taxonomies() {
		$expected = get_object_taxonomies( 'attachment', 'objects' );
		$this->assertSameSets( $expected, $this->instance->get_all_taxonomies() );
	}

	public function test_get_taxonomies_to_show() {
		$taxonomies = $this->instance->get_taxonomies_to_show();
		$this->assertArrayHasKey( 'attachment_category', $taxonomies );
		$this->assertArrayHasKey( 'attachment_tag', $taxonomies );
	}

	public function test_get_taxonomies_to_show_requires_show_ui() {
		register_taxonomy(
			'test_w_show_ui',
			'attachment',
			array(
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => true,
			)
		);
		register_taxonomy(
			'test_wo_show_ui',
			'attachment',
			array(
				'public'       => false,
				'show_ui'      => false,
				'show_in_rest' => true,
			)
		);

		$taxonomies = $this->instance->get_taxonomies_to_show();
		$this->assertArrayHasKey( 'test_w_show_ui', $taxonomies );
		$this->assertArrayNotHasKey( 'test_wo_show_ui', $taxonomies );
	}

	public function test_get_taxonomies_to_show_requires_query_var_or_show_in_rest() {
		register_taxonomy(
			'test_w_query_var',
			'attachment',
			array(
				'public'    => false,
				'show_ui'   => true,
				'query_var' => 'test_w_query_var',
			)
		);
		// Fix `$query_var` being forced to `false` outside of WP Admin.
		$GLOBALS['wp_taxonomies']['test_w_query_var']->query_var = 'test_w_query_var';
		register_taxonomy(
			'test_w_show_in_rest',
			'attachment',
			array(
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => true,
			)
		);
		register_taxonomy(
			'test_wo',
			'attachment',
			array(
				'public'       => false,
				'show_ui'      => true,
			)
		);

		$taxonomies = $this->instance->get_taxonomies_to_show();
		$this->assertArrayHasKey( 'test_w_query_var', $taxonomies );
		$this->assertArrayHasKey( 'test_w_show_in_rest', $taxonomies );
		$this->assertArrayNotHasKey( 'test_wo', $taxonomies );
	}

	/**
	 * @expectedDeprecated has_taxonomies
	 */
	public function test_has_taxonomies() {
		$taxonomies = get_object_taxonomies( 'attachment', 'names' );

		$result = $this->instance->has_taxonomies();
		if ( 0 < count( $taxonomies ) ) {
			$this->assertTrue( $result );
		} else {
			$this->assertFalse( $result );
		}
	}

	/**
	 * @expectedDeprecated get_taxonomies
	 */
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
