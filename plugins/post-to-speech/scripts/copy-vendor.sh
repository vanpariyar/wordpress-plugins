#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f node_modules/espeak-ng/dist/espeak-ng.js ]; then
	echo "espeak-ng is not installed. Run npm install first." >&2
	exit 1
fi

DEST="assets/vendor/espeak-ng"
mkdir -p "$DEST"

cp node_modules/espeak-ng/dist/espeak-ng.js "$DEST/"
cp node_modules/espeak-ng/dist/espeak-ng.wasm "$DEST/"

echo "Copied eSpeak-NG assets to $DEST/"
