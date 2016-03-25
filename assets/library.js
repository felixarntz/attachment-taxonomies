( function( wp ) {

	if ( 'undefined' === typeof window._attachment_taxonomies ) {
		return;
	}

	if ( 'undefined' === typeof wp.media ) {
		wp.media = {};
	}

	wp.media.taxonomies = window._attachment_taxonomies;

	window._attachment_taxonomies = undefined;

})( window.wp || {} );
