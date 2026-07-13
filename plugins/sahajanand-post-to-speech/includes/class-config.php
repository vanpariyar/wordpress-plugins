<?php
/**
 * Shared plugin configuration helpers.
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static plugin configuration for browser and API TTS modes.
 */
class Sahajanand_Post_To_Speech_Config {

	const MODE_BROWSER = 'browser';
	const MODE_API     = 'api';

	/**
	 * Friendly voice names exposed in the block editor.
	 *
	 * @return string[]
	 */
	public static function get_voices() {
		return array( 'Bella', 'Jasper', 'Luna', 'Bruno', 'Rosie', 'Hugo', 'Kiki', 'Leo' );
	}

	/**
	 * Voice alias map from KittenTTS config.json.
	 *
	 * @return array<string, string>
	 */
	public static function get_voice_aliases() {
		return array(
			'Bella'  => 'expr-voice-2-f',
			'Jasper' => 'expr-voice-2-m',
			'Luna'   => 'expr-voice-3-f',
			'Bruno'  => 'expr-voice-3-m',
			'Rosie'  => 'expr-voice-4-f',
			'Hugo'   => 'expr-voice-4-m',
			'Kiki'   => 'expr-voice-5-f',
			'Leo'    => 'expr-voice-5-m',
		);
	}

	/**
	 * Supported generation modes.
	 *
	 * @return string[]
	 */
	public static function get_generation_modes() {
		return array( self::MODE_BROWSER, self::MODE_API );
	}

	/**
	 * Get the active generation mode.
	 *
	 * @return string
	 */
	public static function get_generation_mode() {
		$mode = get_option( 'sahajanand_post_to_speech_generation_mode', self::MODE_BROWSER );

		if ( ! in_array( $mode, self::get_generation_modes(), true ) ) {
			return self::MODE_BROWSER;
		}

		return $mode;
	}

	/**
	 * Editor/runtime settings passed to the block.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_editor_settings() {
		$model_repo = get_option( 'sahajanand_post_to_speech_model', 'KittenML/kitten-tts-nano-0.8-fp32' );
		$mode       = self::get_generation_mode();

		if ( self::MODE_BROWSER === $mode && substr( $model_repo, -5 ) === '-int8' ) {
			$model_repo = 'KittenML/kitten-tts-nano-0.8-fp32';
		}

		return array(
			'generationMode'       => $mode,
			'modelRepo'            => $model_repo,
			'defaultVoice'         => get_option( 'sahajanand_post_to_speech_default_voice', 'Bella' ),
			'defaultSpeed'         => 1.0,
			'voices'               => self::get_voices(),
			'voiceAliases'         => self::get_voice_aliases(),
			'apiConfigured'        => self::MODE_API === $mode && self::is_api_configured(),
			'pricePerRequest'      => (float) get_option( 'sahajanand_post_to_speech_price_per_request', 0 ),
			'espeakModuleUrl'      => self::get_espeak_module_url(),
			'espeakWasmUrl'        => Sahajanand_Post_To_Speech_Runtime_Assets::get_espeak_wasm_url(),
			'onnxScriptUrl'        => self::get_onnx_script_url(),
			'onnxWasmUrl'          => Sahajanand_Post_To_Speech_Runtime_Assets::get_wasm_base_url(),
			'runtimeWasmInstalled' => Sahajanand_Post_To_Speech_Runtime_Assets::is_installed(),
		);
	}

	/**
	 * URL to the bundled eSpeak-NG ES module (editor only).
	 *
	 * @return string
	 */
	public static function get_espeak_module_url() {
		if ( defined( 'SAHAJANAND_POST_TO_SPEECH_URL' ) ) {
			return SAHAJANAND_POST_TO_SPEECH_URL . 'assets/vendor/espeak-ng/espeak-ng.js';
		}

		return '/wp-content/plugins/sahajanand-post-to-speech/assets/vendor/espeak-ng/espeak-ng.js';
	}

	/**
	 * URL to the bundled ONNX Runtime Web script (editor only).
	 *
	 * @return string
	 */
	public static function get_onnx_script_url() {
		if ( defined( 'SAHAJANAND_POST_TO_SPEECH_URL' ) ) {
			return SAHAJANAND_POST_TO_SPEECH_URL . 'assets/vendor/onnxruntime-web/ort.min.js';
		}

		return '/wp-content/plugins/sahajanand-post-to-speech/assets/vendor/onnxruntime-web/ort.min.js';
	}

	/**
	 * @deprecated 1.0.0 Use get_onnx_script_url() and Sahajanand_Post_To_Speech_Runtime_Assets::get_wasm_base_url().
	 * @return string
	 */
	public static function get_onnx_runtime_url() {
		return self::get_onnx_script_url();
	}

	/**
	 * Admin URL for the plugin settings screen (block editor only).
	 *
	 * @return string
	 */
	public static function get_settings_page_url() {
		if ( function_exists( 'admin_url' ) ) {
			return admin_url( 'options-general.php?page=sahajanand-post-to-speech' );
		}

		return '/wp-admin/options-general.php?page=sahajanand-post-to-speech';
	}

	/**
	 * Maximum decoded WAV size allowed for media uploads.
	 *
	 * Defaults to 64 MB (~22 minutes at 24 kHz mono). Filter with post_to_speech_max_upload_bytes.
	 *
	 * @return int
	 */
	public static function get_max_upload_bytes() {
		if ( ! defined( 'MB_IN_BYTES' ) ) {
			define( 'MB_IN_BYTES', 1024 * 1024 );
		}

		$default = 64 * MB_IN_BYTES;

		return max( MB_IN_BYTES, (int) apply_filters( 'sahajanand_post_to_speech_max_upload_bytes', $default ) );
	}

	/**
	 * Whether API mode has the minimum required settings.
	 *
	 * @return bool
	 */
	public static function is_api_configured() {
		$api_url = get_option( 'sahajanand_post_to_speech_api_url', '' );
		$api_key = get_option( 'sahajanand_post_to_speech_api_key', '' );

		return ! empty( $api_url ) && ! empty( $api_key );
	}

	/**
	 * Allowed Hugging Face model repositories.
	 *
	 * @return string[]
	 */
	public static function get_allowed_model_repos() {
		return array(
			'KittenML/kitten-tts-nano-0.8-int8',
			'KittenML/kitten-tts-nano-0.8-fp32',
			'KittenML/kitten-tts-micro-0.8',
			'KittenML/kitten-tts-mini-0.8',
		);
	}

	/**
	 * Validate a model repository slug.
	 *
	 * @param string $model_repo Hugging Face repo ID.
	 * @return bool
	 */
	public static function is_allowed_model_repo( $model_repo ) {
		return in_array( $model_repo, self::get_allowed_model_repos(), true );
	}
}
