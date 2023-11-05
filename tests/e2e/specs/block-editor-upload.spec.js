/**
 * External dependencies
 */
const path = require( 'path' );
const fs = require( 'fs/promises' );
const os = require( 'os' );

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
	imageBlockUtils: async ( { page }, use ) => {
		await use( new ImageBlockUtils( { page } ) );
	},
} );

test.describe( 'Block editor upload', () => {
	test.beforeAll( async ( { termUtils } ) => {
		// Create a category and tag, just so that attachment terms are available.
		await termUtils.createAttachmentCategory( {
			name: 'Test Category',
		} );
		await termUtils.createAttachmentTag( {
			name: 'Test Tag',
		} );
	} );

	test.afterAll( async ( { requestUtils, termUtils } ) => {
		await termUtils.deleteAllAttachmentCategories();
		await termUtils.deleteAllAttachmentTags();
		await requestUtils.deleteAllMedia();
		await requestUtils.deleteAllPosts();
	} );

	test( 'Uploading an image within the block editor image block works as expected', async ( {
		page,
		admin,
		editor,
		imageBlockUtils,
	} ) => {
		await admin.createNewPost();

		// Insert a new image block and upload an image.
		await editor.insertBlock( { name: 'core/image' } );
		const imageBlock = editor.canvas.locator(
			'role=document[name="Block: Image"i]'
		);
		await imageBlockUtils.upload(
			imageBlock.locator( 'data-testid=form-file-upload-input' )
		);

		// Click "Replace" button in block toolbar, then open the media library modal.
		await imageBlock.click();
		const blockPopover = page.locator( '.block-editor-block-popover' );
		expect( blockPopover ).toBeVisible();
		await blockPopover.getByRole( 'button', { name: 'Replace' } ).click();
		const replacePopover = page.locator(
			'.block-editor-media-replace-flow__media-upload-menu'
		);
		expect( replacePopover ).toBeVisible();
		await replacePopover
			.getByRole( 'menuitem' )
			.filter( { hasText: 'Open Media Library' } )
			.click();
		await page
			.locator( '.media-modal' )
			.getByRole( 'tab', { name: 'Media Library' } )
			.click();

		// Run a basic check to ensure the single category and tag are present.
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
} );

class ImageBlockUtils {
	constructor( { page } ) {
		this.page = page;

		this.TEST_IMAGE_FILE_NAME = '1024x768_e2e_test_image_size.jpeg';
		this.TEST_IMAGE_FILE_PATH = path.join(
			'./tests/assets',
			this.TEST_IMAGE_FILE_NAME
		);
	}

	async upload( inputElement ) {
		const tmpDirectory = await fs.mkdtemp(
			path.join( os.tmpdir(), 'at-test-image-' )
		);
		const tmpFileName = path.join(
			tmpDirectory,
			this.TEST_IMAGE_FILE_NAME
		);
		await fs.copyFile( this.TEST_IMAGE_FILE_PATH, tmpFileName );

		await inputElement.setInputFiles( tmpFileName );

		return this.TEST_IMAGE_FILE_NAME;
	}
}
