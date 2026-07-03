/**
 * Load external browser runtime libraries without bundling large WASM assets.
 */

import { normalizePhonemeString } from './text-cleaner';

// 1.27.x ort.min.js pulls the JSEP worker (ort-wasm-simd-threaded.jsep.*), which has
// been missing or 404 on jsDelivr. 1.20.1 is pinned for stable CDN + WASM-only inference.
const ONNX_VERSION = '1.20.1';
const ONNX_CDN_BASES = [
	`https://cdn.jsdelivr.net/npm/onnxruntime-web@${ ONNX_VERSION }/dist`,
	`https://unpkg.com/onnxruntime-web@${ ONNX_VERSION }/dist`,
];
/**
 * Resolve the bundled eSpeak-NG module URL from editor settings.
 *
 * @return {string}
 */
function getEspeakModuleUrl() {
	const url = window.postToSpeechSettings?.espeakModuleUrl;

	if ( ! url ) {
		throw new Error(
			'eSpeak-NG is not configured. Reinstall the plugin or run npm run build in the plugin directory.'
		);
	}

	return url;
}

/**
 * Point ONNX Runtime Web at CDN-hosted WASM/worker files.
 *
 * @param {typeof import('onnxruntime-web')} ort ONNX Runtime global.
 * @param {string}                             cdnBase CDN dist directory (no trailing slash).
 */
function configureOrtEnv( ort, cdnBase ) {
	// Workers resolve WASM relative to the page unless wasmPaths is set explicitly.
	ort.env.wasm.wasmPaths = `${ cdnBase }/`;
	// wp-admin does not send COOP/COEP, so disable threaded WASM (needs SharedArrayBuffer).
	ort.env.wasm.numThreads = 1;
}

/**
 * Load ONNX Runtime Web from CDN.
 *
 * @return {Promise<typeof import('onnxruntime-web')>}
 */
export async function loadOnnxRuntime() {
	if ( window.ort ) {
		configureOrtEnv( window.ort, ONNX_CDN_BASES[ 0 ] );
		return window.ort;
	}

	let lastError = null;

	for ( const cdnBase of ONNX_CDN_BASES ) {
		try {
			await loadScript( `${ cdnBase }/ort.min.js` );

			if ( ! window.ort ) {
				throw new Error( 'ONNX Runtime Web failed to load.' );
			}

			configureOrtEnv( window.ort, cdnBase );

			return window.ort;
		} catch ( error ) {
			lastError = error;
		}
	}

	throw lastError || new Error( 'ONNX Runtime Web failed to load from CDN.' );
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
				getEspeakModuleUrl()
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
