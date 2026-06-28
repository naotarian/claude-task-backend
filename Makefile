.PHONY: up down build art test types fresh seed front-install front-test

# Bring up the full stack (php, node, nginx, mysql, redis, mailhog, minio)
up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

# Run an artisan command, e.g. `make art c="migrate"`
art:
	docker compose run --rm php php artisan $(c)

# Backend test suite with coverage threshold
test:
	docker compose run --rm --no-deps php php artisan test --coverage --min=80

# Regenerate TypeScript types for the frontend from Laravel DTOs
types:
	docker compose run --rm --no-deps php php artisan typescript:transform

# Drop, re-migrate and seed the database
fresh:
	docker compose run --rm php php artisan migrate:fresh --seed

seed:
	docker compose run --rm php php artisan db:seed

front-install:
	docker compose run --rm --no-deps node npm install

front-test:
	docker compose run --rm --no-deps node npm run coverage

# Production build of the frontend (NODE_ENV must be production for next build)
front-build:
	docker compose run --rm --no-deps -e NODE_ENV=production node npm run build
