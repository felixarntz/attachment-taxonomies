<?php
/**
 * Tests for Attachment_Taxonomies_Plugin_Env
 *
 * @package AttachmentTaxonomies\Tests
 * @author Felix Arntz <hello@felix-arntz.me>
 */

class Attachment_Taxonomies_Plugin_Env_Tests extends WP_UnitTestCase {

	private $plugin_main_file   = WP_PLUGIN_DIR . '/attachment-taxonomies/attachment-taxonomies.php';
	private $muplugin_main_file = WPMU_PLUGIN_DIR . '/attachment-taxonomies/attachment-taxonomies.php';

	public function test_main_file() {
		$plugin_env = new Attachment_Taxonomies_Plugin_Env( $this->plugin_main_file );
		$this->assertSame( $this->plugin_main_file, $plugin_env->main_file() );
	}

	public function test_is_mu_plugin_with_regular_plugin() {
		$plugin_env = new Attachment_Taxonomies_Plugin_Env( $this->plugin_main_file );
		$this->assertFalse( $plugin_env->is_mu_plugin() );
	}

	public function test_is_mu_plugin_with_mu_plugin() {
		$plugin_env = new Attachment_Taxonomies_Plugin_Env( $this->muplugin_main_file );
		$this->assertTrue( $plugin_env->is_mu_plugin() );
	}

	public function test_basename_with_regular_plugin() {
		$plugin_env = new Attachment_Taxonomies_Plugin_Env( $this->plugin_main_file );
		$this->assertSame( 'attachment-taxonomies/attachment-taxonomies.php', $plugin_env->basename() );
	}

	public function test_basename_with_mu_plugin() {
		$plugin_env = new Attachment_Taxonomies_Plugin_Env( $this->muplugin_main_file );
		$this->assertSame( 'attachment-taxonomies/attachment-taxonomies.php', $plugin_env->basename() );
	}

	public function test_path_with_regular_plugin() {
		$plugin_env = new Attachment_Taxonomies_Plugin_Env( $this->plugin_main_file );
		$this->assertSame( WP_PLUGIN_DIR . '/attachment-taxonomies/inc/some-file.php', $plugin_env->path( 'inc/some-file.php') );
	}

	public function test_path_with_mu_plugin() {
		$plugin_env = new Attachment_Taxonomies_Plugin_Env( $this->muplugin_main_file );
		$this->assertSame( WPMU_PLUGIN_DIR . '/attachment-taxonomies/inc/some-file.php', $plugin_env->path( 'inc/some-file.php' ) );
	}

	public function test_url_with_regular_plugin() {
		$plugin_env = new Attachment_Taxonomies_Plugin_Env( $this->plugin_main_file );
		$this->assertSame( WP_PLUGIN_URL . '/attachment-taxonomies/style.css', $plugin_env->url( 'style.css') );
	}

	public function test_url_with_mu_plugin() {
		$plugin_env = new Attachment_Taxonomies_Plugin_Env( $this->muplugin_main_file );
		$this->assertSame( WPMU_PLUGIN_URL . '/attachment-taxonomies/style.css', $plugin_env->url( 'style.css' ) );
	}
}
