<?php
/**
 * Attachment_Taxonomy_Default_Terms class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <hello@felix-arntz.me>
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Attachment_Taxonomy_Default_Terms' ) ) {
	return;
}

/**
 * Handles default terms for taxonomies.
 *
 * @since 1.1.0
 */
final class Attachment_Taxonomy_Default_Terms {
	/**
	 * The Singleton instance.
	 *
	 * @since 1.1.0
	 * @deprecated 1.2.0
	 * @var Attachment_Taxonomy_Default_Terms|null
	 */
	private static $instance = null;

	/**
	 * Returns the Singleton instance.
	 *
	 * @since 1.1.0
	 * @deprecated 1.2.0
	 *
	 * @return Attachment_Taxonomy_Default_Terms The Singleton class instance.
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
	 * Ensures the default term for each taxonomy that supports it is set on an attachment.
	 *
	 * @since 1.1.0
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function ensure_default_attachment_taxonomy_terms( $attachment_id ) {
		$attachment = get_post( $attachment_id );
		if ( 'attachment' !== $attachment->post_type || 'auto-draft' === $attachment->post_status ) {
			return;
		}

		foreach ( $this->core->get_taxonomies_to_show() as $taxonomy ) {
			if ( 'category' !== $taxonomy->name && ( ! isset( $taxonomy->has_default ) || ! $taxonomy->has_default ) ) {
				continue;
			}

			$default_term = get_option( 'default_' . $taxonomy->name );
			if ( empty( $default_term ) ) {
				continue;
			}

			$terms = wp_get_post_terms(
				$attachment_id,
				$taxonomy->name,
				array(
					'fields'                 => 'ids',
					'update_term_meta_cache' => false,
				)
			);
			if ( is_wp_error( $terms ) ) {
				continue;
			}

			if ( ! empty( $terms ) ) {
				continue;
			}

			wp_set_post_terms( $attachment_id, array( (int) $default_term ), $taxonomy->name );
		}
	}

	/**
	 * Registers settings for each taxonomy that supports a default term.
	 *
	 * @since 1.1.0
	 */
	public function register_settings() {
		foreach ( $this->core->get_taxonomies_to_show() as $taxonomy ) {
			if ( ! isset( $taxonomy->has_default ) || ! $taxonomy->has_default ) {
				continue;
			}

			if ( ! isset( $taxonomy->name ) || 'category' === $taxonomy->name ) {
				continue;
			}

			$label = $this->get_taxonomy_label_for_setting( $taxonomy );

			register_setting(
				'writing',
				'default_' . $taxonomy->name,
				array(
					'type'              => 'integer',
					/* translators: %s: taxonomy label */
					'description'       => sprintf( _x( 'Default %s.', 'REST API description', 'attachment-taxonomies' ), $label ),
					'sanitize_callback' => 'absint',
					'show_in_rest'      => true,
					'default'           => 0,
				)
			);
		}
	}

	/**
	 * Adds settings fields for each taxonomy that supports a default term.
	 *
	 * @since 1.1.0
	 */
	public function add_settings_fields() {
		foreach ( $this->core->get_taxonomies_to_show() as $taxonomy ) {
			if ( ! isset( $taxonomy->has_default ) || ! $taxonomy->has_default ) {
				continue;
			}

			if ( ! isset( $taxonomy->name ) || 'category' === $taxonomy->name ) {
				continue;
			}

			$label = $this->get_taxonomy_label_for_setting( $taxonomy );

			/* translators: %s: taxonomy label */
			$title = sprintf( _x( 'Default %s', 'settings field title', 'attachment-taxonomies' ), $label );

			add_settings_field(
				'default_' . $taxonomy->name,
				$title,
				array( $this, 'render_settings_field' ),
				'writing',
				'default',
				array(
					'label_for' => 'default_' . $taxonomy->name,
					'taxonomy'  => $taxonomy,
				)
			);
		}
	}

	/**
	 * Renders a default term settings field.
	 *
	 * @since 1.1.0
	 *
	 * @param array $args {
	 *     Settings field arguments.
	 *
	 *     @type string      $label_for 'for' attribute for the field label.
	 *     @type WP_Taxonomy $taxonomy  Taxonomy for which to render the field.
	 * }
	 */
	public function render_settings_field( $args ) {
		$taxonomy = $args['taxonomy'];

		wp_dropdown_categories(
			array(
				'id'                => ! empty( $args['label_for'] ) ? $args['label_for'] : 'default_' . $taxonomy->name,
				'name'              => 'default_' . $taxonomy->name,
				'value_field'       => 'term_id',
				'selected'          => get_option( 'default_' . $taxonomy->name ),
				'taxonomy'          => $taxonomy->name,
				'hierarchical'      => $taxonomy->hierarchical,
				'hide_empty'        => false,
				'orderby'           => 'name',
				'order'             => 'ASC',
				'show_option_none'  => _x( 'None', 'default term dropdown', 'attachment-taxonomies' ),
				'option_none_value' => 0,
			)
		);
	}

	/**
	 * Returns the taxonomy label to use for its setting.
	 *
	 * @since 1.1.0
	 *
	 * @param WP_Taxonomy $taxonomy Taxonomy object.
	 * @return string Taxonomy label.
	 */
	private function get_taxonomy_label_for_setting( $taxonomy ) {
		if ( 'attachment_category' === $taxonomy->name ) {
			return __( 'Attachment Category', 'attachment-taxonomies' );
		}

		if ( 'attachment-tag' === $taxonomy->name ) {
			return __( 'Attachment Tag', 'attachment-taxonomies' );
		}

		return $taxonomy->labels->singular_name;
	}
}
