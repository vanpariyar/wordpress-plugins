<?php
/**
 * Local WordPress debug overrides (included from wp-config.php).
 *
 * @package Post_To_Speech_Dev
 */

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}
if ( ! defined( 'WP_DISABLE_FATAL_ERROR_HANDLER' ) ) {
	define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
}
if ( ! defined( 'WP_DEBUG_LOG' ) ) {
	define( 'WP_DEBUG_LOG', true );
}
if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
	define( 'WP_DEBUG_DISPLAY', true );
}
if ( ! defined( 'SCRIPT_DEBUG' ) ) {
	define( 'SCRIPT_DEBUG', true );
}
if ( ! defined( 'SAVEQUERIES' ) ) {
	define( 'SAVEQUERIES', true );
}
if ( ! defined( 'WP_MEMORY_LIMIT' ) ) {
	define( 'WP_MEMORY_LIMIT', '256M' );
}
if ( ! defined( 'WP_MAX_MEMORY_LIMIT' ) ) {
	define( 'WP_MAX_MEMORY_LIMIT', '512M' );
}

@ini_set( 'display_errors', '1' );
@ini_set( 'log_errors', '1' );
@ini_set( 'error_log', __DIR__ . '/wp-content/local-dev/debug.log' );
