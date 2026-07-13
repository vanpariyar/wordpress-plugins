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
	 * Whether all runtime files exist in a directory.
	 *
	 * @param string $directory Absolute directory path.
	 * @return bool
	 */
	private static function directory_has_runtime_files( $directory ) {
		foreach ( self::get_required_files() as $file ) {
			if ( ! file_exists( trailingslashit( $directory ) . $file ) ) {
				return false;
			}
		}

		return true;
	}

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

		if ( self::directory_has_runtime_files( $upload_dir ) ) {
			return self::get_upload_url();
		}

		if ( defined( 'SAHAJANAND_POST_TO_SPEECH_PATH' ) && defined( 'SAHAJANAND_POST_TO_SPEECH_URL' ) ) {
			$plugin_vendor = SAHAJANAND_POST_TO_SPEECH_PATH . 'assets/vendor/onnxruntime-web';

			if ( self::directory_has_runtime_files( $plugin_vendor ) && file_exists( SAHAJANAND_POST_TO_SPEECH_PATH . 'assets/vendor/espeak-ng/' . self::ESPEAK_WASM_FILE ) ) {
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

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$remote_base = self::get_remote_base_url();

		if ( '' === $remote_base ) {
			return new WP_Error(
				'sahajanand_post_to_speech_runtime_url',
				__( 'Browser runtime download URL is not configured.', 'sahajanand-post-to-speech' ),
				array( 'status' => 500 )
			);
		}

		$files = self::get_required_files();

		foreach ( $files as $file ) {
			$destination = $target_dir . '/' . $file;

			if ( file_exists( $destination ) ) {
				continue;
			}

			if ( self::copy_bundled_vendor_file( $file, $destination ) ) {
				continue;
			}

			$result = self::download_remote_file( $remote_base . $file, $destination, $file );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		if ( ! self::is_installed() ) {
			return new WP_Error(
				'sahajanand_post_to_speech_runtime_download',
				__( 'Browser runtime files could not be installed.', 'sahajanand-post-to-speech' ),
				array( 'status' => 502 )
			);
		}

		return true;
	}

	/**
	 * Copy a WASM file from the plugin vendor directory when present (local dev).
	 *
	 * @param string $file        WASM file name.
	 * @param string $destination Absolute destination path.
	 * @return bool
	 */
	private static function copy_bundled_vendor_file( $file, $destination ) {
		if ( ! defined( 'SAHAJANAND_POST_TO_SPEECH_PATH' ) ) {
			return false;
		}

		$sources = array(
			self::ESPEAK_WASM_FILE => SAHAJANAND_POST_TO_SPEECH_PATH . 'assets/vendor/espeak-ng/' . self::ESPEAK_WASM_FILE,
			self::ONNX_WASM_FILE   => SAHAJANAND_POST_TO_SPEECH_PATH . 'assets/vendor/onnxruntime-web/' . self::ONNX_WASM_FILE,
			self::ONNX_MJS_FILE    => SAHAJANAND_POST_TO_SPEECH_PATH . 'assets/vendor/onnxruntime-web/' . self::ONNX_MJS_FILE,
		);

		if ( empty( $sources[ $file ] ) || ! file_exists( $sources[ $file ] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
		return copy( $sources[ $file ], $destination );
	}

	/**
	 * Download a remote runtime file to disk using WordPress HTTP API streaming.
	 *
	 * @param string $url         Remote file URL.
	 * @param string $destination Absolute destination path.
	 * @param string $file_label  File name for error messages.
	 * @return true|WP_Error
	 */
	private static function download_remote_file( $url, $destination, $file_label ) {
		$timeout = (int) apply_filters( 'sahajanand_post_to_speech_runtime_download_timeout', 300 );

		$filter = static function ( $parsed_args, $request_url ) use ( $url, $file_label, $timeout ) {
			if ( $request_url !== $url ) {
				return $parsed_args;
			}

			$parsed_args['timeout']     = $timeout;
			$parsed_args['redirection'] = 5;
			$parsed_args['headers']     = isset( $parsed_args['headers'] ) && is_array( $parsed_args['headers'] )
				? $parsed_args['headers']
				: array();

			$parsed_args['headers']['Accept'] = 'application/octet-stream';

			if ( defined( 'SAHAJANAND_POST_TO_SPEECH_VERSION' ) ) {
				$user_agent = 'Sahajanand-Post-To-Speech/' . SAHAJANAND_POST_TO_SPEECH_VERSION;
				if ( function_exists( 'home_url' ) ) {
					$user_agent .= '; ' . home_url( '/' );
				}
				$parsed_args['headers']['User-Agent'] = $user_agent;
			}

			return apply_filters( 'sahajanand_post_to_speech_runtime_download_args', $parsed_args, $url, $file_label );
		};

		add_filter( 'http_request_args', $filter, 10, 2 );

		$temp_file = download_url( $url, $timeout, false );

		remove_filter( 'http_request_args', $filter, 10 );

		if ( is_wp_error( $temp_file ) ) {
			$message = sprintf(
				/* translators: 1: file name, 2: error message */
				__( 'Could not download browser runtime file: %1$s (%2$s)', 'sahajanand-post-to-speech' ),
				$file_label,
				$temp_file->get_error_message()
			);

			return new WP_Error(
				'sahajanand_post_to_speech_runtime_download',
				$message,
				array(
					'status' => 502,
					'url'    => $url,
				)
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
		$copied = copy( $temp_file, $destination );
		wp_delete_file( $temp_file );

		if ( ! $copied || ! file_exists( $destination ) || 0 === filesize( $destination ) ) {
			return new WP_Error(
				'sahajanand_post_to_speech_runtime_write',
				sprintf(
					/* translators: %s: local file name */
					__( 'Could not save browser runtime file: %s', 'sahajanand-post-to-speech' ),
					$file_label
				),
				array( 'status' => 500 )
			);
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
