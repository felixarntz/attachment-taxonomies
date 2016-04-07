( function( wp, $ ) {

	if ( 'undefined' === typeof window._attachment_taxonomies ) {
		return;
	}

	if ( 'undefined' === typeof wp.media ) {
		return;
	}

	wp.media.taxonomies = window._attachment_taxonomies;

	window._attachment_taxonomies = undefined;

	wp.media.view.AttachmentFilters.Taxonomy = wp.media.view.AttachmentFilters.extend({
		id: 'media-attachment-taxonomy-filters',
		createFilters: function() {
			var filters = {};

			if ( this.options.queryVar && this.options.allLabel ) {
				filters.all = {
					text: this.options.allLabel,
					props: {},
					priority: 1
				};
				filters.all.props[ this.options.queryVar ] = null;

				if ( this.options.terms && this.options.terms.length ) {
					for ( var i in this.options.terms ) {
						filters[ this.options.terms[ i ].slug ] = {
							text: this.options.terms[ i ].name,
							props: {},
							priority: i + 2
						};
						filters[ this.options.terms[ i ].slug ].props[ this.options.queryVar ] = this.options.terms[ i ].slug;
					}
				}
			}

			this.filters = filters;
		}
	});

	wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
		createToolbar: function() {
			wp.media.view.AttachmentsBrowser.__super__.createToolbar.apply( this, arguments );

			var data = wp.media.taxonomies.data;

			for ( var i in data ) {
				this.toolbar.set( data[ i ].slug + 'FilterLabel', new wp.media.view.Label({
					value: wp.media.taxonomies.l10n.filterBy[ data[ i ].slug ],
					attributes: {
						'for': 'media-attachment-' + data[ i ].slugId + '-filters'
					},
					priority: -72
				}).render() );
				this.toolbar.set( data[ i ].slug + 'Filter', new wp.media.view.AttachmentFilters.Taxonomy({
					controller: this.controller,
					model: this.collection.props,
					priority: -72,
					queryVar: data[ i ].queryVar,
					terms: data[ i ].terms,
					id: 'media-attachment-' + data[ i ].slugId + '-filters',
					allLabel: wp.media.taxonomies.l10n.all[ data[ i ].slug ]
				}).render() );
			}
		}
	});

	$( document ).on( 'change', '.attachment-taxonomy-select > select', function( e ) {
		var options = [];
		for ( var i in e.target.options ) {
			if ( e.target.options[ i ].selected ) {
				options.push( e.target.options[ i ].value );
			}
		}

		$( e.target ).parent().prev( '.attachment-taxonomy-input' ).find( 'input' ).val( options.join( ',' ) ).trigger( 'change' );
	});

})( window.wp || {}, window.jQuery );
