=== Post to Speech ===
Contributors: vanpariyar
Donate link: https://vanpariyar.github.io
Tags: audio, text-to-speech, gutenberg, speech, blog
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn blog posts into audio with a Gutenberg block.

== Description ==

Post to Speech helps you convert WordPress posts into listenable audio. Add the **Post to Speech** block, generate speech from your post content, and visitors get a simple audio player — no post text is shown on the frontend.

**Features**

* Gutenberg block for post-to-audio conversion
* **Post content** or **custom text** as the audio source
* Frontend shows only the audio player (no duplicated post text)
* Browser (WASM) or external API generation modes
* 8 built-in voices with adjustable speed
* Generated audio saved to the WordPress media library

**Generation modes**

* **Browser (WASM)** — runs in the block editor using ONNX Runtime Web. No server-side TTS engine required. Speech models are downloaded on first use.
* **API** — proxies requests to a compatible HTTP TTS API you host. Useful for long posts or low-powered devices. Your API key stays on the server.

== Credits ==

This plugin uses the open-source [KittenTTS](https://github.com/KittenML/KittenTTS) speech synthesis models from [KittenML](https://github.com/KittenML) and bundles [eSpeak-NG](https://github.com/espeak-ng/espeak-ng) (GPL-3.0-or-later) for browser phonemization.

Post to Speech is developed independently and is **not affiliated with, endorsed by, or sponsored by KittenML**.

== External services ==

This plugin may contact the following third-party services when generating audio:

**Browser mode (editor only)**

* [Hugging Face](https://huggingface.co/) — downloads speech model files configured in settings (default models published by KittenML).
* [jsDelivr CDN](https://www.jsdelivr.com/) — loads ONNX Runtime Web in the editor. eSpeak-NG is bundled with the plugin.

**API mode (server-side, when configured)**

* Your configured TTS API URL — WordPress sends post text and receives WAV audio. Only the site administrator configures this URL and API key.

No data is sent to external services on the public frontend unless you use API mode during generation in the editor.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/post-to-speech/` or install from the WordPress plugin directory.
2. Activate the plugin through the **Plugins** screen.
3. Go to **Settings → Post to Speech** and choose **Browser** or **API** mode.
4. Edit a post, add the **Post to Speech** block, and click **Generate audio**.

== Frequently Asked Questions ==

= Can I convert an entire blog post to audio? =

Yes. Set **Text source** to **Post content** in the block sidebar. The block reads the full post text when you generate audio.

= Is post text shown on the frontend? =

No. Only the audio player is displayed to visitors.

= Which mode should I use? =

Use **Browser** on modern desktops with a good internet connection for the first model download. Use **API** if you host a compatible TTS HTTP service or need server-side generation.

= Where do I get an API service? =

API mode expects a REST API that accepts JSON and returns WAV audio. You can self-host a service using the open-source [KittenTTS](https://github.com/KittenML/KittenTTS) project.

= Does this work on shared hosting? =

Yes. Browser mode needs no special server software. API mode only requires `wp_remote_post()` outbound HTTP access to your API URL.

== Screenshots ==

1. Post to Speech block in the editor with post content preview
2. Plugin settings — browser and API mode options
3. Frontend audio player embedded in a post

== Changelog ==

= 1.4.0 =
* Renamed plugin to Post to Speech for WordPress.org trademark compliance.
* KittenTTS credited in readme; no Kitten branding in plugin name.

= 1.3.0 =
* Added post content vs custom text source for blog-to-audio workflows.
* Frontend now shows only the audio player (no caption text).
* Fixed browser phonemization for words like "writing".
* WordPress.org submission readiness: uninstall cleanup, security hardening, documentation.

= 1.2.0 =
* Added API generation mode with server-side proxy and usage support.

= 1.1.0 =
* Browser-based WASM generation with ONNX Runtime Web and eSpeak-NG.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.4.0 =
Plugin renamed to Post to Speech. Re-add the block if needed after updating.
