<?php
/**
 * Base test case for Post to Speech.
 *
 * @package Post_To_Speech
 */

use WP_Mock\Tools\TestCase as WpMockTestCase;

/**
 * Shared helpers for plugin unit tests.
 */
abstract class Post_To_Speech_TestCase extends WpMockTestCase {

	/**
	 * Register WordPress function mocks used across plugin tests.
	 */
	protected function set_up_wordpress_mocks() {
		WP_Mock::userFunction(
			'__',
			array(
				'return' => function ( $text ) {
					return $text;
				},
			)
		);

		WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return' => function ( $text ) {
					return is_string( $text ) ? trim( $text ) : '';
				},
			)
		);

		WP_Mock::userFunction(
			'trailingslashit',
			array(
				'return' => function ( $url ) {
					return rtrim( (string) $url, '/' ) . '/';
				},
			)
		);

		WP_Mock::userFunction(
			'is_wp_error',
			array(
				'return' => function ( $thing ) {
					return $thing instanceof WP_Error;
				},
			)
		);

		WP_Mock::userFunction(
			'size_format',
			array(
				'return' => function ( $bytes ) {
					return (string) $bytes . ' B';
				},
			)
		);
	}

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		WP_Mock::setUp();
		$this->set_up_wordpress_mocks();
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * Load a plugin include file once.
	 *
	 * @param string $filename File name relative to includes/.
	 */
	protected function load_include( $filename ) {
		$path = dirname( __DIR__ ) . '/includes/' . $filename;

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
}
