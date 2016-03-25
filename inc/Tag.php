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
	public function get_slug() {
		return 'attachment_tag';
	}

	protected function get_labels() {
		// leave empty to use WordPress Core tag labels
		return array();
	}

	protected function get_args() {
		return array(
			'hierarchical'		=> false,
			'query_var'			=> 'attachment_tag',
			'rewrite'			=> $rewrite['post_tag'], //TODO
			'public'			=> true,
			'show_ui'			=> true,
			'show_admin_column'	=> true,
		);
	}
}
