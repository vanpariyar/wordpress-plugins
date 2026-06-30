<?php
/**
 * Local development: show PHP errors instead of a blank critical error page.
 *
 * @package Post_To_Speech_Dev
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

@ini_set( 'display_errors', '1' );
@ini_set( 'display_startup_errors', '1' );
error_reporting( E_ALL );

register_shutdown_function(
	static function () {
		$error = error_get_last();

		if ( ! $error || ! in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR ), true ) ) {
			return;
		}

		if ( ! headers_sent() ) {
			header( 'Content-Type: text/plain; charset=utf-8', true, 500 );
		}

		echo "PHP Fatal: {$error['message']}\n";
		echo "File: {$error['file']}:{$error['line']}\n";
	}
);
