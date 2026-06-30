<?php
/**
 * REST API endpoints for browser and API-based TTS.
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers REST routes used by the Gutenberg block.
 */
class Post_To_Speech_REST_API {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		register_rest_route(
			'post-to-speech/v1',
			'/upload',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'upload_audio' ),
				'permission_callback' => array( $this, 'can_edit_content' ),
				'args'                => array(
					'audio'   => array(
						'type'              => 'string',
						'validate_callback' => function ( $value ) {
							return is_string( $value ) && '' !== $value;
						},
					),
					'post_id' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'post-to-speech/v1',
			'/generate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'generate_audio' ),
				'permission_callback' => array( $this, 'can_edit_content' ),
				'args'                => array(
					'text'    => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'voice'   => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => 'Bella',
					),
					'speed'   => array(
						'type'    => 'number',
						'default' => 1.0,
					),
					'post_id' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'post-to-speech/v1',
			'/config',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_config' ),
				'permission_callback' => array( $this, 'can_edit_content' ),
			)
		);

		register_rest_route(
			'post-to-speech/v1',
			'/usage',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_usage' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Check whether the current user can use editor features.
	 *
	 * @return bool
	 */
	public function can_edit_content() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Check whether the current user may attach audio to a post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function can_edit_post( $post_id ) {
		$post_id = (int) $post_id;

		if ( $post_id <= 0 ) {
			return true;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Return editor configuration.
	 *
	 * @return WP_REST_Response
	 */
	public function get_config() {
		return rest_ensure_response( Post_To_Speech_Config::get_editor_settings() );
	}

	/**
	 * Return API usage stats for administrators.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_usage() {
		if ( Post_To_Speech_Config::MODE_API !== Post_To_Speech_Config::get_generation_mode() ) {
			return new WP_Error(
				'post_to_speech_not_api_mode',
				__( 'Usage stats are only available in API mode.', 'post-to-speech' ),
				array( 'status' => 400 )
			);
		}

		$client = new Post_To_Speech_API_Client();
		$usage  = $client->get_usage();

		if ( is_wp_error( $usage ) ) {
			return $usage;
		}

		return rest_ensure_response( $usage );
	}

	/**
	 * Generate audio via the external KittenTTS API service.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate_audio( WP_REST_Request $request ) {
		if ( Post_To_Speech_Config::MODE_API !== Post_To_Speech_Config::get_generation_mode() ) {
			return new WP_Error(
				'post_to_speech_wrong_mode',
				__( 'Server API generation is disabled. Switch to API mode in plugin settings.', 'post-to-speech' ),
				array( 'status' => 400 )
			);
		}

		if ( ! Post_To_Speech_Config::is_api_configured() ) {
			return new WP_Error(
				'post_to_speech_api_not_configured',
				__( 'KittenTTS API URL and API key must be configured.', 'post-to-speech' ),
				array( 'status' => 400 )
			);
		}

		$text  = $request->get_param( 'text' );
		$voice = $request->get_param( 'voice' );
		$speed = (float) $request->get_param( 'speed' );

		if ( empty( trim( $text ) ) ) {
			return new WP_Error(
				'post_to_speech_empty_text',
				__( 'Please enter text to convert to audio.', 'post-to-speech' ),
				array( 'status' => 400 )
			);
		}

		if ( ! in_array( $voice, Post_To_Speech_Config::get_voices(), true ) ) {
			return new WP_Error(
				'post_to_speech_invalid_voice',
				__( 'Invalid voice selected.', 'post-to-speech' ),
				array( 'status' => 400 )
			);
		}

		$speed = max( 0.5, min( 2.0, $speed ) );

		$post_id = (int) $request->get_param( 'post_id' );

		if ( ! $this->can_edit_post( $post_id ) ) {
			return new WP_Error(
				'post_to_speech_forbidden_post',
				__( 'You do not have permission to attach audio to this post.', 'post-to-speech' ),
				array( 'status' => 403 )
			);
		}

		$client   = new Post_To_Speech_API_Client();
		$wav_data = $client->generate( $text, $voice, $speed );

		if ( is_wp_error( $wav_data ) ) {
			return $wav_data;
		}

		$media  = new Post_To_Speech_Media();
		$result = $media->upload_wav_bytes( $wav_data, $post_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Upload browser-generated audio to the media library.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function upload_audio( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'post_id' );

		if ( ! $this->can_edit_post( $post_id ) ) {
			return new WP_Error(
				'post_to_speech_forbidden_post',
				__( 'You do not have permission to attach audio to this post.', 'post-to-speech' ),
				array( 'status' => 403 )
			);
		}

		$media = new Post_To_Speech_Media();

		$files = $request->get_file_params();
		if ( ! empty( $files['file'] ) ) {
			$result = $media->upload_wav( $files['file'], $post_id );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response( $result );
		}

		$audio = $request->get_param( 'audio' );
		if ( empty( $audio ) ) {
			return new WP_Error(
				'post_to_speech_missing_file',
				__( 'No audio file was uploaded.', 'post-to-speech' ),
				array( 'status' => 400 )
			);
		}

		$wav_bytes = base64_decode( $audio, true );

		if ( false === $wav_bytes || empty( $wav_bytes ) ) {
			return new WP_Error(
				'post_to_speech_invalid_audio',
				__( 'Invalid audio payload.', 'post-to-speech' ),
				array( 'status' => 400 )
			);
		}

		$max_bytes = (int) apply_filters( 'post_to_speech_max_upload_bytes', 15 * MB_IN_BYTES );

		if ( strlen( $wav_bytes ) > $max_bytes ) {
			return new WP_Error(
				'post_to_speech_too_large',
				__( 'Generated audio exceeds the maximum upload size.', 'post-to-speech' ),
				array( 'status' => 400 )
			);
		}

		$result = $media->upload_wav_bytes( $wav_bytes, $post_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}
}
