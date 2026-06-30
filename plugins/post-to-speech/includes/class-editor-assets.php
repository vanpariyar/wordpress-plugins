<?php
/**
 * Editor asset localization.
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pass runtime settings to the block editor script.
 */
class Post_To_Speech_Editor_Assets {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'localize_settings' ) );
	}

	/**
	 * Localize plugin settings for browser-side TTS.
	 */
	public function localize_settings() {
		$handle = 'post-to-speech-post-audio-editor-script';

		if ( ! wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		$settings = Post_To_Speech_Config::get_editor_settings();

		$settings['settingsUrl'] = Post_To_Speech_Config::get_settings_page_url();

		wp_add_inline_script(
			$handle,
			'window.postToSpeechSettings = ' . wp_json_encode( $settings ) . ';',
			'before'
		);
	}
}
