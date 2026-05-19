#!/usr/bin/env bash
set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}Installing Enterprise SaaS Starter Kit...${NC}"

check_command() {
    if ! command -v "$1" >/dev/null 2>&1; then
        echo -e "${RED}$1 is not installed. Please install it first.${NC}"
        exit 1
    fi
}

check_command docker
check_command make

if docker compose version >/dev/null 2>&1; then
    COMPOSE_CMD="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
    COMPOSE_CMD="docker-compose"
else
    echo -e "${RED}Docker Compose is not installed.${NC}"
    exit 1
fi

echo -e "${GREEN}Prerequisites OK${NC}"

if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${YELLOW}.env created from .env.example${NC}"
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    if command -v openssl >/dev/null 2>&1; then
        KEY="base64:$(openssl rand -base64 32)"
        if grep -q '^APP_KEY=' .env; then
            sed -i.bak "s|^APP_KEY=.*|APP_KEY=${KEY}|" .env && rm -f .env.bak
        else
            printf "\nAPP_KEY=%s\n" "$KEY" >> .env
        fi
        echo -e "${GREEN}APP_KEY generated locally${NC}"
    else
        echo -e "${YELLOW}openssl not available. APP_KEY will be generated inside container.${NC}"
    fi
fi

echo -e "${GREEN}Building containers...${NC}"
$COMPOSE_CMD build

echo -e "${GREEN}Starting containers...${NC}"
$COMPOSE_CMD up -d

echo -e "${GREEN}Waiting for postgres healthcheck...${NC}"
until [ "$(docker inspect --format='{{json .State.Health.Status}}' postgres 2>/dev/null || echo '"starting"')" = '"healthy"' ]; do
    sleep 3
    echo -e "${YELLOW}Postgres is starting...${NC}"
done

echo -e "${GREEN}Installing PHP dependencies...${NC}"
docker exec php-fpm composer config --no-plugins allow-plugins.pestphp/pest-plugin true
docker exec php-fpm composer install --no-interaction --prefer-dist

echo -e "${GREEN}Generating app key and running setup...${NC}"
docker exec php-fpm php artisan key:generate --force
docker exec php-fpm php artisan migrate --seed --force
docker exec php-fpm php artisan storage:link || true
docker exec php-fpm php artisan horizon:install || true
docker exec php-fpm php artisan pulse:check || true

if [ "${APP_ENV:-local}" = "local" ]; then
    docker exec php-fpm php artisan telescope:install || true
fi

echo -e "${GREEN}Installing frontend dependencies...${NC}"
docker exec php-fpm sh -lc "npm install && npm run build" || echo -e "${YELLOW}npm install/build skipped (Node may be unavailable in php-fpm image).${NC}"

echo -e "${GREEN}Installation completed successfully.${NC}"
echo -e "${GREEN}App:${NC} http://localhost"
echo -e "${GREEN}Mailpit:${NC} http://localhost:8025"
echo -e "${GREEN}MinIO:${NC} http://localhost:9001"
echo -e "${GREEN}Horizon:${NC} http://localhost/horizon"
echo -e "${GREEN}Pulse:${NC} http://localhost/pulse"
echo -e "${GREEN}Telescope:${NC} http://localhost/telescope"
