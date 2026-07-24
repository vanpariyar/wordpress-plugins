/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const BLOCK_NAME = 'sahajanand-post-to-speech/post-audio';
const SPEECH_TEXT = 'Hello from the Sahajanand e2e generate test.';

async function setupApiMock( requestUtils ) {
	await requestUtils.rest( {
		method: 'POST',
		path: '/sahajanand-post-to-speech-e2e/v1/setup-api-mock',
	} );
}

async function teardownApiMock( requestUtils ) {
	await requestUtils.rest( {
		method: 'POST',
		path: '/sahajanand-post-to-speech-e2e/v1/teardown-api-mock',
	} );
}

async function insertBlockAndGenerate( { admin, editor } ) {
	await admin.createNewPost( {
		title: 'Generated speech post',
	} );

	await editor.insertBlock( {
		name: BLOCK_NAME,
		attributes: {
			textSource: 'custom',
			text: SPEECH_TEXT,
		},
	} );

	const block = editor.canvas.locator( '.sahajanand-speech-block' );
	await expect( block.getByLabel( 'Custom text' ) ).toHaveValue( SPEECH_TEXT );

	await block.getByRole( 'button', { name: 'Generate audio' } ).click();

	const player = block.locator( '.sahajanand-speech-block__player audio' );
	await expect( player ).toBeVisible( { timeout: 30_000 } );
	await expect( player ).toHaveAttribute( 'src', /\/wp-content\/uploads\// );

	return { block, player };
}

test.describe( 'Audio generation', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await setupApiMock( requestUtils );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await teardownApiMock( requestUtils );
	} );

	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
	} );

	test( 'inserts the block and generates audio in the editor', async ( {
		admin,
		editor,
	} ) => {
		const { player } = await insertBlockAndGenerate( { admin, editor } );

		const blocks = await editor.getBlocks();
		const speechBlock = blocks.find( ( block ) => block.name === BLOCK_NAME );

		expect( speechBlock ).toBeTruthy();
		expect( speechBlock.attributes.audioUrl ).toMatch(
			/\/wp-content\/uploads\//
		);
		expect( speechBlock.attributes.attachmentId ).toBeGreaterThan( 0 );
		await expect( player ).toHaveAttribute( 'controls', '' );
	} );

	test( 'shows generated audio on the frontend after publish', async ( {
		admin,
		editor,
		page,
	} ) => {
		const { player } = await insertBlockAndGenerate( { admin, editor } );
		const editorAudioSrc = await player.getAttribute( 'src' );

		const postId = await editor.publishPost();
		expect( postId ).toBeTruthy();

		await page.goto( `/?p=${ postId }` );

		const frontendPlayer = page.locator( '.sahajanand-speech-block audio' );
		await expect( frontendPlayer ).toBeVisible();
		await expect( frontendPlayer ).toHaveAttribute( 'src', editorAudioSrc );
		await expect( frontendPlayer ).toHaveAttribute( 'controls', '' );

		await expect( page.getByText( SPEECH_TEXT ) ).toHaveCount( 0 );
		await expect(
			page.getByRole( 'button', { name: 'Generate audio' } )
		).toHaveCount( 0 );
	} );
} );
