/**
 * External dependencies
 */
const { defineConfig } = require( '@playwright/test' );
/**
 * WordPress dependencies
 */
const baseConfig = require( '@wordpress/scripts/config/playwright.config.js' );

module.exports = defineConfig( {
	...baseConfig,
	testDir: './tests/e2e',
} );
