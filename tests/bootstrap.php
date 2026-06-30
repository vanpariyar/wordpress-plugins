<?php
/**
 * PHPUnit bootstrap for the WordPress plugins monorepo.
 *
 * @package WordPress_Plugins_Monorepo
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/tests/wordpress-stub/' );
}

if ( ! defined( 'MB_IN_BYTES' ) ) {
	define( 'MB_IN_BYTES', 1024 * 1024 );
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error stub for unit tests.
	 */
	class WP_Error {
		/**
		 * Error code.
		 *
		 * @var string
		 */
		private $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private $message;

		/**
		 * Constructor.
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 * @param mixed  $data    Optional data.
		 */
		public function __construct( $code = '', $message = '', $data = '' ) {
			unset( $data );
			$this->code    = $code;
			$this->message = $message;
		}

		/**
		 * Get error code.
		 *
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}

		/**
		 * Get error message.
		 *
		 * @return string
		 */
		public function get_error_message() {
			return $this->message;
		}
	}
}

WP_Mock::bootstrap();

$admin_includes = ABSPATH . 'wp-admin/includes';

if ( ! is_dir( $admin_includes ) ) {
	mkdir( $admin_includes, 0777, true );
}

foreach ( array( 'file.php', 'media.php', 'image.php' ) as $admin_file ) {
	$path = $admin_includes . '/' . $admin_file;

	if ( ! file_exists( $path ) ) {
		file_put_contents( $path, "<?php\n// WordPress admin stub for tests.\n" );
	}
}
