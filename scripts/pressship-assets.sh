#!/usr/bin/env bash
# Upload WordPress.org plugin assets (banner, icon, screenshots) via Pressship SVN.
#
# Uses .wporg_assets/ (monorepo convention) or .wordpress-org/ (Pressship convention).
#
# Usage:
#   scripts/pressship-assets.sh <plugin-slug> [--dry-run] [--message "Update screenshots"]

set -euo pipefail

MONOREPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGINS_DIR="$MONOREPO_ROOT/plugins"

usage() {
	cat <<'EOF'
Usage:
  scripts/pressship-assets.sh <plugin-slug> [--dry-run] [--message "Commit message"]

Uploads banner, icon, and screenshot files to WordPress.org SVN assets/.
Requires: pressship login, WordPress.org SVN password (saved on first release).

Examples:
  scripts/pressship-assets.sh sahajanand-post-to-speech
  scripts/pressship-assets.sh sahajanand-post-to-speech --dry-run
EOF
}

resolve_assets_dir() {
	local plugin_dir="$1"

	if [ -d "$plugin_dir/.wporg_assets" ]; then
		echo "$plugin_dir/.wporg_assets"
	elif [ -d "$plugin_dir/.wordpress-org" ]; then
		echo "$plugin_dir/.wordpress-org"
	else
		return 1
	fi
}

read_svn_credentials() {
	node <<'NODE'
const { existsSync, readFileSync } = require("node:fs");
const { homedir } = require("node:os");
const path = require("node:path");

const credPath = path.join(homedir(), ".config", "pressship", "svn-credentials.json");
if (!existsSync(credPath)) {
	process.exit(2);
}

const data = JSON.parse(readFileSync(credPath, "utf8"));
const entries = Object.entries(data.credentials || {});
if (!entries.length) {
	process.exit(2);
}

const preferred = process.env.WORDPRESS_USERNAME;
const match = preferred
	? entries.find(([username]) => username === preferred)
	: entries[0];

if (!match) {
	process.exit(2);
}

process.stdout.write(`${match[0]}\n${match[1].password}`);
NODE
}

main() {
	if [ $# -lt 1 ] || [ "${1:-}" = "--help" ] || [ "${1:-}" = "-h" ]; then
		usage
		exit 0
	fi

	local slug="$1"
	shift

	local dry_run=false
	local message="Update WordPress.org plugin assets"

	while [ $# -gt 0 ]; do
		case "$1" in
			--dry-run)
				dry_run=true
				shift
				;;
			--message)
				message="${2:-}"
				shift 2
				;;
			*)
				echo "Unknown option: $1" >&2
				usage
				exit 1
				;;
		esac
	done

	local plugin_dir="$PLUGINS_DIR/$slug"
	if [ ! -d "$plugin_dir" ]; then
		echo "Plugin not found: $slug" >&2
		exit 1
	fi

	local assets_src
	if ! assets_src="$(resolve_assets_dir "$plugin_dir")"; then
		echo "No .wporg_assets/ or .wordpress-org/ directory in $plugin_dir" >&2
		exit 1
	fi

	local image_count blueprint_count
	image_count="$(find "$assets_src" -maxdepth 1 -type f \( -iname '*.png' -o -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.gif' -o -iname '*.svg' \) | wc -l | tr -d ' ')"
	blueprint_count="$(find "$assets_src/blueprints" -maxdepth 1 -type f -name '*.json' 2>/dev/null | wc -l | tr -d ' ')"
	if [ "$image_count" -eq 0 ] && [ "$blueprint_count" -eq 0 ]; then
		echo "No assets found in $assets_src (images or blueprints/blueprint.json)" >&2
		exit 1
	fi

	local svn_dir="$MONOREPO_ROOT/.pressship-svn/$slug"
	echo "Plugin: $slug"
	echo "Assets: $assets_src ($image_count images, $blueprint_count blueprint files)"
	echo "SVN:    https://plugins.svn.wordpress.org/$slug/"

	if [ "$dry_run" = true ]; then
		echo "Dry run — would upload:"
		find "$assets_src" -maxdepth 1 -type f \( -iname '*.png' -o -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.gif' -o -iname '*.svg' \) -print
		find "$assets_src/blueprints" -type f -name '*.json' -print 2>/dev/null || true
		exit 0
	fi

	npx pressship get "$slug" "$svn_dir"

	mkdir -p "$svn_dir/assets"
	rsync -a --delete \
		--exclude 'README.md' \
		"$assets_src/" "$svn_dir/assets/"

	(
		cd "$svn_dir"
		svn add --force assets >/dev/null 2>&1 || true

		for png in assets/*.png; do
			[ -f "$png" ] || continue
			svn propset svn:mime-type image/png "$png" >/dev/null 2>&1 || true
		done
		for jpg in assets/*.jpg assets/*.jpeg; do
			[ -f "$jpg" ] || continue
			svn propset svn:mime-type image/jpeg "$jpg" >/dev/null 2>&1 || true
		done
		for gif in assets/*.gif; do
			[ -f "$gif" ] || continue
			svn propset svn:mime-type image/gif "$gif" >/dev/null 2>&1 || true
		done
		for svg in assets/*.svg; do
			[ -f "$svg" ] || continue
			svn propset svn:mime-type image/svg+xml "$svg" >/dev/null 2>&1 || true
		done
		for json in assets/blueprints/*.json; do
			[ -f "$json" ] || continue
			svn propset svn:mime-type text/plain "$json" >/dev/null 2>&1 || true
		done

		if [ -z "$(svn status -q assets)" ]; then
			echo "No asset changes to commit."
			exit 0
		fi

		echo "SVN changes:"
		svn status assets

		local creds username password
		if creds="$(read_svn_credentials 2>/dev/null)"; then
			username="$(printf '%s' "$creds" | sed -n '1p')"
			password="$(printf '%s' "$creds" | sed -n '2p')"
		elif [ -n "${WORDPRESS_USERNAME:-}" ] && [ -n "${WORDPRESS_PASSWORD:-}" ]; then
			username="$WORDPRESS_USERNAME"
			password="$WORDPRESS_PASSWORD"
		else
			echo "No saved Pressship SVN password found." >&2
			echo "Run once: bash scripts/pressship.sh release $slug" >&2
			echo "Or set WORDPRESS_USERNAME and WORDPRESS_PASSWORD, then retry." >&2
			exit 1
		fi

		svn commit assets \
			-m "$message" \
			--username "$username" \
			--password "$password" \
			--no-auth-cache \
			--non-interactive
	)

	echo "WordPress.org assets updated for $slug."
	echo "Changes may take a few minutes to appear on https://wordpress.org/plugins/$slug/"
}

main "$@"
