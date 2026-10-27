#!/usr/bin/env bash
# Start script for the public demo API (and any Linux server without PHP installed).
# Uses the system PHP if it is 8.2+, otherwise the bundled static PHP in ./bin.
set -e
cd "$(dirname "$0")"
PHP=./bin/php
if command -v php >/dev/null 2>&1 && php -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' && php -m | grep -qi pdo_sqlite; then PHP=php; fi
chmod +x ./bin/php 2>/dev/null || true
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache database
# Demo data: (re)seed when the database is new or the shipped seed version changed.
WANT=$(cat database/seed_version.txt 2>/dev/null || echo 1)
HAVE=$(cat database/.seeded 2>/dev/null || echo none)
if [ ! -s database/database.sqlite ] || [ "$WANT" != "$HAVE" ]; then
  touch database/database.sqlite
  $PHP artisan migrate:fresh --force --seed
  echo "$WANT" > database/.seeded
else
  $PHP artisan migrate --force
fi
$PHP artisan config:cache
$PHP artisan route:cache
export PHP_CLI_SERVER_WORKERS=${PHP_CLI_SERVER_WORKERS:-6}
exec $PHP artisan serve --host=0.0.0.0 --port=${PORT:-8080} --no-reload
