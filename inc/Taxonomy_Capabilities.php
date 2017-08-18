<?php
/**
 * Attachment_Taxonomy_Capabilities class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( class_exists( 'Attachment_Taxonomy_Capabilities' ) ) {
	return;
}

/**
 * Handles capabilities for attachment taxonomies.
 *
 * @since 1.1.0
 */
final class Attachment_Taxonomy_Capabilities {
	/**
	 * The Singleton instance.
	 *
	 * @since 1.1.0
	 * @access private
	 * @static
	 * @var Attachment_Taxonomy_Capabilities|null
	 */
	private static $instance = null;

	/**
	 * Returns the Singleton instance.
	 *
	 * @since 1.1.0
	 * @access public
	 * @static
	 *
	 * @return Attachment_Taxonomy_Capabilities The Singleton class instance.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.1.0
	 * @access private
	 */
	private function __construct() {}

	/**
	 * Maps capabilities for the plugin's attachment taxonomies to respective core capabilities.
	 *
	 * @since 1.0.0
	 * @access public
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
				$caps = array( 'upload_files', 'manage_categories' );
				break;
			case 'assign_attachment_categories':
			case 'assign_attachment_tags':
				$post_type = get_post_type_object( 'attachment' );
				if ( ! $post_type ) {
					// This should never happen.
					$caps = array( 'upload_files', 'edit_others_posts' );
				} else {
					$caps = map_meta_cap( $post_type->cap->edit_posts, $user_id );
					$caps[] = 'upload_files';
				}
				break;
		}

		return $caps;
	}
}
