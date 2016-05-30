<?php
/**
 * Attachment_Taxonomy class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 1.0.0
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
	protected $slug = '';

	/**
	 * Holds the taxonomy labels.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $labels = array();

	/**
	 * Holds the taxonomy arguments.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $args = array();

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

		if ( function_exists( 'unregister_taxonomy' ) ) {
			unregister_taxonomy( $slug );
			return;
		}

		global $wp_taxonomies;

		$taxonomy_args = get_taxonomy( $this->slug );
		if ( ! $taxonomy_args || $taxonomy_args->_builtin ) {
			return;
		}

		remove_filter( 'wp_ajax_add-' . $this->slug, '_wp_ajax_add_hierarchical_term' );

		unset( $wp_taxonomies[ $this->slug ] );
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
		$slug = $this->get_slug();

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
		$slug = $this->get_slug();

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
