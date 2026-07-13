#!/usr/bin/env bash
# Deploy a monorepo plugin to WordPress.org SVN (trunk + tags/<version>).
#
# Usage:
#   WORDPRESS_USERNAME=... WORDPRESS_PASSWORD=... \
#     scripts/deploy-wordpress-org.sh <plugin-slug> <version> [--dry-run]
#
# Version may be passed with or without a leading "v" (e.g. 1.0.0 or v1.0.0).

set -euo pipefail

MONOREPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGINS_DIR="$MONOREPO_ROOT/plugins"

usage() {
	cat <<'EOF'
Usage:
  scripts/deploy-wordpress-org.sh <plugin-slug> <version> [--dry-run]

Environment:
  WORDPRESS_USERNAME   WordPress.org SVN username (required unless --dry-run)
  WORDPRESS_PASSWORD   WordPress.org application password (required unless --dry-run)
EOF
}

sync_wporg_assets() {
	local plugin_dir="$1"
	local svn_assets_dir="$2"
	local assets_src=""

	if [ -d "$plugin_dir/.wporg_assets" ]; then
		assets_src="$plugin_dir/.wporg_assets"
	elif [ -d "$plugin_dir/.wordpress-org" ]; then
		assets_src="$plugin_dir/.wordpress-org"
	else
		return 0
	fi

	echo "Syncing WordPress.org assets from $assets_src..."
	mkdir -p "$svn_assets_dir"
	rsync -a --delete \
		--exclude 'README.md' \
		--include '*/' \
		--include '*.png' --include '*.jpg' --include '*.jpeg' --include '*.gif' --include '*.svg' \
		--exclude '*' \
		"$assets_src/" "$svn_assets_dir/"
}

set_asset_mime_types() {
	local svn_dir="$1"

	(
		cd "$svn_dir"
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
	)
}

if [ $# -lt 2 ]; then
	usage
	exit 1
fi

SLUG="$1"
VERSION="${2#v}"
DRY_RUN=false

if [ "${3:-}" = "--dry-run" ]; then
	DRY_RUN=true
fi

PLUGIN_DIR="$PLUGINS_DIR/$SLUG"
CONFIG_FILE="$MONOREPO_ROOT/.github/wporg-plugins.json"

if [ ! -d "$PLUGIN_DIR" ]; then
	echo "Plugin not found: $SLUG" >&2
	exit 1
fi

if [ ! -f "$CONFIG_FILE" ] || ! jq -e --arg slug "$SLUG" 'has($slug)' "$CONFIG_FILE" >/dev/null 2>&1; then
	echo "Plugin '$SLUG' is not listed in .github/wporg-plugins.json. Skipping WordPress.org deploy." >&2
	exit 1
fi

if [ "$DRY_RUN" = false ]; then
	if [ -z "${WORDPRESS_USERNAME:-}" ] || [ -z "${WORDPRESS_PASSWORD:-}" ]; then
		echo "WORDPRESS_USERNAME and WORDPRESS_PASSWORD are required." >&2
		exit 1
	fi
fi

MAIN_FILE="$(find "$PLUGIN_DIR" -maxdepth 1 -name "*.php" -exec grep -l "Plugin Name:" {} \; | head -n 1)"
if [ -z "$MAIN_FILE" ]; then
	echo "No plugin header found in $PLUGIN_DIR" >&2
	exit 1
fi

HEADER_VERSION="$(grep -i "Version:" "$MAIN_FILE" | sed -E 's/.*Version:[[:space:]]*([0-9.]+).*/\1/' | tr -d '\r' | head -n 1)"
if [ "$HEADER_VERSION" != "$VERSION" ]; then
	echo "Version mismatch: tag/release is $VERSION but plugin header is $HEADER_VERSION" >&2
	exit 1
fi

STAGING_ROOT="$(mktemp -d)"
cleanup() {
	rm -rf "$STAGING_ROOT"
}
trap cleanup EXIT

STAGING_DIR="$(bash "$MONOREPO_ROOT/scripts/stage-plugin.sh" "$SLUG" "$STAGING_ROOT")"

SVN_SLUG="$(jq -r --arg slug "$SLUG" '.[$slug].slug // $slug' "$CONFIG_FILE")"
SVN_URL="https://plugins.svn.wordpress.org/${SVN_SLUG}/"
SVN_DIR="$(mktemp -d)"

echo "Preparing WordPress.org deploy for $SVN_SLUG version $VERSION"

if [ "$DRY_RUN" = true ]; then
	echo "Dry run: would rsync staged plugin to SVN trunk and create tags/$VERSION"
	echo "Staged files:"
	find "$STAGING_DIR" -type f | wc -l | awk '{print "  " $1 " files"}'
	du -sh "$STAGING_DIR" | awk '{print "  " $1 " total"}'
	if [ -f "$STAGING_DIR/build/block.json" ]; then
		echo "  build/block.json: present"
	else
		echo "  build/block.json: missing" >&2
		exit 1
	fi
	exit 0
fi

if ! command -v svn >/dev/null 2>&1; then
	echo "Subversion (svn) is required but not installed." >&2
	exit 1
fi

echo "Checking out WordPress.org SVN repository..."
svn checkout --depth immediates "$SVN_URL" "$SVN_DIR"
(
	cd "$SVN_DIR"
	svn update --set-depth infinity trunk
	if svn info assets >/dev/null 2>&1; then
		svn update --set-depth infinity assets
	fi

	echo "Syncing plugin files to trunk..."
	rsync -ra --delete "$STAGING_DIR/" trunk/

	sync_wporg_assets "$PLUGIN_DIR" "$SVN_DIR/assets"
	set_asset_mime_types "$SVN_DIR"

	echo "Committing trunk and assets..."
	svn add . --force >/dev/null
	if svn status | grep -q '^!'; then
		svn status | grep '^!' | sed 's/! *//' | xargs -r svn rm >/dev/null
	fi

	if svn info "tags/$VERSION" >/dev/null 2>&1; then
		echo "SVN tag tags/$VERSION already exists." >&2
		exit 1
	fi

	echo "Creating tag tags/$VERSION..."
	svn cp trunk "tags/$VERSION"

	svn commit \
		-m "Release ${SVN_SLUG} ${VERSION} from GitHub (${GITHUB_REPOSITORY:-local}@${GITHUB_SHA:-manual})" \
		--username "$WORDPRESS_USERNAME" \
		--password "$WORDPRESS_PASSWORD" \
		--no-auth-cache \
		--non-interactive
)

rm -rf "$SVN_DIR"
echo "Deployed ${SVN_SLUG} ${VERSION} to WordPress.org SVN."
