# README

## BookFlow

A portfolio-grade, multi-tenant SaaS booking system built with modern PHP.

![Build Status](https://img.shields.io/badge/build-passing-brightgreen)
![PHP Version](https://img.shields.io/badge/php-8.4-blue)
![License](https://img.shields.io/badge/license-MIT-green)

### Stack

- PHP 8.4+ (strict types, no framework)
- MariaDB 10.11+
- Vanilla JavaScript
- Docker for development

### Architecture

- **Domain-Driven Design**: Pure domain logic, no infrastructure leakage
- **Layered Architecture**: Domain → Application → Infrastructure → HTTP
- **Multi-Tenancy**: Single database, shared schema, tenant_id enforced everywhere
- **Testability First**: All business logic is unit-testable

### Key Features

- Booking creation with conflict detection
- Availability rules enforcement
- Google Calendar integration
- Background job processing for async tasks
- Timezone-safe time handling

### Getting Started

#### Prerequisites

- Docker & Docker Compose

#### Installation

```bash
# Clone the repository
git clone https://github.com/ricvale/bookflow.git
cd bookflow

# Start the Docker environment
docker compose up -d

# Install dependencies
docker compose exec app composer install

# Run tests
docker compose exec app ./vendor/bin/phpunit
```

#### Access

- Application: http://localhost:8000
- MariaDB: localhost:3306 (user: `bookflow`, password: `bookflow`)

#### Deployment & Sharing

- **🚀 Instant Public Demo (Easiest)**: This project includes a **Zero-Config Cloudflare Quick Tunnel**. Just run `docker compose up -d` and see [Cloudflare Quick Tunnel](docs/CLOUDFLARE_TUNNEL_GUIDE.md) to get your public URL instantly. **No account or credit card required.**
- **Public Cloud Hosting**: For always-on hosting, see our [Truly Free Hosting Options](docs/FREE_DEPLOYMENT.md).

### Project Structure

```
bookflow/
├── docs/               # Architecture and domain documentation
├── skills/             # AI-agent skills (agentskills.io compliant)
│   ├── booking/
│   ├── tenancy/
│   ├── authentication/
│   ├── availability/
│   └── calendar-integration/
├── src/                # Backend (PHP)
│   ├── Domain/        # Pure business logic
│   ├── Application/   # Use cases and policies
│   ├── Infrastructure/# MariaDB, APIs, logging
│   └── Http/          # Controllers and routing
├── frontend/           # Frontend (Vanilla JS) - SEPARATE
│   ├── src/           # JavaScript modules
│   ├── public/        # HTML entry point
│   └── assets/        # CSS, images
├── tests/
│   ├── Unit/          # Domain and application tests
│   └── Integration/   # Infrastructure tests
├── public/            # Backend web entry point
└── docker/            # Docker configuration
```

### Frontend/Backend Separation

- **Backend**: PHP API (REST, JSON)
- **Frontend**: Vanilla JavaScript (can be deployed separately)
- **Communication**: HTTP only (JWT for auth)
- **No shared code**: Completely decoupled

### AI-Agent Ready

This project follows [agentskills.io](https://agentskills.io) for AI-friendly documentation:

- Each domain context has a `SKILL.md` file
- Progressive disclosure (lightweight descriptions, detailed on-demand)
- Clear constraints, edge cases, and examples
- See [skills/README.md](skills/README.md) for details

### Design Principles

1. **Explicit over Implicit**: No magic, no hidden behavior
2. **Domain Purity**: Business logic has zero infrastructure dependencies
3. **Tenant Isolation**: tenant_id is never accepted from user input
4. **Type Safety**: Strict types everywhere
5. **Small Classes**: Single responsibility, focused methods

### Testing

```bash
# Run all tests
docker compose exec app ./vendor/bin/phpunit

# Run only unit tests
docker compose exec app ./vendor/bin/phpunit --testsuite=Unit

# Run with coverage
docker compose exec app ./vendor/bin/phpunit --coverage-html coverage
```

### Documentation

- **Architecture & Design**
  - [Architecture](docs/ARCHITECTURE.md) - System design and layers
  - [Multi-Tenancy](docs/TENANCY.md) - Tenant isolation strategy
  - [Domain Glossary](docs/GLOSSARY.md) - Domain terminology
- **Development**
  - [Testing Guide](docs/TESTING.md) - Unit, integration, and E2E testing
  - [Debugging Guide](docs/DEBUGGING.md) - Xdebug setup and troubleshooting
  - [Contributing](CONTRIBUTING.md) - How to contribute
- **Operations**
  - [Cloudflare Sharing](docs/CLOUDFLARE_TUNNEL_GUIDE.md) - Share your local instance
  - [Free Deployment](docs/FREE_DEPLOYMENT.md) - Truly free hosting options
  - [Deployment Guide](docs/DEPLOYMENT.md) - Production deployment
  - [Monitoring](docs/MONITORING.md) - Observability and alerting
- **AI-Agent Skills**
  - [Skills Index](skills/README.md) - AI-friendly documentation
  - [Booking Skill](skills/booking/SKILL.md)
  - [Tenancy Skill](skills/tenancy/SKILL.md)
  - [Authentication Skill](skills/authentication/SKILL.md)
  - [Availability Skill](skills/availability/SKILL.md)
  - [Calendar Integration Skill](skills/calendar-integration/SKILL.md)

### DevOps & CI/CD

#### Makefile Commands

```bash
make help          # Show all available commands
make install       # Install dependencies
make test          # Run all tests
make lint          # Run code quality checks
make up            # Start Docker services
make down          # Stop Docker services
```

#### GitHub Actions

- **CI Pipeline**: Runs on every push and PR
  - PHPUnit tests with coverage
  - PHPStan static analysis (level 8)
  - PHP-CS-Fixer code style checks
  - Security audit (composer audit)
  - Docker build verification

#### Code Quality Tools
- **PHPStan**: Static analysis at level 8
- **PHP-CS-Fixer**: PSR-12 code style enforcement
- **PHPUnit**: Unit and integration testing
- **Codecov**: Test coverage tracking

### License

MIT
