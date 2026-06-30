#!/usr/bin/env bash
# Reset local WordPress Docker (fresh DB + debug log).
set -euo pipefail

cd "$(dirname "$0")"
mkdir -p wp-content
: > wp-content/debug.log
: > wp-content/pts-fatal.log

echo "Stopping containers and removing database volume..."
docker compose down -v

echo "Starting fresh WordPress..."
docker compose up -d

echo
echo "Open http://localhost:8888 and complete the WordPress install."
echo "Then activate Post to Speech and try Generate audio again."
echo
echo "If it fails, check:"
echo "  docker/wordpress-local/wp-content/debug.log"
echo "  docker/wordpress-local/wp-content/pts-fatal.log"
