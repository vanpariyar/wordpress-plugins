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
class Sahajanand_Post_To_Speech_Editor_Assets {

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
		$handle = 'sahajanand-post-to-speech-post-audio-editor-script';

		if ( ! wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		$settings = Sahajanand_Post_To_Speech_Config::get_editor_settings();

		$settings['settingsUrl'] = Sahajanand_Post_To_Speech_Config::get_settings_page_url();

		wp_add_inline_script(
			$handle,
			'window.sahajanandPostToSpeechSettings = ' . wp_json_encode( $settings ) . ';',
			'before'
		);
	}
}
