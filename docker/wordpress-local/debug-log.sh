#!/usr/bin/env bash
# Show whether local WordPress debug logging is active.
set -euo pipefail

cd "$(dirname "$0")"

echo "=== Host debug log ==="
LOG="./wp-content/debug.log"
if [ -f "$LOG" ]; then
	echo "Path: $LOG"
	echo "Size: $(wc -c < "$LOG" | tr -d ' ') bytes"
	tail -20 "$LOG" 2>/dev/null || true
else
	echo "Missing: $LOG"
fi

echo
echo "=== Container check ==="
if ! docker compose ps --status running wordpress 2>/dev/null | grep -q wordpress; then
	echo "WordPress container is not running."
	echo "Start it with: docker compose up -d"
	exit 1
fi

docker compose exec -T wordpress sh -c '
echo "--- wp-config (first 8 lines) ---"
head -8 /var/www/html/wp-config.php
echo "--- mu-plugins ---"
ls -la /var/www/html/wp-content/mu-plugins/ 2>/dev/null || true
echo "--- debug.log (container) ---"
tail -20 /var/www/html/wp-content/debug.log 2>/dev/null || true
'
