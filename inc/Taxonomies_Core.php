<?php
/**
 * Attachment_Taxonomies_Core class
 *
 * @package AttachmentTaxonomies
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
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
	 * Stores the Singleton instance.
	 *
	 * @since 1.0.0
	 * @var Attachment_Taxonomies_Core|null
	 */
	private static $instance = null;

	/**
	 * Returns the Singleton instance.
	 *
	 * @since 1.0.0
	 * @return Attachment_Taxonomies_Core the class instance
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - private because of Singleton pattern.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {}

	/**
	 * Checks whether there are any attachment taxonomies registered.
	 *
	 * @since 1.0.0
	 * @return boolean true if there are attachment taxonomies, otherwise false
	 */
	public function has_taxonomies() {
		$taxonomies = $this->get_taxonomies();
		return 0 < count( $taxonomies );
	}

	/**
	 * Returns attachment taxonomies.
	 *
	 * @since 1.0.0
	 * @param string $mode either 'names' (for an array of taxonomy slugs) or 'objects' (for an array of objects)
	 * @return array a list of taxonomy names or objects
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
	 * @param string $taxonomy_slug the taxonomy to get the terms for
	 * @return array|WP_Error a list of term objects or an error if the taxonomy does not exist
	 */
	public function get_terms_for_taxonomy( $taxonomy_slug ) {
		$args = array(
			'hide_empty'	=> false,
		);

		if ( version_compare( get_bloginfo( 'version' ), '4.5', '<' ) ) {
			return get_terms( $taxonomy_slug, $args );
		}

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
	 * @param string $post_type the current post type
	 */
	public function render_taxonomy_filters( $post_type ) {
		if ( 'attachment' !== $post_type && 'upload' !== get_current_screen()->base ) {
			return;
		}

		if ( isset( $_REQUEST['attachment-filter'] ) && 'trash' === $_REQUEST['attachment-filter'] ) {
			return;
		}

		foreach ( $this->get_taxonomies( 'objects' ) as $taxonomy_slug => $taxonomy ) {
			if ( ! $taxonomy->query_var ) {
				continue;
			}

			$value = isset( $_REQUEST[ $taxonomy->query_var ] ) ? $_REQUEST[ $taxonomy->query_var ] : '';

			?>
			<label for="attachment-<?php echo $taxonomy->slug; ?>-filter" class="screen-reader-text"><?php echo $this->get_filter_by_label( $taxonomy ); ?></label>
			<select class="attachment-filters" name="<?php echo $taxonomy->query_var; ?>" id="attachment-<?php echo $taxonomy->slug; ?>-filter">
				<option value="" <?php selected( '', $value ); ?>><?php echo $taxonomy->labels->all_items; ?></option>
				<?php foreach ( $this->get_terms_for_taxonomy( $taxonomy_slug ) as $term ) : ?>
					<option value="<?php echo $term->slug; ?>" <?php selected( $term->slug, $value ); ?>><?php echo $term->name; ?></option>
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

		$taxonomies = array();
		$all_items = array();
		$filter_by_item = array();
		foreach ( $this->get_taxonomies( 'objects' ) as $taxonomy_slug => $taxonomy ) {
			if ( ! $taxonomy->query_var ) {
				continue;
			}

			$js_slug = $this->make_js_slug( $taxonomy_slug );

			$taxonomies[] = $this->prepare_taxonomy_for_js( $taxonomy_slug, $taxonomy );
			$all_items[ $js_slug ] = $taxonomy->labels->all_items;
			$filter_by_item[ $js_slug ] = $this->get_filter_by_label( $taxonomy );
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'attachment-taxonomies', Attachment_Taxonomies::instance()->get_url( 'assets/dist/js/library' . $min . '.js' ), array( 'jquery', 'media-views' ), Attachment_Taxonomies::VERSION, true );
		wp_localize_script( 'attachment-taxonomies', '_attachment_taxonomies', array(
			'data'			=> $taxonomies,
			'l10n'			=> array(
				'all'			=> $all_items,
				'filterBy'		=> $filter_by_item,
			),
		) );
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

		$count = 2 + count( $taxonomies );

		$percentage = intval( round( 84 / $count ) );
		$percentage_calc = intval( round( 96 / $count ) );

		?>
		<style type="text/css">
			.media-modal-content .media-frame .media-toolbar-secondary > select {
				width: <?php echo $percentage; ?>% !important;
				width: -webkit-calc(<?php echo $percentage_calc; ?>% - 12px) !important;
				width: calc(<?php echo $percentage_calc; ?>% - 12px) !important;
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
	 * @param  string $taxonomy_slug the taxonomy slug
	 * @param  object $taxonomy      the taxonomy object
	 * @return array an associative array for the taxonomy
	 */
	private function prepare_taxonomy_for_js( $taxonomy_slug, $taxonomy ) {
		$js_slug = $this->make_js_slug( $taxonomy_slug );

		return array(
			'name'		=> $taxonomy->label,
			'slug'		=> $js_slug,
			'slugId'	=> str_replace( '_', '-', $taxonomy_slug ),
			'queryVar'	=> $taxonomy->query_var,
			'terms'		=> array_map( array( $this, 'get_term_array' ), $this->get_terms_for_taxonomy( $taxonomy_slug ) ),
		);
	}

	/**
	 * Transforms a taxonomy slug into a slug for JavaScript.
	 *
	 * This is basically a transformation into camel case.
	 *
	 * @since 1.0.0
	 * @param string $taxonomy_slug the taxonomy slug to transform
	 * @return string the camel case taxonomy slug
	 */
	private function make_js_slug( $taxonomy_slug ) {
		return lcfirst( implode( array_map( 'ucfirst', explode( '_', $taxonomy_slug ) ) ) );
	}

	/**
	 * Transforms a term object into an array.
	 *
	 * @since 1.0.0
	 * @param object $term a term object (`WP_Term` if WordPress >= 4.4)
	 * @return array a term array
	 */
	private function get_term_array( $term ) {
		if ( ! class_exists( 'WP_Term' ) || ! is_a( $term, 'WP_Term' ) ) {
			return get_object_vars( $term );
		}

		return $term->to_array();
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
	 * @param object $taxonomy the taxonomy object
	 * @return string the "Filter by" label for that taxonomy
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
