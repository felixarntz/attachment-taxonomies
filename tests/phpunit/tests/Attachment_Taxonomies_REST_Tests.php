<?php
/**
 * Tests for Attachment_Taxonomies_REST
 *
 * @package AttachmentTaxonomies\Tests
 * @author Felix Arntz <hello@felix-arntz.me>
 */

class Attachment_Taxonomies_REST_Tests extends WP_UnitTestCase {
	private static $cat_id;
	private static $author_id;

	private $instance;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$cat_id    = $factory->term->create( array( 'taxonomy' => 'attachment_category' ) );
		self::$author_id = $factory->user->create( array( 'role' => 'author' ) );
	}

	public static function wpTearDownAfterClass() {
		wp_delete_term( self::$cat_id, 'attachment_category' );
		if ( is_multisite() ) {
			wpmu_delete_user( self::$author_id );
		} else {
			wp_delete_user( self::$author_id );
		}
	}

	public function set_up() {
		$this->instance = new Attachment_Taxonomies_REST(
			new Attachment_Taxonomies_Core(
				new Attachment_Taxonomies_Plugin_Env( TESTS_PLUGIN_DIR . '/attachment-taxonomies.php' )
			)
		);
	}

	/**
	 * @dataProvider data_unrelated_handlers
	 */
	public function test_fail_permission_check_if_cannot_assign_attachment_terms_with_unrelated_handler( $handler ) {
		$expected = new WP_Error( 'test_error' );

		$this->assertSame(
			$expected,
			$this->instance->fail_permission_check_if_cannot_assign_attachment_terms( $expected, $handler, new WP_REST_Request() )
		);
	}

	public function data_unrelated_handlers() {
		return array(
			'missing permission callback' => array(
				array(
					'methods' => array( 'POST' ),
				),
			),
			'invalid permission callback' => array(
				array(
					'methods'             => array( 'POST' ),
					'permission_callback' => 'some_missing_func',
				),
			),
			'unrelated handler class'     => array(
				array(
					'methods'             => array( 'POST' ),
					'permission_callback' => array(
						new WP_REST_Posts_Controller( 'post' ),
						'update_item_permissions_check',
					)
				),
			),
			'unrelated handler method'    => array(
				array(
					'methods'             => array( 'POST' ),
					'permission_callback' => array(
						new WP_REST_Attachments_Controller( 'attachment' ),
						'get_item_permissions_check',
					)
				),
			),
		);
	}

	public function test_fail_permission_check_if_cannot_assign_attachment_terms_with_no_terms_provided() {
		$expected = new WP_Error( 'test_error' );

		$handler = array(
			'methods'             => array( 'POST' ),
			'permission_callback' => array(
				new WP_REST_Attachments_Controller( 'attachment' ),
				'update_item_permissions_check',
			),
		);

		$request = new WP_REST_Request( 'POST' );
		$request->set_body_params( array( 'some_field' => 'some_value' ) );

		$this->assertSame(
			$expected,
			$this->instance->fail_permission_check_if_cannot_assign_attachment_terms( $expected, $handler, $request )
		);
	}

	public function test_fail_permission_check_if_cannot_assign_attachment_terms_with_invalid_terms() {
		$handler = array(
			'methods'             => array( 'POST' ),
			'permission_callback' => array(
				new WP_REST_Attachments_Controller( 'attachment' ),
				'update_item_permissions_check',
			),
		);

		$request = new WP_REST_Request( 'POST' );
		$request->set_body_params( array( 'attachment_categories' => array( 99999 ) ) );

		$result = $this->instance->fail_permission_check_if_cannot_assign_attachment_terms( null, $handler, $request );
		$this->assertWPError( $result );
		$this->assertSame( 'rest_invalid_term_id', $result->get_error_code(), 'Unexpected error code' );
	}

	public function test_fail_permission_check_if_cannot_assign_attachment_terms_with_missing_permissions() {
		$handler = array(
			'methods'             => array( 'POST' ),
			'permission_callback' => array(
				new WP_REST_Attachments_Controller( 'attachment' ),
				'update_item_permissions_check',
			),
		);

		$request = new WP_REST_Request( 'POST' );
		$request->set_body_params( array( 'attachment_categories' => array( self::$cat_id ) ) );

		$result = $this->instance->fail_permission_check_if_cannot_assign_attachment_terms( null, $handler, $request );
		$this->assertWPError( $result );
		$this->assertSame( 'rest_cannot_assign_term', $result->get_error_code(), 'Unexpected error code' );
	}

	public function test_fail_permission_check_if_cannot_assign_attachment_terms_with_permissions() {
		$handler = array(
			'methods'             => array( 'POST' ),
			'permission_callback' => array(
				new WP_REST_Attachments_Controller( 'attachment' ),
				'update_item_permissions_check',
			),
		);

		$request = new WP_REST_Request( 'POST' );
		$request->set_body_params( array( 'attachment_categories' => array( self::$cat_id ) ) );

		wp_set_current_user( self::$author_id );

		$result = $this->instance->fail_permission_check_if_cannot_assign_attachment_terms( null, $handler, $request );
		$this->assertNull( $result );
	}

	public function test_handle_attachment_terms() {
		$attachment_id = self::factory()->attachment->create_upload_object( TESTS_PLUGIN_DIR . '/tests/assets/1024x768_e2e_test_image_size.jpeg' );

		$request = new WP_REST_Request( 'POST' );
		$request->set_body_params( array( 'attachment_categories' => array( self::$cat_id ) ) );

		$this->instance->handle_attachment_terms( get_post( $attachment_id ), $request );
		$this->assertSameSets(
			array( self::$cat_id ),
			wp_get_object_terms( $attachment_id, array( 'attachment_category' ), array( 'fields' => 'ids' ) )
		);
	}
}
