#!/usr/bin/env bash
# Disable all plugins in the local WordPress database (recovery from fatal plugin errors).
set -euo pipefail

cd "$(dirname "$0")"

if ! docker compose ps --status running db 2>/dev/null | grep -q db; then
	echo "Database container is not running. Start with: docker compose up -d" >&2
	exit 1
fi

echo "Deactivating all plugins in wp_options.active_plugins..."
docker compose exec -T db mysql -uwordpress -pwordpress wordpress -e \
	"UPDATE wp_options SET option_value = 'a:0:{}' WHERE option_name = 'active_plugins';"

echo "Done. Open http://localhost:8888/wp-admin/ and re-activate Post to Speech."
