<?php
/**
 * Plugin Name:       Sahajanand Post to Speech
 * Plugin URI:        https://wordpress.org/plugins/sahajanand-post-to-speech/
 * Description:       Convert post content or custom text to speech with Sahajanand Post to Speech and embed an audio player using a Gutenberg block.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Ronak Vanpariya
 * Author URI:        https://vanpariyar.github.io
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sahajanand-post-to-speech
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SAHAJANAND_POST_TO_SPEECH_VERSION', '1.0.0' );
define( 'SAHAJANAND_POST_TO_SPEECH_PATH', plugin_dir_path( __FILE__ ) );
define( 'SAHAJANAND_POST_TO_SPEECH_URL', plugin_dir_url( __FILE__ ) );

if ( defined( 'WP_DEBUG' ) && WP_DEBUG && is_admin() ) {
	register_shutdown_function(
		static function () {
			$error = error_get_last();

			if ( ! $error || ! in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR ), true ) ) {
				return;
			}

			$line = $error['message'] . ' in ' . $error['file'] . ':' . $error['line'];
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $line );
		}
	);
}

require_once SAHAJANAND_POST_TO_SPEECH_PATH . 'includes/class-config.php';
require_once SAHAJANAND_POST_TO_SPEECH_PATH . 'includes/class-runtime-assets.php';
require_once SAHAJANAND_POST_TO_SPEECH_PATH . 'includes/class-media.php';
require_once SAHAJANAND_POST_TO_SPEECH_PATH . 'includes/class-api-client.php';
require_once SAHAJANAND_POST_TO_SPEECH_PATH . 'includes/class-settings.php';
require_once SAHAJANAND_POST_TO_SPEECH_PATH . 'includes/class-rest-api.php';
require_once SAHAJANAND_POST_TO_SPEECH_PATH . 'includes/class-editor-assets.php';

/**
 * Register the Gutenberg block.
 */
function sahajanand_post_to_speech_register_block() {
	$block_dir = SAHAJANAND_POST_TO_SPEECH_PATH . 'build';

	if ( ! file_exists( $block_dir . '/block.json' ) ) {
		add_action( 'admin_notices', 'sahajanand_post_to_speech_missing_build_notice' );
		return;
	}

	register_block_type( $block_dir );
}
add_action( 'init', 'sahajanand_post_to_speech_register_block' );

/**
 * Warn administrators when compiled block assets are missing.
 */
function sahajanand_post_to_speech_missing_build_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__(
			'Sahajanand Post to Speech is missing compiled block assets in build/. Reinstall the plugin from a package created with scripts/pack-plugin.sh.',
			'sahajanand-post-to-speech'
		)
	);
}

/**
 * Bootstrap plugin services.
 */
function sahajanand_post_to_speech_bootstrap() {
	new Sahajanand_Post_To_Speech_Settings();
	new Sahajanand_Post_To_Speech_REST_API();
	new Sahajanand_Post_To_Speech_Editor_Assets();
}
add_action( 'plugins_loaded', 'sahajanand_post_to_speech_bootstrap' );
