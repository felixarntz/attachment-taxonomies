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

test.describe( 'Media library post modal', () => {
	let tags;
	let attachments;

	test.beforeAll( async ( { requestUtils, termUtils } ) => {
		tags = {};
		tags[ 'Test Tag 1' ] = await termUtils.createAttachmentTag( {
			name: 'Test Tag 1',
		} );
		tags[ 'Test Tag 2' ] = await termUtils.createAttachmentTag( {
			name: 'Test Tag 2',
		} );

		const filename = '1024x768_e2e_test_image_size.jpeg';
		const filepath = path.join( './tests/assets', filename );

		attachments = {};
		attachments.withTag1 = await requestUtils.uploadMedia( filepath );
		attachments.withTag2 = await requestUtils.uploadMedia( filepath );
		attachments.noTerms = await requestUtils.uploadMedia( filepath );

		// Assign "Test Tag 1" to the first attachment.
		await termUtils.assignAttachmentTerms( attachments.withTag1.id, [
			{
				taxonomy: 'attachment_tag',
				id: tags[ 'Test Tag 1' ].id,
			},
		] );

		// Assign "Test Tag 2" to the second attachment.
		await termUtils.assignAttachmentTerms( attachments.withTag2.id, [
			{
				taxonomy: 'attachment_tag',
				id: tags[ 'Test Tag 2' ].id,
			},
		] );
	} );

	test.afterAll( async ( { requestUtils, termUtils } ) => {
		await termUtils.deleteAllAttachmentTags();
		await requestUtils.deleteAllMedia();
		await requestUtils.deleteAllPosts();

		tags = undefined;
		attachments = undefined;
	} );

	test( 'Post with gallery shortcode and tag attribute results in correct frontend output', async ( {
		page,
		admin,
		editor,
	} ) => {
		await admin.createNewPost();

		// Insert a paragraph with gallery shortcode for the first tag.
		await editor.insertBlock( {
			name: 'core/paragraph',
		} );
		await page.keyboard.type(
			`[gallery attachment_tag="${ tags[ 'Test Tag 1' ].slug }" size="full" link="none"]`
		);

		// Insert another paragraph with gallery shortcode for the second tag.
		await editor.insertBlock( {
			name: 'core/paragraph',
		} );
		await page.keyboard.type(
			`[gallery attachment_tag="${ tags[ 'Test Tag 2' ].slug }" size="medium" link="none"]`
		);

		const postId = await editor.publishPost();
		if ( postId ) {
			await page.goto( `/?p=${ postId }` );
		} else {
			await page.click(
				'role=region[name="Editor publish"] >> role=link[name="View Post"i]'
			);
		}

		// Check that first gallery contains expected image.
		const gallery1 = page.locator( '#gallery-1' );
		await expect( gallery1 ).toBeVisible();
		await expect( gallery1.locator( 'img.attachment-full' ) ).toHaveCount(
			1
		);
		await expect(
			gallery1.locator( 'img.attachment-full' )
		).toHaveAttribute( 'src', attachments.withTag1.source_url );

		// Check that second gallery contains expected image.
		const gallery2 = page.locator( '#gallery-2' );
		await expect( gallery2 ).toBeVisible();
		await expect( gallery2.locator( 'img.attachment-medium' ) ).toHaveCount(
			1
		);
		await expect(
			gallery2.locator( 'img.attachment-medium' )
		).toHaveAttribute(
			'src',
			attachments.withTag2.media_details.sizes.medium.source_url
		);
	} );
} );
