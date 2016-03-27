<?php
/**
 * @package AttachmentTaxonomies
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( class_exists( 'Attachment_Tag' ) ) {
	return;
}

final class Attachment_Tag extends Attachment_Taxonomy {
	protected $slug = 'attachment_tag';
	protected $labels = array(); // leave empty to use WordPress Core tag labels
	protected $args = array(
		'hierarchical'		=> false,
		'query_var'			=> 'attachment_tag',
		'rewrite'			=> true,
		'public'			=> true,
		'show_ui'			=> true,
		'show_admin_column'	=> true,
		'capabilities'		=> array(
			'manage_terms'		=> 'upload_files',
			'edit_terms'		=> 'upload_files',
			'delete_terms'		=> 'upload_files',
			'assign_terms'		=> 'upload_files',
		),
	);
}
