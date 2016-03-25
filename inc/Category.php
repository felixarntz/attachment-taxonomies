<?php
/**
 * @package AttachmentTaxonomies
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( class_exists( 'Attachment_Category' ) ) {
	return;
}

final class Attachment_Category extends Attachment_Taxonomy {
	public function get_slug() {
		return 'attachment_category';
	}

	protected function get_labels() {
		// leave empty to use WordPress Core category labels
		return array();
	}

	protected function get_args() {
		return array(
			'hierarchical'		=> true,
			'query_var'			=> 'attachment_category',
			'rewrite'			=> $rewrite['category'], //TODO
			'public'			=> true,
			'show_ui'			=> true,
			'show_admin_column'	=> true,
		);
	}
}
