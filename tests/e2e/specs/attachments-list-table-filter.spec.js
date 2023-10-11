/**
 * External dependencies
 */
import path from 'path';

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

test.describe( 'Attachments list table filter', () => {
	let categories;
	let tags;
	let attachment;

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
		attachment = await requestUtils.uploadMedia( filepath );

		// Assign "Test Category 2" to the attachment.
		await termUtils.assignAttachmentTerms( attachment.id, [
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

		categories = undefined;
		tags = undefined;
		attachment = undefined;
	} );

	/*
	 * Since visiting the `mode=list` version of the media library will change the user settings for that to become the
	 * default, this snippet forces a visit to `mode=grid` after each test to reset that default.
	 */
	test.afterEach( async ( { admin } ) => {
		const query = addQueryArgs( '', { mode: 'grid' } ).slice( 1 );
		await admin.visitAdminPage( 'upload.php', query );
	} );

	test( 'Attachments list table filter dropdowns are present with terms', async ( {
		page,
		admin,
	} ) => {
		const query = addQueryArgs( '', { mode: 'list' } ).slice( 1 );
		await admin.visitAdminPage( 'upload.php', query );

		const categoryFilters = page.locator(
			'#attachment-attachment_category-filter'
		);
		await expect( categoryFilters ).toBeVisible();
		await expect( categoryFilters.locator( 'option' ) ).toHaveCount( 3 ); // 2 categories, plus 'all'.

		const tagFilters = page.locator( '#attachment-attachment_tag-filter' );
		await expect( tagFilters ).toBeVisible();
		await expect( tagFilters.locator( 'option' ) ).toHaveCount( 2 ); // 1 tag, plus 'all'.
	} );

	test( 'Attachments list table category filter limits attachments visible', async ( {
		page,
		admin,
	} ) => {
		const query = addQueryArgs( '', { mode: 'list' } ).slice( 1 );
		await admin.visitAdminPage( 'upload.php', query );

		// There should be one attachment present overall.
		await expect(
			page.locator( '#the-list > tr:not(.no-items)' )
		).toHaveCount( 1 );

		const categoryFilters = page.locator(
			'#attachment-attachment_category-filter'
		);
		const filterButton = page.locator( '#post-query-submit' );

		// Selecting the category not assigned to the attachment should lead to no attachments being visible.
		await categoryFilters.selectOption(
			categories[ 'Test Category 1' ].slug
		);
		await filterButton.click();
		await expect(
			page.locator( '#the-list > tr:not(.no-items)' )
		).toHaveCount( 0 );

		// Selecting the category assigned to the attachment should lead to the attachments being visible again.
		await categoryFilters.selectOption(
			categories[ 'Test Category 2' ].slug
		);
		await filterButton.click();
		await expect(
			page.locator( '#the-list > tr:not(.no-items)' )
		).toHaveCount( 1 );
	} );

	test( 'Attachments list table tag filter limits attachments visible', async ( {
		page,
		admin,
	} ) => {
		const query = addQueryArgs( '', { mode: 'list' } ).slice( 1 );
		await admin.visitAdminPage( 'upload.php', query );

		// There should be one attachment present overall.
		await expect(
			page.locator( '#the-list > tr:not(.no-items)' )
		).toHaveCount( 1 );

		const tagFilters = page.locator( '#attachment-attachment_tag-filter' );
		const filterButton = page.locator( '#post-query-submit' );

		// Selecting a tag should lead to no attachments being visible.
		await tagFilters.selectOption( tags[ 'Test Tag 1' ].slug );
		await filterButton.click();
		await expect(
			page.locator( '#the-list > tr:not(.no-items)' )
		).toHaveCount( 0 );

		// Selecting 'all' should lead to the attachments being visible again.
		await tagFilters.selectOption( { label: 'All Tags' } );
		await filterButton.click();
		await expect(
			page.locator( '#the-list > tr:not(.no-items)' )
		).toHaveCount( 1 );
	} );
} );
