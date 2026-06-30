<?php
/**
 * Tests for Post_To_Speech_Settings sanitization.
 *
 * @package Post_To_Speech
 */

require_once __DIR__ . '/Post_To_Speech_TestCase.php';

/**
 * Settings sanitization tests.
 */
class Post_To_Speech_Settings_Test extends Post_To_Speech_TestCase {

	/**
	 * Settings instance.
	 *
	 * @var Post_To_Speech_Settings
	 */
	private $settings;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		WP_Mock::userFunction( 'add_action' );
		WP_Mock::userFunction( 'add_settings_error' );
		WP_Mock::userFunction(
			'esc_url_raw',
			array(
				'return' => function ( $url ) {
					return $url;
				},
			)
		);

		$this->load_include( 'class-config.php' );
		$this->load_include( 'class-settings.php' );

		$this->settings = new Post_To_Speech_Settings();
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Invalid generation mode should fall back to browser.
	 */
	public function test_sanitize_generation_mode_rejects_invalid_value() {
		$this->assertSame(
			Post_To_Speech_Config::MODE_BROWSER,
			$this->settings->sanitize_generation_mode( 'not-a-mode' )
		);
	}

	/**
	 * API mode should be accepted.
	 */
	public function test_sanitize_generation_mode_accepts_api_mode() {
		$this->assertSame(
			Post_To_Speech_Config::MODE_API,
			$this->settings->sanitize_generation_mode( Post_To_Speech_Config::MODE_API )
		);
	}

	/**
	 * Invalid model repo should fall back to default int8 model.
	 */
	public function test_sanitize_model_repo_rejects_unknown_repo() {
		$this->assertSame(
			'KittenML/kitten-tts-nano-0.8-int8',
			$this->settings->sanitize_model_repo( 'Unknown/model' )
		);
	}

	/**
	 * Valid API URL should be stored with trailing slash.
	 */
	public function test_sanitize_api_url_adds_trailing_slash() {
		WP_Mock::userFunction(
			'wp_http_validate_url',
			array(
				'return' => true,
			)
		);

		$this->assertSame(
			'https://tts.example.com/',
			$this->settings->sanitize_api_url( 'https://tts.example.com' )
		);
	}

	/**
	 * Invalid API URL should be rejected.
	 */
	public function test_sanitize_api_url_rejects_invalid_url() {
		WP_Mock::userFunction(
			'wp_http_validate_url',
			array(
				'return' => false,
			)
		);

		$this->assertSame( '', $this->settings->sanitize_api_url( 'not-a-url' ) );
	}

	/**
	 * Negative price should be clamped to zero.
	 */
	public function test_sanitize_price_clamps_negative_values() {
		$this->assertEquals( 0.0, $this->settings->sanitize_price( -5 ) );
	}
}
