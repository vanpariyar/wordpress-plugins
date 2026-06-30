#!/usr/bin/env bash
# Create a WordPress-installable plugin zip (includes compiled block assets in build/).
set -euo pipefail

MONOREPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGINS_DIR="$MONOREPO_ROOT/plugins"

usage() {
	cat <<'EOF'
Usage:
  scripts/pack-plugin.sh <plugin-slug> [output-dir]

Creates <slug>.zip with build/ and src/ included, node_modules excluded.
EOF
}

if [ $# -lt 1 ]; then
	usage
	exit 1
fi

SLUG="$1"
OUTPUT_DIR="${2:-$MONOREPO_ROOT}"
PLUGIN_DIR="$PLUGINS_DIR/$SLUG"

if [ ! -d "$PLUGIN_DIR" ]; then
	echo "Plugin not found: $SLUG" >&2
	exit 1
fi

if [ -f "$PLUGIN_DIR/package.json" ]; then
	echo "Building assets in $SLUG..."
	( cd "$PLUGIN_DIR" && npm ci --legacy-peer-deps 2>/dev/null || npm install --legacy-peer-deps )
	( cd "$PLUGIN_DIR" && npm run build --if-present )
fi

if [ ! -f "$PLUGIN_DIR/build/block.json" ]; then
	echo "Missing compiled block assets at $PLUGIN_DIR/build/block.json" >&2
	echo "Run npm run build in the plugin directory first." >&2
	exit 1
fi

STAGING_ROOT="$(mktemp -d)"
STAGING_DIR="$STAGING_ROOT/$SLUG"
mkdir -p "$STAGING_DIR"
cp -R "$PLUGIN_DIR/." "$STAGING_DIR/"

if [ -f "$PLUGIN_DIR/.distignore" ]; then
	bash "$MONOREPO_ROOT/scripts/apply-distignore.sh" "$STAGING_DIR" "$PLUGIN_DIR/.distignore"
fi

ZIP_PATH="$OUTPUT_DIR/$SLUG.zip"
rm -f "$ZIP_PATH"
( cd "$STAGING_ROOT" && zip -rq "$ZIP_PATH" "$SLUG" )
rm -rf "$STAGING_ROOT"

echo "Created $ZIP_PATH ($(du -h "$ZIP_PATH" | awk '{print $1}'))"
