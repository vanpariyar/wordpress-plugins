#!/usr/bin/env bash
# Stage a plugin for distribution and run Plugin Check on the staged copy.
set -euo pipefail

MONOREPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGINS_DIR="$MONOREPO_ROOT/plugins"

usage() {
	cat <<'EOF'
Usage:
  scripts/verify-plugin.sh <plugin-slug> [pressship verify args...]

Builds assets, applies .distignore to a temp copy, and runs pressship verify
against the staged tree (matches the WordPress.org zip contents).
EOF
}

if [ $# -lt 1 ]; then
	usage
	exit 1
fi

SLUG="$1"
shift

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
	exit 1
fi

STAGING_ROOT="$(mktemp -d)"
cleanup() {
	rm -rf "$STAGING_ROOT"
}
trap cleanup EXIT

STAGING_DIR="$STAGING_ROOT/$SLUG"
mkdir -p "$STAGING_DIR"
cp -R "$PLUGIN_DIR/." "$STAGING_DIR/"

if [ -f "$PLUGIN_DIR/.distignore" ]; then
	bash "$MONOREPO_ROOT/scripts/apply-distignore.sh" "$STAGING_DIR" "$PLUGIN_DIR/.distignore"
fi

exec npx pressship verify "$STAGING_DIR" "$@"
