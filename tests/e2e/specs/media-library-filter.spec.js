/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Media library filter', () => {
	test( 'Media library filter dropdowns are present', async ( {
		page,
		admin,
	} ) => {
		await admin.visitAdminPage( 'upload.php' );

		await expect(
			page.locator( '#media-attachment-attachment-category-filters' )
		).toBeVisible();
		await expect(
			page.locator( '#media-attachment-attachment-tag-filters' )
		).toBeVisible();
	} );
} );
