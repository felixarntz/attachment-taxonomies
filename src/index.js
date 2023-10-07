/**
 * Internal dependencies
 */
import {
	createAttachmentTaxonomyFilter,
	extendAttachmentsBrowser,
} from './utils';

( function ( wp, $ ) {
	if ( ! window._attachment_taxonomies ) {
		return;
	}

	if ( ! wp.media ) {
		return;
	}

	wp.media.taxonomies = window._attachment_taxonomies;
	window._attachment_taxonomies = undefined;

	wp.media.view.AttachmentFilters.Taxonomy = createAttachmentTaxonomyFilter(
		wp.media.view.AttachmentFilters
	);
	wp.media.view.AttachmentsBrowser = extendAttachmentsBrowser(
		wp.media.view.AttachmentsBrowser,
		wp.media.view.AttachmentFilters.Taxonomy,
		wp.media.view.Label,
		wp.media.taxonomies
	);

	$( document ).on(
		'change',
		'.attachment-taxonomy-select > select',
		function ( e ) {
			const options = [];
			for ( const i in e.target.options ) {
				if ( e.target.options[ i ].selected ) {
					options.push( e.target.options[ i ].value );
				}
			}

			$( e.target )
				.parent()
				.prev( '.attachment-taxonomy-input' )
				.find( 'input' )
				.val( options.join( ',' ) )
				.trigger( 'change' );
		}
	);
} )( window.wp || {}, window.jQuery );
