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

	/**
	 * Runtime files required for browser WASM inference.
	 *
	 * @return string[]
	 */
	public static function get_required_files() {
		return array(
			self::ESPEAK_WASM_FILE,
			self::ONNX_WASM_FILE,
			self::ONNX_MJS_FILE,
		);
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
	 * Whether bundled runtime WASM is present in the plugin vendor directory.
	 *
	 * @return bool
	 */
	public static function is_installed() {
		return '' !== self::get_wasm_base_url();
	}

	/**
	 * Base URL directory containing ONNX WASM in the plugin package.
	 *
	 * @return string Empty when WASM is not bundled.
	 */
	public static function get_wasm_base_url() {
		if ( ! defined( 'SAHAJANAND_POST_TO_SPEECH_PATH' ) || ! defined( 'SAHAJANAND_POST_TO_SPEECH_URL' ) ) {
			return '';
		}

		$onnx_vendor = SAHAJANAND_POST_TO_SPEECH_PATH . 'assets/vendor/onnxruntime-web';
		$espeak_wasm = SAHAJANAND_POST_TO_SPEECH_PATH . 'assets/vendor/espeak-ng/' . self::ESPEAK_WASM_FILE;

		if ( self::directory_has_files( $onnx_vendor, self::get_onnx_files() ) && file_exists( $espeak_wasm ) ) {
			return SAHAJANAND_POST_TO_SPEECH_URL . 'assets/vendor/onnxruntime-web';
		}

		return '';
	}

	/**
	 * URL for the eSpeak-NG WASM file.
	 *
	 * @return string
	 */
	public static function get_espeak_wasm_url() {
		if ( ! self::is_installed() ) {
			return '';
		}

		return SAHAJANAND_POST_TO_SPEECH_URL . 'assets/vendor/espeak-ng/' . self::ESPEAK_WASM_FILE;
	}
}
