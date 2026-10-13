/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Settings page', () => {
	test( 'loads Sahajanand Post to Speech settings', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage(
			'options-general.php',
			'page=sahajanand-post-to-speech'
		);

		await expect(
			page.getByRole( 'heading', {
				name: 'Sahajanand Post to Speech',
				level: 1,
			} )
		).toBeVisible();

		await expect(
			page.locator( '#sahajanand_post_to_speech_generation_mode' )
		).toBeVisible();

		await expect(
			page.getByRole( 'button', { name: 'Save Changes' } )
		).toBeVisible();
	} );

	test( 'defaults to browser generation mode', async ( { admin, page } ) => {
		await admin.visitAdminPage(
			'options-general.php',
			'page=sahajanand-post-to-speech'
		);

		await expect(
			page.locator( '#sahajanand_post_to_speech_generation_mode' )
		).toHaveValue( 'browser' );
	} );
} );
