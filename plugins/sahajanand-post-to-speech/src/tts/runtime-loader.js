/**
 * Load external browser runtime libraries without bundling large WASM assets.
 */

import { normalizePhonemeString } from './text-cleaner';

/**
 * Editor settings object from PHP.
 *
 * @return {Record<string, unknown>|undefined}
 */
function getSettings() {
	return window.sahajanandPostToSpeechSettings;
}

/**
 * Resolve the bundled eSpeak-NG module URL from editor settings.
 *
 * @return {string}
 */
function getEspeakModuleUrl() {
	const url = getSettings()?.espeakModuleUrl;

	if ( ! url ) {
		throw new Error(
			'eSpeak-NG is not configured. Reinstall the plugin or run npm run build in the plugin directory.'
		);
	}

	return url;
}

/**
 * Resolve the eSpeak-NG WASM URL (uploads, plugin vendor, or empty).
 *
 * @return {string}
 */
function getEspeakWasmUrl() {
	return getSettings()?.espeakWasmUrl || '';
}

/**
 * Point ONNX Runtime Web at local WASM/worker files.
 *
 * @param {typeof import('onnxruntime-web')} ort ONNX Runtime global.
 * @param {string}                             wasmBase Local directory (no trailing slash).
 */
function configureOrtEnv( ort, wasmBase ) {
	ort.env.wasm.wasmPaths = `${ wasmBase }/`;
	ort.env.wasm.numThreads = 1;
}

/**
 * Ensure runtime WASM is installed on the server (uploads) before browser TTS.
 *
 * @return {Promise<void>}
 */
export async function ensureRuntimeWasm() {
	const settings = getSettings();

	if ( settings?.runtimeWasmInstalled ) {
		return;
	}

	const apiFetch = ( await import( '@wordpress/api-fetch' ) ).default;
	const response = await apiFetch( {
		path: '/sahajanand-post-to-speech/v1/prepare-runtime',
		method: 'POST',
	} );

	window.sahajanandPostToSpeechSettings = {
		...settings,
		...response,
	};

	if ( ! window.sahajanandPostToSpeechSettings?.runtimeWasmInstalled ) {
		throw new Error(
			'Browser runtime files are not installed. Check your server can write to uploads and can reach the runtime-assets URL on GitHub.'
		);
	}
}

/**
 * Load ONNX Runtime Web locally.
 *
 * @return {Promise<typeof import('onnxruntime-web')>}
 */
export async function loadOnnxRuntime() {
	const settings = getSettings();
	const scriptUrl = settings?.onnxScriptUrl;
	const wasmBase = settings?.onnxWasmUrl;

	if ( ! scriptUrl || ! wasmBase ) {
		throw new Error( 'ONNX Runtime is not configured.' );
	}

	if ( window.ort ) {
		configureOrtEnv( window.ort, wasmBase );
		return window.ort;
	}

	await loadScript( scriptUrl );

	if ( ! window.ort ) {
		throw new Error( 'ONNX Runtime Web failed to load.' );
	}

	configureOrtEnv( window.ort, wasmBase );

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
	const wasmUrl = getEspeakWasmUrl();
	const instance = await ESpeakNg( {
		...( wasmUrl
			? {
					locateFile: ( file ) =>
						file === 'espeak-ng.wasm' ? wasmUrl : file,
			  }
			: {} ),
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
