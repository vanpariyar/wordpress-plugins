<?php
/**
 * Plugin Name:       Post to Speech
 * Plugin URI:        https://wordpress.org/plugins/post-to-speech/
 * Description:       Convert post content or custom text to speech and embed an audio player using a Gutenberg block.
 * Version:           1.4.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Ronak Vanpariya
 * Author URI:        https://vanpariyar.github.io
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       post-to-speech
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'POST_TO_SPEECH_VERSION', '1.4.0' );
define( 'POST_TO_SPEECH_PATH', plugin_dir_path( __FILE__ ) );
define( 'POST_TO_SPEECH_URL', plugin_dir_url( __FILE__ ) );

require_once POST_TO_SPEECH_PATH . 'includes/class-config.php';
require_once POST_TO_SPEECH_PATH . 'includes/class-media.php';
require_once POST_TO_SPEECH_PATH . 'includes/class-api-client.php';
require_once POST_TO_SPEECH_PATH . 'includes/class-settings.php';
require_once POST_TO_SPEECH_PATH . 'includes/class-rest-api.php';
require_once POST_TO_SPEECH_PATH . 'includes/class-editor-assets.php';

/**
 * Register the Gutenberg block.
 */
function post_to_speech_register_block() {
	register_block_type( POST_TO_SPEECH_PATH . 'build' );
}
add_action( 'init', 'post_to_speech_register_block' );

/**
 * Bootstrap plugin services.
 */
function post_to_speech_bootstrap() {
	new Post_To_Speech_Settings();
	new Post_To_Speech_REST_API();
	new Post_To_Speech_Editor_Assets();
}
add_action( 'plugins_loaded', 'post_to_speech_bootstrap' );
