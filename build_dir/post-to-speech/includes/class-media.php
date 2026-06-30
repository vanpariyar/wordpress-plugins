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

		$file = array(
			'name'     => 'post-to-speech-' . wp_generate_password( 8, false ) . '.wav',
			'tmp_name' => $tmp_file,
			'type'     => 'audio/wav',
			'size'     => strlen( $wav_bytes ),
		);

		$result = $this->sideload_file_array( $file, $post_id );

		if ( file_exists( $tmp_file ) ) {
			wp_delete_file( $tmp_file );
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
}
