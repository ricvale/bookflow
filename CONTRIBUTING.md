# Contributing to BookFlow

Thank you for considering contributing to BookFlow! This document outlines the process and guidelines.

## Code of Conduct

- Be respectful and inclusive
- Provide constructive feedback
- Focus on what is best for the project

## Getting Started

### 1. Fork and Clone

```bash
git clone https://github.com/ricvale/bookflow.git
cd bookflow
```

### 2. Set Up Development Environment

```bash
# Start Docker containers
docker compose up -d

# Install dependencies
make install

# Run tests
make test
```

## Development Workflow

### 1. Create a Branch

```bash
git checkout -b feature/your-feature-name
```

Branch naming conventions:
- `feature/` - New features
- `fix/` - Bug fixes
- `docs/` - Documentation updates
- `refactor/` - Code refactoring

### 2. Make Changes

Follow the architectural principles:
- Domain layer: Pure PHP, no infrastructure
- Application layer: Use cases and policies
- Infrastructure layer: Database, APIs, external services
- HTTP layer: Controllers and routing

### 3. Write Tests

- **Unit tests** for domain and application logic
- **Integration tests** for infrastructure
- Aim for >80% code coverage

```bash
make test
```

### 4. Run Code Quality Checks

```bash
make lint
make format
```

### 5. Commit Changes

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```bash
git commit -m "feat: add booking cancellation feature"
git commit -m "fix: resolve timezone issue in availability check"
git commit -m "docs: update TESTING.md with new examples"
```

### 6. Push and Create Pull Request

```bash
git push origin feature/your-feature-name
```

Create a pull request on GitHub with:
- Clear description of changes
- Link to related issues
- Screenshots (if UI changes)
- Test results

## Code Style

### PHP

- **PSR-12** coding standard
- **Strict types** (`declare(strict_types=1)`)
- **Type hints** for all parameters and return types
- **Final classes** by default (unless designed for inheritance)

```php
<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking;

final class Booking
{
    public function __construct(
        private string $id,
        private TenantId $tenantId,
        private DateTimeImmutable $startsAt
    ) {}
    
    public function overlaps(self $other): bool
    {
        return $this->startsAt < $other->endsAt 
            && $this->endsAt > $other->startsAt;
    }
}
```

### Testing

```php
final class BookingTest extends TestCase
{
    public function testDetectsOverlappingBookings(): void
    {
        $booking1 = new Booking(/* ... */);
        $booking2 = new Booking(/* ... */);
        
        $this->assertTrue($booking1->overlaps($booking2));
    }
}
```

## Pull Request Checklist

- [ ] Tests pass (`make test`)
- [ ] Code quality checks pass (`make lint`)
- [ ] Code is formatted (`make format`)
- [ ] Documentation updated (if needed)
- [ ] CHANGELOG.md updated (for significant changes)
- [ ] No merge conflicts
- [ ] Commit messages follow conventions

## Review Process

1. **Automated checks** run via GitHub Actions
2. **Code review** by maintainers
3. **Feedback** addressed by contributor
4. **Approval** and merge

## Reporting Bugs

Use GitHub Issues with:
- Clear title
- Steps to reproduce
- Expected vs actual behavior
- Environment details (PHP version, OS, etc.)

## Feature Requests

Use GitHub Issues with:
- Clear description
- Use case / motivation
- Proposed solution (optional)

## Questions?

- Open a GitHub Discussion
- Check existing documentation
- Review SKILL.md files for context-specific guidance
