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

test.describe( 'Attachment modal terms', () => {
	let category;
	let tag;
	let attachment;

	test.beforeAll( async ( { requestUtils, termUtils } ) => {
		category = await termUtils.createAttachmentCategory( {
			name: 'Foo Cat',
		} );

		tag = await termUtils.createAttachmentTag( {
			name: 'Foo Tag',
		} );

		const filename = '1024x768_e2e_test_image_size.jpeg';
		const filepath = path.join( './tests/assets', filename );
		attachment = await requestUtils.uploadMedia( filepath );
	} );

	test.afterAll( async ( { requestUtils, termUtils } ) => {
		await termUtils.deleteAllAttachmentCategories();
		await termUtils.deleteAllAttachmentTags();
		await requestUtils.deleteAllMedia();

		category = undefined;
		tag = undefined;
		attachment = undefined;
	} );

	test( 'Attachment modal term selectors are present with terms', async ( {
		page,
		admin,
	} ) => {
		const query = addQueryArgs( '', { item: attachment.id } ).slice( 1 );
		await admin.visitAdminPage( 'upload.php', query );

		const categoryAssignment = page.locator(
			'#attachment-details-two-column-taxonomy-attachment_category-terms'
		);
		await expect( categoryAssignment ).toBeVisible();
		await expect( categoryAssignment.locator( 'option' ) ).toHaveCount( 1 ); // 1 category.

		const tagAssignment = page.locator(
			'#attachment-details-two-column-taxonomy-attachment_tag-terms'
		);
		await expect( tagAssignment ).toBeVisible();
		await expect( tagAssignment.locator( 'option' ) ).toHaveCount( 1 ); // 1 tag.
	} );

	test( 'Attachment modal category selector allows assigning and unassigning category', async ( {
		page,
		admin,
		termUtils,
	} ) => {
		const query = addQueryArgs( '', { item: attachment.id } ).slice( 1 );
		await admin.visitAdminPage( 'upload.php', query );

		const categoryAssignment = page.locator(
			'#attachment-details-two-column-taxonomy-attachment_category-terms'
		);

		// Select the category and ensure it is assigned to the attachment afterwards.
		await Promise.all( [
			categoryAssignment.selectOption( [ `${ category.id }` ] ),
			page.waitForResponse( '/wp-admin/admin-ajax.php' ),
		] );
		await expect(
			await termUtils.getAttachmentTerms(
				attachment.id,
				'attachment_category'
			)
		).toEqual( [ category.id ] );

		// Unselect the category and ensure it is no longer assigned to the attachment afterwards.
		await Promise.all( [
			categoryAssignment.selectOption( [] ),
			page.waitForResponse( '/wp-admin/admin-ajax.php' ),
		] );
		await expect(
			await termUtils.getAttachmentTerms(
				attachment.id,
				'attachment_category'
			)
		).toEqual( [] );
	} );

	test( 'Attachment modal tag selector allows assigning and unassigning tag', async ( {
		page,
		admin,
		termUtils,
	} ) => {
		const query = addQueryArgs( '', { item: attachment.id } ).slice( 1 );
		await admin.visitAdminPage( 'upload.php', query );

		const tagAssignment = page.locator(
			'#attachment-details-two-column-taxonomy-attachment_tag-terms'
		);

		// Select the tag and ensure it is assigned to the attachment afterwards.
		await Promise.all( [
			tagAssignment.selectOption( [ tag.slug ] ),
			page.waitForResponse( '/wp-admin/admin-ajax.php' ),
		] );
		await expect(
			await termUtils.getAttachmentTerms(
				attachment.id,
				'attachment_tag'
			)
		).toEqual( [ tag.id ] );

		// Unselect the tag and ensure it is no longer assigned to the attachment afterwards.
		// TODO: This does not correctly unselect the single tag, its slug is still sent to admin-ajax.php.
		await Promise.all( [
			tagAssignment.selectOption( [] ),
			page.waitForResponse( '/wp-admin/admin-ajax.php' ),
		] );
		await expect(
			await termUtils.getAttachmentTerms(
				attachment.id,
				'attachment_tag'
			)
		).toEqual( [] );
	} );
} );