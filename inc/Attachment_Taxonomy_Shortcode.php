<?php
/**
 * Attachment_Taxonomy_Shortcode class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <hello@felix-arntz.me>
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
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
	 * @deprecated 1.2.0
	 * @var Attachment_Taxonomy_Shortcode|null
	 */
	private static $instance = null;

	/**
	 * Returns the Singleton instance.
	 *
	 * @since 1.1.0
	 * @deprecated 1.2.0
	 *
	 * @return Attachment_Taxonomy_Shortcode The Singleton class instance.
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
	 * Plugin core instance.
	 *
	 * @since 1.2.0
	 * @var Attachment_Taxonomies_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.1.0
	 * @since 1.2.0 Constructor is now public with $core parameter added.
	 *
	 * @param Attachment_Taxonomies_Core $core Plugin core instance.
	 */
	public function __construct( Attachment_Taxonomies_Core $core ) {
		$this->core = $core;

		if ( null === self::$instance ) {
			self::$instance = $this;
		}
	}

	/**
	 * Filters the [gallery] shortcode attributes and adds support for taxonomies.
	 *
	 * The taxonomy attributes will trigger appropriate attachment queries, and then
	 * the resulting attachments will be added to the 'include' attribute.
	 *
	 * @since 1.1.0
	 *
	 * @param array $out   Combined and filtered attribute list.
	 * @param array $pairs Entire list of supported attributes and their defaults.
	 * @param array $atts  User defined attributes in shortcode tag.
	 * @return array Possibly modified attribute list.
	 */
	public function support_gallery_taxonomy_attributes( $out, $pairs, $atts ) {
		$taxonomy_slugs = array_map(
			static function ( $taxonomy ) {
				return $taxonomy->name;
			},
			$this->core->get_all_taxonomies()
		);

		$all_term_ids = $this->get_all_term_ids( $taxonomy_slugs, $atts );
		if ( empty( $all_term_ids ) ) {
			return $out;
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

		$tax_relation = ( isset( $atts['tax_relation'] ) && 'AND' === strtoupper( $atts['tax_relation'] ) ) ? 'AND' : 'OR';

		$attachment_ids = $this->get_shortcode_attachment_ids( $all_term_ids, $limit, $original_ids, $tax_relation );
		if ( ! empty( $attachment_ids ) ) {
			$out['include'] = array_merge( $original_ids, $attachment_ids );
		}

		return $out;
	}

	/**
	 * Gets all term IDs for the given taxonomy slugs based on the given shortcode attributes.
	 *
	 * @since 1.2.0
	 *
	 * @param array $taxonomy_slugs List of taxonomy slugs.
	 * @param array $atts           User defined attributes in shortcode tag.
	 * @return array Map of taxonomy slugs and their list of term IDs based on the shortcode attributes.
	 */
	private function get_all_term_ids( $taxonomy_slugs, $atts ) {
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

		return $all_term_ids;
	}

	/**
	 * Parses an attribute of one or more term slugs or IDs into an array of valid term IDs.
	 *
	 * @since 1.1.0
	 *
	 * @param string $taxonomy_slug Taxonomy slug.
	 * @param string $attr          Shortcode attribute with term slugs or IDs.
	 * @return array Array of term IDs.
	 */
	private function get_term_ids_from_attribute( $taxonomy_slug, $attr ) {
		$query_arg = 'slug';
		$items     = wp_parse_slug_list( $attr );

		if ( empty( $items ) ) {
			return array();
		}

		$ids = array_filter( $items, 'is_numeric' );
		if ( count( $ids ) === count( $items ) ) {
			$query_arg = 'include';
			$items     = array_map( 'absint', $items );
		}

		$query_args               = array(
			'number'                 => 0,
			'fields'                 => 'ids',
			'update_term_meta_cache' => false,
		);
		$query_args[ $query_arg ] = $items;

		$term_ids = $this->core->get_terms_for_taxonomy( $taxonomy_slug, $query_args );
		if ( ! $term_ids || is_wp_error( $term_ids ) ) {
			return array();
		}

		return $term_ids;
	}

	/**
	 * Queries attachments with specific taxonomies and terms.
	 *
	 * @since 1.1.0
	 * @since 1.2.0 The $tax_relation parameter was added.
	 *
	 * @param array  $all_term_ids Array of `$taxonomy_slug => $term_ids` pairs.
	 * @param int    $limit        Optional. Limit for the query. Default is -1 (no limit).
	 * @param array  $exclude_ids  Optional. Attachment IDs to exclude. Default empty array.
	 * @param string $tax_relation Optional. How to combine multiple tax queries. Either 'OR' or 'AND'. Default 'OR'.
	 * @return array Array of attachment IDs.
	 */
	private function get_shortcode_attachment_ids( $all_term_ids, $limit = -1, $exclude_ids = array(), $tax_relation = 'OR' ) {
		$tax_query = array();
		if ( count( $all_term_ids ) > 1 ) {
			$tax_query['relation'] = $tax_relation;
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

		$args = array(
			'posts_per_page'         => $limit,
			'fields'                 => 'ids',
			'post_status'            => 'inherit',
			'post_type'              => 'attachment',
			'post_mime_type'         => 'image',
			'tax_query'              => $tax_query,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		);

		if ( ! empty( $exclude_ids ) ) {
			$args['post__not_in'] = $exclude_ids;
		}

		return get_posts( $args );
	}
}
