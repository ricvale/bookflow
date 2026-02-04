.PHONY: help install test lint fix up down build clean migrate backup

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install dependencies
	docker compose exec -e XDEBUG_MODE=off app composer install

install-prod: ## Install production dependencies (no dev)
	docker compose exec -e XDEBUG_MODE=off app composer install --no-dev --optimize-autoloader

test: ## Run all tests
	docker compose exec app ./vendor/bin/phpunit

test-unit: ## Run unit tests only
	docker compose exec app ./vendor/bin/phpunit --testsuite=Unit

test-debug: ## Run tests with Xdebug triggered (requires trigger mode)
	docker compose exec -e XDEBUG_TRIGGER=1 app ./vendor/bin/phpunit

test-integration: ## Run integration tests only
	docker compose exec app ./vendor/bin/phpunit --testsuite=Integration

test-coverage: ## Run tests with coverage report
	docker compose exec app ./vendor/bin/phpunit --coverage-html coverage

lint: ## Run code quality checks
	docker compose exec -e XDEBUG_MODE=off app ./vendor/bin/phpstan analyse src tests --level=8 --memory-limit=1G
	docker compose exec -e XDEBUG_MODE=off app ./vendor/bin/php-cs-fixer fix --dry-run --diff

fix: ## Fix code style issues
	docker compose exec -e XDEBUG_MODE=off app ./vendor/bin/php-cs-fixer fix

up: ## Start all services
	docker compose up -d

down: ## Stop all services
	docker compose down

build: ## Build Docker images
	docker compose build

rebuild: ## Rebuild Docker images from scratch
	docker compose build --no-cache

logs: ## Show logs (use SERVICE=app for specific service)
	docker compose logs -f $(SERVICE)

shell: ## Open shell in app container
	docker compose exec app /bin/bash

db-shell: ## Open MySQL shell
	docker compose exec db mysql -u bookflow -pbookflow bookflow

clean: ## Clean up containers, volumes, and cache
	docker compose down -v
	rm -rf vendor coverage .phpunit.cache

migrate: ## Run database migrations
	docker compose exec app php bin/migrate.php

migrate-rollback: ## Rollback last migration
	docker compose exec app php bin/migrate.php rollback

backup: ## Backup database
	docker compose exec db mysqldump -u bookflow -pbookflow bookflow > backup_$(shell date +%Y%m%d_%H%M%S).sql

restore: ## Restore database from backup (use FILE=backup.sql)
	docker compose exec -T db mysql -u bookflow -pbookflow bookflow < $(FILE)

health: ## Check application health
	curl -s http://localhost:8000/health | jq

security: ## Run security audit
	docker compose exec app composer audit

format-check: ## Check code formatting
	docker compose exec -e XDEBUG_MODE=off app ./vendor/bin/php-cs-fixer fix --dry-run

format: ## Format code
	docker compose exec -e XDEBUG_MODE=off app ./vendor/bin/php-cs-fixer fix

ci: lint test ## Run CI checks locally
