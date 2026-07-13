# WordPress.org plugin assets

These files deploy to the WordPress.org SVN `assets/` directory (not `trunk/`).
They appear on https://wordpress.org/plugins/sahajanand-post-to-speech/

Push changes here, then upload with Pressship:

```bash
bash scripts/pressship.sh assets sahajanand-post-to-speech
```

Or push to GitHub to run `.github/workflows/deploy-wordpress-org-assets.yml`.

## Required files

| File | Size | Purpose |
|------|------|---------|
| `banner-772x250.png` | 772×250 | Plugin page header |
| `icon-128x128.png` | 128×128 | Search / admin icon |
| `icon-256x256.png` | 256×256 | Retina icon |
| `screenshot-1.png` | any | Block in editor (see readme.txt) |
| `screenshot-2.png` | any | Settings screen |
| `screenshot-3.png` | any | Frontend audio player |

Optional: `banner-1544x500.png` (retina banner), `icon.svg` (with PNG fallback).

## Playground demo blueprint

WordPress.org **Live Preview** requires `blueprints/blueprint.json` under the SVN `assets/` folder:

```
.wporg_assets/
└── blueprints/
    └── blueprint.json
```

This demo blueprint:

- Logs in as admin
- Sets **Browser** generation mode
- Replaces the default “Hello world” post with a draft containing the **Sahajanand Post To Speech** block
- Opens the post editor (`/wp-admin/post.php?post=1&action=edit`)
- Enables **networking** so browser mode can download speech models from Hugging Face

After upload, committers see a **Test Preview** button on the plugin page. A WordPress.org committer must set the preview to **public** in the plugin Advanced settings.

Docs: https://developer.wordpress.org/plugins/wordpress-org/previews-and-blueprints/

Naming rules: https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/

Replace the placeholder PNGs in this folder with final designs before release.
