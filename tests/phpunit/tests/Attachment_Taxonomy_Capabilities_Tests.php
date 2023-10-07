<?php
/**
 * Tests for Attachment_Taxonomy_Capabilities
 *
 * @package AttachmentTaxonomies\Tests
 * @author Felix Arntz <hello@felix-arntz.me>
 */

class Attachment_Taxonomy_Capabilities_Tests extends WP_UnitTestCase {
	private static $admin_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	public static function wpTearDownAfterClass() {
		if ( is_multisite() ) {
			wpmu_delete_user( self::$admin_id );
		} else {
			wp_delete_user( self::$admin_id );
		}
	}

	/**
	 * @dataProvider data_map_meta_cap
	 */
	public function test_map_meta_cap( $meta_cap, $expected_caps ) {
		$instance = new Attachment_Taxonomy_Capabilities();

		$caps = $instance->map_meta_cap( array(), $meta_cap, self::$admin_id );
		$this->assertSameSets( $expected_caps, $caps );
	}

	public function data_map_meta_cap() {
		$base_caps   = array( 'upload_files', 'manage_categories' );
		$assign_caps = array( 'upload_files', 'edit_posts' );

		return array(
			array( 'manage_attachment_categories', $base_caps ),
			array( 'edit_attachment_categories', $base_caps ),
			array( 'delete_attachment_categories', $base_caps ),
			array( 'assign_attachment_categories', $assign_caps ),
			array( 'manage_attachment_tags', $base_caps ),
			array( 'edit_attachment_tags', $base_caps ),
			array( 'delete_attachment_tags', $base_caps ),
			array( 'assign_attachment_tags', $assign_caps ),
			array( 'unrelated_cap', array() ),
		);
	}
}
