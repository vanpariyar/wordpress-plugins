<?php
/**
 * Browser runtime WASM installers (kept out of the plugin zip to stay under 10 MB).
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Downloads ONNX Runtime and eSpeak-NG WASM into uploads on first use.
 */
class Sahajanand_Post_To_Speech_Runtime_Assets {

	const ESPEAK_WASM_FILE = 'espeak-ng.wasm';
	const ONNX_WASM_FILE   = 'ort-wasm-simd-threaded.wasm';

	/**
	 * Remote base URL for runtime WASM (documented external service).
	 *
	 * @return string
	 */
	public static function get_remote_base_url() {
		$default = defined( 'SAHAJANAND_POST_TO_SPEECH_RUNTIME_ASSETS_URL' )
			? SAHAJANAND_POST_TO_SPEECH_RUNTIME_ASSETS_URL
			: '';

		return trailingslashit(
			apply_filters( 'sahajanand_post_to_speech_runtime_assets_remote_base', $default )
		);
	}

	/**
	 * Absolute uploads directory for runtime WASM files.
	 *
	 * @return string
	 */
	public static function get_upload_dir() {
		$upload = wp_upload_dir();

		return trailingslashit( $upload['basedir'] ) . 'sahajanand-post-to-speech/vendor';
	}

	/**
	 * Public URL for runtime WASM files in uploads.
	 *
	 * @return string
	 */
	public static function get_upload_url() {
		$upload = wp_upload_dir();

		return trailingslashit( $upload['baseurl'] ) . 'sahajanand-post-to-speech/vendor';
	}

	/**
	 * Whether both WASM files are available locally (uploads or plugin vendor).
	 *
	 * @return bool
	 */
	public static function is_installed() {
		return '' !== self::get_wasm_base_url();
	}

	/**
	 * Base URL directory containing ONNX WASM (uploads preferred, then plugin vendor).
	 *
	 * @return string Empty when WASM is not present locally.
	 */
	public static function get_wasm_base_url() {
		$upload_dir = self::get_upload_dir();

		if (
			file_exists( $upload_dir . '/' . self::ESPEAK_WASM_FILE )
			&& file_exists( $upload_dir . '/' . self::ONNX_WASM_FILE )
		) {
			return self::get_upload_url();
		}

		if ( defined( 'SAHAJANAND_POST_TO_SPEECH_PATH' ) && defined( 'SAHAJANAND_POST_TO_SPEECH_URL' ) ) {
			$plugin_dir = SAHAJANAND_POST_TO_SPEECH_PATH . 'assets/vendor/onnxruntime-web/' . self::ONNX_WASM_FILE;

			if ( file_exists( $plugin_dir ) && file_exists( SAHAJANAND_POST_TO_SPEECH_PATH . 'assets/vendor/espeak-ng/' . self::ESPEAK_WASM_FILE ) ) {
				return SAHAJANAND_POST_TO_SPEECH_URL . 'assets/vendor/onnxruntime-web';
			}
		}

		return '';
	}

	/**
	 * URL for the eSpeak-NG WASM file.
	 *
	 * @return string
	 */
	public static function get_espeak_wasm_url() {
		$base = self::get_wasm_base_url();

		if ( '' === $base ) {
			return '';
		}

		if ( str_contains( $base, 'onnxruntime-web' ) ) {
			return SAHAJANAND_POST_TO_SPEECH_URL . 'assets/vendor/espeak-ng/' . self::ESPEAK_WASM_FILE;
		}

		return trailingslashit( $base ) . self::ESPEAK_WASM_FILE;
	}

	/**
	 * Download WASM files into uploads when missing.
	 *
	 * @return true|WP_Error
	 */
	public static function install() {
		if ( self::is_installed() ) {
			return true;
		}

		$target_dir = self::get_upload_dir();

		if ( ! wp_mkdir_p( $target_dir ) ) {
			return new WP_Error(
				'sahajanand_post_to_speech_runtime_dir',
				__( 'Could not create a directory for browser runtime files.', 'sahajanand-post-to-speech' ),
				array( 'status' => 500 )
			);
		}

		self::write_silence_file( $target_dir . '/index.php' );
		self::write_silence_file( dirname( $target_dir ) . '/index.php' );

		$remote_base = self::get_remote_base_url();
		$files       = array( self::ESPEAK_WASM_FILE, self::ONNX_WASM_FILE );

		foreach ( $files as $file ) {
			$response = wp_remote_get(
				$remote_base . $file,
				array(
					'timeout' => 120,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$status = wp_remote_retrieve_response_code( $response );

			if ( 200 !== $status ) {
				return new WP_Error(
					'sahajanand_post_to_speech_runtime_download',
					sprintf(
						/* translators: %s: remote file name */
						__( 'Could not download browser runtime file: %s', 'sahajanand-post-to-speech' ),
						$file
					),
					array( 'status' => 502 )
				);
			}

			$body = wp_remote_retrieve_body( $response );

			if ( '' === $body ) {
				return new WP_Error(
					'sahajanand_post_to_speech_runtime_download',
					sprintf(
						/* translators: %s: remote file name */
						__( 'Downloaded browser runtime file was empty: %s', 'sahajanand-post-to-speech' ),
						$file
					),
					array( 'status' => 502 )
				);
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			if ( false === file_put_contents( $target_dir . '/' . $file, $body ) ) {
				return new WP_Error(
					'sahajanand_post_to_speech_runtime_write',
					sprintf(
						/* translators: %s: local file name */
						__( 'Could not save browser runtime file: %s', 'sahajanand-post-to-speech' ),
						$file
					),
					array( 'status' => 500 )
				);
			}
		}

		return true;
	}

	/**
	 * Write a silence index file.
	 *
	 * @param string $path Absolute file path.
	 */
	private static function write_silence_file( $path ) {
		if ( file_exists( $path ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $path, "<?php\n// Silence is golden.\n" );
	}
}
