# WordPress Plugins Monorepo

This repository is a monorepo containing multiple WordPress plugins under a unified structure. Each plugin lives in its own subdirectory inside the `plugins/` folder, operates independently, and is versioned/released separately.

Inspired by the [sahajananddigital/wordpress-plugins](https://github.com/sahajananddigital/wordpress-plugins) monorepo layout.

---

## Repository Structure

```
wordpress-plugins/
├── .github/
│   └── workflows/
│       └── release-plugins.yml          # CI/CD version-based release workflow
├── docker/
│   └── kitten-tts-api/                    # Self-hosted KittenTTS API (pay-per-request)
├── plugins/                             # All WordPress plugins
│   ├── creole-demo/                     # Shortcode demo plugin
│   ├── creole-demo-lightbox/            # Lightbox demo plugin
│   ├── post-to-speech/                  # Post-to-speech Gutenberg block
│   └── like/                            # Gutenberg block plugin
├── scripts/
│   ├── bump-plugin-version.sh           # Version bump helper
│   └── pressship.sh                     # WordPress.org publish helper (Pressship)
├── composer.json                        # Shared PHP dev dependencies
├── phpcs.xml.dist                       # WordPress coding standards config
├── phpunit.xml.dist                     # PHPUnit test suite config
├── LICENSE
└── README.md
```

---

## Plugins

| Plugin | Description |
|--------|-------------|
| [creole-demo](plugins/creole-demo/) | Display content using a shortcode |
| [creole-demo-lightbox](plugins/creole-demo-lightbox/) | Lightbox demo plugin |
| [post-to-speech](plugins/post-to-speech/) | Convert posts to audio with a Gutenberg block — browser WASM or API mode |

### KittenTTS API (Docker)

Self-host a pay-per-request KittenTTS API for the plugin's **API mode**:

```bash
cd docker/kitten-tts-api
cp .env.example .env
docker compose up --build
```

See [docker/kitten-tts-api/README.md](docker/kitten-tts-api/README.md) for endpoints, billing, and WordPress setup.

| [like](plugins/like/) | Gutenberg block with Gilbert color font |

---

## How Automated Releases Work

Releases are fully automated via GitHub Actions using **version detection** on plugins inside the `plugins/` folder. You do not need to manually create git tags or releases.

### Version Bump Script

Use the helper script to bump a plugin version and sync related files (`package.json`, `readme.txt`, `block.json`):

```bash
# List plugins and current versions
./scripts/bump-plugin-version.sh --list

# Bump patch (default), minor, or major
./scripts/bump-plugin-version.sh like patch
./scripts/bump-plugin-version.sh creole-demo minor

# Set an exact version
./scripts/bump-plugin-version.sh like 1.2.3

# Via Composer
composer bump -- like patch
```

### Triggering a Release

1. Make your changes inside a specific plugin directory (e.g. `plugins/creole-demo/`).
2. Bump the plugin version with the script above, or manually update the **`Version:`** value in the plugin's main PHP file header:

   ```php
   /**
    * Plugin Name: Creole Plugin demo
    * Version: 0.2.0    <-- Bump this version number
    */
   ```

3. Commit and push your changes to the `master` or `main` branch.

The workflow scans every plugin directory, compares the version in the plugin header against existing git tags, and creates a GitHub release with a `.zip` archive when a new version is detected.

### Compiling Assets (Gutenberg Blocks / SCSS / JS)

If a plugin directory contains a **`package.json`** file, the workflow will automatically:

1. Install Node.js dependencies (`npm ci` or `npm install`).
2. Run the build command (`npm run build`).

This compiles Gutenberg blocks and packages production assets before creating the release archive.

### Custom File Exclusions (`.distignore`)

To control which files end up in the final release `.zip`, add a **`.distignore`** file to the root of your plugin directory.

**Example `.distignore`:**

```text
# Exclude testing environments
tests/
phpunit.xml.dist

# Exclude package managers and source code
composer.json
composer.lock
package.json
package-lock.json
node_modules/
src/
webpack.config.js

# Exclude git system files
.gitignore
.distignore
```

If no `.distignore` file is present, the workflow falls back to a default set of exclusions (such as `tests/`, `composer.json`, and `vendor/`).

---

## WordPress.org Publishing (Pressship)

For publishing plugins to the [WordPress.org Plugin Directory](https://wordpress.org/plugins/), use [Pressship](https://pressship.org/docs/intro) — a CLI that handles validation, packaging, review submission, and SVN releases.

**Requirements:** Node.js 20+, a WordPress.org account, PHP (for Plugin Check), and `svn` (for approved-plugin releases).

```bash
# Log in to WordPress.org (browser-based, no password stored)
./scripts/pressship.sh login
./scripts/pressship.sh whoami

# Inspect a plugin
./scripts/pressship.sh info post-to-speech
./scripts/pressship.sh status post-to-speech

# Validate and package (builds block assets automatically; use pack-plugin.sh so build/ is included)
./scripts/pack-plugin.sh post-to-speech

# Or via Pressship wrapper (pack uses pack-plugin.sh; verify/publish still use Pressship)
./scripts/pressship.sh verify post-to-speech

# Publish to WordPress.org (dry-run first)
./scripts/pressship.sh publish post-to-speech --dry-run
./scripts/pressship.sh publish post-to-speech

# Demo in WordPress Playground
./scripts/pressship.sh demo post-to-speech
```

`publish` routes automatically: new plugins go through review (`submit`), approved plugins release to SVN (`release`). Use `--submit` or `--release` to be explicit.

Pressship complements the GitHub Actions workflow above — GitHub releases zip files for direct download; Pressship handles the WordPress.org directory workflow.

---

## Development

### PHP Linting & Tests

Install shared dev dependencies from the repo root:

```bash
composer install
composer lint      # Run PHPCS
composer lint:fix  # Auto-fix coding standard issues
composer test      # Run PHPUnit (when plugin tests exist)
```

### Contributing

Anyone can contribute. Make changes inside the relevant `plugins/<plugin-name>/` directory and open a pull request.
