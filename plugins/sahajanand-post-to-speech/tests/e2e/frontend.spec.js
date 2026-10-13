/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const SAMPLE_AUDIO_URL =
	'https://upload.wikimedia.org/wikipedia/commons/c/c8/Example.ogg';

test.describe( 'Frontend audio player', () => {
	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
	} );

	test( 'renders only the audio player when audioUrl is set', async ( {
		page,
		requestUtils,
	} ) => {
		const post = await requestUtils.createPost( {
			title: 'Speech block frontend',
			status: 'publish',
			content: [
				'<!-- wp:sahajanand-post-to-speech/post-audio {"audioUrl":"' +
					SAMPLE_AUDIO_URL +
					'","textSource":"custom","text":"Secret text that must not show"} -->',
				'<figure class="wp-block-sahajanand-post-to-speech-post-audio sahajanand-speech-block"><audio controls src="' +
					SAMPLE_AUDIO_URL +
					'"></audio></figure>',
				'<!-- /wp:sahajanand-post-to-speech/post-audio -->',
			].join( '\n' ),
		} );

		await page.goto( `/?p=${ post.id }` );

		const player = page.locator( '.sahajanand-speech-block audio' );
		await expect( player ).toBeVisible();
		await expect( player ).toHaveAttribute( 'src', SAMPLE_AUDIO_URL );
		await expect( player ).toHaveAttribute( 'controls', '' );

		await expect(
			page.getByText( 'Secret text that must not show' )
		).toHaveCount( 0 );
		await expect(
			page.getByRole( 'button', { name: 'Generate audio' } )
		).toHaveCount( 0 );
	} );

	test( 'outputs nothing on the frontend when audioUrl is empty', async ( {
		page,
		requestUtils,
	} ) => {
		const post = await requestUtils.createPost( {
			title: 'Speech block without audio',
			status: 'publish',
			content:
				'<!-- wp:sahajanand-post-to-speech/post-audio {"textSource":"custom","text":"Only in the editor"} /-->',
		} );

		await page.goto( `/?p=${ post.id }` );

		await expect( page.locator( '.sahajanand-speech-block' ) ).toHaveCount(
			0
		);
		await expect( page.getByText( 'Only in the editor' ) ).toHaveCount( 0 );
	} );
} );
