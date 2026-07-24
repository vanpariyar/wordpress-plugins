<?php
/**
 * PHPStan bootstrap for Sahajanand Post to Speech.
 *
 * Defines plugin constants that are normally set in the main plugin file after
 * the WordPress ABSPATH guard.
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/wordpress-stub/' );
}

if ( ! defined( 'SAHAJANAND_POST_TO_SPEECH_VERSION' ) ) {
	define( 'SAHAJANAND_POST_TO_SPEECH_VERSION', '1.0.2' );
}

if ( ! defined( 'SAHAJANAND_POST_TO_SPEECH_PATH' ) ) {
	define(
		'SAHAJANAND_POST_TO_SPEECH_PATH',
		dirname( __DIR__ ) . '/plugins/sahajanand-post-to-speech/'
	);
}

if ( ! defined( 'SAHAJANAND_POST_TO_SPEECH_URL' ) ) {
	define(
		'SAHAJANAND_POST_TO_SPEECH_URL',
		'http://example.com/wp-content/plugins/sahajanand-post-to-speech/'
	);
}
