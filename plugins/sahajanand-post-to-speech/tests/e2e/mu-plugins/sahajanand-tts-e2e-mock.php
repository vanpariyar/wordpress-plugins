<?php
/**
 * Plugin Name: Sahajanand Post to Speech E2E Mock
 * Description: Mocks the KittenTTS HTTP API for Playwright e2e tests.
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SAHAJANAND_E2E_MOCK_API_HOST = 'sahajanand-tts-e2e.mock';

/**
 * Build a tiny valid silent WAV payload.
 *
 * @return string
 */
function sahajanand_e2e_minimal_wav() {
	$sample_rate = 16000;
	$num_samples = 1600; // 0.1 seconds of silence.
	$data_size   = $num_samples * 2;

	$header = pack(
		'a4Va4a4VvvVVvva4V',
		'RIFF',
		36 + $data_size,
		'WAVE',
		'fmt ',
		16,
		1,
		1,
		$sample_rate,
		$sample_rate * 2,
		2,
		16,
		'data',
		$data_size
	);

	return $header . str_repeat( "\0\0", $num_samples );
}

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'sahajanand-post-to-speech-e2e/v1',
			'/setup-api-mock',
			array(
				'methods'             => 'POST',
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'callback'            => static function () {
					update_option( 'sahajanand_post_to_speech_generation_mode', 'api' );
					update_option(
						'sahajanand_post_to_speech_api_url',
						'http://' . SAHAJANAND_E2E_MOCK_API_HOST . '/'
					);
					update_option( 'sahajanand_post_to_speech_api_key', 'e2e-test-key' );

					return rest_ensure_response( array( 'ok' => true ) );
				},
			)
		);

		register_rest_route(
			'sahajanand-post-to-speech-e2e/v1',
			'/teardown-api-mock',
			array(
				'methods'             => 'POST',
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'callback'            => static function () {
					update_option( 'sahajanand_post_to_speech_generation_mode', 'browser' );
					delete_option( 'sahajanand_post_to_speech_api_url' );
					delete_option( 'sahajanand_post_to_speech_api_key' );

					return rest_ensure_response( array( 'ok' => true ) );
				},
			)
		);
	}
);

add_filter(
	'pre_http_request',
	static function ( $preempt, $args, $url ) {
		if ( false === strpos( $url, SAHAJANAND_E2E_MOCK_API_HOST ) ) {
			return $preempt;
		}

		if ( false !== strpos( $url, '/v1/generate' ) ) {
			return array(
				'headers'  => array(
					'content-type' => 'audio/wav',
				),
				'body'     => sahajanand_e2e_minimal_wav(),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		}

		return $preempt;
	},
	10,
	3
);
