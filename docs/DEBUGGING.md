# Debugging Guide

## Overview
Effective debugging is critical for development. This guide covers **Xdebug setup**, **logging strategies**, and **troubleshooting common issues**.

## Xdebug Setup (Docker)

### 1. Install Xdebug in Docker

Update `Dockerfile`:
```dockerfile
FROM php:8.4-fpm

# ... existing setup ...

# Install Xdebug
RUN pecl install xdebug-3.4.0 \
    && docker-php-ext-enable xdebug

# Configure Xdebug
RUN echo "xdebug.mode=debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=trigger" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
```

### 2. VS Code Configuration

Create `.vscode/launch.json`:
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www": "${workspaceFolder}"
            }
        }
    ]
}
```

### 3. PhpStorm Configuration

1. Go to **Settings → PHP → Debug**
2. Set Xdebug port to `9003`
3. Enable "Break at first line in PHP scripts"
4. Go to **Settings → PHP → Servers**
5. Add server:
   - Name: `bookflow`
   - Host: `localhost`
   - Port: `8000`
   - Debugger: `Xdebug`
   - Path mappings: `/var/www` → `<project root>`

### 4. Start Debugging

```bash
# Rebuild Docker with Xdebug
docker compose up -d --build

# Set breakpoint in your IDE
# Make HTTP request
curl http://localhost:8000/api/v1/bookings
```

## Logging Strategy

### PSR-3 Logger Interface

```php
interface LoggerInterface
{
    public function emergency(string $message, array $context = []): void;
    public function alert(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function notice(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function debug(string $message, array $context = []): void;
}
```

### Logging Best Practices

#### Always Include Context
```php
// ❌ Bad: No context
$logger->error('Booking creation failed');

// ✅ Good: Rich context
$logger->error('Booking creation failed', [
    'tenant_id' => $tenantId->toString(),
    'resource_id' => $resourceId,
    'starts_at' => $startsAt->format('c'),
    'error' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString(),
]);
```

#### Log at Appropriate Levels
```php
// DEBUG: Detailed flow information
$logger->debug('Checking availability', ['resource_id' => $resourceId]);

// INFO: Significant events
$logger->info('Booking created', ['booking_id' => $booking->id()]);

// WARNING: Unexpected but handled
$logger->warning('Rate limit approaching', ['requests' => 950, 'limit' => 1000]);

// ERROR: Errors that need attention
$logger->error('Database connection failed', ['error' => $e->getMessage()]);

// CRITICAL: System-wide failures
$logger->critical('All database replicas down');
```

#### Structured Logging (JSON)
```php
final class JsonLogger implements LoggerInterface
{
    public function error(string $message, array $context = []): void
    {
        $log = [
            'timestamp' => (new DateTimeImmutable())->format('c'),
            'level' => 'ERROR',
            'message' => $message,
            'context' => $context,
            'environment' => getenv('APP_ENV'),
        ];
        
        error_log(json_encode($log));
    }
}
```

### Log Rotation

Use **logrotate** in production:
```bash
# /etc/logrotate.d/bookflow
/var/log/bookflow/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

## Debugging Common Issues

### Issue: "No tenant context set"

**Symptom**: `RuntimeException: No tenant context set in InMemory implementation`

**Cause**: Tenant context not initialized

**Solution**:
```php
// In HTTP middleware
$payload = $this->jwtValidator->validate($request->bearerToken());
$this->tenantContext->setTenantId(
    TenantId::fromString($payload['tenant_id'])
);
```

### Issue: "Booking not found" (but it exists)

**Symptom**: Repository returns `null` for existing booking

**Cause**: Wrong tenant context

**Debug**:
```php
// Add logging
$tenantId = $this->tenantContext->getTenantId();
$this->logger->debug('Searching for booking', [
    'booking_id' => $id,
    'tenant_id' => $tenantId->toString(),
]);

// Check database directly
SELECT * FROM bookings WHERE id = 'booking-123';
-- Does it have a different tenant_id?
```

### Issue: Timezone Confusion

**Symptom**: Bookings appear at wrong times

**Debug**:
```php
// Always log with timezone
$logger->debug('Booking time', [
    'starts_at' => $startsAt->format('c'), // ISO 8601 with timezone
    'timezone' => $startsAt->getTimezone()->getName(),
]);

// Check database storage
SELECT starts_at, CONVERT_TZ(starts_at, '+00:00', 'America/New_York') 
FROM bookings WHERE id = 'booking-123';
```

### Issue: Slow Queries

**Enable Query Logging**:
```php
final class LoggingPDO extends PDO
{
    public function prepare($statement, $options = [])
    {
        $start = microtime(true);
        $stmt = parent::prepare($statement, $options);
        $duration = microtime(true) - $start;
        
        if ($duration > 0.1) { // Log slow queries (>100ms)
            $this->logger->warning('Slow query detected', [
                'query' => $statement,
                'duration_ms' => $duration * 1000,
            ]);
        }
        
        return $stmt;
    }
}
```

## Profiling

### Xdebug Profiler

Enable in `php.ini`:
```ini
xdebug.mode=profile
xdebug.output_dir=/tmp/xdebug
xdebug.profiler_output_name=cachegrind.out.%p
```

Analyze with **KCachegrind** or **Webgrind**.

### Blackfire.io (Production Profiling)

```bash
# Install Blackfire probe
docker compose exec app curl -L https://packages.blackfire.io/binaries/blackfire/2.0.0/blackfire-linux_amd64 -o /usr/local/bin/blackfire
docker compose exec app chmod +x /usr/local/bin/blackfire

# Profile a request
blackfire curl http://localhost:8000/api/bookings
```

## Error Tracking

### Sentry Integration

```php
use Sentry\SentrySdk;

// In bootstrap
SentrySdk::init(['dsn' => getenv('SENTRY_DSN')]);

// Automatic error capture
try {
    $booking = $this->bookings->findById($id);
} catch (Exception $e) {
    SentrySdk::getCurrentHub()->captureException($e);
    throw $e;
}
```

## Debugging Tools

### Useful Commands

```bash
# View PHP logs
docker compose logs -f app

# View Nginx logs
docker compose logs -f nginx

# View MySQL slow query log
docker compose exec db cat /var/log/mysql/slow-query.log

# Execute SQL directly
docker compose exec db mysql -u bookflow -pbookflow bookflow

# Check PHP configuration
docker compose exec app php -i | grep xdebug

# Run single test with verbose output
docker compose exec app ./vendor/bin/phpunit --testdox --verbose
```

### Database Debugging

```sql
-- Show all queries for a tenant
SELECT * FROM bookings WHERE tenant_id = 'tenant-123';

-- Check for missing indexes
EXPLAIN SELECT * FROM bookings WHERE tenant_id = 'tenant-123' AND starts_at > NOW();

-- Show slow queries
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;
```

## See Also
- [Xdebug Documentation](https://xdebug.org/docs/)
- [PSR-3 Logger Interface](https://www.php-fig.org/psr/psr-3/)
- [Debugging Best Practices](https://martinfowler.com/articles/debugging.html)
