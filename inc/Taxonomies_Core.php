<?php
/**
 * @package AttachmentTaxonomies
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( class_exists( 'Attachment_Taxonomies_Core' ) ) {
	return;
}

final class Attachment_Taxonomies_Core {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function has_taxonomies() {
		$taxonomies = $this->get_taxonomies();
		return 0 < count( $taxonomies );
	}

	public function get_taxonomies( $mode = 'names' ) {
		return get_object_taxonomies( 'attachment', $mode );
	}

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

			$js_slug = lcfirst( implode( array_map( 'ucfirst', explode( '_', $taxonomy_slug ) ) ) );

			$taxonomies[] = array(
				'name'		=> $taxonomy->label,
				'slug'		=> $js_slug,
				'slugId'	=> str_replace( '_', '-', $taxonomy_slug ),
				'queryVar'	=> $taxonomy->query_var,
				'terms'		=> array_map( array( $this, 'get_term_array' ), $this->get_terms_for_taxonomy( $taxonomy_slug ) ),
			);

			$all_items[ $js_slug ] = $taxonomy->labels->all_items;
			$filter_by_item[ $js_slug ] = $this->get_filter_by_label( $taxonomy );
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'attachment-taxonomies', Attachment_Taxonomies::instance()->get_url( 'assets/library' . $min . '.js' ), array( 'media-views' ), Attachment_Taxonomies::VERSION, true );
		wp_localize_script( 'attachment-taxonomies', '_attachment_taxonomies', array(
			'data'			=> $taxonomies,
			'l10n'			=> array(
				'all'			=> $all_items,
				'filterBy'		=> $filter_by_item,
			),
		) );
	}

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
		</style>
		<?php
	}

	private function get_terms_for_taxonomy( $taxonomy_slug ) {
		$args = array(
			'hide_empty'	=> false,
		);

		if ( version_compare( get_bloginfo( 'version' ), '4.5', '<' ) ) {
			return get_terms( $taxonomy_slug, $args );
		}

		$args['taxonomy'] = $taxonomy_slug;
		return get_terms( $args );
	}

	private function get_term_array( $term ) {
		if ( ! class_exists( 'WP_Term' ) || ! is_a( $term, 'WP_Term' ) ) {
			return get_object_vars( $term );
		}

		return $term->to_array();
	}

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
