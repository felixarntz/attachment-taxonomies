<?php
/**
 * Attachment_Taxonomies class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <hello@felix-arntz.me>
 * @since 1.0.0
 *
 * @wordpress-plugin
 * Plugin Name: Attachment Taxonomies
 * Plugin URI: https://wordpress.org/plugins/attachment-taxonomies/
 * Description: This plugin adds categories and tags to the WordPress media library - lightweight and developer-friendly.
 * Version: 1.1.1
 * Requires at least: 6.0
 * Requires PHP: 7.0
 * Author: Felix Arntz
 * Author URI: https://felix-arntz.me
 * License: GNU General Public License v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: attachment-taxonomies
 * Tags: attachment, media, taxonomy, categories, tags
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
	 * The plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '1.1.1';

	/**
	 * The Singleton instance.
	 *
	 * @since 1.0.0
	 * @static
	 * @var Attachment_Taxonomies|null
	 */
	private static $instance = null;

	/**
	 * Returns the Singleton instance.
	 *
	 * @since 1.0.0
	 * @static
	 *
	 * @return Attachment_Taxonomies The Singleton class instance.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Whether the plugin is a must-use plugin.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private $is_mu_plugin = false;

	/**
	 * The relative path to the plugin files, relative from this file's directory.
	 *
	 * This is empty for a regular plugin, but contains the directory name for a must-use plugin.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $base_path_relative = '';

	/**
	 * The custom taxonomies which are added through the plugin's API.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $taxonomies = array();

	/**
	 * Constructor.
	 *
	 * Determines whether the plugin is used as a must-use plugin or not.
	 * Then it loads the plugin files and hooks in the bootstrapping action.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$file          = wp_normalize_path( __FILE__ );
		$mu_plugin_dir = wp_normalize_path( WPMU_PLUGIN_DIR );
		if ( preg_match( '#^' . preg_quote( $mu_plugin_dir, '#' ) . '/#', $file ) ) {
			$this->is_mu_plugin = true;
			if ( file_exists( $mu_plugin_dir . '/attachment-taxonomies.php' ) ) {
				$this->base_path_relative = 'attachment-taxonomies/';
			}
			add_action( 'muplugins_loaded', array( $this, 'bootstrap' ), 1 );
		} else {
			add_action( 'plugins_loaded', array( $this, 'bootstrap' ), 1 );
		}

		require_once $this->get_path( 'inc/Taxonomies_Core.php' );
		require_once $this->get_path( 'inc/Taxonomy_Edit.php' );
		require_once $this->get_path( 'inc/Taxonomy_Capabilities.php' );
		require_once $this->get_path( 'inc/Taxonomy_Shortcode.php' );
		require_once $this->get_path( 'inc/Taxonomy_Default_Terms.php' );
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
		$core = Attachment_Taxonomies_Core::instance();
		add_action( 'restrict_manage_posts', array( $core, 'render_taxonomy_filters' ), 10, 1 );
		add_action( 'wp_enqueue_media', array( $core, 'enqueue_script' ) );
		add_action( 'wp_enqueue_media', array( $core, 'print_styles' ) );

		$edit = Attachment_Taxonomy_Edit::instance();
		add_action( 'edit_attachment', array( $edit, 'save_ajax_attachment_taxonomies' ), 10, 1 );
		add_action( 'add_attachment', array( $edit, 'save_ajax_attachment_taxonomies' ), 10, 1 );
		add_filter( 'wp_prepare_attachment_for_js', array( $edit, 'add_taxonomies_to_attachment_js' ), 10, 2 );
		add_filter( 'attachment_fields_to_edit', array( $edit, 'remove_taxonomies_from_attachment_compat' ), 10, 1 );
		add_action( 'wp_enqueue_media', array( $edit, 'adjust_media_templates' ) );

		$capabilities = Attachment_Taxonomy_Capabilities::instance();
		add_filter( 'map_meta_cap', array( $capabilities, 'map_meta_cap' ), 10, 3 );

		$shortcode = Attachment_Taxonomy_Shortcode::instance();
		add_filter( 'shortcode_atts_gallery', array( $shortcode, 'support_gallery_taxonomy_attributes' ), 10, 3 );

		$default_terms = Attachment_Taxonomy_Default_Terms::instance();
		add_action( 'edit_attachment', array( $default_terms, 'ensure_default_attachment_taxonomy_terms' ), 100, 1 );
		add_action( 'add_attachment', array( $default_terms, 'ensure_default_attachment_taxonomy_terms' ), 100, 1 );
		add_action( 'rest_api_init', array( $default_terms, 'register_settings' ), 10, 0 );
		add_action( 'admin_init', array( $default_terms, 'register_settings' ), 10, 0 );
		add_action( 'admin_init', array( $default_terms, 'add_settings_fields' ), 10, 0 );

		/**
		 * Filters the taxonomy class names that will be instantiated by default.
		 *
		 * @since 1.1.0
		 *
		 * @param array $taxonomy_class_names Array of taxonomy class names.
		 */
		$taxonomy_class_names = apply_filters( 'attachment_taxonomy_class_names', array( 'Attachment_Category', 'Attachment_Tag' ) );

		$taxonomy_class_names = array_filter( array_unique( $taxonomy_class_names ), 'class_exists' );

		foreach ( $taxonomy_class_names as $class_name ) {
			$this->add_taxonomy( new $class_name() );
		}
	}

	/**
	 * Adds an attachment taxonomy.
	 *
	 * The first argument must be an object of a class that is derived from the class
	 * `Attachment_Taxonomy` or from `Attachment_Existing_Taxonomy` (if the second parameter
	 * is set to true).
	 *
	 * @since 1.0.0
	 * @since 1.1.0 The second parameter has been deprecated.
	 * @since 1.2.0 The second parameter has been removed.
	 *
	 * @param Attachment_Taxonomy $taxonomy The taxonomy object.
	 * @return bool True if successful, otherwise false.
	 */
	public function add_taxonomy( $taxonomy ) {
		if ( ! $taxonomy instanceof Attachment_Taxonomy ) {
			return false;
		}

		$taxonomy_slug = $taxonomy->get_slug();

		if ( isset( $this->taxonomies[ $taxonomy_slug ] ) ) {
			return false;
		}

		$this->taxonomies[ $taxonomy_slug ] = $taxonomy;
		if ( doing_action( 'init' ) || did_action( 'init' ) ) {
			$this->taxonomies[ $taxonomy_slug ]->register();
		} else {
			add_action( 'init', array( $this->taxonomies[ $taxonomy_slug ], 'register' ) );
		}

		return true;
	}

	/**
	 * Returns the taxonomy object for a specific taxonomy.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 The second parameter has been deprecated.
	 * @since 1.2.0 The second parameter has been removed.
	 *
	 * @param string $taxonomy_slug The taxonomy slug.
	 * @return Attachment_Taxonomy|null The object (class derived from Attachment_Taxonomy) or null if it does not exist.
	 */
	public function get_taxonomy( $taxonomy_slug ) {
		if ( ! isset( $this->taxonomies[ $taxonomy_slug ] ) ) {
			return null;
		}

		return $this->taxonomies[ $taxonomy_slug ];
	}

	/**
	 * Removes a specific taxonomy.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 The second parameter has been deprecated.
	 * @since 1.2.0 The second parameter has been removed.
	 *
	 * @param string $taxonomy_slug The taxonomy slug.
	 * @return bool True if successful, otherwise false.
	 */
	public function remove_taxonomy( $taxonomy_slug ) {
		if ( ! isset( $this->taxonomies[ $taxonomy_slug ] ) ) {
			return false;
		}

		if ( doing_action( 'init' ) || did_action( 'init' ) ) {
			$this->taxonomies[ $taxonomy_slug ]->unregister();
		} else {
			remove_action( 'init', array( $this->taxonomies[ $taxonomy_slug ], 'register' ) );
		}
		unset( $this->taxonomies[ $taxonomy_slug ] );

		return true;
	}

	/**
	 * Returns the full path to a path relative from the plugin's directory.
	 *
	 * This also works when the plugin is used as a must-use plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string $rel_path Path relative from the plugin's directory.
	 * @return string The full path.
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
	 *
	 * @param string $rel_path Path relative from the plugin's directory.
	 * @return string The full URL.
	 */
	public function get_url( $rel_path ) {
		return plugin_dir_url( __FILE__ ) . $this->base_path_relative . ltrim( $rel_path, '/' );
	}
}

Attachment_Taxonomies::instance();
