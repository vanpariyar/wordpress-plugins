#!/usr/bin/env bash
# Create a WordPress-installable plugin zip (includes compiled block assets in build/).
set -euo pipefail

MONOREPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

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

STAGING_ROOT="$(mktemp -d)"
cleanup() {
	rm -rf "$STAGING_ROOT"
}
trap cleanup EXIT

STAGING_DIR="$(bash "$MONOREPO_ROOT/scripts/stage-plugin.sh" "$SLUG" "$STAGING_ROOT")"

ZIP_PATH="$OUTPUT_DIR/$SLUG.zip"
rm -f "$ZIP_PATH"
( cd "$STAGING_ROOT" && zip -rq "$ZIP_PATH" "$SLUG" )

echo "Created $ZIP_PATH ($(du -h "$ZIP_PATH" | awk '{print $1}'))"
