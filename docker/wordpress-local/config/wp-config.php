<?php
/**
 * Local WordPress config (mounted over container wp-config.php).
 *
 * @package Post_To_Speech_Dev
 */

if ( ! function_exists( 'getenv_docker' ) ) {
	/**
	 * Docker env helper (matches official WordPress image).
	 *
	 * @param string $env     Environment variable name.
	 * @param string $default Default value.
	 * @return string
	 */
	function getenv_docker( $env, $default ) {
		if ( $file_env = getenv( $env . '_FILE' ) ) {
			return rtrim( file_get_contents( $file_env ), "\r\n" );
		}

		if ( ( $val = getenv( $env ) ) !== false ) {
			return $val;
		}

		return $default;
	}
}

define( 'DB_NAME', getenv_docker( 'WORDPRESS_DB_NAME', 'wordpress' ) );
define( 'DB_USER', getenv_docker( 'WORDPRESS_DB_USER', 'wordpress' ) );
define( 'DB_PASSWORD', getenv_docker( 'WORDPRESS_DB_PASSWORD', 'wordpress' ) );
define( 'DB_HOST', getenv_docker( 'WORDPRESS_DB_HOST', 'db' ) );
define( 'DB_CHARSET', getenv_docker( 'WORDPRESS_DB_CHARSET', 'utf8' ) );
define( 'DB_COLLATE', getenv_docker( 'WORDPRESS_DB_COLLATE', '' ) );

define( 'AUTH_KEY', 'local-dev-auth-key' );
define( 'SECURE_AUTH_KEY', 'local-dev-secure-auth-key' );
define( 'LOGGED_IN_KEY', 'local-dev-logged-in-key' );
define( 'NONCE_KEY', 'local-dev-nonce-key' );
define( 'AUTH_SALT', 'local-dev-auth-salt' );
define( 'SECURE_AUTH_SALT', 'local-dev-secure-auth-salt' );
define( 'LOGGED_IN_SALT', 'local-dev-logged-in-salt' );
define( 'NONCE_SALT', 'local-dev-nonce-salt' );

$table_prefix = getenv_docker( 'WORDPRESS_TABLE_PREFIX', 'wp_' );

define( 'WP_DEBUG', true );

require_once __DIR__ . '/wp-config-debug.php';

register_shutdown_function(
	static function () {
		$error = error_get_last();

		if ( ! $error || ! in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR ), true ) ) {
			return;
		}

		$line = gmdate( 'c' ) . ' ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line'] . PHP_EOL;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		@file_put_contents( __DIR__ . '/wp-content/local-dev/pts-fatal.log', $line, FILE_APPEND );
	}
);

/* That's all, stop editing! Happy publishing. */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
