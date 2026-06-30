<?php
/**
 * Uninstall Post to Speech.
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options = array(
	'post_to_speech_generation_mode',
	'post_to_speech_model',
	'post_to_speech_default_voice',
	'post_to_speech_api_url',
	'post_to_speech_api_key',
	'post_to_speech_price_per_request',
	// Legacy options from the pre-rename plugin slug.
	'kitten_tts_generation_mode',
	'kitten_tts_model',
	'kitten_tts_default_voice',
	'kitten_tts_api_url',
	'kitten_tts_api_key',
	'kitten_tts_price_per_request',
);

foreach ( $options as $option ) {
	delete_option( $option );
}
