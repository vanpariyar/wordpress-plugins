<?php
/**
 * HTTP client for the external KittenTTS API service.
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calls a self-hosted or paid KittenTTS API and returns WAV bytes.
 */
class Post_To_Speech_API_Client {

	/**
	 * Generate speech audio via the configured API service.
	 *
	 * @param string $text  Text to synthesize.
	 * @param string $voice Voice name.
	 * @param float  $speed Speech speed.
	 * @return string|WP_Error Raw WAV bytes.
	 */
	public function generate( $text, $voice, $speed ) {
		$api_url = trailingslashit( get_option( 'post_to_speech_api_url', '' ) );
		$api_key = get_option( 'post_to_speech_api_key', '' );

		if ( empty( $api_url ) ) {
			return new WP_Error(
				'post_to_speech_missing_api_url',
				__( 'KittenTTS API URL is not configured.', 'post-to-speech' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $api_key ) ) {
			return new WP_Error(
				'post_to_speech_missing_api_key',
				__( 'KittenTTS API key is not configured.', 'post-to-speech' ),
				array( 'status' => 400 )
			);
		}

		$endpoint = $api_url . 'v1/generate';
		$body     = wp_json_encode(
			array(
				'text'  => $text,
				'voice' => $voice,
				'speed' => $speed,
			)
		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 120,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'X-API-Key'     => $api_key,
					'Accept'        => 'audio/wav',
					'User-Agent'    => 'Post-To-Speech-WordPress/' . POST_TO_SPEECH_VERSION,
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$raw    = wp_remote_retrieve_body( $response );

		if ( 200 !== $status ) {
			$message = $raw;

			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) && ! empty( $decoded['detail'] ) ) {
				$message = is_string( $decoded['detail'] ) ? $decoded['detail'] : wp_json_encode( $decoded['detail'] );
			}

			return new WP_Error(
				'post_to_speech_api_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: error message */
					__( 'KittenTTS API error (%1$d): %2$s', 'post-to-speech' ),
					$status,
					$message
				),
				array( 'status' => $status )
			);
		}

		if ( empty( $raw ) ) {
			return new WP_Error(
				'post_to_speech_api_empty',
				__( 'KittenTTS API returned an empty audio response.', 'post-to-speech' ),
				array( 'status' => 502 )
			);
		}

		return $raw;
	}

	/**
	 * Fetch usage stats for the configured API key.
	 *
	 * @return array|WP_Error
	 */
	public function get_usage() {
		$api_url = trailingslashit( get_option( 'post_to_speech_api_url', '' ) );
		$api_key = get_option( 'post_to_speech_api_key', '' );

		if ( empty( $api_url ) || empty( $api_key ) ) {
			return new WP_Error(
				'post_to_speech_api_not_configured',
				__( 'KittenTTS API is not configured.', 'post-to-speech' )
			);
		}

		$response = wp_remote_get(
			$api_url . 'v1/usage',
			array(
				'timeout' => 30,
				'headers' => array(
					'X-API-Key'  => $api_key,
					'Accept'     => 'application/json',
					'User-Agent' => 'Post-To-Speech-WordPress/' . POST_TO_SPEECH_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$raw    = wp_remote_retrieve_body( $response );
		$data   = json_decode( $raw, true );

		if ( 200 !== $status || ! is_array( $data ) ) {
			return new WP_Error(
				'post_to_speech_usage_error',
				__( 'Could not fetch API usage information.', 'post-to-speech' )
			);
		}

		return $data;
	}
}
