<?php
/**
 * @package AttachmentTaxonomies
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( class_exists( 'Attachment_Existing_Taxonomy' ) ) {
	return;
}

final class Attachment_Existing_Taxonomy {
	private $slug = '';

	public function __construct( $slug ) {
		$this->slug = $slug;
	}

	public function register() {
		register_taxonomy_for_object_type( $this->slug, 'attachment' );
	}

	public function unregister() {
		unregister_taxonomy_for_object_type( $this->slug, 'attachment' );
	}

	public function get_slug() {
		return $this->slug;
	}
}
