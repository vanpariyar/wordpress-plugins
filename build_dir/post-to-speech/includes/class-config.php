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
class Post_To_Speech_Config {

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
		$mode = get_option( 'post_to_speech_generation_mode', self::MODE_BROWSER );

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
		$model_repo = get_option( 'post_to_speech_model', 'KittenML/kitten-tts-nano-0.8-fp32' );
		$mode       = self::get_generation_mode();

		if ( self::MODE_BROWSER === $mode && substr( $model_repo, -5 ) === '-int8' ) {
			$model_repo = 'KittenML/kitten-tts-nano-0.8-fp32';
		}

		return array(
			'generationMode' => $mode,
			'modelRepo'      => $model_repo,
			'defaultVoice'   => get_option( 'post_to_speech_default_voice', 'Bella' ),
			'defaultSpeed'   => 1.0,
			'voices'         => self::get_voices(),
			'voiceAliases'   => self::get_voice_aliases(),
			'apiConfigured'  => self::MODE_API === $mode && self::is_api_configured(),
			'pricePerRequest'=> (float) get_option( 'post_to_speech_price_per_request', 0 ),
		);
	}

	/**
	 * Whether API mode has the minimum required settings.
	 *
	 * @return bool
	 */
	public static function is_api_configured() {
		$api_url = get_option( 'post_to_speech_api_url', '' );
		$api_key = get_option( 'post_to_speech_api_key', '' );

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
