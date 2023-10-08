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

test.use( {
	termUtils: async ( { requestUtils }, use ) => {
		await use( new TermUtils( { requestUtils } ) );
	},
} );

test.describe( 'Media library filter', () => {
	let categories;
	let tags;
	let attachment;

	test.beforeAll( async ( { requestUtils, termUtils } ) => {
		categories = {};
		categories['Test Category 1'] = await termUtils.createAttachmentCategory( { name: 'Test Category 1' } );
		categories['Test Category 2'] = await termUtils.createAttachmentCategory( { name: 'Test Category 2' } );

		tags = {};
		tags['Test Tag 1'] = await termUtils.createAttachmentTag( { name: 'Test Tag 1' } );

		const filename = '1024x768_e2e_test_image_size.jpeg';
		const filepath = path.join( './tests/assets', filename );
		attachment = await requestUtils.uploadMedia( filepath );

		// Assign "Test Category 2" to the attachment.
		await termUtils.assignAttachmentTerms( attachment.id, [ {
			taxonomy: 'attachment_category',
			id: categories['Test Category 2'].id,
		} ] );
	} );

	test.afterAll( async ( { requestUtils, termUtils } ) => {
		await termUtils.deleteAllAttachmentCategories();
		await termUtils.deleteAllAttachmentTags();
		await requestUtils.deleteAllMedia();

		categories = undefined;
		tags = undefined;
		attachment = undefined;
	} );

	test( 'Media library filter dropdowns are present with terms', async ( {
		page,
		admin,
	} ) => {
		await admin.visitAdminPage( 'upload.php' );

		const categoryFilters = page.locator( '#media-attachment-attachment-category-filters' );
		await expect( categoryFilters ).toBeVisible();
		await expect( categoryFilters.locator( 'option' ) ).toHaveCount( 3 ); // 2 categories, plus 'all'.

		const tagFilters = page.locator( '#media-attachment-attachment-tag-filters' );
		await expect( tagFilters ).toBeVisible();
		await expect( tagFilters.locator( 'option' ) ).toHaveCount( 2 ); // 1 tag, plus 'all'.
	} );

	test( 'Media library category filter limits attachments visible', async ( {
		page,
		admin,
	} ) => {
		await admin.visitAdminPage( 'upload.php' );

		// There should be one attachment present overall.
		await expect( page.locator( '.attachments > .attachment' ) ).toHaveCount( 1 );

		const categoryFilters = page.locator( '#media-attachment-attachment-category-filters' );

		// Selecting the category not assigned to the attachment should lead to no attachments being visible.
		await categoryFilters.selectOption( categories['Test Category 1'].slug );
		await expect( page.locator( '.attachments > .attachment' ) ).toHaveCount( 0 );

		// Selecting the category assigned to the attachment should lead to the attachments being visible again.
		await categoryFilters.selectOption( categories['Test Category 2'].slug );
		await expect( page.locator( '.attachments > .attachment' ) ).toHaveCount( 1 );
	} );

	test( 'Media library tag filter limits attachments visible', async ( {
		page,
		admin,
	} ) => {
		await admin.visitAdminPage( 'upload.php' );

		// There should be one attachment present overall.
		await expect( page.locator( '.attachments > .attachment' ) ).toHaveCount( 1 );

		const tagFilters = page.locator( '#media-attachment-attachment-tag-filters' );

		// Selecting a tag should lead to no attachments being visible.
		await tagFilters.selectOption( tags['Test Tag 1'].slug );
		await expect( page.locator( '.attachments > .attachment' ) ).toHaveCount( 0 );

		// Selecting 'all' should lead to the attachments being visible again.
		await tagFilters.selectOption( 'all' );
		await expect( page.locator( '.attachments > .attachment' ) ).toHaveCount( 1 );
	} );
} );
