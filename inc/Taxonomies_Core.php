<?php
/**
 * @package AttachmentTaxonomies
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( class_exists( 'Attachment_Taxonomies_Core' ) ) {
	return;
}

final class Attachment_Taxonomies_Core {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function has_taxonomies() {
		$taxonomies = get_object_taxonomies( 'attachment' );
		return 0 < count( $taxonomies );
	}

	public function enqueue_script() {
		if ( ! $this->has_taxonomies() ) {
			return;
		}

		$taxonomies = array();
		$filter_by = array();
		foreach ( get_object_taxonomies( 'attachment', 'objects' ) as $taxonomy_slug => $taxonomy ) {
			if ( ! $taxonomy->query_var ) {
				continue;
			}

			$js_slug = lcfirst( implode( array_map( 'ucfirst', explode( '_', $taxonomy_slug ) ) ) );

			$taxonomies[] = array(
				'name'		=> $taxonomy->label,
				'slug'		=> $js_slug,
				'slugId'	=> str_replace( '_', '-', $taxonomy_slug ),
				'queryVar'	=> $taxonomy->query_var,
				'terms'		=> array_map( array( $this, 'get_term_array' ), $this->get_terms_for_taxonomy( $taxonomy_slug ) ),
			);

			if ( isset( $taxonomy->labels->filter_by_item ) ) {
				$filter_by[ $js_slug ] = $taxonomy->labels->filter_by_item;
			} elseif ( $taxonomy->hierarchical ) {
				$filter_by[ $js_slug ] = __( 'Filter by Category', 'attachment-taxonomies' );
			} else {
				$filter_by[ $js_slug ] = __( 'Filter by Tag', 'attachment-taxonomies' );
			}
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'attachment-taxonomies', Attachment_Taxonomies::instance()->get_url( 'assets/library' . $min . '.js' ), array( 'media-views' ), Attachment_Taxonomies::VERSION, true );
		wp_localize_script( 'attachment-taxonomies', '_attachment_taxonomies', array(
			'data'			=> $taxonomies,
			'l10n'			=> array(
				'all'			=> __( 'All', 'attachment-taxonomies' ),
				'filterBy'		=> $filter_by,
			),
		) );
	}

	private function get_terms_for_taxonomy( $taxonomy_slug ) {
		$args = array(
			'hide_empty'	=> false,
		);

		if ( version_compare( get_bloginfo( 'version' ), '4.5', '<' ) ) {
			return get_terms( $taxonomy_slug, $args );
		}

		$args['taxonomy'] = $taxonomy_slug;
		return get_terms( $args );
	}

	private function get_term_array( $term ) {
		if ( ! class_exists( 'WP_Term' ) || ! is_a( $term, 'WP_Term' ) ) {
			return get_object_vars( $term );
		}

		return $term->to_array();
	}
}
