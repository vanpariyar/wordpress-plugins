import { normalizeModelId, normalizeVoiceId } from './engine';

describe( 'normalizeModelId', () => {
	it( 'defaults to nano-int8 when empty', () => {
		expect( normalizeModelId() ).toBe( 'nano-int8' );
		expect( normalizeModelId( '' ) ).toBe( 'nano-int8' );
	} );

	it( 'maps Hugging Face repo strings to KittenTTS model IDs', () => {
		expect( normalizeModelId( 'KittenML/kitten-tts-nano-0.8-int8' ) ).toBe(
			'nano-int8'
		);
		expect( normalizeModelId( 'KittenML/kitten-tts-nano-0.8-fp32' ) ).toBe(
			'nano'
		);
		expect( normalizeModelId( 'KittenML/kitten-tts-micro-0.8' ) ).toBe(
			'micro'
		);
		expect( normalizeModelId( 'KittenML/kitten-tts-mini-0.8' ) ).toBe(
			'mini'
		);
	} );

	it( 'accepts short model names directly', () => {
		expect( normalizeModelId( 'nano-int8' ) ).toBe( 'nano-int8' );
		expect( normalizeModelId( 'nano' ) ).toBe( 'nano' );
		expect( normalizeModelId( 'micro' ) ).toBe( 'micro' );
		expect( normalizeModelId( 'mini' ) ).toBe( 'mini' );
	} );
} );

describe( 'normalizeVoiceId', () => {
	it( 'normalizes title-cased voice names to lowercase', () => {
		expect( normalizeVoiceId( 'Bella' ) ).toBe( 'bella' );
		expect( normalizeVoiceId( 'Jasper' ) ).toBe( 'jasper' );
		expect( normalizeVoiceId( 'Luna' ) ).toBe( 'luna' );
		expect( normalizeVoiceId( 'Bruno' ) ).toBe( 'bruno' );
		expect( normalizeVoiceId( 'Rosie' ) ).toBe( 'rosie' );
		expect( normalizeVoiceId( 'Hugo' ) ).toBe( 'hugo' );
		expect( normalizeVoiceId( 'Kiki' ) ).toBe( 'kiki' );
		expect( normalizeVoiceId( 'Leo' ) ).toBe( 'leo' );
	} );

	it( 'handles unknown or empty voices gracefully by defaulting to bella', () => {
		expect( normalizeVoiceId() ).toBe( 'bella' );
		expect( normalizeVoiceId( 'UnknownVoice' ) ).toBe( 'bella' );
	} );
} );
