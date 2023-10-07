<?php
/**
 * Tests for Attachment_Taxonomies_Hooks
 *
 * @package AttachmentTaxonomies\Tests
 * @author Felix Arntz <hello@felix-arntz.me>
 */

class Attachment_Taxonomies_Hooks_Tests extends WP_UnitTestCase {
	private $instance;

	public function set_up() {
		$this->instance = new Attachment_Taxonomies_Hooks(
			new Attachment_Taxonomies_Plugin_Env( TESTS_PLUGIN_DIR . '/attachment-taxonomies.php' )
		);
	}

	public function test_add_all() {
		$expected_actions = array(
			'restrict_manage_posts',
			'wp_enqueue_media',
			'edit_attachment',
			'add_attachment',
			'rest_api_init',
			'admin_init',
		);
		$expected_filters = array(
			'wp_prepare_attachment_for_js',
			'attachment_fields_to_edit',
			'map_meta_cap',
			'shortcode_atts_gallery',
		);

		foreach ( $expected_actions as $action ) {
			remove_all_actions( $action );
		}
		foreach ( $expected_filters as $filter ) {
			remove_all_filters( $filter );
		}

		$this->instance->add_all();

		foreach ( $expected_actions as $action ) {
			$this->assertTrue( has_action( $action ), sprintf( 'Failed asserting that actions for %s were added.', $action ) );
		}
		foreach ( $expected_filters as $filter ) {
			$this->assertTrue( has_filter( $filter ), sprintf( 'Failed asserting that filters for %s were added.', $filter ) );
		}
	}
}
