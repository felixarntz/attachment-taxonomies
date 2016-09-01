<?php
/**
 * Attachment_Tag class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
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
	 * @access protected
	 * @var string
	 */
	protected $slug = 'attachment_tag';

	/**
	 * The taxonomy labels.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $labels = array(); // leave empty to use WordPress Core tag labels

	/**
	 * The taxonomy arguments.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $args = array(
		'public'				=> false,
		'show_ui'				=> true,
		'show_in_menu'			=> true,
		'show_in_nav_menus'		=> false,
		'show_tagcloud'			=> false,
		'show_admin_column'		=> true,
		'hierarchical'			=> false,
		'update_count_callback'	=> '_update_generic_term_count',
		'query_var'				=> 'attachment_tag',
		'rewrite'				=> false,
		'capabilities'			=> array(
			'manage_terms'			=> 'upload_files',
			'edit_terms'			=> 'upload_files',
			'delete_terms'			=> 'upload_files',
			'assign_terms'			=> 'upload_files',
		),
	);
}
