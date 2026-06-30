<?php
/**
 * Local smoke test: load plugin bootstrap without full WordPress.
 *
 * Usage: php scripts/smoke-load.php
 */

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

define( 'ABSPATH', sys_get_temp_dir() . '/' );
define( 'MB_IN_BYTES', 1024 * 1024 );
define( 'POST_TO_SPEECH_VERSION', '1.4.0' );
define( 'POST_TO_SPEECH_PATH', dirname( __DIR__ ) . '/' );
define( 'POST_TO_SPEECH_URL', 'http://example.com/wp-content/plugins/post-to-speech/' );

$hooks = array();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	global $hooks;
	$hooks[ $hook ][] = $callback;
}

function __ ( $text ) {
	return $text;
}

function esc_html__( $text ) {
	return $text;
}

function esc_html( $text ) {
	return $text;
}

function current_user_can() {
	return true;
}

function register_block_type( $path ) {
	if ( ! file_exists( $path . '/block.json' ) ) {
		throw new RuntimeException( 'block.json missing at ' . $path );
	}
	echo "register_block_type OK: {$path}\n";
}

function get_option( $key, $default = false ) {
	return $default;
}

function register_setting() {}
function add_settings_section() {}
function add_settings_field() {}
function register_rest_route() {}

require POST_TO_SPEECH_PATH . 'post-to-speech.php';

foreach ( $hooks['plugins_loaded'] ?? array() as $callback ) {
	$callback();
}

foreach ( $hooks['init'] ?? array() as $callback ) {
	$callback();
}

echo "Smoke load passed.\n";
