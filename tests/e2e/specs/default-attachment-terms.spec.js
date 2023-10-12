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

test.describe( 'Default attachment terms', () => {
	let defaultCategory;

	test.beforeAll( async ( { termUtils } ) => {
		defaultCategory = await termUtils.createAttachmentCategory( {
			name: 'Default Cat',
		} );

		await termUtils.createAttachmentCategory( {
			name: 'Another Cat',
		} );
	} );

	test.afterAll( async ( { requestUtils, termUtils } ) => {
		// Reset default attachment category to none, so that it does not prevent deletion.
		await requestUtils.updateSiteSettings( {
			default_attachment_category: 0,
		} );

		await termUtils.deleteAllAttachmentCategories();
		await requestUtils.deleteAllMedia();

		defaultCategory = undefined;
	} );

	test( 'Setting default attachment category leads to it being automatically assigned to new attachment', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await admin.visitAdminPage( 'options-writing.php' );

		const defaultCategorySelector = page.locator(
			'#default_attachment_category'
		);
		await expect( defaultCategorySelector ).toBeVisible();
		await expect( defaultCategorySelector.locator( 'option' ) ).toHaveCount(
			3
		); // 2 categories, plus 'None'.

		// Select a default category and then submit to save the option.
		await defaultCategorySelector.selectOption( `${ defaultCategory.id }` );
		await page.locator( '#submit' ).click();

		// Ensure the selected category was indeed saved as default category.
		await expect(
			page.locator( '#default_attachment_category' )
		).toHaveValue( `${ defaultCategory.id }` );

		// Upload a new attachment and load its attachment details view.
		const filename = '1024x768_e2e_test_image_size.jpeg';
		const filepath = path.join( './tests/assets', filename );
		const attachment = await requestUtils.uploadMedia( filepath );
		const query = addQueryArgs( '', { item: attachment.id } ).slice( 1 );
		await admin.visitAdminPage( 'upload.php', query );

		// Ensure the newly uploaded attachment automatically has the default category assigned.
		const categoryAssignment = page.locator(
			'#attachment-details-two-column-taxonomy-attachment_category-terms'
		);
		await expect( categoryAssignment ).toHaveValues( [
			`${ defaultCategory.id }`,
		] );
	} );
} );
