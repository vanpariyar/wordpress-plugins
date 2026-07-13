# WordPress Plugins Monorepo

This repository is a monorepo for WordPress plugins. Each plugin lives in `plugins/<slug>/`, is versioned independently, and can be released to GitHub and WordPress.org on its own.

Inspired by the [sahajananddigital/wordpress-plugins](https://github.com/sahajananddigital/wordpress-plugins) monorepo layout.

---

## Repository Structure

```
wordpress-plugins/
├── .github/
│   ├── workflows/
│   │   ├── release-plugins.yml           # GitHub Release + WordPress.org SVN deploy
│   │   ├── verify-plugins.yml            # CI: validate release zip includes build/
│   │   ├── deploy-wordpress-org.yml      # Manual WordPress.org SVN deploy
│   │   └── deploy-wordpress-org-assets.yml
│   └── wporg-plugins.json                # Plugins auto-deployed to WordPress.org
├── docker/
│   ├── kitten-tts-api/                   # Self-hosted KittenTTS API (API mode)
│   └── wordpress-local/                  # Local WordPress for plugin development
├── plugins/
│   └── sahajanand-post-to-speech/        # Sahajanand Post to Speech Gutenberg block
├── scripts/
│   ├── bump-plugin-version.sh            # Version bump helper
│   ├── stage-plugin.sh                   # Build + .distignore staging
│   ├── pack-plugin.sh                    # Create release zip (requires build/)
│   ├── deploy-wordpress-org.sh           # SVN trunk/tags/assets deploy
│   ├── pressship-assets.sh               # Upload .wporg_assets to WordPress.org
│   └── pressship.sh                      # WordPress.org publish helper (Pressship)
├── composer.json                         # Shared PHP dev dependencies
├── phpcs.xml.dist
├── phpunit.xml.dist
├── LICENSE
└── README.md
```

---

## Plugins

| Plugin | Description | WordPress.org |
|--------|-------------|---------------|
| [sahajanand-post-to-speech](plugins/sahajanand-post-to-speech/) | Convert posts to audio with a Gutenberg block — browser WASM or API mode | [wordpress.org/plugins/sahajanand-post-to-speech](https://wordpress.org/plugins/sahajanand-post-to-speech/) |

**Current version:** 1.0.1

---

## Local Development

### WordPress (Docker)

```bash
cd docker/wordpress-local
docker compose up -d
```

- **WordPress:** http://localhost:8888
- **Admin:** `admin` / `admin123`
- The plugin is mounted from `plugins/sahajanand-post-to-speech/`

See [docker/wordpress-local/README.md](docker/wordpress-local/README.md) for debugging, resets, and KittenTTS API setup.

### Build plugin assets

```bash
cd plugins/sahajanand-post-to-speech
npm install
npm run build
```

Block plugins register from `build/` — the compiled output must be present for the block to work on WordPress.org installs.

### PHP linting and tests

From the repo root:

```bash
composer install
composer lint      # Run PHPCS
composer lint:fix  # Auto-fix coding standard issues
composer test      # Run PHPUnit
```

---

## KittenTTS API (optional)

Self-host a pay-per-request KittenTTS API for the plugin's **API mode**:

```bash
cd docker/kitten-tts-api
cp .env.example .env
docker compose up --build
```

See [docker/kitten-tts-api/README.md](docker/kitten-tts-api/README.md) for endpoints, billing, and WordPress setup.

---

## Automated Releases

Releases are driven by **version detection** in each plugin's main PHP file. You do not need to manually create git tags.

### Version bump

```bash
# List plugins and current versions
./scripts/bump-plugin-version.sh --list

# Bump patch (default), minor, or major
./scripts/bump-plugin-version.sh sahajanand-post-to-speech patch

# Set an exact version
./scripts/bump-plugin-version.sh sahajanand-post-to-speech 1.0.2

# Via Composer
composer bump -- sahajanand-post-to-speech patch
```

Or update the `Version:` header in `plugins/sahajanand-post-to-speech/sahajanand-post-to-speech.php`, then commit and push to `master` / `main`.

### Release flow

1. Push a version bump to `master` / `main`.
2. `release-plugins.yml` detects the new version, runs `npm run build`, creates a tag like `sahajanand-post-to-speech/v1.0.1`, and publishes a GitHub Release zip.
3. For plugins listed in `.github/wporg-plugins.json`, the same workflow deploys to WordPress.org SVN (`trunk`, `tags/<version>`, and `assets/`).

Tag pushes from GitHub Actions do not trigger other workflows, so WordPress.org deploy runs in the release workflow itself.

**CI:** `.github/workflows/verify-plugins.yml` runs on every push/PR that touches `plugins/` and validates the release zip includes `build/block.json`.

### Release zip contents (`.distignore`)

Add a `.distignore` file in the plugin root to exclude dev files from the release zip. Example:

```text
tests/
node_modules/
src/
package.json
package-lock.json
.gitignore
.distignore
.wporg_assets/
```

The pack step uses `scripts/stage-plugin.sh` and `scripts/pack-plugin.sh`, which require `build/block.json` for block plugins.

---

## WordPress.org Publishing

### Pressship (manual)

[Pressship](https://pressship.org/docs/intro) handles validation, packaging, review submission, and SVN releases.

**Requirements:** Node.js 20+, a WordPress.org account, PHP (for Plugin Check), and `svn` (for approved-plugin releases).

```bash
./scripts/pressship.sh login
./scripts/pressship.sh whoami

./scripts/pressship.sh info sahajanand-post-to-speech
./scripts/pressship.sh status sahajanand-post-to-speech

./scripts/pack-plugin.sh sahajanand-post-to-speech
./scripts/pressship.sh verify sahajanand-post-to-speech

./scripts/pressship.sh publish sahajanand-post-to-speech --dry-run
./scripts/pressship.sh publish sahajanand-post-to-speech

./scripts/pressship.sh demo sahajanand-post-to-speech
```

`publish` routes automatically: new plugins go through review (`submit`); approved plugins release to SVN (`release`).

### Automated SVN deploy

Plugins in `.github/wporg-plugins.json` deploy automatically when a new version is released:

```json
{
  "sahajanand-post-to-speech": {
    "slug": "sahajanand-post-to-speech"
  }
}
```

**GitHub secrets** (repo → Settings → Secrets and variables → Actions):

| Secret | Value |
|--------|--------|
| `WORDPRESS_USERNAME` | Your WordPress.org username |
| `WORDPRESS_PASSWORD` | A WordPress.org [application password](https://wordpress.org/support/article/application-passwords/) |

You can also deploy manually via **Actions → Deploy to WordPress.org → Run workflow**, or locally:

```bash
WORDPRESS_USERNAME=youruser WORDPRESS_PASSWORD='xxxx xxxx xxxx xxxx' \
  bash scripts/deploy-wordpress-org.sh sahajanand-post-to-speech 1.0.1

bash scripts/deploy-wordpress-org.sh sahajanand-post-to-speech 1.0.1 --dry-run
```

### Screenshots and banners

Add files under `plugins/<slug>/.wporg_assets/`. Include `blueprints/blueprint.json` for the WordPress.org **Live Preview** demo (Playground). Pushing changes there runs `deploy-wordpress-org-assets.yml`, or upload manually:

```bash
./scripts/pressship-assets.sh sahajanand-post-to-speech
./scripts/pressship-assets.sh sahajanand-post-to-speech --dry-run
```

---

## What not to commit

These are gitignored or should never live in the repo:

| Path | Reason |
|------|--------|
| `*.zip` | Release artifacts |
| `build_dir/` | Staging output |
| `.pressship-svn/` | Local SVN checkout |
| `/sahajanand-post-to-speech/` (repo root) | Duplicate of `plugins/sahajanand-post-to-speech/` |
| `node_modules/` | Install via `npm install` |
| `.phpunit.result.cache` | PHPUnit cache |

The canonical plugin path is **`plugins/sahajanand-post-to-speech/`** only.

---

## Contributing

Make changes inside `plugins/sahajanand-post-to-speech/` and open a pull request. CI validates the release zip before merge.
