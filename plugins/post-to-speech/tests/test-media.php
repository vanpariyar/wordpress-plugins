<?php
/**
 * Tests for Post_To_Speech_Media.
 *
 * @package Post_To_Speech
 */

require_once __DIR__ . '/Post_To_Speech_TestCase.php';

/**
 * Media helper tests.
 */
class Post_To_Speech_Media_Test extends Post_To_Speech_TestCase {

	/**
	 * Media instance.
	 *
	 * @var Post_To_Speech_Media
	 */
	private $media;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->load_include( 'class-media.php' );

		$this->media = new Post_To_Speech_Media();
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * upload_wav should reject missing temp files.
	 */
	public function test_upload_wav_rejects_missing_file() {
		$result = $this->media->upload_wav(
			array(
				'tmp_name' => '/tmp/does-not-exist.wav',
				'name'     => 'test.wav',
			)
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'post_to_speech_missing_file', $result->get_error_code() );
	}

	/**
	 * upload_wav_bytes should reject empty payloads.
	 */
	public function test_upload_wav_bytes_rejects_empty_payload() {
		$result = $this->media->upload_wav_bytes( '' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'post_to_speech_missing_file', $result->get_error_code() );
	}
}
