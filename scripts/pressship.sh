#!/usr/bin/env bash
# pressship.sh
# Wrapper around Pressship CLI for plugins in this monorepo.
# Docs: https://pressship.org/docs/intro

set -euo pipefail

MONOREPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGINS_DIR="$MONOREPO_ROOT/plugins"

usage() {
	cat <<'EOF'
Usage:
  scripts/pressship.sh <command> <plugin-slug> [pressship args...]

Commands:
  login     Log in to WordPress.org (browser-based)
  whoami    Show logged-in WordPress.org account
  info      Show plugin metadata
  status    Show WordPress.org review status for the plugin
  verify    Validate readme.txt and run Plugin Check
  pack      Validate and create a WordPress-installable zip
  publish   Submit for review or release to WordPress.org SVN
  demo      Run the plugin in WordPress Playground
  release   Release an approved plugin to WordPress.org SVN
  submit    Submit a plugin for WordPress.org review

Examples:
  scripts/pressship.sh login
  scripts/pressship.sh verify post-to-speech
  scripts/pressship.sh pack post-to-speech
  scripts/pressship.sh publish post-to-speech --dry-run
  scripts/pressship.sh demo post-to-speech

Requires Node.js 20+ and npx. See https://pressship.org/docs/getting-started
EOF
}

resolve_plugin_dir() {
	local slug="$1"
	local plugin_dir="$PLUGINS_DIR/$slug"

	if [ ! -d "$plugin_dir" ]; then
		echo "Plugin not found: $slug" >&2
		echo "Available plugins:" >&2
		for dir in "$PLUGINS_DIR"/*; do
			[ -d "$dir" ] && echo "  - $(basename "$dir")" >&2
		done
		exit 1
	fi

	echo "$plugin_dir"
}

main() {
	if [ $# -lt 1 ]; then
		usage
		exit 1
	fi

	local command="$1"
	shift

	case "$command" in
		--help|-h|help)
			usage
			exit 0
			;;
		login|whoami)
			exec npx pressship "$command" "$@"
			;;
	esac

	if [ $# -lt 1 ]; then
		echo "Missing plugin slug for command: $command" >&2
		usage
		exit 1
	fi

	local slug="$1"
	shift

	local plugin_dir
	plugin_dir="$(resolve_plugin_dir "$slug")"

	# Build block assets before verify/pack/publish when package.json exists.
	case "$command" in
		verify|pack|publish|demo|release|submit)
			if [ -f "$plugin_dir/package.json" ]; then
				echo "Building assets in $slug..."
				( cd "$plugin_dir" && npm ci --legacy-peer-deps 2>/dev/null || npm install --legacy-peer-deps )
				( cd "$plugin_dir" && npm run build --if-present )
			fi

			if [ -f "$plugin_dir/.distignore" ] && [ "$command" = "pack" ]; then
				bash "$MONOREPO_ROOT/scripts/pack-plugin.sh" "$slug" "$MONOREPO_ROOT"
				exit 0
			fi
			;;
	esac

	exec npx pressship "$command" "$plugin_dir" "$@"
}

main "$@"
