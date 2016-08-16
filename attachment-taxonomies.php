<?php
/*
Plugin Name: Attachment Taxonomies
Plugin URI:  http://wordpress.org/plugins/attachment-taxonomies/
Description: This plugin adds categories and tags to the WordPress media library - lightweight and developer-friendly.
Version:     1.0.1
Author:      Felix Arntz
Author URI:  https://leaves-and-love.net
License:     GNU General Public License v3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: attachment-taxonomies
Tags:        attachment, media, taxonomy, categories, tags
*/
/**
 * Attachment_Taxonomies class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Initializes the plugin and contains API methods.
 *
 * @since 1.0.0
 */
final class Attachment_Taxonomies {
	/**
	 * Contains the plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '1.0.1';

	/**
	 * Stores the Singleton instance.
	 *
	 * @since 1.0.0
	 * @var Attachment_Taxonomies|null
	 */
	private static $instance = null;

	/**
	 * Returns the Singleton instance.
	 *
	 * @since 1.0.0
	 * @return Attachment_Taxonomies the class instance
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Stores whether the plugin is a must-use plugin.
	 *
	 * @since 1.0.0
	 * @var boolean true if the plugin is a must-use plugin, otherwise false
	 */
	private $is_mu_plugin = false;

	/**
	 * Stores the relative path to the plugin files, relative from this file's directory.
	 *
	 * This is empty for a regular plugin, but contains the directory name for a must-use plugin.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $base_path_relative = '';

	/**
	 * Stores custom taxonomies which are added through the plugin's API.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $taxonomies = array();

	/**
	 * Stores existing taxonomies which are added through the plugin's API.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $existing_taxonomies = array();

	/**
	 * Constructor - determines whether the plugin is used as a must-use plugin or not.
	 * Then it loads the plugin files and hooks in the bootstrapping action.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$file = wp_normalize_path( __FILE__ );
		$mu_plugin_dir = wp_normalize_path( WPMU_PLUGIN_DIR );
		if ( preg_match( '#^' . preg_quote( $mu_plugin_dir, '#' ) . '/#', $file ) ) {
			$this->is_mu_plugin = true;
			$this->base_path_relative = 'attachment-taxonomies/';
			add_action( 'muplugins_loaded', array( $this, 'bootstrap' ), 1 );
		} else {
			add_action( 'plugins_loaded', array( $this, 'bootstrap' ), 1 );
		}

		require_once $this->get_path( 'inc/Taxonomies_Core.php' );
		require_once $this->get_path( 'inc/Taxonomy_Edit.php' );
		require_once $this->get_path( 'inc/Taxonomy.php' );
		require_once $this->get_path( 'inc/Existing_Taxonomy.php' );
		require_once $this->get_path( 'inc/Category.php' );
		require_once $this->get_path( 'inc/Tag.php' );
	}

	/**
	 * Bootstraps the plugin.
	 *
	 * The textdomain is loaded and actions and filters are hooked in.
	 * Furthermore the two custom attachment taxonomies the plugin defines by default are added.
	 *
	 * @since 1.0.0
	 */
	public function bootstrap() {
		if ( $this->is_mu_plugin ) {
			load_muplugin_textdomain( 'attachment-taxonomies' );
		} else {
			load_plugin_textdomain( 'attachment-taxonomies' );
		}

		$core = Attachment_Taxonomies_Core::instance();
		add_action( 'restrict_manage_posts', array( $core, 'render_taxonomy_filters' ), 10, 1 );
		add_action( 'wp_enqueue_media', array( $core, 'enqueue_script' ) );
		add_action( 'wp_enqueue_media', array( $core, 'print_styles' ) );

		$edit = Attachment_Taxonomy_Edit::instance();
		add_action( 'edit_attachment', array( $edit, 'save_ajax_attachment_taxonomies' ), 10, 1 );
		add_action( 'add_attachment', array( $edit, 'save_ajax_attachment_taxonomies' ), 10, 1 );
		add_filter( 'wp_prepare_attachment_for_js', array( $edit, 'add_taxonomies_to_attachment_js' ), 10, 3 );
		add_filter( 'attachment_fields_to_edit', array( $edit, 'remove_taxonomies_from_attachment_compat' ), 10, 2 );
		add_action( 'wp_enqueue_media', array( $edit, 'adjust_media_templates' ) );

		$this->add_taxonomy( new Attachment_Category() );
		$this->add_taxonomy( new Attachment_Tag() );
	}

	/**
	 * Adds an attachment taxonomy.
	 *
	 * The first argument must be an object of a class that is derived from the class
	 * `Attachment_Taxonomy` or from `Attachment_Existing_Taxonomy` (if the second parameter
	 * is set to true).
	 *
	 * @since 1.0.0
	 * @param Attachment_Taxonomy $taxonomy the taxonomy object
	 * @param boolean             $existing whether the taxonomy already exists (for another post type)
	 * @return boolean true if successful, otherwise false
	 */
	public function add_taxonomy( $taxonomy, $existing = false ) {
		if ( $existing && ! is_a( $taxonomy, 'Attachment_Existing_Taxonomy' ) ) {
			return false;
		} elseif ( ! $existing && ! is_a( $taxonomy, 'Attachment_Taxonomy' ) ) {
			return false;
		}

		$taxonomy_slug = $taxonomy->get_slug();

		$holder = 'taxonomies';
		if ( $existing ) {
			$holder = 'existing_taxonomies';
		}

		if ( isset( $this->{$holder}[ $taxonomy_slug ] ) ) {
			return false;
		}

		$this->{$holder}[ $taxonomy_slug ] = $taxonomy;
		if ( doing_action( 'init' ) || did_action( 'init' ) ) {
			$this->{$holder}[ $taxonomy_slug ]->register();
		} else {
			add_action( 'init', array( $this->{$holder}[ $taxonomy_slug ], 'register' ) );
		}

		return true;
	}

	/**
	 * Returns the taxonomy object for a specific taxonomy.
	 *
	 * @since 1.0.0
	 * @param string  $taxonomy_slug the taxonomy slug
	 * @param boolean $existing whether the taxonomy already exists (for another post type)
	 * @return Attachment_Taxonomy|null the object (class derived from Attachment_Taxonomy) or null if it does not exist
	 */
	public function get_taxonomy( $taxonomy_slug, $existing = false ) {
		$holder = 'taxonomies';
		if ( $existing ) {
			$holder = 'existing_taxonomies';
		}

		if ( ! isset( $this->{$holder}[ $taxonomy_slug ] ) ) {
			return null;
		}
		return $this->{$holder}[ $taxonomy_slug ];
	}

	/**
	 * Removes a specific taxonomy.
	 *
	 * @since 1.0.0
	 * @param string  $taxonomy_slug the taxonomy slug
	 * @param boolean $existing whether the taxonomy already exists (for another post type)
	 * @return boolean true if successful, otherwise false
	 */
	public function remove_taxonomy( $taxonomy_slug, $existing = false ) {
		$holder = 'taxonomies';
		if ( $existing ) {
			$holder = 'existing_taxonomies';
		}

		if ( ! isset( $this->{$holder}[ $taxonomy_slug ] ) ) {
			return false;
		}

		if ( doing_action( 'init' ) || did_action( 'init' ) ) {
			$this->{$holder}[ $taxonomy_slug ]->unregister();
		} else {
			remove_action( 'init', array( $this->{$holder}[ $taxonomy_slug ], 'register' ) );
		}
		unset( $this->{$holder}[ $taxonomy_slug ] );

		return true;
	}

	/**
	 * Returns the full path to a path relative from the plugin's directory.
	 *
	 * This also works when the plugin is used as a must-use plugin.
	 *
	 * @since 1.0.0
	 * @param string $rel_path path relative from the plugin's directory
	 * @return string the full path
	 */
	public function get_path( $rel_path ) {
		return plugin_dir_path( __FILE__ ) . $this->base_path_relative . ltrim( $rel_path, '/' );
	}

	/**
	 * Returns the full URL to a path relative from the plugin's directory.
	 *
	 * This also works when the plugin is used as a must-use plugin.
	 *
	 * @since 1.0.0
	 * @param string $rel_path path relative from the plugin's directory
	 * @return string the full URL
	 */
	public function get_url( $rel_path ) {
		return plugin_dir_url( __FILE__ ) . $this->base_path_relative . ltrim( $rel_path, '/' );
	}
}

Attachment_Taxonomies::instance();
