<?php
/**
 * Attachment_Taxonomy_Capabilities class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <hello@felix-arntz.me>
 * @since 1.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Attachment_Taxonomy_Capabilities' ) ) {
	return;
}

/**
 * Handles capabilities for attachment taxonomies.
 *
 * @since 1.1.1
 */
final class Attachment_Taxonomy_Capabilities {
	/**
	 * The Singleton instance.
	 *
	 * @since 1.1.1
	 * @deprecated 1.2.0
	 * @var Attachment_Taxonomy_Capabilities|null
	 */
	private static $instance = null;

	/**
	 * Returns the Singleton instance.
	 *
	 * @since 1.1.1
	 * @deprecated 1.2.0
	 *
	 * @return Attachment_Taxonomy_Capabilities The Singleton class instance.
	 *
	 * @throws Exception Thrown when called before plugin initialization.
	 */
	public static function instance() {
		_deprecated_function( __METHOD__, 'Attachment Taxonomies 1.2.0' );
		if ( null === self::$instance ) {
			throw new Exception(
				esc_html__( 'Class instance can only be retrieved once the Attachment Taxonomies plugin has been initialized.', 'attachment-taxonomies' )
			);
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.1.1
	 * @since 1.2.0 Constructor is now public.
	 */
	public function __construct() {
		if ( null === self::$instance ) {
			self::$instance = $this;
		}
	}

	/**
	 * Maps capabilities for the plugin's attachment taxonomies to respective core capabilities.
	 *
	 * @since 1.1.1
	 *
	 * @param array  $caps    Capabilities that should be checked.
	 * @param string $cap     Original capability to map.
	 * @param int    $user_id ID of the user whose capabilities are checked.
	 * @return array Modified capabilities to be checked.
	 */
	public function map_meta_cap( $caps, $cap, $user_id ) {
		switch ( $cap ) {
			case 'manage_attachment_categories':
			case 'edit_attachment_categories':
			case 'delete_attachment_categories':
			case 'manage_attachment_tags':
			case 'edit_attachment_tags':
			case 'delete_attachment_tags':
				return $this->get_manage_base_caps();
			case 'assign_attachment_categories':
			case 'assign_attachment_tags':
				return $this->get_assign_base_caps( $user_id );
		}

		return $caps;
	}

	/**
	 * Gets the base capabilities to manage attachment taxonomies (except assigning them).
	 *
	 * This is used to map the corresponding attachment taxonomy meta capabilities to base capabilities.
	 *
	 * @since 1.2.0
	 *
	 * @return array List of base capabilities.
	 */
	private function get_manage_base_caps() {
		return array( 'upload_files', 'manage_categories' );
	}

	/**
	 * Gets the base capabilities to assign attachment taxonomies to attachments.
	 *
	 * This is used to map the corresponding attachment taxonomy meta capabilities to base capabilities.
	 *
	 * @since 1.2.0
	 *
	 * @param int $user_id User ID to get the base capabilities for.
	 * @return array List of base capabilities.
	 */
	private function get_assign_base_caps( $user_id ) {
		$post_type = get_post_type_object( 'attachment' );
		if ( ! $post_type ) {
			// This should never happen.
			return array( 'do_not_allow' );
		}

		$caps   = map_meta_cap( $post_type->cap->edit_posts, $user_id );
		$caps[] = 'upload_files';
		return $caps;
	}
}
