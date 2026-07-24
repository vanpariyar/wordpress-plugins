#!/usr/bin/env bash
# Run Playwright e2e tests against a specific WordPress core.
# Usage:
#   ./scripts/run-e2e-on-wp.sh 6.9
#   ./scripts/run-e2e-on-wp.sh trunk
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN="$ROOT/plugins/sahajanand-post-to-speech"
TARGET="${1:-}"

if [[ -z "$TARGET" ]]; then
	echo "Usage: $0 <6.9|trunk>" >&2
	exit 1
fi

case "$TARGET" in
	6.9|wp-6.9|past)
		export WP_ENV_CORE='https://wordpress.org/wordpress-6.9.5.zip'
		LABEL='WordPress 6.9.5 (past release)'
		;;
	trunk|dev|development|next)
		# WordPress/WordPress GitHub mirror tracks development on `master`
		# (there is no `trunk` ref on that remote).
		export WP_ENV_CORE='WordPress/WordPress#master'
		LABEL='WordPress master (development / next release)'
		;;
	*)
		echo "Unknown WordPress target: $TARGET (expected 6.9 or trunk)" >&2
		exit 1
		;;
esac

cd "$PLUGIN"

echo "==> Building plugin assets"
npm run build

echo "==> Starting wp-env with $LABEL"
npm run wp-env -- start --update

echo "==> Running e2e tests"
npm run test:e2e
