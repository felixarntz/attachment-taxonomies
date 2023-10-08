<?php
/**
 * Attachment_Taxonomies_Core class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <hello@felix-arntz.me>
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Attachment_Taxonomies_Core' ) ) {
	return;
}

/**
 * Contains core methods to handle attachment taxonomies.
 *
 * @since 1.0.0
 */
final class Attachment_Taxonomies_Core {
	/**
	 * The Singleton instance.
	 *
	 * @since 1.0.0
	 * @deprecated 1.2.0
	 * @var Attachment_Taxonomies_Core|null
	 */
	private static $instance = null;

	/**
	 * The Singleton instance.
	 *
	 * @since 1.0.0
	 * @deprecated 1.2.0
	 *
	 * @return Attachment_Taxonomies_Core The Singleton class instance.
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
	 * @since 1.0.0
	 * @since 1.2.0 Constructor is now public.
	 */
	public function __construct() {
		if ( null === self::$instance ) {
			self::$instance = $this;
		}
	}

	/**
	 * Checks whether there are any attachment taxonomies registered.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if there are attachment taxonomies, otherwise false.
	 */
	public function has_taxonomies() {
		$taxonomies = $this->get_taxonomies();
		return 0 < count( $taxonomies );
	}

	/**
	 * Returns attachment taxonomies.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mode Either 'names' (for an array of taxonomy slugs) or 'objects' (for an array of objects).
	 * @return array A list of taxonomy names or objects.
	 */
	public function get_taxonomies( $mode = 'names' ) {
		return get_object_taxonomies( 'attachment', $mode );
	}

	/**
	 * Returns all terms for a specific taxonomy.
	 *
	 * Empty terms are also included.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 The $args parameter has been added.
	 *
	 * @param string $taxonomy_slug The taxonomy to get the terms for.
	 * @param array  $args          Optional. Additional query arguments. Default empty array.
	 * @return array|WP_Error A list of term objects or an error if the taxonomy does not exist.
	 */
	public function get_terms_for_taxonomy( $taxonomy_slug, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'hide_empty' => false,
			)
		);

		$args['taxonomy'] = $taxonomy_slug;
		return get_terms( $args );
	}
}
