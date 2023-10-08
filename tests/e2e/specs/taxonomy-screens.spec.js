/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addQueryArgs } from '@wordpress/url';

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

	test.beforeAll( async ( { termUtils } ) => {
		categories = {};
		categories['Test Category 1'] = await termUtils.createAttachmentCategory( { name: 'Test Category 1' } );
		categories['Test Category 2'] = await termUtils.createAttachmentCategory( { name: 'Test Category 2' } );

		tags = {};
		tags['Test Tag 1'] = await termUtils.createAttachmentTag( { name: 'Test Tag 1' } );
	} );

	test.afterAll( async ( { termUtils } ) => {
		await termUtils.deleteAllAttachmentCategories();
		await termUtils.deleteAllAttachmentTags();
		categories = undefined;
		tags = undefined;
	} );

	test( 'Attachment categories screen is present', async ( {
		page,
		admin,
	} ) => {
		const query = addQueryArgs( '', {
			taxonomy: 'attachment_category',
			post_type: 'attachment',
		} ).slice( 1 );
		await admin.visitAdminPage( 'edit-tags.php', query );

		const rowTitles = page.locator( '.row-title' );
		await expect( rowTitles ).toHaveCount( 2 );
		await expect( rowTitles.first() ).toHaveText( 'Test Category 1' );
		await expect( rowTitles.last() ).toHaveText( 'Test Category 2' );
	} );

	test( 'Attachment tags screen is present', async ( {
		page,
		admin,
	} ) => {
		const query = addQueryArgs( '', {
			taxonomy: 'attachment_tag',
			post_type: 'attachment',
		} ).slice( 1 );
		await admin.visitAdminPage( 'edit-tags.php', query );

		const rowTitles = page.locator( '.row-title' );
		await expect( rowTitles ).toHaveCount( 1 );
		await expect( rowTitles.first() ).toHaveText( 'Test Tag 1' );
	} );
} );
