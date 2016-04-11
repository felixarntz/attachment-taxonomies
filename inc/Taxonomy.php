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

/**
 * Represents a custom attachment taxonomy.
 *
 * @since 1.0.0
 */
abstract class Attachment_Taxonomy {
	/**
	 * Holds the taxonomy slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected abstract $slug;

	/**
	 * Holds the taxonomy labels.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected abstract $labels;

	/**
	 * Holds the taxonomy arguments.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected abstract $args;

	/**
	 * Registers the taxonomy.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$slug = $this->get_slug();
		$args = $this->get_args();

		register_taxonomy( $slug, 'attachment', $args );
	}

	/**
	 * Unregisters the taxonomy.
	 *
	 * @since 1.0.0
	 */
	public function unregister() {
		$slug = $this->get_slug();

		unregister_taxonomy( $slug );
	}

	/**
	 * Returns the taxonomy slug.
	 *
	 * @since 1.0.0
	 * @return string the taxonomy slug
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Returns the taxonomy labels.
	 *
	 * @since 1.0.0
	 * @return array the taxonomy labels
	 */
	public function get_labels() {
		$labels = $this->labels;

		/**
		 * Filter the attachment taxonomy labels for a specific taxonomy.
		 *
		 * The dynamic portion of the hook name, `$slug` refers to the taxonomy slug.
		 *
		 * @since 1.0.0
		 * @param array $labels the taxonomy labels
		 */
		$labels = apply_filters( "attachment_taxonomy_{$slug}_labels", $labels );

		/**
		 * Filter the attachment taxonomy labels.
		 *
		 * @since 1.0.0
		 * @param array  $labels the taxonomy labels
		 * @param string $slug   the taxonomy slug
		 */
		return apply_filters( 'attachment_taxonomy_labels', $labels, $slug );
	}

	/**
	 * Returns the taxonomy arguments.
	 *
	 * @since 1.0.0
	 * @return array the taxonomy arguments
	 */
	public function get_args() {
		$args = $this->args;
		$args['labels'] = $this->get_labels();

		/**
		 * Filter the attachment taxonomy arguments for a specific taxonomy.
		 *
		 * The dynamic portion of the hook name, `$slug` refers to the taxonomy slug.
		 *
		 * @since 1.0.0
		 * @param array $args the taxonomy arguments
		 */
		$args = apply_filters( "attachment_taxonomy_{$slug}_args", $args );

		/**
		 * Filter the attachment taxonomy arguments.
		 *
		 * @since 1.0.0
		 * @param array  $args the taxonomy arguments
		 * @param string $slug the taxonomy slug
		 */
		return apply_filters( 'attachment_taxonomy_args', $args, $slug );
	}
}
