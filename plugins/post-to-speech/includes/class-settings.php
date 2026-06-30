<?php
/**
 * Plugin settings page.
 *
 * @package Post_To_Speech
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders Post to Speech settings.
 */
class Post_To_Speech_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add settings page under Settings.
	 */
	public function register_menu() {
		add_options_page(
			__( 'Post to Speech', 'post-to-speech' ),
			__( 'Post to Speech', 'post-to-speech' ),
			'manage_options',
			'post-to-speech',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register plugin options.
	 */
	public function register_settings() {
		register_setting(
			'post_to_speech_settings',
			'post_to_speech_generation_mode',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_generation_mode' ),
				'default'           => Post_To_Speech_Config::MODE_BROWSER,
			)
		);

		register_setting(
			'post_to_speech_settings',
			'post_to_speech_model',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_model_repo' ),
				'default'           => 'KittenML/kitten-tts-nano-0.8-int8',
			)
		);

		register_setting(
			'post_to_speech_settings',
			'post_to_speech_default_voice',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'Bella',
			)
		);

		register_setting(
			'post_to_speech_settings',
			'post_to_speech_api_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_api_url' ),
				'default'           => '',
			)
		);

		register_setting(
			'post_to_speech_settings',
			'post_to_speech_api_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'post_to_speech_settings',
			'post_to_speech_price_per_request',
			array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_price' ),
				'default'           => 0,
			)
		);

		add_settings_section(
			'post_to_speech_main',
			__( 'Generation Mode', 'post-to-speech' ),
			array( $this, 'render_section' ),
			'post-to-speech'
		);

		add_settings_field(
			'post_to_speech_generation_mode',
			__( 'Mode', 'post-to-speech' ),
			array( $this, 'render_mode_field' ),
			'post-to-speech',
			'post_to_speech_main'
		);

		add_settings_field(
			'post_to_speech_model',
			__( 'Browser model', 'post-to-speech' ),
			array( $this, 'render_model_field' ),
			'post-to-speech',
			'post_to_speech_main',
			array( 'class' => 'post-to-speech-setting-browser' )
		);

		add_settings_field(
			'post_to_speech_api_url',
			__( 'API base URL', 'post-to-speech' ),
			array( $this, 'render_api_url_field' ),
			'post-to-speech',
			'post_to_speech_main',
			array( 'class' => 'post-to-speech-setting-api' )
		);

		add_settings_field(
			'post_to_speech_api_key',
			__( 'API key', 'post-to-speech' ),
			array( $this, 'render_api_key_field' ),
			'post-to-speech',
			'post_to_speech_main',
			array( 'class' => 'post-to-speech-setting-api' )
		);

		add_settings_field(
			'post_to_speech_price_per_request',
			__( 'Price per request', 'post-to-speech' ),
			array( $this, 'render_price_field' ),
			'post-to-speech',
			'post_to_speech_main',
			array( 'class' => 'post-to-speech-setting-api' )
		);

		add_settings_field(
			'post_to_speech_default_voice',
			__( 'Default voice', 'post-to-speech' ),
			array( $this, 'render_voice_field' ),
			'post-to-speech',
			'post_to_speech_main'
		);
	}

	/**
	 * Enqueue small admin script for conditional settings UI.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_post-to-speech' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'post-to-speech-settings',
			POST_TO_SPEECH_URL . 'assets/admin-settings.js',
			array(),
			POST_TO_SPEECH_VERSION,
			true
		);
	}

	/**
	 * Sanitize generation mode.
	 *
	 * @param string $mode Submitted mode.
	 * @return string
	 */
	public function sanitize_generation_mode( $mode ) {
		$mode = sanitize_text_field( $mode );

		if ( ! in_array( $mode, Post_To_Speech_Config::get_generation_modes(), true ) ) {
			return Post_To_Speech_Config::MODE_BROWSER;
		}

		return $mode;
	}

	/**
	 * Sanitize the selected model repository.
	 *
	 * @param string $model_repo Submitted model repo.
	 * @return string
	 */
	public function sanitize_model_repo( $model_repo ) {
		$model_repo = sanitize_text_field( $model_repo );

		if ( ! Post_To_Speech_Config::is_allowed_model_repo( $model_repo ) ) {
			add_settings_error(
				'post_to_speech_model',
				'post_to_speech_invalid_model',
				__( 'Please choose one of the supported KittenTTS models.', 'post-to-speech' )
			);

			return 'KittenML/kitten-tts-nano-0.8-int8';
		}

		return $model_repo;
	}

	/**
	 * Sanitize API base URL.
	 *
	 * @param string $url Submitted URL.
	 * @return string
	 */
	public function sanitize_api_url( $url ) {
		$url = esc_url_raw( trim( $url ) );

		if ( empty( $url ) ) {
			return '';
		}

		if ( ! wp_http_validate_url( $url ) ) {
			add_settings_error(
				'post_to_speech_api_url',
				'post_to_speech_invalid_api_url',
				__( 'Please enter a valid HTTP or HTTPS API URL.', 'post-to-speech' )
			);

			return '';
		}

		return trailingslashit( $url );
	}

	/**
	 * Sanitize price per request.
	 *
	 * @param mixed $price Submitted price.
	 * @return float
	 */
	public function sanitize_price( $price ) {
		$price = (float) $price;
		return max( 0, $price );
	}

	/**
	 * Render settings section description.
	 */
	public function render_section() {
		echo '<p>';
		esc_html_e(
			'Choose browser WASM generation (runs in the block editor) or an external KittenTTS-compatible API service (better for low-powered devices or long posts).',
			'post-to-speech'
		);
		echo '</p>';
		echo '<p>';
		printf(
			wp_kses(
				/* translators: %s: KittenTTS project link. */
				__( 'API mode requires a self-hosted KittenTTS HTTP API. See the %s project for details.', 'post-to-speech' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
						'rel'    => array(),
					),
				)
			),
			'<a href="https://github.com/KittenML/KittenTTS" target="_blank" rel="noopener noreferrer">KittenTTS</a>'
		);
		echo '</p>';
	}

	/**
	 * Render generation mode field.
	 */
	public function render_mode_field() {
		$value = get_option( 'post_to_speech_generation_mode', Post_To_Speech_Config::MODE_BROWSER );

		echo '<select name="post_to_speech_generation_mode" id="post_to_speech_generation_mode">';
		printf(
			'<option value="%s" %s>%s</option>',
			esc_attr( Post_To_Speech_Config::MODE_BROWSER ),
			selected( $value, Post_To_Speech_Config::MODE_BROWSER, false ),
			esc_html__( 'Browser (WASM in editor)', 'post-to-speech' )
		);
		printf(
			'<option value="%s" %s>%s</option>',
			esc_attr( Post_To_Speech_Config::MODE_API ),
			selected( $value, Post_To_Speech_Config::MODE_API, false ),
			esc_html__( 'API (external KittenTTS service)', 'post-to-speech' )
		);
		echo '</select>';
	}

	/**
	 * Render model field.
	 */
	public function render_model_field() {
		$value  = get_option( 'post_to_speech_model', 'KittenML/kitten-tts-nano-0.8-int8' );
		$models = Post_To_Speech_Config::get_allowed_model_repos();
		$labels = array(
			'KittenML/kitten-tts-nano-0.8-int8' => __( 'Nano int8 (~25 MB)', 'post-to-speech' ),
			'KittenML/kitten-tts-nano-0.8-fp32' => __( 'Nano fp32 (~56 MB)', 'post-to-speech' ),
			'KittenML/kitten-tts-micro-0.8'     => __( 'Micro (~41 MB)', 'post-to-speech' ),
			'KittenML/kitten-tts-mini-0.8'      => __( 'Mini (~80 MB)', 'post-to-speech' ),
		);

		echo '<select name="post_to_speech_model">';
		foreach ( $models as $model ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $model ),
				selected( $value, $model, false ),
				esc_html( $labels[ $model ] ?? $model )
			);
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Browser mode auto-uses the fp32 model when int8 is selected (int8 is unreliable in WASM). Downloaded on first use.', 'post-to-speech' ) . '</p>';
	}

	/**
	 * Render API URL field.
	 */
	public function render_api_url_field() {
		$value = get_option( 'post_to_speech_api_url', '' );
		printf(
			'<input type="url" class="regular-text" name="post_to_speech_api_url" value="%s" placeholder="https://tts.example.com/" />',
			esc_attr( $value )
		);
		echo '<p class="description">' . esc_html__( 'Base URL of your KittenTTS API service (include trailing slash).', 'post-to-speech' ) . '</p>';
	}

	/**
	 * Render API key field.
	 */
	public function render_api_key_field() {
		$value = get_option( 'post_to_speech_api_key', '' );
		printf(
			'<input type="password" class="regular-text" name="post_to_speech_api_key" value="%s" autocomplete="new-password" />',
			esc_attr( $value )
		);
		echo '<p class="description">' . esc_html__( 'Stored on the server and never exposed to the browser.', 'post-to-speech' ) . '</p>';
	}

	/**
	 * Render price per request field.
	 */
	public function render_price_field() {
		$value = get_option( 'post_to_speech_price_per_request', 0 );
		printf(
			'<input type="number" step="0.0001" min="0" class="small-text" name="post_to_speech_price_per_request" value="%s" />',
			esc_attr( $value )
		);
		echo '<p class="description">' . esc_html__( 'Display-only estimate shown in the editor. Actual billing is handled by your API service.', 'post-to-speech' ) . '</p>';
	}

	/**
	 * Render default voice field.
	 */
	public function render_voice_field() {
		$value  = get_option( 'post_to_speech_default_voice', 'Bella' );
		$voices = Post_To_Speech_Config::get_voices();

		echo '<select name="post_to_speech_default_voice">';
		foreach ( $voices as $voice ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $voice ),
				selected( $value, $voice, false ),
				esc_html( $voice )
			);
		}
		echo '</select>';
	}

	/**
	 * Render settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'post_to_speech_settings' );
				do_settings_sections( 'post-to-speech' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
