#!/usr/bin/env bash
# bump-plugin-version.sh
# Bump the version for a plugin in plugins/<slug>/ and sync related files.

set -euo pipefail

MONOREPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGINS_DIR="$MONOREPO_ROOT/plugins"

usage() {
	cat <<'EOF'
Usage:
  scripts/bump-plugin-version.sh --list
  scripts/bump-plugin-version.sh <plugin-slug> [patch|minor|major|<version>]

Examples:
  scripts/bump-plugin-version.sh like patch
  scripts/bump-plugin-version.sh creole-demo minor
  scripts/bump-plugin-version.sh like 1.2.3

Updates the plugin header Version, and when present also syncs:
  - package.json
  - readme.txt (Stable tag)
  - block.json files outside build/ and node_modules/
EOF
}

find_main_plugin_file() {
	local plugin_dir="$1"
	find "$plugin_dir" -maxdepth 1 -name "*.php" -exec grep -l "Plugin Name:" {} \; | head -n 1
}

get_plugin_version() {
	local main_file="$1"
	grep -i "Version:" "$main_file" | sed -E 's/.*Version:[[:space:]]*([0-9.]+).*/\1/' | tr -d '\r' | head -n 1
}

is_semver() {
	[[ "$1" =~ ^[0-9]+(\.[0-9]+){0,2}$ ]]
}

bump_version() {
	local version="$1"
	local bump_type="$2"

	if is_semver "$bump_type"; then
		echo "$bump_type"
		return
	fi

	IFS='.' read -r -a parts <<< "$version"
	local major="${parts[0]:-0}"
	local minor="${parts[1]:-0}"
	local patch="${parts[2]:-0}"
	local parts_count="${#parts[@]}"

	case "$bump_type" in
		major)
			major=$((major + 1))
			minor=0
			patch=0
			;;
		minor)
			minor=$((minor + 1))
			patch=0
			;;
		patch)
			if [ "$parts_count" -eq 2 ]; then
				minor=$((minor + 1))
			else
				patch=$((patch + 1))
			fi
			;;
		*)
			echo "Invalid bump type: $bump_type" >&2
			exit 1
			;;
	esac

	if [ "$parts_count" -eq 2 ]; then
		echo "${major}.${minor}"
	else
		echo "${major}.${minor}.${patch}"
	fi
}

list_plugins() {
	printf "%-24s %s\n" "PLUGIN" "VERSION"
	printf "%-24s %s\n" "------" "-------"

	for plugin_dir in "$PLUGINS_DIR"/*; do
		[ -d "$plugin_dir" ] || continue

		local slug
		slug="$(basename "$plugin_dir")"
		local main_file
		main_file="$(find_main_plugin_file "$plugin_dir")"

		if [ -z "$main_file" ]; then
			printf "%-24s %s\n" "$slug" "(not a plugin)"
			continue
		fi

		local version
		version="$(get_plugin_version "$main_file")"
		printf "%-24s %s\n" "$slug" "$version"
	done
}

update_php_version() {
	local main_file="$1"
	local new_version="$2"

	if [[ "$OSTYPE" == "darwin"* ]]; then
		sed -E -i '' "s/(\* Version:[[:space:]]*)[0-9.]+/\1${new_version}/" "$main_file"
	else
		sed -E -i "s/(\* Version:[[:space:]]*)[0-9.]+/\1${new_version}/" "$main_file"
	fi
}

update_package_json() {
	local plugin_dir="$1"
	local new_version="$2"
	local package_json="$plugin_dir/package.json"

	[ -f "$package_json" ] || return 0

	if [[ "$OSTYPE" == "darwin"* ]]; then
		sed -E -i '' 's/"version"[[:space:]]*:[[:space:]]*"[^"]+"/"version": "'"${new_version}"'"/' "$package_json"
	else
		sed -E -i 's/"version"[[:space:]]*:[[:space:]]*"[^"]+"/"version": "'"${new_version}"'"/' "$package_json"
	fi

	echo "  updated package.json"
}

update_readme_txt() {
	local plugin_dir="$1"
	local new_version="$2"
	local readme_txt="$plugin_dir/readme.txt"

	[ -f "$readme_txt" ] || return 0

	if [[ "$OSTYPE" == "darwin"* ]]; then
		sed -E -i '' "s/^(Stable tag:[[:space:]]*)[0-9.]+/\1${new_version}/" "$readme_txt"
	else
		sed -E -i "s/^(Stable tag:[[:space:]]*)[0-9.]+/\1${new_version}/" "$readme_txt"
	fi

	echo "  updated readme.txt"
}

update_block_json_files() {
	local plugin_dir="$1"
	local new_version="$2"

	while IFS= read -r block_json; do
		if [[ "$OSTYPE" == "darwin"* ]]; then
			sed -E -i '' 's/"version"[[:space:]]*:[[:space:]]*"[^"]+"/"version": "'"${new_version}"'"/' "$block_json"
		else
			sed -E -i 's/"version"[[:space:]]*:[[:space:]]*"[^"]+"/"version": "'"${new_version}"'"/' "$block_json"
		fi
		echo "  updated ${block_json#$MONOREPO_ROOT/}"
	done < <(find "$plugin_dir" -name block.json -not -path '*/build/*' -not -path '*/node_modules/*')
}

main() {
	if [ $# -eq 0 ] || [ "${1:-}" = "--help" ] || [ "${1:-}" = "-h" ]; then
		usage
		exit 0
	fi

	if [ "${1:-}" = "--list" ] || [ "${1:-}" = "-l" ]; then
		list_plugins
		exit 0
	fi

	local slug="$1"
	local bump_type="${2:-patch}"
	local plugin_dir="$PLUGINS_DIR/$slug"

	if [ ! -d "$plugin_dir" ]; then
		echo "Plugin not found: $slug" >&2
		echo "Available plugins:" >&2
		list_plugins >&2
		exit 1
	fi

	local main_file
	main_file="$(find_main_plugin_file "$plugin_dir")"

	if [ -z "$main_file" ]; then
		echo "No WordPress plugin header found in $plugin_dir" >&2
		exit 1
	fi

	local current_version
	current_version="$(get_plugin_version "$main_file")"

	if [ -z "$current_version" ]; then
		echo "Could not read Version from $main_file" >&2
		exit 1
	fi

	local new_version
	new_version="$(bump_version "$current_version" "$bump_type")"

	if [ "$current_version" = "$new_version" ]; then
		echo "Version unchanged: $current_version"
		exit 0
	fi

	echo "Bumping $slug: $current_version -> $new_version"

	update_php_version "$main_file" "$new_version"
	echo "  updated $(basename "$main_file")"

	update_package_json "$plugin_dir" "$new_version"
	update_readme_txt "$plugin_dir" "$new_version"
	update_block_json_files "$plugin_dir" "$new_version"

	echo "Done. Commit and push to trigger a release."
}

main "$@"
