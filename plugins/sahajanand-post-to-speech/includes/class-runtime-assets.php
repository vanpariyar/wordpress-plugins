<?php
/**
 * Bundled browser runtime WASM shipped inside the plugin.
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves URLs for ONNX Runtime and eSpeak-NG WASM bundled in assets/vendor/.
 */
class Sahajanand_Post_To_Speech_Runtime_Assets {

	const ESPEAK_WASM_FILE = 'espeak-ng.wasm';
	const ONNX_WASM_FILE   = 'ort-wasm-simd-threaded.wasm';
	const ONNX_MJS_FILE    = 'ort-wasm-simd-threaded.mjs';
	const KITTENTTS_FILE   = 'kittentts-web.js';

	/**
	 * Runtime files required for browser WASM inference.
	 *
	 * @return string[]
	 */
	public static function get_required_files() {
		return self::get_onnx_files();
	}

	/**
	 * ONNX Runtime files bundled under assets/vendor/onnxruntime-web/.
	 *
	 * @return string[]
	 */
	public static function get_onnx_files() {
		return array(
			self::ONNX_WASM_FILE,
			self::ONNX_MJS_FILE,
		);
	}

	/**
	 * Whether expected files exist in a directory.
	 *
	 * @param string   $directory Absolute directory path.
	 * @param string[] $files     File names to check.
	 * @return bool
	 */
	private static function directory_has_files( $directory, $files ) {
		foreach ( $files as $file ) {
			if ( ! file_exists( trailingslashit( $directory ) . $file ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether KittenTTS Web SDK bundle is present in the plugin vendor directory.
	 *
	 * @return bool
	 */
	public static function is_kittentts_installed() {
		$plugin_path = defined( 'SAHAJANAND_POST_TO_SPEECH_PATH' )
			? SAHAJANAND_POST_TO_SPEECH_PATH
			: dirname( __DIR__ ) . '/';

		return file_exists( $plugin_path . 'assets/vendor/kittentts/' . self::KITTENTTS_FILE );
	}

	/**
	 * URL for the KittenTTS Web SDK script.
	 *
	 * @return string
	 */
	public static function get_kittentts_url() {
		if ( ! defined( 'SAHAJANAND_POST_TO_SPEECH_URL' ) ) {
			return '/wp-content/plugins/sahajanand-post-to-speech/assets/vendor/kittentts/' . self::KITTENTTS_FILE;
		}

		return SAHAJANAND_POST_TO_SPEECH_URL . 'assets/vendor/kittentts/' . self::KITTENTTS_FILE;
	}

	/**
	 * Whether bundled runtime WASM or KittenTTS SDK is present in the plugin vendor directory.
	 *
	 * @return bool
	 */
	public static function is_installed() {
		if ( self::is_kittentts_installed() ) {
			return true;
		}

		return '' !== self::get_wasm_base_url();
	}

	/**
	 * Base URL directory containing ONNX WASM in the plugin package.
	 *
	 * @return string Empty when WASM is not bundled.
	 */
	public static function get_wasm_base_url() {
		$plugin_path = defined( 'SAHAJANAND_POST_TO_SPEECH_PATH' )
			? SAHAJANAND_POST_TO_SPEECH_PATH
			: dirname( __DIR__ ) . '/';
		$plugin_url  = defined( 'SAHAJANAND_POST_TO_SPEECH_URL' )
			? SAHAJANAND_POST_TO_SPEECH_URL
			: '/wp-content/plugins/sahajanand-post-to-speech/';

		$onnx_vendor = $plugin_path . 'assets/vendor/onnxruntime-web';

		if ( self::directory_has_files( $onnx_vendor, self::get_onnx_files() ) ) {
			return rtrim( (string) $plugin_url, '/' ) . '/assets/vendor/onnxruntime-web';
		}

		return '';
	}

	/**
	 * URL for the eSpeak-NG WASM file.
	 *
	 * @deprecated 1.1.0 eSpeak-NG is superseded by KittenTTS Web SDK.
	 * @return string
	 */
	public static function get_espeak_wasm_url() {
		return '';
	}
}
