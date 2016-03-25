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
	public function register() {
		$slug = $this->get_slug();

		$labels = $this->get_labels();

		$labels = apply_filters( 'attachment_taxonomy_' . $slug . '_labels', $labels );

		$labels = apply_filters( 'attachment_taxonomy_labels', $labels, $slug );

		$args = $this->get_args();
		$args['labels'] = $labels;

		$args = apply_filters( 'attachment_taxonomy_' . $slug . '_args', $args );

		$args = apply_filters( 'attachment_taxonomy_args', $args, $slug );

		register_taxonomy( $slug, 'attachment', $args );
	}

	public abstract function get_slug();

	protected abstract function get_labels();

	protected abstract function get_args();
}
