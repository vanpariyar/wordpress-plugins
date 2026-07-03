import { loadOnnxRuntime, phonemizeWithEspeak } from './runtime-loader';
import { phonemeStringToTokens, chunkText } from './text-cleaner';
import { loadVoicesNpz } from './npz-loader';
import { encodeWav } from './wav-encoder';

const SAMPLE_RATE = 24000;
const DEFAULT_CONFIG = {
	voice_aliases: {
		Bella: 'expr-voice-2-f',
		Jasper: 'expr-voice-2-m',
		Luna: 'expr-voice-3-f',
		Bruno: 'expr-voice-3-m',
		Rosie: 'expr-voice-4-f',
		Hugo: 'expr-voice-4-m',
		Kiki: 'expr-voice-5-f',
		Leo: 'expr-voice-5-m',
	},
	speed_priors: {
		'expr-voice-2-f': 0.8,
		'expr-voice-2-m': 0.8,
		'expr-voice-3-m': 0.8,
		'expr-voice-3-f': 0.8,
		'expr-voice-4-m': 0.9,
		'expr-voice-4-f': 0.8,
		'expr-voice-5-m': 0.8,
		'expr-voice-5-f': 0.8,
	},
};

let engineInstance = null;

function resolveVoiceId( voice, config ) {
	const aliases = config.voice_aliases || DEFAULT_CONFIG.voice_aliases;
	return aliases[ voice ] || voice;
}

const BROWSER_MODEL_DEFAULT = 'KittenML/kitten-tts-nano-0.8-fp32';
const BROWSER_MODEL_FALLBACKS = {
	'KittenML/kitten-tts-nano-0.8-int8': 'KittenML/kitten-tts-nano-0.8-fp32',
};

function resolveBrowserModelRepo( modelRepo ) {
	if ( BROWSER_MODEL_FALLBACKS[ modelRepo ] ) {
		return BROWSER_MODEL_FALLBACKS[ modelRepo ];
	}

	return modelRepo || BROWSER_MODEL_DEFAULT;
}

function getStyleTensor( ort, voices, voiceId, text ) {
	const voiceTensor = voices[ voiceId ];

	if ( ! voiceTensor ) {
		throw new Error( `Voice "${ voiceId }" is not available in the model.` );
	}

	const shape = voiceTensor.shape || [];
	const rows = shape[ 0 ] || 1;
	const cols = shape[ 1 ] || voiceTensor.data.length;
	const refId = Math.min( text.length, Math.max( rows - 1, 0 ) );
	const start = refId * cols;
	const end = start + cols;
	const styleData = new Float32Array(
		voiceTensor.data.subarray
			? voiceTensor.data.subarray( start, end )
			: voiceTensor.data.slice( start, end )
	);

	return new ort.Tensor( 'float32', styleData, [ 1, cols ] );
}

async function fetchArrayBuffer( url ) {
	const response = await fetch( url );

	if ( ! response.ok ) {
		throw new Error( `Failed to fetch ${ url } (${ response.status })` );
	}

	return response.arrayBuffer();
}

export class KittenTTSEngine {
	constructor( settings = {} ) {
		this.settings = settings;
		this.config = null;
		this.session = null;
		this.voices = null;
		this.ort = null;
		this.loadingPromise = null;
	}

	static getInstance( settings = {} ) {
		const resolvedModel = resolveBrowserModelRepo( settings.modelRepo );

		if (
			! engineInstance ||
			resolveBrowserModelRepo( engineInstance.settings.modelRepo ) !==
				resolvedModel
		) {
			engineInstance = new KittenTTSEngine( settings );
		} else {
			engineInstance.settings = {
				...engineInstance.settings,
				...settings,
			};
		}

		return engineInstance;
	}

	async load( onProgress ) {
		if ( this.session && this.voices && this.config && this.ort ) {
			return;
		}

		if ( this.loadingPromise ) {
			return this.loadingPromise;
		}

		this.loadingPromise = this.#loadInternal( onProgress );

		try {
			await this.loadingPromise;
		} finally {
			this.loadingPromise = null;
		}
	}

	async #loadInternal( onProgress ) {
		onProgress?.( 'Loading ONNX runtime…' );

		this.ort = await loadOnnxRuntime();

		const modelRepo = resolveBrowserModelRepo( this.settings.modelRepo );
		const hfBase = `https://huggingface.co/${ modelRepo }/resolve/main`;

		onProgress?.( 'Downloading model config…' );

		const configResponse = await fetch( `${ hfBase }/config.json` );

		if ( ! configResponse.ok ) {
			throw new Error( 'Could not load KittenTTS model config.' );
		}

		this.config = await configResponse.json();

		onProgress?.( 'Downloading voice data…' );

		const voicesBuffer = await fetchArrayBuffer(
			`${ hfBase }/${ this.config.voices }`
		);
		this.voices = await loadVoicesNpz( voicesBuffer );

		onProgress?.( 'Loading ONNX model…' );

		this.session = await this.ort.InferenceSession.create(
			`${ hfBase }/${ this.config.model_file }`,
			{
				executionProviders: [ 'wasm' ],
			}
		);
	}

	async generate( text, voice = 'Bella', speed = 1, onProgress ) {
		await this.load( onProgress );

		const chunks = chunkText( text );
		const chunkAudio = [];

		for ( const chunk of chunks ) {
			chunkAudio.push(
				await this.#generateChunk( chunk, voice, speed, onProgress )
			);
		}

		const totalLength = chunkAudio.reduce(
			( sum, audio ) => sum + audio.length,
			0
		);
		const merged = new Float32Array( totalLength );
		let offset = 0;

		for ( const audio of chunkAudio ) {
			merged.set( audio, offset );
			offset += audio.length;
		}

		return encodeWav( merged, SAMPLE_RATE );
	}

	async #generateChunk( text, voice, speed, onProgress ) {
		const voiceId = resolveVoiceId( voice, this.config );
		const speedPriors = this.config.speed_priors || DEFAULT_CONFIG.speed_priors;
		const effectiveSpeed = speed * ( speedPriors[ voiceId ] || 1 );

		onProgress?.( 'Phonemizing text…' );

		const phonemeString = await phonemizeWithEspeak( text );
		const tokens = phonemeStringToTokens( phonemeString );
		const inputIds = new this.ort.Tensor(
			'int64',
			BigInt64Array.from( tokens.map( ( token ) => BigInt( token ) ) ),
			[ 1, tokens.length ]
		);
		const style = getStyleTensor( this.ort, this.voices, voiceId, text );
		const speedTensor = new this.ort.Tensor(
			'float32',
			new Float32Array( [ effectiveSpeed ] ),
			[ 1 ]
		);

		onProgress?.( 'Synthesizing speech…' );

		const feeds = {};
		for ( const name of this.session.inputNames ) {
			if ( name.includes( 'input' ) ) {
				feeds[ name ] = inputIds;
			} else if ( name === 'style' ) {
				feeds[ name ] = style;
			} else if ( name === 'speed' ) {
				feeds[ name ] = speedTensor;
			}
		}

		if ( ! Object.keys( feeds ).length ) {
			feeds.input_ids = inputIds;
			feeds.style = style;
			feeds.speed = speedTensor;
		}

		const outputs = await this.session.run( feeds );

		const outputKey = Object.keys( outputs )[ 0 ];
		const audio = outputs[ outputKey ].data;
		const trimLength = Math.max( 0, audio.length - 5000 );

		return audio.slice( 0, trimLength );
	}
}

export async function generateSpeechBlob( text, voice, speed, settings = {}, onProgress ) {
	const engine = KittenTTSEngine.getInstance( settings );
	onProgress?.( 'Preparing speech model…' );
	return engine.generate( text, voice, speed, onProgress );
}
