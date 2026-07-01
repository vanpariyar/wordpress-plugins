<?php
/**
 * Tests for Post_To_Speech_REST_API.
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Post_To_Speech_TestCase.php';

/**
 * REST API tests.
 */
class Post_To_Speech_REST_API_Test extends Post_To_Speech_TestCase {

	/**
	 * REST API instance.
	 *
	 * @var Post_To_Speech_REST_API
	 */
	private $api;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->load_include( 'class-config.php' );
		$this->load_include( 'class-media.php' );
		$this->load_include( 'class-api-client.php' );
		$this->load_include( 'class-rest-api.php' );

		$this->api = new Post_To_Speech_REST_API();
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * can_edit_post should allow unattached uploads.
	 */
	public function test_can_edit_post_allows_zero_post_id() {
		$this->assertTrue( $this->api->can_edit_post( 0 ) );
	}

	/**
	 * can_edit_post should defer to edit_post capability.
	 */
	public function test_can_edit_post_checks_capability() {
		WP_Mock::userFunction( 'current_user_can' )
			->with( 'edit_post', 42 )
			->andReturn( false );

		$this->assertFalse( $this->api->can_edit_post( 42 ) );
	}

	/**
	 * upload_audio should reject missing audio payload.
	 */
	public function test_upload_audio_rejects_missing_audio() {
		$request = new WP_REST_Request(
			'POST',
			'/post-to-speech/v1/upload',
			array(
				'post_id' => 0,
			)
		);

		$result = $this->api->upload_audio( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'post_to_speech_missing_file', $result->get_error_code() );
	}

	/**
	 * upload_audio should reject invalid base64 payload.
	 */
	public function test_upload_audio_rejects_invalid_base64() {
		$request = new WP_REST_Request(
			'POST',
			'/post-to-speech/v1/upload',
			array(
				'post_id' => 0,
				'audio'   => '%%%not-base64%%%',
			)
		);

		$result = $this->api->upload_audio( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'post_to_speech_invalid_audio', $result->get_error_code() );
	}

	/**
	 * upload_audio should reject unauthorized post attachment.
	 */
	public function test_upload_audio_rejects_forbidden_post() {
		WP_Mock::userFunction( 'current_user_can' )
			->with( 'edit_post', 99 )
			->andReturn( false );

		$request = new WP_REST_Request(
			'POST',
			'/post-to-speech/v1/upload',
			array(
				'post_id' => 99,
				'audio'   => base64_encode( 'RIFF' ),
			)
		);

		$result = $this->api->upload_audio( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'post_to_speech_forbidden_post', $result->get_error_code() );
	}

	/**
	 * generate_audio should reject browser mode.
	 */
	public function test_generate_audio_rejects_browser_mode() {
		WP_Mock::userFunction( 'get_option' )
			->with( 'post_to_speech_generation_mode', Post_To_Speech_Config::MODE_BROWSER )
			->andReturn( Post_To_Speech_Config::MODE_BROWSER );

		$request = new WP_REST_Request(
			'POST',
			'/post-to-speech/v1/generate',
			array(
				'text'  => 'Hello',
				'voice' => 'Bella',
			)
		);

		$result = $this->api->generate_audio( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'post_to_speech_wrong_mode', $result->get_error_code() );
	}

	/**
	 * generate_audio should reject invalid voice names.
	 */
	public function test_generate_audio_rejects_invalid_voice() {
		WP_Mock::userFunction( 'get_option' )
			->andReturnUsing(
				function ( $key, $default = false ) {
					$options = array(
						'post_to_speech_generation_mode' => Post_To_Speech_Config::MODE_API,
						'post_to_speech_api_url'         => 'https://tts.example.com/',
						'post_to_speech_api_key'         => 'secret',
					);

					return $options[ $key ] ?? $default;
				}
			);

		$request = new WP_REST_Request(
			'POST',
			'/post-to-speech/v1/generate',
			array(
				'text'    => 'Hello',
				'voice'   => 'NotAVoice',
				'post_id' => 0,
			)
		);

		$result = $this->api->generate_audio( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'post_to_speech_invalid_voice', $result->get_error_code() );
	}
}

/**
 * Minimal REST request stub for unit tests.
 */
class WP_REST_Request {

	/**
	 * Request params.
	 *
	 * @var array<string, mixed>
	 */
	private $params = array();

	/**
	 * Uploaded files.
	 *
	 * @var array<string, mixed>
	 */
	private $files = array();

	/**
	 * Constructor.
	 *
	 * @param string               $method  HTTP method.
	 * @param string               $route   Route.
	 * @param array<string, mixed> $params  Params.
	 * @param array<string, mixed> $files   Files.
	 */
	public function __construct( $method, $route, $params = array(), $files = array() ) {
		unset( $method, $route );
		$this->params = $params;
		$this->files  = $files;
	}

	/**
	 * Get a request parameter.
	 *
	 * @param string $key Parameter key.
	 * @return mixed
	 */
	public function get_param( $key ) {
		return $this->params[ $key ] ?? null;
	}

	/**
	 * Get uploaded files.
	 *
	 * @return array<string, mixed>
	 */
	public function get_file_params() {
		return $this->files;
	}
}
