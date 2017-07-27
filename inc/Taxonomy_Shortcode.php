<?php
/**
 * Attachment_Taxonomy_Shortcode class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( class_exists( 'Attachment_Taxonomy_Shortcode' ) ) {
	return;
}

/**
 * Contains adjustments for the [gallery] shortcode.
 *
 * @since 1.1.0
 */
final class Attachment_Taxonomy_Shortcode {
	/**
	 * The Singleton instance.
	 *
	 * @since 1.1.0
	 * @access private
	 * @static
	 * @var Attachment_Taxonomy_Shortcode|null
	 */
	private static $instance = null;

	/**
	 * Returns the Singleton instance.
	 *
	 * @since 1.1.0
	 * @access public
	 * @static
	 *
	 * @return Attachment_Taxonomy_Shortcode The Singleton class instance.
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
	 * Filters the [gallery] shortcode attributes and adds support for taxonomies.
	 *
	 * The taxonomy attributes will trigger appropriate attachment queries, and then
	 * the resulting attachments will be added to the 'include' attribute.
	 *
	 * @since 1.1.0
	 * @access public
	 *
	 * @param array $out   Combined and filtered attribute list.
	 * @param array $pairs Entire list of supported attributes and their defaults.
	 * @param array $atts  User defined attributes in shortcode tag.
	 * @return array Possibly modified attribute list.
	 */
	public function support_gallery_taxonomy_attributes( $out, $pairs, $atts ) {
		$taxonomy_slugs = Attachment_Taxonomies_Core::instance()->get_taxonomies();

		$all_term_ids = array();
		foreach ( $taxonomy_slugs as $taxonomy_slug ) {
			if ( empty( $atts[ $taxonomy_slug ] ) ) {
				continue;
			}

			$term_ids = $this->get_term_ids_from_attribute( $taxonomy_slug, $atts[ $taxonomy_slug ] );
			if ( empty( $term_ids ) ) {
				continue;
			}

			$all_term_ids[ $taxonomy_slug ] = $term_ids;
		}

		$original_ids = array();
		if ( ! empty( $out['include'] ) ) {
			$original_ids = wp_parse_id_list( $out['include'] );
		}

		$limit = ! empty( $atts['limit'] ) ? absint( $atts['limit'] ) : -1;
		if ( -1 !== $limit ) {
			$limit -= count( $original_ids );
			if ( $limit < 1 ) {
				return $out;
			}
		}

		$attachment_ids = $this->get_shortcode_attachment_ids( $all_term_ids, $limit );
		if ( ! empty( $attachment_ids ) ) {
			$out['include'] = array_merge( $original_ids, $attachment_ids );
		}

		return $out;
	}

	/**
	 * Parses an attribute of one or more term slugs or IDs into an array of valid term IDs.
	 *
	 * @since 1.1.0
	 * @access private
	 *
	 * @param string $taxonomy_slug Taxonomy slug.
	 * @param string $attr          Shortcode attribute with term slugs or IDs.
	 * @return array Array of term IDs.
	 */
	private function get_term_ids_from_attribute( $taxonomy_slug, $attr ) {
		$query_arg = 'slug';
		$items = wp_parse_slug_list( $attr );

		if ( empty( $items ) ) {
			return array();
		}

		$ids = array_filter( $items, 'is_numeric' );
		if ( count( $ids ) === count( $items ) ) {
			$query_arg = 'include';
			$items = array_map( 'absint', $items );
		}

		$query_args = array(
			'number'                 => 0,
			'fields'                 => 'ids',
			'update_term_meta_cache' => false,
		);
		$query_args[ $query_arg ] = $items;

		$term_ids = Attachment_Taxonomies_Core::instance()->get_terms_for_taxonomy( $taxonomy_slug, $query_args );
		if ( ! $term_ids || is_wp_error( $term_ids ) ) {
			return array();
		}

		return $term_ids;
	}

	/**
	 * Queries attachments with specific taxonomies and terms.
	 *
	 * @since 1.1.0
	 * @access private
	 *
	 * @param array $all_term_ids Array of `$taxonomy_slug => $term_ids` pairs.
	 * @param int   $limit        Optional. Limit for the query. Default is -1 (no limit).
	 * @return array Array of attachment IDs.
	 */
	private function get_shortcode_attachment_ids( $all_term_ids, $limit = -1 ) {
		$tax_query = array();
		if ( count( $all_term_ids ) > 1 ) {
			$tax_query['relation'] = 'OR';
		}

		foreach ( $all_term_ids as $taxonomy_slug => $term_ids ) {
			$tax_query[] = array(
				'taxonomy'         => $taxonomy_slug,
				'field'            => 'term_id',
				'terms'            => $term_ids,
				'include_children' => false,
				'operator'         => 'IN',
			);
		}

		return get_posts( array(
			'posts_per_page'         => $limit,
			'fields'                 => 'ids',
			'post_status'            => 'inherit',
			'post_type'              => 'attachment',
			'post_mime_type'         => 'image',
			'tax_query'              => $tax_query,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		) );
	}
}
