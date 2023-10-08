<?php
/**
 * Tests for Attachment_Taxonomy_Capabilities
 *
 * @package AttachmentTaxonomies\Tests
 * @author Felix Arntz <hello@felix-arntz.me>
 */

class Attachment_Taxonomy_Capabilities_Tests extends WP_UnitTestCase {
	private static $user_ids;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$user_ids = array(
			'admin'       => $factory->user->create( array( 'role' => 'administrator' ) ),
			'editor'      => $factory->user->create( array( 'role' => 'editor' ) ),
			'author'      => $factory->user->create( array( 'role' => 'author' ) ),
			'contributor' => $factory->user->create( array( 'role' => 'contributor' ) ),
		);
	}

	public static function wpTearDownAfterClass() {
		if ( is_multisite() ) {
			$delete_user = 'wpmu_delete_user';
		} else {
			$delete_user = 'wp_delete_user';
		}
		foreach ( self::$user_ids as $user_id ) {
			call_user_func( $delete_user, $user_id );
		}
	}

	/**
	 * @dataProvider data_map_meta_cap
	 */
	public function test_map_meta_cap( $meta_cap, $expected_caps ) {
		$instance = new Attachment_Taxonomy_Capabilities();

		$caps = $instance->map_meta_cap( array(), $meta_cap, self::$user_ids['admin'] );
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

	/**
	 * @dataProvider data_integration_user_can
	 */
	public function test_integration_user_can( $user_role, $cap, $expected ) {
		if ( $expected ) {
			$this->assertTrue( user_can( self::$user_ids[ $user_role ], $cap ) );
		} else {
			$this->assertFalse( user_can( self::$user_ids[ $user_role ], $cap ) );
		}
	}

	public function data_integration_user_can() {
		return array(
			array( 'editor', 'manage_attachment_categories', true ),
			array( 'editor', 'edit_attachment_categories', true ),
			array( 'editor', 'delete_attachment_categories', true ),
			array( 'editor', 'assign_attachment_categories', true ),
			array( 'editor', 'manage_attachment_tags', true ),
			array( 'editor', 'edit_attachment_tags', true ),
			array( 'editor', 'delete_attachment_tags', true ),
			array( 'editor', 'assign_attachment_tags', true ),
			array( 'author', 'manage_attachment_categories', false ),
			array( 'author', 'edit_attachment_categories', false ),
			array( 'author', 'delete_attachment_categories', false ),
			array( 'author', 'assign_attachment_categories', true ),
			array( 'author', 'manage_attachment_tags', false ),
			array( 'author', 'edit_attachment_tags', false ),
			array( 'author', 'delete_attachment_tags', false ),
			array( 'author', 'assign_attachment_tags', true ),
			array( 'contributor', 'manage_attachment_categories', false ),
			array( 'contributor', 'edit_attachment_categories', false ),
			array( 'contributor', 'delete_attachment_categories', false ),
			array( 'contributor', 'assign_attachment_categories', false ),
			array( 'contributor', 'manage_attachment_tags', false ),
			array( 'contributor', 'edit_attachment_tags', false ),
			array( 'contributor', 'delete_attachment_tags', false ),
			array( 'contributor', 'assign_attachment_tags', false ),
		);
	}
}
