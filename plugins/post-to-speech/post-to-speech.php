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

if ( defined( 'WP_DEBUG' ) && WP_DEBUG && is_admin() ) {
	register_shutdown_function(
		static function () {
			$error = error_get_last();

			if ( ! $error || ! in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR ), true ) ) {
				return;
			}

			$line = gmdate( 'c' ) . ' ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line'] . PHP_EOL;
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( POST_TO_SPEECH_PATH . 'local-fatal.log', $line, FILE_APPEND );
		}
	);
}

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
	$block_dir = POST_TO_SPEECH_PATH . 'build';

	if ( ! file_exists( $block_dir . '/block.json' ) ) {
		add_action( 'admin_notices', 'post_to_speech_missing_build_notice' );
		return;
	}

	register_block_type( $block_dir );
}
add_action( 'init', 'post_to_speech_register_block' );

/**
 * Warn administrators when compiled block assets are missing.
 */
function post_to_speech_missing_build_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__(
			'Post to Speech is missing compiled block assets in build/. Reinstall the plugin from a package created with scripts/pack-plugin.sh.',
			'post-to-speech'
		)
	);
}

/**
 * Bootstrap plugin services.
 */
function post_to_speech_bootstrap() {
	new Post_To_Speech_Settings();
	new Post_To_Speech_REST_API();
	new Post_To_Speech_Editor_Assets();
}
add_action( 'plugins_loaded', 'post_to_speech_bootstrap' );
