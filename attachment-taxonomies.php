<?php
/*
Plugin Name: Attachment Taxonomies
Plugin URI: http://wordpress.org/plugins/attachment-taxonomies/
Description: This plugin adds categories and tags to the WordPress media library - lightweight and developer-friendly.
Version: 1.0.0
Author: Felix Arntz
Author URI: http://leaves-and-love.net
License: GNU General Public License v3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: attachment-taxonomies
Tags: wordpress, plugin, attachment, media, taxonomy
*/
/**
 * @package AttachmentTaxonomies
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

final class Attachment_Taxonomies {
	const VERSION = '1.0.0';
	const DIRNAME = 'attachment-taxonomies';
	const TEXTDOMAIN = 'attachment-taxonomies';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private $is_mu_plugin = false;
	private $base_path_relative = '';
	private $taxonomies = array();

	private function __construct() {
		$file = wp_normalize_path( __FILE__ );
		$mu_plugin_dir = wp_normalize_path( WPMU_PLUGIN_DIR );
		if ( preg_match( '#^' . preg_quote( $mu_plugin_dir, '#' ) . '/#' ) ) {
			$this->is_mu_plugin = true;
			$this->base_path_relative = self::DIRNAME . '/';
			add_action( 'muplugins_loaded', array( $this, 'bootstrap' ), 1 );
		} else {
			add_action( 'plugins_loaded', array( $this, 'bootstrap' ), 1 );
		}

		require_once $this->get_path( 'inc/Taxonomies_Core.php' );
		require_once $this->get_path( 'inc/Taxonomy.php' );
		require_once $this->get_path( 'inc/Category.php' );
		require_once $this->get_path( 'inc/Tag.php' );
	}

	public function bootstrap() {
		if ( $this->is_mu_plugin ) {
			load_muplugin_textdomain( self::TEXTDOMAIN );
		} else {
			load_plugin_textdomain( self::TEXTDOMAIN );
		}

		$core = Attachment_Taxonomies_Core::instance();
		add_action( 'registered_taxonomy', array( $core, 'registered_taxonomy' ), 10, 3 );
		add_action( 'wp_enqueue_media', array( $core, 'enqueue_script' ) );

		$this->add_taxonomy( new Attachment_Category() );
		$this->add_taxonomy( new Attachment_Tag() );
	}

	public function add_taxonomy( $taxonomy ) {
		if ( ! is_a( $taxonomy, 'Attachment_Taxonomy' ) ) {
			return false;
		}

		$taxonomy_slug = $taxonomy->get_slug();

		if ( isset( $this->taxonomies[ $taxonomy_slug ] ) ) {
			return false;
		}

		$this->taxonomies[ $taxonomy_slug ] = $taxonomy;
		add_action( 'init', array( $this->taxonomies[ $taxonomy_slug ], 'register' ) );

		return true;
	}

	public function get_taxonomy( $taxonomy_slug ) {
		if ( ! isset( $this->taxonomies[ $taxonomy_slug ] ) ) {
			return null;
		}
		return $this->taxonomies[ $taxonomy_slug ];
	}

	public function remove_taxonomy( $taxonomy_slug ) {
		if ( ! isset( $this->taxonomies[ $taxonomy_slug ] ) ) {
			return false;
		}

		remove_action( 'init', array( $this->taxonomies[ $taxonomy_slug ], 'register' ) );
		unset( $this->taxonomies[ $taxonomy_slug ] );

		return true;
	}

	public function get_path( $rel_path ) {
		return plugin_dir_path( __FILE__ ) . $this->base_path_relative . ltrim( $rel_path, '/' );
	}

	public function get_url( $rel_url ) {
		return plugin_dir_url( __FILE__ ) . $this->base_path_relative . ltrim( $rel_path, '/' );
	}
}

Attachment_Taxonomies::instance();
