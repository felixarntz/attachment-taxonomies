<?php
/**
 * Attachment_Taxonomy_Edit class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <hello@felix-arntz.me>
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Attachment_Taxonomy_Edit' ) ) {
	return;
}

/**
 * Contains methods to set / remove attachment taxonomy terms from attachments through
 * the media modals.
 *
 * @since 1.0.0
 */
final class Attachment_Taxonomy_Edit {
	/**
	 * The Singleton instance.
	 *
	 * @since 1.0.0
	 * @deprecated 1.2.0
	 * @static
	 * @var Attachment_Taxonomy_Edit|null
	 */
	private static $instance = null;

	/**
	 * Returns the Singleton instance.
	 *
	 * @since 1.0.0
	 * @deprecated 1.2.0
	 * @static
	 *
	 * @return Attachment_Taxonomy_Edit The Singleton class instance.
	 *
	 * @throws Exception Thrown when called before plugin initialization.
	 */
	public static function instance() {
		_deprecated_function( __METHOD__, 'Attachment Taxonomies 1.2.0' );
		if ( null === self::$instance ) {
			throw new Exception(
				esc_html__( 'Class instance can only be retrieved once the Attachment Taxonomies plugin has been initialized.', 'attachment-taxonomies' )
			);
		}
		return self::$instance;
	}

	/**
	 * Plugin core instance.
	 *
	 * @since 1.2.0
	 * @var Attachment_Taxonomies_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 Constructor is now public with $core parameter added.
	 *
	 * @param Attachment_Taxonomies_Core $core Plugin core instance.
	 */
	public function __construct( Attachment_Taxonomies_Core $core ) {
		$this->core = $core;

		if ( null === self::$instance ) {
			self::$instance = $this;
		}
	}

	/**
	 * Sets terms for attachment taxonomies through the `save_attachment` AJAX action.
	 *
	 * This is a workaround to handle terms through this AJAX action as it normally does not support
	 * terms.
	 *
	 * This method is hooked into the `add_attachment` and `edit_attachment` actions.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $attachment_id The attachment ID.
	 */
	public function save_ajax_attachment_taxonomies( $attachment_id ) {
		if ( ! doing_action( 'wp_ajax_save-attachment' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_REQUEST['changes'] ) ) {
			return;
		}

		foreach ( $this->core->get_taxonomies( 'objects' ) as $taxonomy ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_REQUEST['changes'][ 'taxonomy-' . $taxonomy->name . '-terms' ] ) ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$terms = $_REQUEST['changes'][ 'taxonomy-' . $taxonomy->name . '-terms' ];
			if ( $taxonomy->hierarchical ) {
				$terms = array_filter( array_map( 'trim', explode( ',', $terms ) ) );
			}

			if ( current_user_can( $taxonomy->cap->assign_terms ) ) {
				wp_set_post_terms( $attachment_id, $terms, $taxonomy->name );
			}
		}
	}

	/**
	 * Adds taxonomies and terms to a specific attachment's JavaScript output.
	 *
	 * This method is hooked into the `wp_prepare_attachment_for_js` filter.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 The $meta parameter has been removed.
	 *
	 * @param array   $response   The original attachment data.
	 * @param WP_Post $attachment The attachment post.
	 * @return array The modified attachment data.
	 */
	public function add_taxonomies_to_attachment_js( $response, $attachment ) {
		$response['taxonomies'] = array();
		foreach ( $this->core->get_taxonomies( 'names' ) as $taxonomy_slug ) {
			$response['taxonomies'][ $taxonomy_slug ] = array();
			foreach ( (array) wp_get_object_terms( $attachment->ID, $taxonomy_slug ) as $term ) {
				$term_data = array(
					'id'   => $term->term_id,
					'slug' => $term->slug,
					'name' => $term->name,
				);
				if ( is_taxonomy_hierarchical( $taxonomy_slug ) ) {
					$response['taxonomies'][ $taxonomy_slug ][ $term->term_id ] = $term_data;
				} else {
					$response['taxonomies'][ $taxonomy_slug ][ $term->slug ] = $term_data;
				}
			}
		}
		return $response;
	}

	/**
	 * Removes all taxonomies from the attachment compat fields.
	 *
	 * This is done since the plugin actually handles taxonomies through its dedicated
	 * dropdowns in Backbone. No need for ugly compat fields here.
	 *
	 * This method is hooked into the `attachment_fields_to_edit` filter.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 The $attachment parameter was removed.
	 *
	 * @param array $form_fields The original form fields array.
	 * @return array The modified form fields array.
	 */
	public function remove_taxonomies_from_attachment_compat( $form_fields ) {
		foreach ( $this->core->get_taxonomies( 'names' ) as $taxonomy_slug ) {
			if ( isset( $form_fields[ $taxonomy_slug ] ) ) {
				unset( $form_fields[ $taxonomy_slug ] );
			}
		}

		return $form_fields;
	}

	/**
	 * Replaces the media templates output action of WordPress Core to be able to modify this output.
	 *
	 * This method is hooked into the `wp_enqueue_media` action.
	 *
	 * @since 1.0.0
	 */
	public function adjust_media_templates() {
		if ( ! $this->core->has_taxonomies() ) {
			return;
		}

		remove_action( 'admin_footer', 'wp_print_media_templates' );
		remove_action( 'wp_footer', 'wp_print_media_templates' );
		remove_action( 'customize_controls_print_footer_scripts', 'wp_print_media_templates' );
		add_action( 'admin_footer', array( $this, 'print_media_templates' ) );
		add_action( 'wp_footer', array( $this, 'print_media_templates' ) );
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'print_media_templates' ) );
	}

	/**
	 * Modifies the media templates for Backbone to include attachment taxonomy term dropdowns.
	 *
	 * This approach is kind of hacky, but there is no other way to adjust this output.
	 *
	 * @since 1.0.0
	 */
	public function print_media_templates() {
		ob_start();
		wp_print_media_templates();
		$output = ob_get_clean();

		$taxonomy_output = $this->get_taxonomy_media_template_output();

		$output = preg_replace( '#<script type="text/html" id="tmpl-attachment-details">(.+)</script>#Us', '<script type="text/html" id="tmpl-attachment-details">$1' . $taxonomy_output . '</script>', $output );

		$output = str_replace( '<div class="attachment-compat"></div>', $taxonomy_output . "\n" . '<div class="attachment-compat"></div>', $output );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $output;
	}

	/**
	 * Returns the media template output for attachment taxonomy term dropdowns.
	 *
	 * @since 1.0.0
	 *
	 * @return string The HTML output for Backbone.
	 */
	private function get_taxonomy_media_template_output() {
		ob_start();
		foreach ( $this->core->get_taxonomies( 'objects' ) as $taxonomy ) {
			$terms        = $this->core->get_terms_for_taxonomy( $taxonomy->name );
			$user_has_cap = current_user_can( $taxonomy->cap->assign_terms );
			?>
			<label class="setting attachment-taxonomy-input" data-setting="taxonomy-<?php echo sanitize_html_class( $taxonomy->name ); ?>-terms">
				<input type="hidden" value="{{ data.taxonomies ? Object.keys(data.taxonomies.<?php echo esc_attr( $taxonomy->name ); ?>).join(',') : '' }}" />
			</label>
			<label class="setting attachment-taxonomy-select">
				<span class="name"><?php echo esc_html( $taxonomy->labels->name ); ?></span>
				<select multiple="multiple"<?php echo $user_has_cap ? '' : ' readonly'; ?>>
					<?php if ( $taxonomy->hierarchical ) : ?>
						<?php foreach ( $terms as $term ) : ?>
							<option value="<?php echo esc_attr( $term->term_id ); ?>"<?php echo $user_has_cap ? '' : ' disabled'; ?> {{ ( data.taxonomies && data.taxonomies.<?php echo esc_attr( $taxonomy->name ); ?>[<?php echo esc_attr( $term->term_id ); ?>] ) ? 'selected' : '' }}><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					<?php else : ?>
						<?php foreach ( $terms as $term ) : ?>
							<option value="<?php echo esc_attr( $term->slug ); ?>"<?php echo $user_has_cap ? '' : ' disabled'; ?> {{ ( data.taxonomies && data.taxonomies.<?php echo esc_attr( $taxonomy->name ); ?>['<?php echo esc_attr( $term->slug ); ?>'] ) ? 'selected' : '' }}><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
			</label>
			<?php
		}
		return ob_get_clean();
	}
}
