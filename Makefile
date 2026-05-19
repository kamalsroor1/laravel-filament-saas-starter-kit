SHELL := /bin/sh

.PHONY: install up down restart build shell tinker migrate fresh seed test test-coverage pint phpstan horizon logs ps prune

install:
	bash scripts/install.sh

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

build:
	docker compose build --no-cache

shell:
	docker exec -it php-fpm bash

tinker:
	docker exec -it php-fpm php artisan tinker

migrate:
	docker exec -it php-fpm php artisan migrate

fresh:
	docker exec -it php-fpm php artisan migrate:fresh --seed

seed:
	docker exec -it php-fpm php artisan db:seed

test:
	docker exec -it php-fpm php artisan test --parallel

test-coverage:
	docker exec -it php-fpm php artisan test --coverage

pint:
	docker exec -it php-fpm ./vendor/bin/pint

phpstan:
	docker exec -it php-fpm ./vendor/bin/phpstan analyse

horizon:
	docker exec -it horizon php artisan horizon

logs:
	docker compose logs -f

ps:
	docker compose ps

prune:
	docker system prune -f && docker volume prune -f