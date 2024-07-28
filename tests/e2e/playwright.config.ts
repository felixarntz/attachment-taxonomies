/**
 * External dependencies
 */
import { defineConfig } from '@playwright/test';

/**
 * WordPress dependencies
 */
const baseConfig = require( '@wordpress/scripts/config/playwright.config' );

const config = defineConfig( {
	...baseConfig,
	use: {
		...baseConfig.use,
		trace: 'retain-on-failure',
	},
} );

export default config;
