<?php
/**
 * Attachment_Taxonomies_Core class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <hello@felix-arntz.me>
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( class_exists( 'Attachment_Taxonomies_Core' ) ) {
	return;
}

/**
 * Contains core methods to handle attachment taxonomies.
 *
 * @since 1.0.0
 */
final class Attachment_Taxonomies_Core {
	/**
	 * The Singleton instance.
	 *
	 * @since 1.0.0
	 * @static
	 * @var Attachment_Taxonomies_Core|null
	 */
	private static $instance = null;

	/**
	 * The Singleton instance.
	 *
	 * @since 1.0.0
	 * @static
	 *
	 * @return Attachment_Taxonomies_Core The Singleton class instance.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {}

	/**
	 * Checks whether there are any attachment taxonomies registered.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if there are attachment taxonomies, otherwise false.
	 */
	public function has_taxonomies() {
		$taxonomies = $this->get_taxonomies();
		return 0 < count( $taxonomies );
	}

	/**
	 * Returns attachment taxonomies.
	 *
	 * @since 1.0.0
	 *
	 * @param string $mode Either 'names' (for an array of taxonomy slugs) or 'objects' (for an array of objects).
	 * @return array A list of taxonomy names or objects.
	 */
	public function get_taxonomies( $mode = 'names' ) {
		return get_object_taxonomies( 'attachment', $mode );
	}

	/**
	 * Returns all terms for a specific taxonomy.
	 *
	 * Empty terms are also included.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 The $args parameter has been added.
	 *
	 * @param string $taxonomy_slug The taxonomy to get the terms for.
	 * @param array  $args          Optional. Additional query arguments. Default empty array.
	 * @return array|WP_Error A list of term objects or an error if the taxonomy does not exist.
	 */
	public function get_terms_for_taxonomy( $taxonomy_slug, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'hide_empty' => false,
			)
		);

		$args['taxonomy'] = $taxonomy_slug;
		return get_terms( $args );
	}

	/**
	 * Renders attachment taxonomy filter dropdowns.
	 *
	 * This is only used for the PHP-based part of the media library (i.e. the regular list).
	 * The JavaScript implementation using Backbone is used elsewhere.
	 *
	 * This method is hooked into the `restrict_manage_posts` action.
	 *
	 * @since 1.0.0
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

		foreach ( $this->get_taxonomies( 'objects' ) as $taxonomy_slug => $taxonomy ) {
			if ( ! $taxonomy->query_var ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$value = isset( $_REQUEST[ $taxonomy->query_var ] ) ? $_REQUEST[ $taxonomy->query_var ] : '';
			?>
			<label for="attachment-<?php echo sanitize_html_class( $taxonomy_slug ); ?>-filter" class="screen-reader-text"><?php echo esc_html( $this->get_filter_by_label( $taxonomy ) ); ?></label>
			<select class="attachment-filters" name="<?php echo esc_attr( $taxonomy->query_var ); ?>" id="attachment-<?php echo sanitize_html_class( $taxonomy_slug ); ?>-filter">
				<option value="" <?php selected( '', $value ); ?>><?php echo esc_html( $taxonomy->labels->all_items ); ?></option>
				<?php foreach ( $this->get_terms_for_taxonomy( $taxonomy_slug ) as $term ) : ?>
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
	 * @since 1.0.0
	 */
	public function enqueue_script() {
		if ( ! $this->has_taxonomies() ) {
			return;
		}

		$taxonomies     = array();
		$all_items      = array();
		$filter_by_item = array();
		foreach ( $this->get_taxonomies( 'objects' ) as $taxonomy_slug => $taxonomy ) {
			if ( ! $taxonomy->query_var ) {
				continue;
			}

			$js_slug = $this->make_js_slug( $taxonomy_slug );

			$taxonomies[]               = $this->prepare_taxonomy_for_js( $taxonomy_slug, $taxonomy );
			$all_items[ $js_slug ]      = $taxonomy->labels->all_items;
			$filter_by_item[ $js_slug ] = $this->get_filter_by_label( $taxonomy );
		}

		/*
		 * Get script data.
		 * Add legacy dependencies manually since they are not supported by `@wordpress/scripts` mapping.
		 */
		$script_data                   = require Attachment_Taxonomies::instance()->get_path( 'build/index.asset.php' );
		$script_data['dependencies'][] = 'jquery';
		$script_data['dependencies'][] = 'media-views';

		wp_enqueue_script(
			'attachment-taxonomies',
			Attachment_Taxonomies::instance()->get_url( 'build/index.js' ),
			$script_data['dependencies'],
			$script_data['version'],
			array( 'in_footer' => true )
		);

		$inline_script = sprintf(
			'window._attachmentTaxonomiesExtendMediaLibrary( wp.media, jQuery, %s );',
			wp_json_encode(
				array(
					'data' => $taxonomies,
					'l10n' => array(
						'all'      => $all_items,
						'filterBy' => $filter_by_item,
					),
				)
			)
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
	 * @since 1.0.0
	 */
	public function print_styles() {
		$taxonomies = $this->get_taxonomies();
		if ( 0 === count( $taxonomies ) ) {
			return;
		}

		if ( ! doing_action( 'admin_footer' ) ) {
			add_action( 'admin_footer', array( $this, 'print_styles' ) );
			return;
		}

		$count = 1 + count( $taxonomies );

		$percentage_calc = intval( floor( 100 / $count ) );

		?>
		<style type="text/css">
			.media-modal-content .media-frame .media-toolbar-secondary > select {
				width: calc(<?php echo esc_attr( $percentage_calc ); ?>% - 12px) !important;
			}

			.attachment-taxonomy-input {
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
	 * Formats a taxonomy to be used in JavaScript.
	 *
	 * Also includes the terms of this taxonomy.
	 *
	 * @since 1.0.0
	 *
	 * @param string $taxonomy_slug The taxonomy slug.
	 * @param object $taxonomy      The taxonomy object.
	 * @return array An associative array for the taxonomy.
	 */
	private function prepare_taxonomy_for_js( $taxonomy_slug, $taxonomy ) {
		$js_slug = $this->make_js_slug( $taxonomy_slug );

		return array(
			'name'     => $taxonomy->label,
			'slug'     => $js_slug,
			'slugId'   => str_replace( '_', '-', $taxonomy_slug ),
			'queryVar' => $taxonomy->query_var,
			'terms'    => array_map(
				function ( $term ) {
					if ( ! $term instanceof WP_Term ) {
						return get_object_vars( $term );
					}
					return $term->to_array();
				},
				$this->get_terms_for_taxonomy( $taxonomy_slug )
			),
		);
	}

	/**
	 * Transforms a taxonomy slug into a slug for JavaScript.
	 *
	 * This is basically a transformation into camel case.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @param object $taxonomy The taxonomy object.
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
