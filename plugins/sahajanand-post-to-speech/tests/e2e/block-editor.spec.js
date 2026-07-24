/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const BLOCK_NAME = 'sahajanand-post-to-speech/post-audio';

test.describe( 'Sahajanand Post to Speech block', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'can be inserted and shows generate controls', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( { name: BLOCK_NAME } );

		const blocks = await editor.getBlocks();
		expect( blocks ).toHaveLength( 1 );
		expect( blocks[ 0 ].name ).toBe( BLOCK_NAME );

		const block = editor.canvas.locator( '.sahajanand-speech-block' );
		await expect( block ).toBeVisible();
		await expect(
			block.getByRole( 'button', { name: 'Generate audio' } )
		).toBeVisible();
		await expect( block.getByLabel( 'Post text preview' ) ).toBeVisible();

		await editor.openDocumentSettingsSidebar();
		await expect(
			page.getByRole( 'button', { name: 'Audio source' } )
		).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'Voice settings' } )
		).toBeVisible();
	} );

	test( 'switches between post content and custom text sources', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( { name: BLOCK_NAME } );

		const block = editor.canvas.locator( '.sahajanand-speech-block' );
		await block.click();

		await editor.openDocumentSettingsSidebar();
		await page.getByLabel( 'Text source' ).selectOption( 'custom' );

		await expect( block.getByLabel( 'Custom text' ) ).toBeVisible();
		await expect( block.getByLabel( 'Post text preview' ) ).toBeHidden();

		await block.getByLabel( 'Custom text' ).fill( 'Hello from e2e.' );

		const blocks = await editor.getBlocks();
		expect( blocks[ 0 ].attributes.textSource ).toBe( 'custom' );
		expect( blocks[ 0 ].attributes.text ).toBe( 'Hello from e2e.' );
	} );

	test( 'shows an error when generating without text', async ( {
		editor,
	} ) => {
		await editor.insertBlock( {
			name: BLOCK_NAME,
			attributes: {
				textSource: 'custom',
				text: '',
			},
		} );

		const block = editor.canvas.locator( '.sahajanand-speech-block' );
		await block.getByRole( 'button', { name: 'Generate audio' } ).click();

		await expect(
			block.getByText(
				'Please enter custom text before generating audio.'
			)
		).toBeVisible();
	} );

	test( 'previews post content as the speech source', async ( { editor } ) => {
		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: {
				content: 'This post will be read aloud.',
			},
		} );
		await editor.insertBlock( { name: BLOCK_NAME } );

		const block = editor.canvas.locator( '.sahajanand-speech-block' );
		await expect( block.getByLabel( 'Post text preview' ) ).toHaveValue(
			'This post will be read aloud.'
		);
	} );
} );
