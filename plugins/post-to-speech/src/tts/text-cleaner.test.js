import {
	basicEnglishTokenize,
	chunkText,
	normalizePhonemeString,
	phonemeStringToTokens,
} from './text-cleaner';

describe( 'normalizePhonemeString', () => {
	it( 'removes zero-width joiners from eSpeak output', () => {
		const raw = 'ɹˈa\u200dɪɾɪŋ';

		expect( normalizePhonemeString( raw ) ).toBe( 'ɹˈaɪɾɪŋ' );
	} );

	it( 'removes combining tie bars', () => {
		expect( normalizePhonemeString( 'a\u0361ɪ' ) ).toBe( 'aɪ' );
	} );
} );

describe( 'basicEnglishTokenize', () => {
	it( 'keeps IPA words intact after normalization', () => {
		const phonemes = normalizePhonemeString( 'ɹˈa\u200dɪɾɪŋ' );

		expect( basicEnglishTokenize( phonemes ) ).toEqual( [ 'ɹˈaɪɾɪŋ' ] );
	} );
} );

describe( 'phonemeStringToTokens', () => {
	it( 'wraps phoneme ids with start and end tokens', () => {
		const tokens = phonemeStringToTokens( 'ɹˈaɪɾɪŋ' );

		expect( tokens[ 0 ] ).toBe( 0 );
		expect( tokens[ tokens.length - 1 ] ).toBe( 0 );
		expect( tokens[ tokens.length - 2 ] ).toBe( 10 );
		expect( tokens.length ).toBeGreaterThan( 3 );
	} );

	it( 'throws when phonemization is empty', () => {
		expect( () => phonemeStringToTokens( '   ' ) ).toThrow(
			'Phonemization produced no usable tokens'
		);
	} );
} );

describe( 'chunkText', () => {
	it( 'returns a single chunk for short text', () => {
		expect( chunkText( 'Hello world.' ) ).toEqual( [ 'Hello world.' ] );
	} );

	it( 'splits long text by sentence boundaries', () => {
		const longText = `${ 'Word. '.repeat( 40 ) }End.`;
		const chunks = chunkText( longText, 80 );

		expect( chunks.length ).toBeGreaterThan( 1 );
		chunks.forEach( ( chunk ) => {
			expect( chunk.length ).toBeLessThanOrEqual( 80 );
		} );
	} );
} );
