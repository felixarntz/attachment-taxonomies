<?php
/**
 * Attachment_Existing_Taxonomy class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <hello@felix-arntz.me>
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Attachment_Existing_Taxonomy' ) ) {
	return;
}

/**
 * Represents an attachment taxonomy that already exists for another post type.
 *
 * This class does not actually register a new taxonomy, it just makes this taxonomy
 * available for attachments.
 */
final class Attachment_Existing_Taxonomy extends Attachment_Taxonomy {
	/**
	 * Constructor - sets the taxonomy slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug The taxonomy slug.
	 */
	public function __construct( $slug ) {
		$this->slug = $slug;
	}

	/**
	 * Registers the taxonomy for attachments.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		register_taxonomy_for_object_type( $this->slug, 'attachment' );
	}

	/**
	 * Unregisters the taxonomy for attachments.
	 *
	 * @since 1.0.0
	 */
	public function unregister() {
		unregister_taxonomy_for_object_type( $this->slug, 'attachment' );
	}
}
