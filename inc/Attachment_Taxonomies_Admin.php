<?php
/**
 * Attachment_Taxonomies_Admin class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <hello@felix-arntz.me>
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Attachment_Taxonomies_Admin' ) ) {
	return;
}

/**
 * Contains methods to set / remove attachment taxonomy terms from attachments through
 * the media modals.
 *
 * @since 1.0.0
 * @since 1.2.0 Renamed from "Attachment_Taxonomy_Edit".
 */
final class Attachment_Taxonomies_Admin {
	/**
	 * The Singleton instance.
	 *
	 * @since 1.0.0
	 * @deprecated 1.2.0
	 * @var Attachment_Taxonomies_Admin|null
	 */
	private static $instance = null;

	/**
	 * Returns the Singleton instance.
	 *
	 * @since 1.0.0
	 * @deprecated 1.2.0
	 *
	 * @return Attachment_Taxonomies_Admin The Singleton class instance.
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
	 * Plugin environment instance.
	 *
	 * @since 1.2.0
	 * @var Attachment_Taxonomies_Plugin_Env
	 */
	private $plugin_env;

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
	 * @param Attachment_Taxonomies_Plugin_Env $plugin_env Plugin environment instance.
	 * @param Attachment_Taxonomies_Core       $core       Plugin core instance.
	 */
	public function __construct( Attachment_Taxonomies_Plugin_Env $plugin_env, Attachment_Taxonomies_Core $core ) {
		$this->plugin_env = $plugin_env;
		$this->core       = $core;

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

		foreach ( $this->core->get_all_taxonomies() as $taxonomy ) {
			if ( ! current_user_can( $taxonomy->cap->assign_terms ) ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_REQUEST['changes'][ 'taxonomy-' . $taxonomy->name . '-terms' ] ) ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$terms = $_REQUEST['changes'][ 'taxonomy-' . $taxonomy->name . '-terms' ];
			if ( is_taxonomy_hierarchical( $taxonomy->name ) ) {
				$terms = array_filter( array_map( 'trim', explode( ',', $terms ) ) );
			}

			wp_set_post_terms( $attachment_id, $terms, $taxonomy->name );
		}
	}

	/**
	 * Renders attachment taxonomy filter dropdowns.
	 *
	 * This is only used for the PHP-based part of the media library (i.e. the regular list).
	 * The JavaScript implementation using Backbone is used elsewhere.
	 *
	 * This method is hooked into the `restrict_manage_posts` action.
	 *
	 * @since 1.2.0 Originally part of {@see Attachment_Taxonomies_Core}.
	 *
	 * @param string $post_type The current post type.
	 */
	public function render_taxonomy_filters( $post_type ) {
		if ( 'attachment' !== $post_type && 'upload' !== get_current_screen()->base ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['attachment-filter'] ) && 'trash' === $_REQUEST['attachment-filter'] ) {
			return;
		}

		foreach ( $this->core->get_taxonomies_to_show() as $taxonomy ) {
			if ( ! $taxonomy->query_var ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$value = isset( $_REQUEST[ $taxonomy->query_var ] ) ? $_REQUEST[ $taxonomy->query_var ] : '';
			?>
			<label for="attachment-<?php echo sanitize_html_class( $taxonomy->name ); ?>-filter" class="screen-reader-text"><?php echo esc_html( $this->get_filter_by_label( $taxonomy ) ); ?></label>
			<select class="attachment-filters" name="<?php echo esc_attr( $taxonomy->query_var ); ?>" id="attachment-<?php echo sanitize_html_class( $taxonomy->name ); ?>-filter">
				<option value="" <?php selected( '', $value ); ?>><?php echo esc_html( $taxonomy->labels->all_items ); ?></option>
				<?php foreach ( $this->core->get_terms_for_taxonomy( $taxonomy->name ) as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $term->slug, $value ); ?>><?php echo esc_html( $term->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
		}
	}

	/**
	 * Enqueues the plugin's JavaScript file.
	 *
	 * The script handles attachment taxonomies through Backbone, allowing filtering by and managing
	 * these taxonomies through the media library and media modal.
	 *
	 * This method is hooked into the `wp_enqueue_media` action.
	 *
	 * @since 1.2.0 Originally part of {@see Attachment_Taxonomies_Core}.
	 */
	public function enqueue_script() {
		$taxonomies = $this->core->get_taxonomies_to_show();
		if ( ! $taxonomies ) {
			return;
		}

		/*
		 * Get script metadata.
		 * Add legacy dependencies manually since they are not supported by `@wordpress/scripts` mapping.
		 */
		$script_metadata                   = require $this->plugin_env->path( 'build/index.asset.php' );
		$script_metadata['dependencies'][] = 'jquery';
		$script_metadata['dependencies'][] = 'media-views';

		wp_enqueue_script(
			'attachment-taxonomies',
			$this->plugin_env->url( 'build/index.js' ),
			$script_metadata['dependencies'],
			$script_metadata['version'],
			array( 'in_footer' => true )
		);

		$inline_script = sprintf(
			'window._attachmentTaxonomiesExtendMediaLibrary( wp.media, jQuery, %s );',
			wp_json_encode( $this->get_script_data() )
		);
		wp_add_inline_script( 'attachment-taxonomies', $inline_script );
	}

	/**
	 * Prints some inline styles for the taxonomy filters and term dropdowns.
	 *
	 * The styles are only printed from within the `admin_footer` action.
	 * Otherwise the function will hook itself into this action and bail.
	 *
	 * This method is hooked into the `wp_enqueue_media` action.
	 *
	 * @since 1.2.0 Originally part of {@see Attachment_Taxonomies_Core}.
	 */
	public function print_styles() {
		$taxonomies = $this->core->get_taxonomies_to_show();
		if ( ! $taxonomies ) {
			return;
		}

		if ( ! doing_action( 'admin_footer' ) ) {
			add_action( 'admin_footer', array( $this, 'print_styles' ) );
			return;
		}

		$tax_count = count( $taxonomies );

		$pct           = (int) floor( 100 / ( $tax_count + 1 ) );
		$pct_with_type = (int) floor( 100 / ( $tax_count + 2 ) );

		?>
		<style type="text/css">
			.media-modal-content .media-frame .media-toolbar-secondary > select {
				width: calc(<?php echo esc_attr( $pct ); ?>% - 12px) !important;
			}

			.media-modal-content .media-frame .media-toolbar-secondary:has(#media-attachment-filters) > select {
				width: calc(<?php echo esc_attr( $pct_with_type ); ?>% - 12px) !important;
			}

			.attachment-details .setting.attachment-taxonomy-input,
			.media-sidebar .setting.attachment-taxonomy-input {
				display: none;
			}

			.attachment-details .setting.attachment-taxonomy-select select,
			.media-sidebar .setting.attachment-taxonomy-select select {
				-webkit-box-sizing: border-box;
				-moz-box-sizing: border-box;
				box-sizing: border-box;
				margin: 1px;
				width: 65%;
				float: right;
			}
		</style>
		<?php
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
		foreach ( $this->core->get_taxonomies_to_show() as $taxonomy ) {
			if ( is_taxonomy_hierarchical( $taxonomy->name ) ) {
				$terms = array_map(
					static function ( $term ) {
						return (int) $term->term_id;
					},
					(array) wp_get_object_terms( $attachment->ID, $taxonomy->name )
				);
			} else {
				$terms = array_map(
					static function ( $term ) {
						return $term->slug;
					},
					(array) wp_get_object_terms( $attachment->ID, $taxonomy->name )
				);
			}
			$response[ 'taxonomy-' . $taxonomy->name . '-terms' ] = implode( ',', $terms );
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
		foreach ( $this->core->get_all_taxonomies() as $taxonomy ) {
			if ( isset( $form_fields[ $taxonomy->name ] ) ) {
				unset( $form_fields[ $taxonomy->name ] );
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
		$taxonomies = $this->core->get_taxonomies_to_show();
		if ( ! $taxonomies ) {
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
		foreach ( $this->core->get_taxonomies_to_show() as $taxonomy ) {
			$terms        = $this->core->get_terms_for_taxonomy( $taxonomy->name );
			$user_has_cap = current_user_can( $taxonomy->cap->assign_terms );
			$setting      = 'taxonomy-' . sanitize_html_class( $taxonomy->name ) . '-terms';
			$id           = 'attachment-details-two-column-taxonomy-' . sanitize_html_class( $taxonomy->name ) . '-terms';
			$term_field   = is_taxonomy_hierarchical( $taxonomy->name ) ? 'term_id' : 'slug';
			?>
			<span class="setting attachment-taxonomy-input" data-setting="<?php echo esc_attr( $setting ); ?>">
				<input type="hidden" value="{{ data['taxonomy-<?php echo esc_attr( $taxonomy->name ); ?>-terms'] }}" />
			</span>
			<span class="setting attachment-taxonomy-select" data-controls-attachment-taxonomy-setting="<?php echo esc_attr( $setting ); ?>">
				<label for="<?php echo esc_attr( $id ); ?>" class="name"><?php echo esc_html( $taxonomy->labels->name ); ?></label>
				<select id="<?php echo esc_attr( $id ); ?>" multiple="multiple"<?php echo $user_has_cap ? '' : ' readonly'; ?>>
					<?php
					foreach ( $terms as $term ) {
						$selected_attr = " {{ ( data['taxonomy-" . esc_attr( $taxonomy->name ) . "-terms'] && data['taxonomy-" . esc_attr( $taxonomy->name ) . "-terms'].split( ',' ).includes( '" . esc_attr( $term->$term_field ) . "' ) ) ? 'selected' : '' }}";
						?>
						<option value="<?php echo esc_attr( $term->$term_field ); ?>"<?php echo ( $user_has_cap ? '' : ' disabled' ) . $selected_attr; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>>
							<?php echo esc_html( $term->name ); ?>
						</option>
						<?php
					}
					?>
				</select>
			</span>
			<?php
		}
		return ob_get_clean();
	}

	/**
	 * Gets data to pass to the script as JSON.
	 *
	 * @since 1.2.0
	 *
	 * @return array Associative array of data to provide to the JS script.
	 */
	private function get_script_data() {
		$taxonomies     = array();
		$all_items      = array();
		$filter_by_item = array();
		foreach ( $this->core->get_taxonomies_to_show() as $taxonomy ) {
			$js_slug = $this->make_js_slug( $taxonomy->name );

			$taxonomies[]               = $this->prepare_taxonomy_for_js( $taxonomy );
			$all_items[ $js_slug ]      = $taxonomy->labels->all_items;
			$filter_by_item[ $js_slug ] = $this->get_filter_by_label( $taxonomy );
		}

		return array(
			'data' => $taxonomies,
			'l10n' => array(
				'all'      => $all_items,
				'filterBy' => $filter_by_item,
			),
		);
	}

	/**
	 * Formats a taxonomy to be used in JavaScript.
	 *
	 * Also includes the terms of this taxonomy.
	 *
	 * @since 1.2.0 Originally part of {@see Attachment_Taxonomies_Core}.
	 *
	 * @param WP_Taxonomy $taxonomy The taxonomy object.
	 * @return array An associative array for the taxonomy.
	 */
	private function prepare_taxonomy_for_js( $taxonomy ) {
		$js_slug = $this->make_js_slug( $taxonomy->name );

		return array(
			'name'     => $taxonomy->label,
			'slug'     => $js_slug,
			'slugId'   => str_replace( '_', '-', $taxonomy->name ),
			'queryVar' => $taxonomy->query_var,
			'terms'    => array_map(
				function ( $term ) {
					if ( ! $term instanceof WP_Term ) {
						return get_object_vars( $term );
					}
					return $term->to_array();
				},
				$this->core->get_terms_for_taxonomy( $taxonomy->name )
			),
		);
	}

	/**
	 * Transforms a taxonomy slug into a slug for JavaScript.
	 *
	 * This is basically a transformation into camel case.
	 *
	 * @since 1.2.0 Originally part of {@see Attachment_Taxonomies_Core}.
	 *
	 * @param string $taxonomy_slug The taxonomy slug to transform.
	 * @return string The camel case taxonomy slug.
	 */
	private function make_js_slug( $taxonomy_slug ) {
		return lcfirst( implode( array_map( 'ucfirst', explode( '_', $taxonomy_slug ) ) ) );
	}

	/**
	 * Returns the "Filter by" label for a taxonomy.
	 *
	 * This is an additional taxonomy label. To define it for a custom taxonomy,
	 * a 'filter_by_item' key must be added to the labels array.
	 *
	 * If it is not defined, the default label will be used.
	 *
	 * @since 1.2.0 Originally part of {@see Attachment_Taxonomies_Core}.
	 *
	 * @param WP_Taxonomy $taxonomy The taxonomy object.
	 * @return string The "Filter by" label for that taxonomy.
	 */
	private function get_filter_by_label( $taxonomy ) {
		if ( isset( $taxonomy->labels->filter_by_item ) ) {
			return $taxonomy->labels->filter_by_item;
		} elseif ( $taxonomy->hierarchical ) {
			return __( 'Filter by Category', 'attachment-taxonomies' );
		} else {
			return __( 'Filter by Tag', 'attachment-taxonomies' );
		}
	}
}
