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
		wp_add_inline_script(
			'post-to-speech-post-audio-editor-script',
			'window.postToSpeechSettings = ' . wp_json_encode( Post_To_Speech_Config::get_editor_settings() ) . ';',
			'before'
		);
	}
}
