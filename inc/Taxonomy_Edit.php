<?php
/**
 * @package AttachmentTaxonomies
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( class_exists( 'Attachment_Taxonomy_Edit' ) ) {
	return;
}

final class Attachment_Taxonomy_Edit {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function save_ajax_attachment_taxonomies( $attachment_id ) {
		if ( ! doing_action( 'wp_ajax_save-attachment' ) ) {
			return;
		}

		if ( ! isset( $_REQUEST['changes'] ) ) {
			return;
		}

		foreach ( get_object_taxonomies( 'attachment', 'objects' ) as $taxonomy ) {
			if ( ! isset( $_REQUEST['changes'][ 'taxonomy-' . $taxonomy->name . '-terms' ] ) ) {
				continue;
			}

			$terms = $_REQUEST['changes'][ 'taxonomy-' . $taxonomy->name . '-terms' ];
			if ( $taxonomy->hierarchical ) {
				$terms = array_filter( array_map( 'trim', explode( ',', $terms ) ) );
			}

			if ( current_user_can( $taxonomy->cap->assign_terms ) ) {
				wp_set_post_terms( $attachment_id, $terms, $taxonomy->name );
			}
		}
	}

	public function add_taxonomies_to_attachment_js( $response, $attachment, $meta ) {
		$response['taxonomies'] = array();
		foreach ( (array) get_object_taxonomies( 'attachment' ) as $taxonomy_slug ) {
			$response['taxonomies'][ $taxonomy_slug ] = array();
			foreach ( (array) wp_get_object_terms( $attachment->ID, $taxonomy_slug ) as $term ) {
				$term_data = array(
					'id'		=> $term->term_id,
					'slug'		=> $term->slug,
					'name'		=> $term->name,
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

	public function remove_taxonomies_from_attachment_compat( $form_fields, $attachment ) {
		foreach ( get_object_taxonomies( 'attachment' ) as $taxonomy_slug ) {
			if ( isset( $form_fields[ $taxonomy_slug ] ) ) {
				unset( $form_fields[ $taxonomy_slug ] );
			}
		}

		return $form_fields;
	}

	public function adjust_media_templates() {
		remove_action( 'admin_footer', 'wp_print_media_templates' );
		remove_action( 'wp_footer', 'wp_print_media_templates' );
		remove_action( 'customize_controls_print_footer_scripts', 'wp_print_media_templates' );
		add_action( 'admin_footer', array( $this, 'print_media_templates' ) );
		add_action( 'wp_footer', array( $this, 'print_media_templates' ) );
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'print_media_templates' ) );
	}

	public function print_media_templates() {
		ob_start();
		wp_print_media_templates();
		$output = ob_get_clean();

		$taxonomy_output = $this->get_taxonomy_media_template_output();

		$output = preg_replace( '#<script type="text/html" id="tmpl-attachment-details">(.+)</script>#Us', '<script type="text/html" id="tmpl-attachment-details">$1' . $taxonomy_output . '</script>', $output );

		$output = str_replace( '<div class="attachment-compat"></div>', $taxonomy_output . "\n" . '<div class="attachment-compat"></div>', $output );

		echo $output;
	}

	private function get_taxonomy_media_template_output() {
		ob_start();
		foreach ( get_object_taxonomies( 'attachment', 'objects' ) as $taxonomy ) :
		$terms = get_terms( $taxonomy->name, array( 'hide_empty' => false ) );
		?>
		<label class="setting attachment-taxonomy-input" data-setting="taxonomy-<?php echo $taxonomy->name; ?>-terms">
			<input type="hidden" value="{{ Object.keys(data.taxonomies.<?php echo $taxonomy->name; ?>).join(',') }}" />
		</label>
		<label class="setting attachment-taxonomy-select">
			<span class="name"><?php echo $taxonomy->labels->name; ?></span>
			<select multiple="multiple">
				<?php if ( $taxonomy->hierarchical ) : ?>
					<?php foreach ( $terms as $term ) : ?>
						<option value="<?php echo $term->term_id; ?>" {{ data.taxonomies.<?php echo $taxonomy->name; ?>[<?php echo $term->term_id; ?>] ? 'selected' : '' }}><?php echo $term->name; ?></option>
					<?php endforeach; ?>
				<?php else : ?>
					<?php foreach ( $terms as $term ) : ?>
						<option value="<?php echo $term->slug; ?>" {{ data.taxonomies.<?php echo $taxonomy->name; ?>['<?php echo $term->slug; ?>'] ? 'selected' : '' }}><?php echo $term->name; ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		</label>
		<?php
		endforeach;
		return ob_get_clean();
	}
}
