# Monitoring & Observability

## Overview
Production monitoring is critical for maintaining system health and debugging issues.

## Metrics to Track

### Application Metrics
- **Request Rate**: Requests per second
- **Response Time**: P50, P95, P99 latency
- **Error Rate**: 4xx and 5xx responses
- **Throughput**: Bookings created per minute

### Infrastructure Metrics
- **CPU Usage**: Per container
- **Memory Usage**: Per container
- **Disk I/O**: Read/write operations
- **Network**: Bandwidth usage

### Business Metrics
- **Active Tenants**: Number of tenants with activity
- **Bookings Created**: Daily/weekly/monthly
- **API Usage**: Requests per tenant
- **Calendar Sync Success Rate**: Percentage of successful syncs

## Logging Stack

### Structured Logging (JSON)

```php
final class JsonLogger implements LoggerInterface
{
    public function log(string $level, string $message, array $context = []): void
    {
        $log = [
            'timestamp' => (new DateTimeImmutable())->format('c'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'environment' => getenv('APP_ENV'),
            'server' => gethostname(),
            'request_id' => $_SERVER['HTTP_X_REQUEST_ID'] ?? uniqid(),
        ];
        
        error_log(json_encode($log));
    }
}
```

### Log Aggregation (ELK Stack)

**Docker Compose addition**:
```yaml
services:
  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.11.0
    environment:
      - discovery.type=single-node
    volumes:
      - es_data:/usr/share/elasticsearch/data

  logstash:
    image: docker.elastic.co/logstash/logstash:8.11.0
    volumes:
      - ./docker/logstash/logstash.conf:/usr/share/logstash/pipeline/logstash.conf
    depends_on:
      - elasticsearch

  kibana:
    image: docker.elastic.co/kibana/kibana:8.11.0
    ports:
      - "5601:5601"
    depends_on:
      - elasticsearch
```

## Application Performance Monitoring (APM)

### New Relic Integration

```php
// In bootstrap
if (extension_loaded('newrelic')) {
    newrelic_set_appname('BookFlow');
    
    // Custom transaction names
    newrelic_name_transaction('/api/v1/bookings/create');
    
    // Custom metrics
    newrelic_custom_metric('Custom/Bookings/Created', 1);
}
```

### Prometheus Metrics

Create `public/metrics.php`:
```php
<?php
header('Content-Type: text/plain');

// Booking metrics
$pdo = new PDO(/* ... */);
$stmt = $pdo->query('SELECT COUNT(*) FROM bookings WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
$bookings_last_hour = $stmt->fetchColumn();

echo "# HELP bookings_created_last_hour Number of bookings created in last hour\n";
echo "# TYPE bookings_created_last_hour gauge\n";
echo "bookings_created_last_hour $bookings_last_hour\n";

// Database connection pool
$stmt = $pdo->query('SHOW STATUS LIKE "Threads_connected"');
$connections = $stmt->fetch()['Value'];

echo "# HELP mariadb_connections Current MariaDB connections\n";
echo "# TYPE mariadb_connections gauge\n";
echo "mariadb_connections $connections\n";
```

**Prometheus config** (`prometheus.yml`):
```yaml
scrape_configs:
  - job_name: 'bookflow'
    scrape_interval: 15s
    static_configs:
      - targets: ['app:8000']
    metrics_path: '/metrics'
```

## Alerting

### Alert Rules (Prometheus)

```yaml
groups:
  - name: bookflow_alerts
    rules:
      - alert: HighErrorRate
        expr: rate(http_requests_total{status=~"5.."}[5m]) > 0.05
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "High error rate detected"
          description: "Error rate is {{ $value }} errors/sec"

      - alert: SlowResponseTime
        expr: histogram_quantile(0.95, http_request_duration_seconds) > 1
        for: 10m
        labels:
          severity: warning
        annotations:
          summary: "Slow response time"
          description: "P95 latency is {{ $value }}s"

      - alert: DatabaseDown
        expr: up{job="mariadb"} == 0
        for: 1m
        labels:
          severity: critical
        annotations:
          summary: "Database is down"
```

### Notification Channels

**Slack Integration**:
```yaml
receivers:
  - name: 'slack'
    slack_configs:
      - api_url: 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL'
        channel: '#alerts'
        title: 'BookFlow Alert'
        text: '{{ range .Alerts }}{{ .Annotations.summary }}{{ end }}'
```

## Uptime Monitoring

### External Monitoring (UptimeRobot, Pingdom)

Monitor these endpoints:
- `https://bookflow.app/health` (every 1 minute)
- `https://bookflow.app/api/v1/bookings` (every 5 minutes)

### Health Check Endpoint

```php
// public/health.php
$checks = [
    'database' => checkDatabase(),
    'redis' => checkRedis(),
    'disk_space' => checkDiskSpace(),
    'calendar_api' => checkGoogleCalendar(),
];

$status = array_reduce($checks, fn($carry, $check) => $carry && $check, true);

http_response_code($status ? 200 : 503);
echo json_encode([
    'status' => $status ? 'healthy' : 'unhealthy',
    'checks' => $checks,
    'timestamp' => time(),
]);
```

## Distributed Tracing

### OpenTelemetry Integration

```php
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\SDK\Trace\TracerProvider;

$tracer = TracerProvider::getTracer('bookflow');

$span = $tracer->spanBuilder('create_booking')
    ->setSpanKind(SpanKind::KIND_SERVER)
    ->startSpan();

try {
    $booking = $this->createBooking->execute($command);
    $span->setAttribute('booking.id', $booking->id());
    $span->setAttribute('tenant.id', $tenantId->toString());
} catch (Exception $e) {
    $span->recordException($e);
    throw $e;
} finally {
    $span->end();
}
```

## Error Tracking

### Sentry Integration

```php
use Sentry\SentrySdk;

SentrySdk::init([
    'dsn' => getenv('SENTRY_DSN'),
    'environment' => getenv('APP_ENV'),
    'traces_sample_rate' => 0.1, // 10% of transactions
]);

// Automatic error capture
try {
    $booking = $this->bookings->findById($id);
} catch (Exception $e) {
    SentrySdk::getCurrentHub()->captureException($e);
    throw $e;
}

// Custom breadcrumbs
SentrySdk::getCurrentHub()->addBreadcrumb(
    new Breadcrumb(
        Breadcrumb::LEVEL_INFO,
        Breadcrumb::TYPE_DEFAULT,
        'booking',
        'Checking availability'
    )
);
```

## Database Monitoring

### Slow Query Log

Enable in MariaDB:
```ini
slow_query_log=1
slow_query_log_file=/var/log/mysql/slow-query.log
long_query_time=1
log_queries_not_using_indexes=1
```

### Query Analysis

```bash
# Analyze slow queries
docker compose exec db mariadb-dumpslow /var/log/mysql/slow-query.log

# Real-time query monitoring
docker compose exec db mariadb -e "SHOW FULL PROCESSLIST;"
```

## Dashboard (Grafana)

### Grafana Setup

```yaml
services:
  grafana:
    image: grafana/grafana:latest
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin
    volumes:
      - grafana_data:/var/lib/grafana
      - ./docker/grafana/dashboards:/etc/grafana/provisioning/dashboards
```

### Sample Dashboard Panels

1. **Request Rate**: `rate(http_requests_total[5m])`
2. **Error Rate**: `rate(http_requests_total{status=~"5.."}[5m])`
3. **Response Time**: `histogram_quantile(0.95, http_request_duration_seconds)`
4. **Active Tenants**: `count(distinct(tenant_id))`
5. **Database Connections**: `mariadb_threads_connected`

## Cost Monitoring

### AWS Cost Explorer (if using AWS)

Track:
- EC2 instance costs
- RDS database costs
- S3 storage costs
- Data transfer costs

### Resource Optimization

```bash
# Check container resource usage
docker stats

# Identify unused volumes
docker volume ls -qf dangling=true

# Clean up old images
docker image prune -a --filter "until=720h"
```

## Incident Response

### Runbook Template

```markdown
# Incident: [Description]

## Severity: [Critical/High/Medium/Low]

## Symptoms
- [What users are experiencing]

## Investigation Steps
1. Check health endpoint: `curl https://bookflow.app/health`
2. Check logs: `docker compose logs -f app`
3. Check metrics: [Grafana dashboard link]

## Resolution Steps
1. [Step 1]
2. [Step 2]

## Prevention
- [What can we do to prevent this?]
```

## See Also
- [DEBUGGING.md](./DEBUGGING.md)
- [DEPLOYMENT.md](./DEPLOYMENT.md)
- [Prometheus Documentation](https://prometheus.io/docs/)
- [Grafana Documentation](https://grafana.com/docs/)
