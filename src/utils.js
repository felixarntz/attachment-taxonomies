/**
 * Returns a taxonomy attachment filter extending the given (Backbone) attachment filters of the Media Library.
 *
 * @param {Object} attachmentFilters See `wp.media.view.AttachmentFilters`.
 * @return {Object} Attachment taxonomy filter to set on `wp.media.view.AttachmentFilters`.
 */
export function createAttachmentTaxonomyFilter( attachmentFilters ) {
	return attachmentFilters.extend( {
		id: 'media-attachment-taxonomy-filters',
		// eslint-disable-next-line object-shorthand
		createFilters: function () {
			const filters = {};

			if ( this.options.queryVar && this.options.allLabel ) {
				filters.all = {
					text: this.options.allLabel,
					props: {},
					priority: 1,
				};
				filters.all.props[ this.options.queryVar ] = null;

				if ( this.options.terms && this.options.terms.length ) {
					for ( const i in this.options.terms ) {
						filters[ this.options.terms[ i ].slug ] = {
							text: this.options.terms[ i ].name,
							props: {},
							priority: i + 2,
						};
						filters[ this.options.terms[ i ].slug ].props[
							this.options.queryVar
						] = this.options.terms[ i ].slug;
					}
				}
			}

			this.filters = filters;
		},
	} );
}

/**
 * Returns an extended version of the given (Backbone) attachments browser of the Media Library.
 *
 * @param {Object} attachmentsBrowser       See `wp.media.view.AttachmentsBrowser`.
 * @param {Object} attachmentTaxonomyFilter The attachment taxonomy filter creator from `createAttachmentTaxonomyFilter()`.
 * @param {Object} mediaViewLabel           See `wp.media.view.Label`.
 * @param {Object} taxonomies               Taxonomies data passed from PHP.
 * @return {Object} Attachments browser to replace `wp.media.view.AttachmentsBrowser` with.
 */
export function extendAttachmentsBrowser(
	attachmentsBrowser,
	attachmentTaxonomyFilter,
	mediaViewLabel,
	taxonomies
) {
	return attachmentsBrowser.extend( {
		// eslint-disable-next-line object-shorthand
		createToolbar: function () {
			if (
				attachmentsBrowser.__super__ &&
				attachmentsBrowser.__super__.createToolbar
			) {
				attachmentsBrowser.__super__.createToolbar.apply(
					this,
					arguments
				);
			} else {
				attachmentsBrowser.prototype.createToolbar.apply(
					this,
					arguments
				);
			}

			// Do not render filters when in gallery editing mode.
			const state = this.controller.state();
			if ( state.id && 'gallery-edit' === state.id ) {
				return;
			}

			const data = taxonomies.data;

			for ( const i in data ) {
				this.toolbar.set(
					data[ i ].slug + 'FilterLabel',
					new mediaViewLabel( {
						value: taxonomies.l10n.filterBy[ data[ i ].slug ],
						attributes: {
							for:
								'media-attachment-' +
								data[ i ].slugId +
								'-filters',
						},
						priority: -72,
					} ).render()
				);
				this.toolbar.set(
					data[ i ].slug + 'Filter',
					new attachmentTaxonomyFilter( {
						controller: this.controller,
						model: this.collection.props,
						priority: -72,
						queryVar: data[ i ].queryVar,
						terms: data[ i ].terms,
						id: 'media-attachment-' + data[ i ].slugId + '-filters',
						allLabel: taxonomies.l10n.all[ data[ i ].slug ],
					} ).render()
				);
			}
		},
	} );
}
