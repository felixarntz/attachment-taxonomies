<?php
/**
 * Attachment_Taxonomies_Plugin_Env class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <hello@felix-arntz.me>
 * @since 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Attachment_Taxonomies_Plugin_Env' ) ) {
	return;
}

/**
 * Read-only class containing utilities for the plugin environment.
 *
 * @since 1.2.0
 */
final class Attachment_Taxonomies_Plugin_Env {

	/**
	 * Absolute path of the plugin main file.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	private $main_file;

	/**
	 * Whether the plugin is a must-use plugin.
	 *
	 * @since 1.2.0
	 * @var bool
	 */
	private $is_mu_plugin = false;

	/**
	 * The relative path to the plugin files, relative from this file's directory.
	 *
	 * This is empty for a regular plugin, but contains the directory name for a must-use plugin.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	private $base_path_relative = '';

	/**
	 * Constructor.
	 *
	 * @since 1.2.0
	 *
	 * @param string $main_file Absolute path to the plugin main file.
	 */
	public function __construct( string $main_file ) {
		$this->main_file = $main_file;

		$mu_plugin_dir = wp_normalize_path( WPMU_PLUGIN_DIR );
		if ( preg_match( '#^' . preg_quote( $mu_plugin_dir, '#' ) . '/#', wp_normalize_path( $main_file ) ) ) {
			$this->is_mu_plugin = true;

			// Is the plugin main file one level above the actual plugin's directory?
			if ( file_exists( dirname( $this->main_file ) . '/attachment-taxonomies/inc/Attachment_Taxonomies_Plugin_Env.php' ) ) {
				$this->base_path_relative = 'attachment-taxonomies/';
			}
		}
	}

	/**
	 * Returns the absolute path to the plugin main file.
	 *
	 * @since 1.2.0
	 *
	 * @return string Absolute path to the plugin main file.
	 */
	public function main_file(): string {
		return $this->main_file;
	}

	/**
	 * Returns whether the plugin is used as a must-use plugin.
	 *
	 * @since 1.2.0
	 *
	 * @return bool True if used as a must-use plugin, false if used as a regular plugin.
	 */
	public function is_mu_plugin(): bool {
		return $this->is_mu_plugin;
	}

	/**
	 * Returns the plugin basename.
	 *
	 * @since 1.2.0
	 *
	 * @return string Plugin basename.
	 */
	public function basename(): string {
		return plugin_basename( $this->main_file );
	}

	/**
	 * Returns the absolute path for a relative path to the plugin directory.
	 *
	 * @since 1.2.0
	 *
	 * @param string $relative_path Optional. Relative path. Default '/'.
	 * @return string Absolute path.
	 */
	public function path( string $relative_path = '/' ): string {
		return plugin_dir_path( $this->main_file ) . $this->base_path_relative . ltrim( $relative_path, '/' );
	}

	/**
	 * Returns the full URL for a path relative to the plugin directory.
	 *
	 * @since 1.2.0
	 *
	 * @param string $relative_path Optional. Relative path. Default '/'.
	 * @return string Full URL.
	 */
	public function url( string $relative_path = '/' ): string {
		return plugin_dir_url( $this->main_file ) . $this->base_path_relative . ltrim( $relative_path, '/' );
	}
}
