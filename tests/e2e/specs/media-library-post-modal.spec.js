/**
 * External dependencies
 */
import path from 'path';

/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import TermUtils from '../utils/term-utils';
import oneOfLocators from '../utils/one-of-locators';

test.use( {
	termUtils: async ( { requestUtils }, use ) => {
		await use( new TermUtils( { requestUtils } ) );
	},
	mediaModal: async ( { page, admin, editor }, use ) => {
		await use( new MediaModal( { page, admin, editor } ) );
	},
} );

test.describe( 'Media library post modal', () => {
	let categories;
	let tags;
	let attachments;

	test.beforeAll( async ( { requestUtils, termUtils } ) => {
		categories = {};
		categories[ 'Test Category 1' ] =
			await termUtils.createAttachmentCategory( {
				name: 'Test Category 1',
			} );
		categories[ 'Test Category 2' ] =
			await termUtils.createAttachmentCategory( {
				name: 'Test Category 2',
			} );

		tags = {};
		tags[ 'Test Tag 1' ] = await termUtils.createAttachmentTag( {
			name: 'Test Tag 1',
		} );

		const filename = '1024x768_e2e_test_image_size.jpeg';
		const filepath = path.join( './tests/assets', filename );

		attachments = {};
		attachments.withCat2 = await requestUtils.uploadMedia( filepath );
		attachments.noTerms = await requestUtils.uploadMedia( filepath );

		// Assign "Test Category 2" to the first attachment.
		await termUtils.assignAttachmentTerms( attachments.withCat2.id, [
			{
				taxonomy: 'attachment_category',
				id: categories[ 'Test Category 2' ].id,
			},
		] );
	} );

	test.afterAll( async ( { requestUtils, termUtils } ) => {
		await termUtils.deleteAllAttachmentCategories();
		await termUtils.deleteAllAttachmentTags();
		await requestUtils.deleteAllMedia();
		await requestUtils.deleteAllPosts();

		categories = undefined;
		tags = undefined;
		attachments = undefined;
	} );

	test( 'Media library post modal filter dropdowns are present with terms', async ( {
		mediaModal,
	} ) => {
		const modal = await mediaModal.openMediaModalForNewPost();
		await expect( modal ).toBeVisible();

		const categoryFilters = modal.locator(
			'#media-attachment-attachment-category-filters'
		);
		await expect( categoryFilters ).toBeVisible();
		await expect( categoryFilters.locator( 'option' ) ).toHaveCount( 3 ); // 2 categories, plus 'all'.

		const tagFilters = modal.locator(
			'#media-attachment-attachment-tag-filters'
		);
		await expect( tagFilters ).toBeVisible();
		await expect( tagFilters.locator( 'option' ) ).toHaveCount( 2 ); // 1 tag, plus 'all'.
	} );

	test( 'Media library post modal category filter limits attachments visible', async ( {
		mediaModal,
	} ) => {
		const modal = await mediaModal.openMediaModalForNewPost();
		await expect( modal ).toBeVisible();

		// There should be two attachments present overall.
		await expect(
			modal.locator( '.attachments > .attachment' )
		).toHaveCount( 2 );

		const categoryFilters = modal.locator(
			'#media-attachment-attachment-category-filters'
		);

		// Selecting the category not assigned to  any attachments should lead to no attachments being visible.
		await categoryFilters.selectOption(
			categories[ 'Test Category 1' ].slug
		);
		await expect(
			modal.locator( '.attachments > .attachment' )
		).toHaveCount( 0 );

		// Selecting the category assigned to the second attachment should lead to only that attachment being visible.
		await categoryFilters.selectOption(
			categories[ 'Test Category 2' ].slug
		);
		await expect(
			modal.locator( '.attachments > .attachment' )
		).toHaveCount( 1 );
	} );

	test( 'Media library post modal tag filter limits attachments visible', async ( {
		mediaModal,
	} ) => {
		const modal = await mediaModal.openMediaModalForNewPost();
		await expect( modal ).toBeVisible();

		// There should be two attachments present overall.
		await expect(
			modal.locator( '.attachments > .attachment' )
		).toHaveCount( 2 );

		const tagFilters = modal.locator(
			'#media-attachment-attachment-tag-filters'
		);

		// Selecting a tag should lead to no attachments being visible.
		await tagFilters.selectOption( tags[ 'Test Tag 1' ].slug );
		await expect(
			modal.locator( '.attachments > .attachment' )
		).toHaveCount( 0 );

		// Selecting 'all' should lead to the attachments being visible again.
		await tagFilters.selectOption( 'all' );
		await expect(
			modal.locator( '.attachments > .attachment' )
		).toHaveCount( 2 );
	} );

	test( 'Media library post modal attachment term selectors are present with terms', async ( {
		mediaModal,
	} ) => {
		const modal = await mediaModal.openMediaModalForNewPost();
		await expect( modal ).toBeVisible();

		await modal
			.locator( `.attachment[data-id="${ attachments.withCat2.id }"]` )
			.click();

		const sidebar = modal.locator( '.media-sidebar' );

		const categoryAssignment = sidebar.locator(
			'#attachment-details-two-column-taxonomy-attachment_category-terms'
		);
		await expect( categoryAssignment ).toBeVisible();
		await expect( categoryAssignment.locator( 'option' ) ).toHaveCount( 2 ); // 2 categories.

		const tagAssignment = sidebar.locator(
			'#attachment-details-two-column-taxonomy-attachment_tag-terms'
		);
		await expect( tagAssignment ).toBeVisible();
		await expect( tagAssignment.locator( 'option' ) ).toHaveCount( 1 ); // 1 tag.
	} );

	test( 'Media library post modal attachment category selector allows assigning and unassigning category', async ( {
		page,
		termUtils,
		mediaModal,
	} ) => {
		const modal = await mediaModal.openMediaModalForNewPost();
		await expect( modal ).toBeVisible();

		await modal
			.locator( `.attachment[data-id="${ attachments.withCat2.id }"]` )
			.click();

		const sidebar = modal.locator( '.media-sidebar' );

		const categoryAssignment = sidebar.locator(
			'#attachment-details-two-column-taxonomy-attachment_category-terms'
		);

		// Unselect the assigned (second) category and ensure it is no longer assigned to the attachment afterwards.
		await Promise.all( [
			categoryAssignment.selectOption( [] ),
			page.waitForResponse( '/wp-admin/admin-ajax.php' ),
		] );
		await expect(
			await termUtils.getAttachmentTerms(
				attachments.withCat2.id,
				'attachment_category'
			)
		).toEqual( [] );

		// Select both categories and ensure they are assigned to the attachment afterwards.
		await Promise.all( [
			categoryAssignment.selectOption( [
				`${ categories[ 'Test Category 1' ].id }`,
				`${ categories[ 'Test Category 2' ].id }`,
			] ),
			page.waitForResponse( '/wp-admin/admin-ajax.php' ),
		] );
		await expect(
			await termUtils.getAttachmentTerms(
				attachments.withCat2.id,
				'attachment_category'
			)
		).toEqual( [
			categories[ 'Test Category 1' ].id,
			categories[ 'Test Category 2' ].id,
		] );

		// Select only the originally assigned (second) category and ensure only that category is assigned to the attachment afterwards.
		await Promise.all( [
			categoryAssignment.selectOption( [
				`${ categories[ 'Test Category 2' ].id }`,
			] ),
			page.waitForResponse( '/wp-admin/admin-ajax.php' ),
		] );
		await expect(
			await termUtils.getAttachmentTerms(
				attachments.withCat2.id,
				'attachment_category'
			)
		).toEqual( [ categories[ 'Test Category 2' ].id ] );
	} );

	test( 'Media library post modal attachment tag selector allows assigning and unassigning tag', async ( {
		page,
		termUtils,
		mediaModal,
	} ) => {
		const modal = await mediaModal.openMediaModalForNewPost();
		await expect( modal ).toBeVisible();

		await modal
			.locator( `.attachment[data-id="${ attachments.noTerms.id }"]` )
			.click();

		const sidebar = modal.locator( '.media-sidebar' );

		const tagAssignment = sidebar.locator(
			'#attachment-details-two-column-taxonomy-attachment_tag-terms'
		);

		// Select the tag and ensure it is assigned to the attachment afterwards.
		await Promise.all( [
			tagAssignment.selectOption( [ tags[ 'Test Tag 1' ].slug ] ),
			page.waitForResponse( '/wp-admin/admin-ajax.php' ),
		] );
		await expect(
			await termUtils.getAttachmentTerms(
				attachments.noTerms.id,
				'attachment_tag'
			)
		).toEqual( [ tags[ 'Test Tag 1' ].id ] );

		// Unselect the tag and ensure it is no longer assigned to the attachment afterwards.
		await Promise.all( [
			tagAssignment.selectOption( [] ),
			page.waitForResponse( '/wp-admin/admin-ajax.php' ),
		] );
		await expect(
			await termUtils.getAttachmentTerms(
				attachments.noTerms.id,
				'attachment_tag'
			)
		).toEqual( [] );
	} );
} );

class MediaModal {
	constructor( { page, admin, editor } ) {
		this.page = page;
		this.admin = admin;
		this.editor = editor;
	}

	async openMediaModalForNewPost() {
		await this.admin.createNewPost();
		await this.editor.insertBlock( { name: 'core/image' } );

		const imageBlock = await oneOfLocators(
			this.editor.canvas.locator( 'role=document[name="Block: Image"i]' ),
			this.page.locator( 'role=document[name="Block: Image"i]' )
		);
		await imageBlock
			.getByRole( 'button', { name: 'Media Library' } )
			.click();

		return this.page.locator( '.media-modal' );
	}
}
