SHELL := /bin/sh

.PHONY: setup start stop logs backend-shell frontend-shell test-backend-smoke check

setup:
	cp -n .env.example .env || true
	@echo "Next: create backend and frontend projects, then run make start."

start:
	docker compose up --build

stop:
	docker compose down

logs:
	docker compose logs -f

backend-shell:
	docker compose run --rm backend sh

frontend-shell:
	docker compose run --rm frontend sh

test-backend-smoke:
	docker compose exec -T backend php tests/api_smoke.php

check:
	docker compose exec -T backend php bin/console lint:container
	docker compose exec -T backend php bin/console doctrine:schema:validate
	docker compose exec -T backend php tests/demo_seed_smoke.php
	docker compose exec -T backend php tests/api_smoke.php
	docker compose exec -T frontend npm run build
	docker compose exec -T frontend npm run smoke
