# Sahajanand Post to Speech — browser runtime WASM

Large WebAssembly binaries used by **browser mode** in the [Sahajanand Post to Speech](../plugins/sahajanand-post-to-speech/) plugin.

These files are **not** included in the WordPress.org plugin zip (keeps the package under 10 MB). On first use in the block editor, the plugin downloads them from this folder into `wp-content/uploads/sahajanand-post-to-speech/vendor/` and serves them locally afterward.

## Files

| File | Source package | Purpose |
|------|----------------|---------|
| `espeak-ng.wasm` | [espeak-ng](https://www.npmjs.com/package/espeak-ng) | Phonemization for browser TTS |
| `ort-wasm-simd-threaded.wasm` | [onnxruntime-web@1.20.1](https://www.npmjs.com/package/onnxruntime-web) | ONNX Runtime Web WASM binary |
| `ort-wasm-simd-threaded.mjs` | [onnxruntime-web@1.20.1](https://www.npmjs.com/package/onnxruntime-web) | ONNX Runtime Web worker module |

## Regenerate

From the plugin directory:

```bash
cd plugins/sahajanand-post-to-speech
npm install
npm run copy:vendor
```

This refreshes both `assets/vendor/` (local dev) and `runtime-assets/` (hosted downloads).

## Download URL

The plugin default base URL is set by `SAHAJANAND_POST_TO_SPEECH_RUNTIME_ASSETS_URL` in `sahajanand-post-to-speech.php`:

`https://raw.githubusercontent.com/vanpariyar/wordpress-plugins/master/runtime-assets/`

You can also override at runtime with the `sahajanand_post_to_speech_runtime_assets_remote_base` filter.
