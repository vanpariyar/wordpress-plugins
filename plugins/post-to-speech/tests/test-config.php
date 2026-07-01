<?php
/**
 * Tests for Post_To_Speech_Config.
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Post_To_Speech_TestCase.php';

/**
 * Config class tests.
 */
class Post_To_Speech_Config_Test extends Post_To_Speech_TestCase {

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->load_include( 'class-config.php' );
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Voices list should contain eight entries.
	 */
	public function test_get_voices_returns_eight_voices() {
		$this->assertCount( 8, Post_To_Speech_Config::get_voices() );
		$this->assertContains( 'Bella', Post_To_Speech_Config::get_voices() );
	}

	/**
	 * Invalid generation mode should fall back to browser.
	 */
	public function test_get_generation_mode_falls_back_to_browser() {
		WP_Mock::userFunction( 'get_option' )
			->with( 'post_to_speech_generation_mode', Post_To_Speech_Config::MODE_BROWSER )
			->andReturn( 'invalid-mode' );

		$this->assertSame( Post_To_Speech_Config::MODE_BROWSER, Post_To_Speech_Config::get_generation_mode() );
	}

	/**
	 * Browser mode should map int8 model to fp32 in editor settings.
	 */
	public function test_get_editor_settings_maps_int8_to_fp32_in_browser_mode() {
		WP_Mock::userFunction( 'get_option' )
			->andReturnUsing(
				function ( $key, $default = false ) {
					$options = array(
						'post_to_speech_generation_mode'   => Post_To_Speech_Config::MODE_BROWSER,
						'post_to_speech_model'             => 'KittenML/kitten-tts-nano-0.8-int8',
						'post_to_speech_default_voice'     => 'Jasper',
						'post_to_speech_price_per_request' => 0,
					);

					return $options[ $key ] ?? $default;
				}
			);

		$settings = Post_To_Speech_Config::get_editor_settings();

		$this->assertSame( Post_To_Speech_Config::MODE_BROWSER, $settings['generationMode'] );
		$this->assertSame( 'KittenML/kitten-tts-nano-0.8-fp32', $settings['modelRepo'] );
		$this->assertFalse( $settings['apiConfigured'] );
		$this->assertArrayNotHasKey( 'settingsUrl', $settings );
	}

	/**
	 * Settings page URL should use admin_url when available.
	 */
	public function test_get_settings_page_url_uses_admin_url() {
		WP_Mock::userFunction( 'admin_url' )
			->with( 'options-general.php?page=post-to-speech' )
			->andReturn( 'http://example.com/wp-admin/options-general.php?page=post-to-speech' );

		$this->assertSame(
			'http://example.com/wp-admin/options-general.php?page=post-to-speech',
			Post_To_Speech_Config::get_settings_page_url()
		);
	}

	/**
	 * API mode should report configured when URL and key exist.
	 */
	public function test_is_api_configured_requires_url_and_key() {
		WP_Mock::userFunction( 'get_option' )
			->andReturnUsing(
				function ( $key ) {
					$options = array(
						'post_to_speech_api_url' => 'https://tts.example.com/',
						'post_to_speech_api_key' => 'secret',
					);

					return $options[ $key ] ?? '';
				}
			);

		$this->assertTrue( Post_To_Speech_Config::is_api_configured() );
	}

	/**
	 * Model repo validation should reject unknown repos.
	 */
	public function test_is_allowed_model_repo_rejects_unknown_repo() {
		$this->assertFalse( Post_To_Speech_Config::is_allowed_model_repo( 'Evil/unknown-model' ) );
		$this->assertTrue( Post_To_Speech_Config::is_allowed_model_repo( 'KittenML/kitten-tts-mini-0.8' ) );
	}

	/**
	 * Upload limit should default to 64 MB.
	 */
	public function test_get_max_upload_bytes_defaults_to_sixty_four_megabytes() {
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'post_to_speech_max_upload_bytes', 64 * MB_IN_BYTES )
			->andReturnUsing(
				function ( $tag, $value ) {
					return $value;
				}
			);

		$this->assertSame( 64 * MB_IN_BYTES, Post_To_Speech_Config::get_max_upload_bytes() );
	}
}
