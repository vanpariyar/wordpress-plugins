<?php
/**
 * Media upload helpers for generated audio.
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Saves generated WAV audio to the media library.
 */
class Post_To_Speech_Media {

	/**
	 * Upload a browser-generated WAV file to the media library.
	 *
	 * @param array $file    Uploaded file array from REST request.
	 * @param int   $post_id Optional post ID.
	 * @return array|WP_Error
	 */
	public function upload_wav( $file, $post_id = 0 ) {
		if ( empty( $file['tmp_name'] ) || ! file_exists( $file['tmp_name'] ) ) {
			return new WP_Error(
				'post_to_speech_missing_file',
				__( 'No audio file was uploaded.', 'post-to-speech' ),
				array( 'status' => 400 )
			);
		}

		$file['type'] = 'audio/wav';

		return $this->sideload_file_array( $file, $post_id );
	}

	/**
	 * Upload raw WAV bytes to the media library.
	 *
	 * @param string $wav_bytes Raw WAV file contents.
	 * @param int    $post_id   Optional post ID.
	 * @return array|WP_Error
	 */
	public function upload_wav_bytes( $wav_bytes, $post_id = 0 ) {
		if ( empty( $wav_bytes ) ) {
			return new WP_Error(
				'post_to_speech_missing_file',
				__( 'No audio data was provided.', 'post-to-speech' ),
				array( 'status' => 400 )
			);
		}

		$size_check = $this->validate_upload_size( strlen( $wav_bytes ) );

		if ( is_wp_error( $size_check ) ) {
			return $size_check;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$tmp_file = wp_tempnam( 'post-to-speech-' );

		if ( ! $tmp_file ) {
			return new WP_Error(
				'post_to_speech_temp_file',
				__( 'Could not create a temporary file for audio upload.', 'post-to-speech' ),
				array( 'status' => 500 )
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $tmp_file, $wav_bytes );
		unset( $wav_bytes );

		return $this->upload_wav_temp_path( $tmp_file, $post_id );
	}

	/**
	 * Upload a WAV file from a temporary path.
	 *
	 * @param string $tmp_path Absolute path to a WAV temp file.
	 * @param int    $post_id  Optional post ID.
	 * @return array|WP_Error
	 */
	public function upload_wav_temp_path( $tmp_path, $post_id = 0 ) {
		if ( empty( $tmp_path ) || ! file_exists( $tmp_path ) ) {
			return new WP_Error(
				'post_to_speech_missing_file',
				__( 'No audio file was uploaded.', 'post-to-speech' ),
				array( 'status' => 400 )
			);
		}

		$size_check = $this->validate_upload_size( (int) filesize( $tmp_path ) );

		if ( is_wp_error( $size_check ) ) {
			wp_delete_file( $tmp_path );
			return $size_check;
		}

		$file = array(
			'name'     => 'post-to-speech-' . wp_generate_password( 8, false ) . '.wav',
			'tmp_name' => $tmp_path,
			'type'     => 'audio/wav',
			'size'     => (int) filesize( $tmp_path ),
		);

		$result = $this->sideload_file_array( $file, $post_id );

		if ( is_wp_error( $result ) && file_exists( $tmp_path ) ) {
			wp_delete_file( $tmp_path );
		}

		return $result;
	}

	/**
	 * Sideload a prepared file array into the media library.
	 *
	 * @param array $file    File array for media_handle_sideload().
	 * @param int   $post_id Optional post ID.
	 * @return array|WP_Error
	 */
	private function sideload_file_array( $file, $post_id = 0 ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$file['name'] = 'post-to-speech-' . wp_generate_password( 8, false ) . '.wav';

		$attachment_id = media_handle_sideload( $file, $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		return array(
			'attachmentId' => $attachment_id,
			'audioUrl'     => wp_get_attachment_url( $attachment_id ),
		);
	}

	/**
	 * Validate decoded audio size before sideloading.
	 *
	 * @param int $byte_length Raw WAV byte length.
	 * @return true|WP_Error
	 */
	private function validate_upload_size( $byte_length ) {
		if ( ! class_exists( 'Post_To_Speech_Config' ) ) {
			require_once dirname( __FILE__ ) . '/class-config.php';
		}

		$max_bytes = Post_To_Speech_Config::get_max_upload_bytes();

		if ( $byte_length > $max_bytes ) {
			return new WP_Error(
				'post_to_speech_too_large',
				sprintf(
					/* translators: 1: generated audio size, 2: maximum allowed size */
					__( 'Generated audio (%1$s) exceeds the maximum upload size (%2$s). Try a shorter post or ask your site administrator to raise the limit.', 'post-to-speech' ),
					size_format( $byte_length ),
					size_format( $max_bytes )
				),
				array( 'status' => 400 )
			);
		}

		return true;
	}
}
