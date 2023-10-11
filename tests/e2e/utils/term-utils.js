class TermUtils {
	constructor( { requestUtils } ) {
		this.requestUtils = requestUtils;
		this.taxRestBases = {};
	}

	async getRestBase( taxonomy ) {
		if ( this.taxRestBases[ taxonomy ] ) {
			return this.taxRestBases[ taxonomy ];
		}

		const taxData = await this.requestUtils.rest( {
			method: 'GET',
			path: `/wp/v2/taxonomies/${ taxonomy }`,
		} );
		this.taxRestBases[ taxonomy ] = taxData.rest_base;

		return this.taxRestBases[ taxonomy ];
	}

	async createTerm( { taxonomy, name, slug, restBase } ) {
		if ( ! restBase ) {
			restBase = await this.getRestBase( taxonomy );
		}

		const termData = { taxonomy };
		if ( name ) {
			termData.name = name;
		}
		if ( slug ) {
			termData.slug = slug;
		}

		const term = await this.requestUtils.rest( {
			method: 'POST',
			path: `/wp/v2/${ restBase }`,
			params: termData,
		} );
		return term;
	}

	async deleteTerm( { taxonomy, id, restBase } ) {
		if ( ! restBase ) {
			restBase = await this.getRestBase( taxonomy );
		}

		await this.requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/${ restBase }/${ id }`,
			params: {
				force: true,
			},
		} );
	}

	async deleteAllTerms( { taxonomy, restBase } ) {
		if ( ! restBase ) {
			restBase = await this.getRestBase( taxonomy );
		}

		const terms = await this.requestUtils.rest( {
			method: 'GET',
			path: `/wp/v2/${ restBase }`,
			params: {
				per_page: 100,
			},
		} );

		await Promise.all(
			terms.map( ( term ) =>
				this.requestUtils.rest( {
					method: 'DELETE',
					path: `/wp/v2/${ restBase }/${ term.id }`,
					params: {
						force: true,
					},
				} )
			)
		);
	}

	async getAttachmentTerms( attachmentId, taxonomy, restBase ) {
		if ( ! restBase ) {
			restBase = await this.getRestBase( taxonomy );
		}
		const attachment = await this.requestUtils.rest( {
			method: 'GET',
			path: `/wp/v2/media/${ attachmentId }`,
		} );
		return attachment[ restBase ] || [];
	}

	async assignAttachmentTerms( attachmentId, terms ) {
		const params = {};
		for ( let { taxonomy, id, restBase } of terms ) {
			if ( ! restBase ) {
				restBase = await this.getRestBase( taxonomy );
			}

			params[ restBase ] = id;
		}

		const attachment = await this.requestUtils.rest( {
			method: 'POST',
			path: `/wp/v2/media/${ attachmentId }`,
			params,
		} );
		return attachment;
	}

	createAttachmentCategory( termData ) {
		return this.createTerm( {
			...termData,
			taxonomy: 'attachment_category',
		} );
	}

	createAttachmentTag( termData ) {
		return this.createTerm( {
			...termData,
			taxonomy: 'attachment_tag',
		} );
	}

	deleteAllAttachmentCategories() {
		return this.deleteAllTerms( { taxonomy: 'attachment_category' } );
	}

	deleteAllAttachmentTags() {
		return this.deleteAllTerms( { taxonomy: 'attachment_tag' } );
	}
}

export default TermUtils;
