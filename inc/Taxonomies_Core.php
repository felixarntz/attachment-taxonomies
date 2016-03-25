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

	private $taxonomies = array();

	private function __construct() {}

	public function has_taxonomies() {
		return 0 < count( $this->taxonomies );
	}

	public function registered_taxonomy( $taxonomy_slug, $object_type, $args ) {
		$object_type = (array) $object_type;

		if ( ! in_array( 'attachment', $object_type, true ) ) {
			return;
		}

		$this->taxonomies[] = $taxonomy_slug;

		//TODO: do stuff here
	}

	public function enqueue_script() {
		if ( ! $this->has_taxonomies() ) {
			return;
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'attachment-taxonomies', Attachment_Taxonomies::instance()->get_url( 'assets/library' . $min . '.js' ), array( 'media-views' ), Attachment_Taxonomies::VERSION, true );
	}
}
