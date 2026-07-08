<?php
/**
 * Uninstall Sahajanand Post to Speech.
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$sahajanand_post_to_speech_options = array(
	'sahajanand_post_to_speech_generation_mode',
	'sahajanand_post_to_speech_model',
	'sahajanand_post_to_speech_default_voice',
	'sahajanand_post_to_speech_api_url',
	'sahajanand_post_to_speech_api_key',
	'sahajanand_post_to_speech_price_per_request',
);

foreach ( $sahajanand_post_to_speech_options as $sahajanand_post_to_speech_option ) {
	delete_option( $sahajanand_post_to_speech_option );
}
