# Architecture

BookFlow is a multi-tenant booking system built with PHP, MySQL, and vanilla JavaScript.

## Architectural Goals
- Framework-light PHP
- Strong separation of concerns
- Business logic isolated from infrastructure
- High testability
- Clear multi-tenant boundaries
- Docker-based development environment

## Development Environment

### Docker Setup
- **PHP 8.4 FPM**: Application runtime with strict types (latest stable, supported until Dec 2026)
- **MySQL 8.0**: Primary data store
- **Nginx**: Web server and reverse proxy
- **Composer**: Dependency management

### Running Locally
```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app ./vendor/bin/phpunit
```

## Layers

### Domain
- Pure PHP
- No database, HTTP, or external APIs
- Contains business rules and invariants
- Emits domain events for side effects

### Application
- Orchestrates use cases
- Applies authorization policies
- Coordinates domain objects
- Handles domain event listeners

### Infrastructure
- MySQL persistence
- Google Calendar integration
- Logging, email, external IO
- Background job processing

### HTTP
- Request validation
- Authentication & tenant resolution
- Delegation only

## Background Jobs & Async Processing

While we avoid network-based microservices, we **do** use background jobs for:
- Email notifications
- Google Calendar sync
- Report generation
- Any long-running or external API calls

**Pattern**: Domain Events → Event Listeners → Queue Jobs

This keeps the domain layer fast and the HTTP response times low.

## Non-Goals
- Network-based microservices
- Event sourcing (full event store)
- Framework magic
- Distributed transactions
