#!/usr/bin/env sh
set -e

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
  php artisan key:generate --force --no-interaction || true
fi

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"
