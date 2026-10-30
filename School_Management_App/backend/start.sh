#!/usr/bin/env bash
# Start script for the public demo API (and any Linux server without PHP installed).
# Uses the system PHP if it is 8.2+, otherwise the bundled static PHP in ./bin.
set -e
cd "$(dirname "$0")"
# The bundled static PHP ships as <=800 KB parts (the publish step's reassembly of big files was unreliable).
if [ ! -x ./bin/php ] && [ -f bin_parts/php.sha256 ]; then
  mkdir -p bin && cat $(ls bin_parts/php.part* | sort) > bin/php.tmp && mv bin/php.tmp bin/php
  [ "$(sha256sum bin/php | cut -d' ' -f1)" = "$(cat bin_parts/php.sha256)" ] && echo "bin/php ok" || echo "bin/php BAD"
  chmod +x bin/php
fi
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
# Downloads: large files ship as <=800 KB parts (the publish step's reassembly of big files was unreliable);
# rebuild them into storage/app/downloads on start (served at /downloads/FILE by the website package) and verify each against SHA256SUMS.txt.
if [ -f dl_parts/SHA256SUMS.txt ]; then
  mkdir -p storage/app/downloads
  rm -rf public/downloads
  while read -r sum name; do
    out="storage/app/downloads/$name"
    if [ ! -f "$out" ] || [ "$(sha256sum "$out" | cut -d' ' -f1)" != "$sum" ]; then
      cat $(ls dl_parts/"$name".part* | sort) > "$out.tmp" && mv "$out.tmp" "$out"
    fi
    [ "$(sha256sum "$out" | cut -d' ' -f1)" = "$sum" ] && echo "download ok: $name" || echo "download BAD: $name"
  done < dl_parts/SHA256SUMS.txt
  cp dl_parts/SHA256SUMS.txt storage/app/downloads/SHA256SUMS.txt
elif ! grep -q '^DOWNLOADS_ENABLED=true' .env 2>/dev/null; then
  # Downloads paused (DOWNLOADS_ENABLED not true) and no release files shipped: make sure none are left on disk either.
  rm -rf storage/app/downloads public/downloads
fi
$PHP artisan storage:link --force >/dev/null 2>&1 || true
$PHP artisan config:cache
$PHP artisan route:cache
export PHP_CLI_SERVER_WORKERS=${PHP_CLI_SERVER_WORKERS:-6}
exec $PHP artisan serve --host=0.0.0.0 --port=${PORT:-8080} --no-reload
