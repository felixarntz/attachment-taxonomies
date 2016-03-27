<?php

// temporary

function test_attachment_data( $data, &$postarr ) {
	if ( doing_action( 'wp_ajax_save-attachment' ) && isset( $_REQUEST['changes'] ) ) {
		//add taxonomy stuff to $postarr from $_REQUEST['changes']
		// $postarr['tax_input'] = ...
	}

	return $data;
}
add_filter( 'wp_insert_attachment_data', 'test_attachment_data', 10, 2 );

function test_prepare_attachment_js( $response, $attachment, $meta ) {
	foreach ( get_object_taxonomies( 'attachment' ) as $taxonomy_slug ) {
		//add taxonomy data to $response
	}
	return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'test_prepare_attachment_js', 10, 3 );

function test_attachment_compat_fields( $form_fields, $post ) {
	foreach ( get_object_taxonomies( 'attachment' ) as $taxonomy_slug ) {
		if ( isset( $form_fields[ $taxonomy_slug ] ) ) {
			unset( $form_fields[ $taxonomy_slug ] );
		}
	}

	return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'test_attachment_compat_fields', 10, 2 );

function test_print_media_templates() {
	ob_start();
	wp_print_media_templates();
	$output = ob_get_clean();

	$output = preg_replace_callback( '#<script type="text/html" id="tmpl-attachment-details">(.+)</script>#U', function( $matches ) {
		$r = '<script type="text/html" id="tmpl-attachment-details">';
		$r .= $matches[1];
		ob_start();
		?>
		<!-- TAXONOMY OUTPUT HERE -->
		<?php
		$r .= ob_get_clean();
		$r .= '</script>';

		return $r;
	}, $output );

	echo $output;
}

function test_adjust_media_templates() {
	remove_action( 'admin_footer', 'wp_print_media_templates' );
	remove_action( 'wp_footer', 'wp_print_media_templates' );
	remove_action( 'customize_controls_print_footer_scripts', 'wp_print_media_templates' );
	add_action( 'admin_footer', 'test_print_media_templates' );
	add_action( 'wp_footer', 'test_print_media_templates' );
	add_action( 'customize_controls_print_footer_scripts', 'test_print_media_templates' );
}
add_action( 'wp_enqueue_media', 'test_adjust_media_templates' );
