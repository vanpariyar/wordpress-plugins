<?php
/**
 * Log PHP fatals to a host-visible file (local dev only).
 *
 * @package Post_To_Speech_Dev
 */

register_shutdown_function(
	static function () {
		$error = error_get_last();

		if ( ! $error || ! in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR ), true ) ) {
			return;
		}

		$line = gmdate( 'c' ) . ' ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line'] . PHP_EOL;
		$paths = array(
			__DIR__ . '/fatal.log',
			dirname( __DIR__ ) . '/local-dev/pts-fatal.log',
			dirname( __DIR__ ) . '/local-dev/debug.log',
		);

		foreach ( $paths as $path ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			@file_put_contents( $path, $line, FILE_APPEND );
		}
	}
);
