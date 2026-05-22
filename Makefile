.PHONY: up down shell artisan migrate seed fresh test test-coverage coverage pint phpstan logs cache-clear indexer es-reindex swagger

up:
	podman-compose up -d

down:
	podman-compose down

shell:
	podman-compose exec app bash

artisan:
	podman-compose exec app php artisan $(cmd)

migrate:
	podman-compose exec app php artisan migrate

seed:
	podman-compose exec app php artisan db:seed

fresh:
	podman-compose exec app php artisan migrate:fresh --seed

test:
	podman-compose exec app php artisan migrate --env=testing --database=mysql_test 2>/dev/null || true
	podman-compose exec app php artisan test

test-coverage: coverage

coverage:
	podman-compose exec app php artisan test --coverage --min=100

pint:
	podman-compose exec app ./vendor/bin/pint

phpstan:
	podman-compose exec app ./vendor/bin/phpstan analyse

logs:
	podman-compose logs -f

cache-clear:
	podman-compose exec app php artisan cache:clear
	podman-compose exec app php artisan config:clear
	podman-compose exec app php artisan route:clear
	podman-compose exec app php artisan view:clear

indexer:
	podman-compose exec indexer node index.js

es-reindex:
	podman-compose exec app php artisan es:reindex

swagger:
	podman-compose exec app php artisan l5-swagger:generate
