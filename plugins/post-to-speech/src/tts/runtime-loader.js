/**
 * Load external browser runtime libraries without bundling large WASM assets.
 */

import { normalizePhonemeString } from './text-cleaner';

const ONNX_VERSION = '1.27.0';
const ONNX_CDN = `https://cdn.jsdelivr.net/npm/onnxruntime-web@${ ONNX_VERSION }/dist`;
const ESPEAK_MODULE = 'https://cdn.jsdelivr.net/npm/espeak-ng@1.0.2/dist/espeak-ng.js';

/**
 * Load ONNX Runtime Web from CDN.
 *
 * @return {Promise<typeof import('onnxruntime-web')>}
 */
export async function loadOnnxRuntime() {
	if ( window.ort ) {
		window.ort.env.wasm.wasmPaths = `${ ONNX_CDN }/`;
		return window.ort;
	}

	await loadScript( `${ ONNX_CDN }/ort.min.js` );

	if ( ! window.ort ) {
		throw new Error( 'ONNX Runtime Web failed to load.' );
	}

	window.ort.env.wasm.wasmPaths = `${ ONNX_CDN }/`;

	return window.ort;
}

/**
 * Load eSpeak-NG and phonemize text to IPA.
 *
 * @param {string} text Input text.
 * @return {Promise<string>}
 */
export async function phonemizeWithEspeak( text ) {
	const ESpeakNg = await loadESpeakNgFactory();
	const instance = await ESpeakNg( {
		arguments: [
			'--phonout',
			'phonemes_out',
			'--sep=',
			'-q',
			'-b=1',
			'--ipa=3',
			'-v',
			'en-us',
			text,
		],
	} );

	return normalizePhonemeString(
		instance.FS.readFile( 'phonemes_out', { encoding: 'utf8' } ).trim()
	);
}

let espeakFactoryPromise = null;

/**
 * Load the eSpeak-NG factory function once.
 *
 * @return {Promise<Function>}
 */
async function loadESpeakNgFactory() {
	if ( ! espeakFactoryPromise ) {
		espeakFactoryPromise = ( async () => {
			if ( window.ESpeakNG ) {
				return window.ESpeakNG;
			}

			// espeak-ng is an ES module (uses import.meta) — must use dynamic import, not <script>.
			const module = await import(
				/* webpackIgnore: true */
				ESPEAK_MODULE
			);
			const factory = module.default || module.ESpeakNG;

			if ( typeof factory !== 'function' ) {
				throw new Error( 'eSpeak-NG module did not export a factory function.' );
			}

			window.ESpeakNG = factory;

			return factory;
		} )();
	}

	return espeakFactoryPromise;
}

/**
 * Inject a script tag and wait for it to load.
 *
 * @param {string} src Script URL.
 * @return {Promise<void>}
 */
function loadScript( src ) {
	return new Promise( ( resolve, reject ) => {
		const existing = document.querySelector( `script[src="${ src }"]` );

		if ( existing ) {
			if ( existing.dataset.loaded === 'true' ) {
				resolve();
				return;
			}

			existing.addEventListener( 'load', () => resolve(), { once: true } );
			existing.addEventListener(
				'error',
				() => reject( new Error( `Failed to load ${ src }` ) ),
				{ once: true }
			);
			return;
		}

		const script = document.createElement( 'script' );
		script.src = src;
		script.async = true;
		script.onload = () => {
			script.dataset.loaded = 'true';
			resolve();
		};
		script.onerror = () => reject( new Error( `Failed to load ${ src }` ) );
		document.head.appendChild( script );
	} );
}
