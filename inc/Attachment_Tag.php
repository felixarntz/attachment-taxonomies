<?php
/**
 * Attachment_Tag class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <hello@felix-arntz.me>
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Attachment_Tag' ) ) {
	return;
}

/**
 * Represents the attachment_tag taxonomy.
 *
 * @since 1.0.0
 */
final class Attachment_Tag extends Attachment_Taxonomy {
	/**
	 * The taxonomy slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $slug = 'attachment_tag';

	/**
	 * The taxonomy labels.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $labels = array(); // Empty to use WordPress Core tag labels.

	/**
	 * The taxonomy arguments.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $args = array(
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => false,
		'show_tagcloud'         => false,
		'show_admin_column'     => true,
		'hierarchical'          => false,
		'has_default'           => false,
		'update_count_callback' => '_update_generic_term_count',
		'query_var'             => 'attachment_tag',
		'rewrite'               => false,
		'capabilities'          => array(
			'manage_terms' => 'manage_attachment_tags',
			'edit_terms'   => 'edit_attachment_tags',
			'delete_terms' => 'delete_attachment_tags',
			'assign_terms' => 'assign_attachment_tags',
		),
		'show_in_rest'          => true,
		'rest_base'             => 'attachment_tags',
	);
}
