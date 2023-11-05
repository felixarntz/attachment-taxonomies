<?php
/**
 * Attachment_Taxonomies class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <hello@felix-arntz.me>
 *
 * @wordpress-plugin
 * Plugin Name: Attachment Taxonomies
 * Plugin URI: https://wordpress.org/plugins/attachment-taxonomies/
 * Description: This plugin adds categories and tags to the WordPress media library - lightweight and developer-friendly.
 * Version: 1.2.1
 * Requires at least: 6.1
 * Requires PHP: 7.0
 * Author: Felix Arntz
 * Author URI: https://felix-arntz.me
 * License: GNU General Public License v3 (or later)
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: attachment-taxonomies
 * Tags: attachment, media, taxonomy, categories, tags
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
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
	const VERSION = '1.2.1';

	/**
	 * The Singleton instance.
	 *
	 * @since 1.0.0
	 * @var Attachment_Taxonomies|null
	 */
	private static $instance = null;

	/**
	 * Returns the Singleton instance.
	 *
	 * @since 1.0.0
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
	 * The plugin environment instance.
	 *
	 * @since 1.2.0
	 * @var Attachment_Taxonomies_Plugin_Env
	 */
	private $plugin_env;

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
		/*
		 * By default, the classes are within the 'inc' directory.
		 * In case of a must-use plugin, they may be nested within the plugin's root directory though, in case the
		 * plugin main file was moved one level up.
		 */
		if ( file_exists( __DIR__ . '/inc/Attachment_Taxonomies_Plugin_Env.php' ) ) {
			require_once __DIR__ . '/inc/Attachment_Taxonomies_Plugin_Env.php';
		} else {
			require_once __DIR__ . '/attachment-taxonomies/inc/Attachment_Taxonomies_Plugin_Env.php';
		}

		$this->plugin_env = new Attachment_Taxonomies_Plugin_Env( __FILE__ );

		$inc_dir = $this->plugin_env->path( 'inc/' );
		spl_autoload_register(
			static function ( $class_name ) use ( $inc_dir ) {
				if (
					str_starts_with( $class_name, 'Attachment_Taxonomies' ) ||
					str_starts_with( $class_name, 'Attachment_Taxonomy' ) ||
					in_array( $class_name, array( 'Attachment_Existing_Taxonomy', 'Attachment_Category', 'Attachment_Tag' ), true )
				) {
					require_once "{$inc_dir}{$class_name}.php";
				}
			},
			true,
			true
		);

		$bootstrap = function () {
			$this->add_hooks();
			$this->add_default_taxonomies();
		};

		if ( $this->plugin_env->is_mu_plugin() ) {
			add_action( 'muplugins_loaded', $bootstrap, 1 );
		} else {
			add_action( 'plugins_loaded', $bootstrap, 1 );
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
	public function add_taxonomy( Attachment_Taxonomy $taxonomy ) {
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
		return $this->plugin_env->path( $rel_path );
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
		return $this->plugin_env->url( $rel_path );
	}

	/**
	 * Bootstraps the plugin.
	 *
	 * @since 1.0.0
	 * @deprecated 1.2.0
	 */
	public function bootstrap() {
		_deprecated_function( __METHOD__, 'Attachment Taxonomies 1.2.0' );
	}

	/**
	 * Adds relevant WordPress hooks.
	 *
	 * @since 1.2.0
	 */
	private function add_hooks() {
		$hooks = new Attachment_Taxonomies_Hooks( $this->plugin_env );
		$hooks->add_all();
	}

	/**
	 * Adds the plugin's default taxonomies.
	 *
	 * @since 1.2.0
	 */
	private function add_default_taxonomies() {
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
}

Attachment_Taxonomies::instance();
