#!/usr/bin/env bash
# Reload local WordPress — fixes corrupted wp-config and restarts containers.
set -euo pipefail

cd "$(dirname "$0")"
URL="http://localhost:8888"

echo "Stopping and recreating WordPress container..."
docker compose down
docker compose up -d --force-recreate

echo
echo "Waiting for WordPress to start..."
for i in 1 2 3 4 5 6 7 8 9 10; do
	sleep 3
	CODE=$(curl -s -o /dev/null -w "%{http_code}" "${URL}/wp-login.php" || echo "000")
	echo "  attempt ${i}: wp-login ${CODE}"
	if [ "$CODE" = "200" ] || [ "$CODE" = "302" ]; then
		echo
		echo "WordPress is ready."
		echo "  Site:  ${URL}/"
		echo "  Admin: ${URL}/wp-admin/"
		exit 0
	fi
done

echo
echo "Site still not responding. Try a full reset:"
echo "  ./reset.sh"
exit 1
