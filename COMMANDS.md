# Quick Command Reference (Windows)

Since `make` is not installed, use these Docker commands directly:

## Starting & Stopping

```bash
# Start all services
docker compose up -d

# Stop all services
docker compose down

# Rebuild containers
docker compose build

# View logs
docker compose logs -f
docker compose logs -f app    # Only app logs
```

## Development

```bash
# Install PHP dependencies
docker compose exec app composer install

# Run all tests
docker compose exec app ./vendor/bin/phpunit

# Run unit tests only
docker compose exec app ./vendor/bin/phpunit --testsuite=Unit

# Run with coverage
docker compose exec -e XDEBUG_MODE=coverage app ./vendor/bin/phpunit --coverage-html coverage

# Run PHPStan (static analysis)
docker compose exec app ./vendor/bin/phpstan analyse src tests --level=8 --memory-limit=1G

# Run PHP-CS-Fixer (code style check)
docker compose exec app ./vendor/bin/php-cs-fixer fix --dry-run

# Auto-fix code style
docker compose exec app ./vendor/bin/php-cs-fixer fix
```

## Database

```bash
# Open MariaDB shell (mysql command still works)
docker compose exec db mariadb -u bookflow -pbookflow bookflow

# Backup database
docker compose exec db mariadb-dump -u bookflow -pbookflow bookflow > backup.sql

# Restore database
docker compose exec -T db mariadb -u bookflow -pbookflow bookflow < backup.sql

# Run migrations (when implemented)
docker compose exec app php bin/migrate.php

# Check MariaDB version
docker compose exec db mariadb --version
```

## Debugging

```bash
# Open shell in app container
docker compose exec app /bin/bash

# Check PHP version
docker compose exec app php -v

# Check installed extensions
docker compose exec app php -m

# View container stats
docker stats
```

## Health Check

```bash
# Check if app is running
curl http://localhost:8000/health

# Check specific endpoint
curl http://localhost:8000/api/v1/bookings
```

## Cleanup

```bash
# Remove containers and volumes
docker compose down -v

# Remove all stopped containers
docker container prune

# Remove unused images
docker image prune -a
```

## Common Workflows

### First Time Setup
```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app ./vendor/bin/phpunit
```

### Daily Development
```bash
docker compose up -d
# ... make code changes ...
docker compose exec app ./vendor/bin/phpunit
docker compose logs -f app
```

### Before Committing
```bash
docker compose exec app ./vendor/bin/phpunit
docker compose exec app ./vendor/bin/phpstan analyse src tests --level=8 --memory-limit=1G
docker compose exec app ./vendor/bin/php-cs-fixer fix
```
