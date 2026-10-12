#!/usr/bin/env bash
# Runs once after the dev container is created. Safe to re-run.
set -euo pipefail

if [ ! -f .env ]; then
    cp .env.example .env
    # `php artisan serve` listens on 8000; uploaded image URLs are built from APP_URL.
    sed -i 's|^APP_URL=.*|APP_URL=http://localhost:8000|' .env
fi

composer install --no-interaction
npm ci

grep -q '^APP_KEY=base64:' .env || php artisan key:generate

# Seed only a fresh database, so demo projects you've deleted don't come back.
if php artisan migrate:status > /dev/null 2>&1; then
    php artisan migrate --force
else
    php artisan migrate --seed --force
fi

[ -L public/storage ] || php artisan storage:link

npm run build
