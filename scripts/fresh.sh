#!/usr/bin/env bash
set -e

echo "Resetting database and caches..."

docker exec php-fpm php artisan migrate:fresh --seed --force

docker exec php-fpm php artisan db:seed --class=SuperAdminSeeder --force || true

if [ "$(docker exec php-fpm sh -lc 'printf "%s" "$SAAS_MODE"')" = "true" ]; then
    docker exec php-fpm php artisan db:seed --class=SampleTenantSeeder --force || true
fi

docker exec php-fpm php artisan optimize:clear

echo "Fresh reset complete."