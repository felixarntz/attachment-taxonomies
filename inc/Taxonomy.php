<?php
/**
 * @package AttachmentTaxonomies
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( class_exists( 'Attachment_Taxonomy' ) ) {
	return;
}

abstract class Attachment_Taxonomy {
	protected $slug = '';
	protected $labels = array();
	protected $args = array();

	public function register() {
		$slug = $this->get_slug();

		$labels = $this->labels;

		$labels = apply_filters( 'attachment_taxonomy_' . $slug . '_labels', $labels );

		$labels = apply_filters( 'attachment_taxonomy_labels', $labels, $slug );

		$args = $this->args;
		$args['labels'] = $labels;

		$args = apply_filters( 'attachment_taxonomy_' . $slug . '_args', $args );

		$args = apply_filters( 'attachment_taxonomy_args', $args, $slug );

		register_taxonomy( $slug, 'attachment', $args );
	}

	public function unregister() {
		$slug = $this->get_slug();

		unregister_taxonomy( $slug );
	}

	public function get_slug() {
		return $this->slug;
	}
}
