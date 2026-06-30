#!/usr/bin/env bash
# Remove paths listed in a plugin's .distignore from a staging directory.
set -euo pipefail

if [ $# -ne 2 ]; then
	echo "Usage: scripts/apply-distignore.sh <staging-dir> <distignore-file>" >&2
	exit 1
fi

STAGING_DIR="$(cd "$1" && pwd)"
DISTIGNORE_FILE="$(cd "$(dirname "$2")" && pwd)/$(basename "$2")"

if [ ! -d "$STAGING_DIR" ]; then
	echo "Staging directory not found: $STAGING_DIR" >&2
	exit 1
fi

if [ ! -f "$DISTIGNORE_FILE" ]; then
	exit 0
fi

cd "$STAGING_DIR"

while IFS= read -r line || [ -n "$line" ]; do
	[[ "$line" =~ ^[[:space:]]*# ]] && continue
	[[ -z "${line// }" ]] && continue

	pattern="$(
		echo "$line" |
			sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' -e 's:/*$::'
	)"

	[ -n "$pattern" ] || continue

	if [[ "$pattern" == *"*"* ]]; then
		# shellcheck disable=SC2086
		rm -rf $pattern 2>/dev/null || true
	elif [ -e "./$pattern" ]; then
		rm -rf "./$pattern"
	fi
done <"$DISTIGNORE_FILE"
