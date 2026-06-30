const PAD = '$';
const PUNCTUATION = ';:,.!?¡¿—…"«»"" ';
const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
const LETTERS_IPA =
	'ɑɐɒæɓʙβɔɕçɗɖðʤəɘɚɛɜɝɞɟʄɡɠɢʛɦɧħɥʜɨɪʝɭɬɫɮʟɱɯɰŋɳɲɴøɵɸθœɶʘɹɺɾɻʀʁɽʂʃʈʧʉʊʋⱱʌɣɤʍχʎʏʑʐʒʔʡʕʢǀǁǂǃˈˌːˑʼʴʰʱʲʷˠˤ˞↓↑→↗↘\'̩\'ᵻ';

const SYMBOLS = [ PAD, ...PUNCTUATION, ...LETTERS, ...LETTERS_IPA ];
const SYMBOL_INDEX = Object.fromEntries(
	SYMBOLS.map( ( symbol, index ) => [ symbol, index ] )
);

function normalizePhonemeString( phonemeString ) {
	return phonemeString
		.replace( /[\u200B-\u200D\uFEFF]/g, '' )
		.replace( /\u0361/g, '' );
}

export { normalizePhonemeString };

/**
 * Tokenize phoneme strings the same way as Python's basic_english_tokenize.
 * JS \w is ASCII-only; use Unicode letter classes so IPA groups stay intact.
 *
 * @param {string} text Phoneme string.
 * @return {string[]}
 */
export function basicEnglishTokenize( text ) {
	return text.match( /[\p{L}\p{M}\p{N}_]+|[^\p{L}\p{M}\p{N}\s_]/gu ) || [];
}

/**
 * Convert a phoneme string into model token IDs.
 *
 * @param {string} phonemeString IPA phonemes from eSpeak.
 * @return {number[]}
 */
export function phonemeStringToTokens( phonemeString ) {
	const normalized = basicEnglishTokenize( phonemeString ).join( ' ' );
	const tokens = [];

	for ( const char of normalized ) {
		if ( Object.prototype.hasOwnProperty.call( SYMBOL_INDEX, char ) ) {
			tokens.push( SYMBOL_INDEX[ char ] );
		}
	}

	tokens.unshift( 0 );
	tokens.push( 10, 0 );

	if ( tokens.length < 4 ) {
		throw new Error(
			'Phonemization produced no usable tokens. Check eSpeak output.'
		);
	}

	return tokens;
}

export function chunkText( text, maxLength = 250 ) {
	const trimmed = text.trim();

	if ( trimmed.length <= maxLength ) {
		return [ trimmed ];
	}

	const sentences = trimmed.match( /[^.!?]+[.!?]+|[^.!?]+$/g ) || [ trimmed ];
	const chunks = [];
	let current = '';

	for ( const sentence of sentences ) {
		const candidate = current ? `${ current } ${ sentence.trim() }` : sentence.trim();

		if ( candidate.length > maxLength && current ) {
			chunks.push( current.trim() );
			current = sentence.trim();
		} else {
			current = candidate;
		}
	}

	if ( current.trim() ) {
		chunks.push( current.trim() );
	}

	return chunks.length ? chunks : [ trimmed ];
}
