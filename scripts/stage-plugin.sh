#!/usr/bin/env bash
# Stage a plugin directory for distribution (build + .distignore).
#
# Usage:
#   scripts/stage-plugin.sh <plugin-slug> [output-dir]
#
# Prints the absolute path to the staged plugin folder on stdout.

set -euo pipefail

MONOREPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGINS_DIR="$MONOREPO_ROOT/plugins"

if [ $# -lt 1 ]; then
	echo "Usage: scripts/stage-plugin.sh <plugin-slug> [output-dir]" >&2
	exit 1
fi

SLUG="$1"
OUTPUT_ROOT="${2:-$(mktemp -d)}"
PLUGIN_DIR="$PLUGINS_DIR/$SLUG"

if [ ! -d "$PLUGIN_DIR" ]; then
	echo "Plugin not found: $SLUG" >&2
	exit 1
fi

if [ -f "$PLUGIN_DIR/package.json" ]; then
	echo "Building assets in $SLUG..." >&2
	( cd "$PLUGIN_DIR" && npm ci --legacy-peer-deps 2>/dev/null || npm install --legacy-peer-deps ) >&2
	( cd "$PLUGIN_DIR" && npm run build --if-present ) >&2

	if [ ! -f "$PLUGIN_DIR/build/block.json" ]; then
		echo "Missing compiled block assets at $PLUGIN_DIR/build/block.json" >&2
		exit 1
	fi
fi

STAGING_DIR="$OUTPUT_ROOT/$SLUG"
mkdir -p "$STAGING_DIR"
cp -R "$PLUGIN_DIR/." "$STAGING_DIR/"

if [ -f "$PLUGIN_DIR/.distignore" ]; then
	bash "$MONOREPO_ROOT/scripts/apply-distignore.sh" "$STAGING_DIR" "$PLUGIN_DIR/.distignore"
fi

if [ -f "$PLUGIN_DIR/package.json" ] && [ ! -f "$STAGING_DIR/build/block.json" ]; then
	echo "Staged package is missing build/block.json for $SLUG" >&2
	exit 1
fi

echo "$STAGING_DIR"
