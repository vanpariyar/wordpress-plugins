/**
 * Load external browser runtime libraries without bundling large WASM assets.
 */

/**
 * Editor settings object from PHP.
 *
 * @return {Record<string, unknown>|undefined} Settings object.
 */
function getSettings() {
	return window.sahajanandPostToSpeechSettings;
}

/**
 * Ensure bundled runtime assets are available before browser TTS.
 *
 * @return {Promise<void>}
 */
export async function ensureRuntimeWasm() {
	if ( getSettings()?.runtimeWasmInstalled || window.KittenTTSWeb ) {
		return;
	}

	throw new Error(
		'Browser runtime files are missing from the plugin. Reinstall Sahajanand Post to Speech or run npm run copy:vendor in the plugin directory.'
	);
}

let kittenTtsPromise = null;

/**
 * Load KittenTTS Web SDK.
 *
 * @return {Promise<typeof window.KittenTTSWeb>} KittenTTSWeb namespace.
 */
export async function loadKittenTTSWeb() {
	if ( window.KittenTTSWeb ) {
		return window.KittenTTSWeb;
	}

	if ( ! kittenTtsPromise ) {
		kittenTtsPromise = ( async () => {
			const settings = getSettings();
			const scriptUrl =
				settings?.kittenTtsScriptUrl ||
				'/wp-content/plugins/sahajanand-post-to-speech/assets/vendor/kittentts/kittentts-web.js';

			await loadScript( scriptUrl );

			if ( ! window.KittenTTSWeb ) {
				throw new Error( 'Failed to initialize KittenTTS Web SDK.' );
			}

			return window.KittenTTSWeb;
		} )();
	}

	return kittenTtsPromise;
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

			existing.addEventListener( 'load', () => resolve(), {
				once: true,
			} );
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
