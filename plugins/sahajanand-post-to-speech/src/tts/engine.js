import { loadKittenTTSWeb, ensureRuntimeWasm } from './runtime-loader';

let engineInstance = null;

/**
 * Map repository or friendly name to KittenTTS model identifier.
 *
 * @param {string} [modelSetting] Model repo or identifier.
 * @return {'nano-int8'|'nano'|'micro'|'mini'} Supported KittenTTS model ID.
 */
export function normalizeModelId( modelSetting = '' ) {
	if ( ! modelSetting ) {
		return 'nano-int8';
	}

	if (
		modelSetting.includes( 'nano-0.8-int8' ) ||
		modelSetting === 'nano-int8'
	) {
		return 'nano-int8';
	}

	if ( modelSetting.includes( 'nano' ) || modelSetting === 'nano' ) {
		return 'nano';
	}

	if ( modelSetting.includes( 'micro' ) || modelSetting === 'micro' ) {
		return 'micro';
	}

	if ( modelSetting.includes( 'mini' ) || modelSetting === 'mini' ) {
		return 'mini';
	}

	return 'nano-int8';
}

/**
 * Map voice name to lowercase KittenTTS voice identifier.
 *
 * @param {string} [voice] Friendly or internal voice name.
 * @return {string} Lowercase voice identifier.
 */
export function normalizeVoiceId( voice = 'bella' ) {
	const voiceMap = {
		bella: 'bella',
		jasper: 'jasper',
		luna: 'luna',
		bruno: 'bruno',
		rosie: 'rosie',
		hugo: 'hugo',
		kiki: 'kiki',
		leo: 'leo',
	};

	const lower = String( voice ).trim().toLowerCase();
	return voiceMap[ lower ] || 'bella';
}

export class KittenTTSEngine {
	constructor( settings = {} ) {
		this.settings = settings;
		this.tts = null;
		this.activeModel = null;
		this.initPromise = null;
	}

	static getInstance( settings = {} ) {
		if ( ! engineInstance ) {
			engineInstance = new KittenTTSEngine( settings );
		} else {
			engineInstance.settings = {
				...engineInstance.settings,
				...settings,
			};
		}

		return engineInstance;
	}

	/**
	 * Initialize or reuse the KittenTTS instance.
	 *
	 * @param {string}   [modelRepo]  Target model name or repo.
	 * @param {Function} [onProgress] Progress callback.
	 * @return {Promise<object>} Active KittenTTS session.
	 */
	async getTTS( modelRepo, onProgress ) {
		const targetModel = normalizeModelId(
			modelRepo || this.settings.modelRepo
		);

		if ( this.tts && this.activeModel === targetModel ) {
			return this.tts;
		}

		if ( this.initPromise ) {
			return this.initPromise;
		}

		this.initPromise = ( async () => {
			if ( this.tts ) {
				await this.tts.dispose().catch( () => {} );
				this.tts = null;
			}

			const { KittenTTS } = await loadKittenTTSWeb();

			onProgress?.( 'Initializing KittenTTS Web SDK…' );

			const wasmBase = this.settings.onnxWasmUrl || undefined;

			const progressCallback = ( progress, info ) => {
				if ( ! onProgress ) {
					return;
				}

				const pct = Math.round( ( progress || 0 ) * 100 );

				if ( info?.stage === 'downloading' ) {
					const asset = info.asset ? ` (${ info.asset })` : '';
					onProgress( `Downloading model${ asset }… ${ pct }%` );
				} else if ( info?.stage === 'checking-cache' ) {
					onProgress( 'Checking cached model…' );
				} else if ( info?.stage === 'cached' ) {
					onProgress( 'Using cached model…' );
				} else if ( info?.stage === 'complete' ) {
					onProgress( 'Model ready…' );
				} else {
					onProgress( `Preparing speech model… ${ pct }%` );
				}
			};

			try {
				this.tts = await KittenTTS.create(
					{
						model: targetModel,
						analytics: false,
						...( wasmBase ? { ortWasmPath: wasmBase } : {} ),
					},
					progressCallback
				);
			} catch ( createErr ) {
				// If local WASM failed (e.g. server MIME type, CORS or version mismatch),
				// fallback to the SDK's built-in CDN assets.
				if ( wasmBase ) {
					this.tts = await KittenTTS.create(
						{
							model: targetModel,
							analytics: false,
						},
						progressCallback
					);
				} else {
					throw createErr;
				}
			}

			this.activeModel = targetModel;
			return this.tts;
		} )();

		try {
			return await this.initPromise;
		} finally {
			this.initPromise = null;
		}
	}

	/**
	 * Generate speech audio using KittenTTS Web SDK.
	 *
	 * @param {string}   text         Input text.
	 * @param {string}   [voice]      Voice name.
	 * @param {number}   [speed]      Speech speed.
	 * @param {Function} [onProgress] Progress callback.
	 * @return {Promise<Blob>} WAV audio blob.
	 */
	async generate( text, voice = 'Bella', speed = 1, onProgress ) {
		const tts = await this.getTTS( this.settings.modelRepo, onProgress );
		const voiceId = normalizeVoiceId( voice );
		const speedNum = Number( speed ) || 1.0;

		onProgress?.( 'Synthesizing speech…' );

		const result = await tts.generate( text, {
			voice: voiceId,
			speed: speedNum,
		} );

		const wavBytes = result.wavData();
		return new Blob( [ wavBytes ], { type: 'audio/wav' } );
	}

	/**
	 * Dispose active KittenTTS engine instance.
	 *
	 * @return {Promise<void>}
	 */
	async dispose() {
		if ( this.tts ) {
			await this.tts.dispose().catch( () => {} );
			this.tts = null;
			this.activeModel = null;
		}
	}
}

/**
 * Generate speech WAV audio blob using KittenTTS Web SDK.
 *
 * @param {string}   text         Input text.
 * @param {string}   [voice]      Voice name.
 * @param {number}   [speed]      Speed multiplier.
 * @param {Object}   [settings]   Plugin runtime settings.
 * @param {Function} [onProgress] Progress status callback.
 * @return {Promise<Blob>} Audio blob.
 */
export async function generateSpeechBlob(
	text,
	voice,
	speed,
	settings = {},
	onProgress
) {
	await ensureRuntimeWasm();

	const runtimeSettings = {
		...settings,
		...( window.sahajanandPostToSpeechSettings || {} ),
	};

	const engine = KittenTTSEngine.getInstance( runtimeSettings );
	onProgress?.( 'Preparing speech model…' );

	return engine.generate( text, voice, speed, onProgress );
}
