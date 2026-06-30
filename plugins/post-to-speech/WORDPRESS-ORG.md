# WordPress.org submission checklist

Use this checklist before submitting **Post to Speech** to the [WordPress Plugin Directory](https://wordpress.org/plugins/developers/add/).

## Account and slug

- [ ] Log in at [wordpress.org/plugins/developers/add/](https://wordpress.org/plugins/developers/add/)
- [ ] Request slug: `post-to-speech` (must match main plugin folder and text domain)
- [ ] Confirm **Contributors** in `readme.txt` uses your WordPress.org username (`vanpariyar`)

## Plugin zip contents

Create a zip of the `post-to-speech` folder **without** `node_modules/`.

Required in the zip:

- [ ] `post-to-speech.php`
- [ ] `readme.txt`
- [ ] `LICENSE`
- [ ] `uninstall.php`
- [ ] `index.php` in root, `includes/`, `assets/`, `build/`, `src/`
- [ ] `includes/` PHP classes
- [ ] `build/` compiled block assets
- [ ] `src/` unminified JavaScript sources (required because `build/` is minified)
- [ ] `assets/admin-settings.js`

Exclude from zip:

- [ ] `node_modules/`
- [ ] `.git/`, `.github/`, `.distignore`

Quick zip from repo root:

```bash
cd plugins
zip -r post-to-speech.zip post-to-speech \
  -x "post-to-speech/node_modules/*" \
  -x "post-to-speech/package-lock.json" \
  -x "post-to-speech/package.json" \
  -x "post-to-speech/.git/*" \
  -x "post-to-speech/.github/*" \
  -x "post-to-speech/.distignore" \
  -x "post-to-speech/WORDPRESS-ORG.md"
```

## SVN assets (after approval)

Upload these separately to `svn://plugins.svn.wordpress.org/post-to-speech/assets/`:

- [ ] `icon-256x256.png` (256×256)
- [ ] `icon-128x128.png` (128×128)
- [ ] `banner-772x250.png` (772×250)
- [ ] `banner-1544x500.png` (1544×500, optional retina)
- [ ] Screenshots `screenshot-1.png`, `screenshot-2.png`, `screenshot-3.png` (1200×900 recommended)

## Pre-submission testing

- [ ] Activate on a clean WordPress 6.x install
- [ ] Generate audio in **Browser** mode
- [ ] Generate audio in **API** mode (if you have a test API)
- [ ] Confirm frontend shows audio only (no post text)
- [ ] Confirm **Post content** source reads the full post
- [ ] Deactivate and delete plugin — options removed via `uninstall.php`

## Plugin review notes

Mention in the submission notes if asked:

1. **External services** — Browser mode loads models from Hugging Face and libraries from jsDelivr in the **editor only**. Documented in `readme.txt` under *External services*.
2. **API mode** — Outbound HTTP only to the administrator-configured API URL.
3. **No telemetry** — The plugin does not phone home or track users.
4. **GPL** — KittenTTS model usage is subject to its own license; the plugin code is GPLv2+.

## After approval

1. Replace `Plugin URI` in `post-to-speech.php` with `https://wordpress.org/plugins/post-to-speech/`
2. Deploy with SVN: `https://plugins.svn.wordpress.org/post-to-speech/`
3. Tag releases matching `Stable tag` in `readme.txt`
