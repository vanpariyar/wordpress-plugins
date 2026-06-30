#!/usr/bin/env bash
set -euo pipefail

mkdir -p /var/www/html/wp-content/uploads
chown -R www-data:www-data /var/www/html/wp-content/uploads
chmod -R 775 /var/www/html/wp-content/uploads

mkdir -p /var/www/html/wp-content/local-dev
touch /var/www/html/wp-content/local-dev/debug.log
touch /var/www/html/wp-content/local-dev/pts-fatal.log
chown -R www-data:www-data /var/www/html/wp-content/local-dev
chmod -R 775 /var/www/html/wp-content/local-dev

exec docker-entrypoint.sh apache2-foreground
