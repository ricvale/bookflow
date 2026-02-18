# Deployment Guide

## Overview
This guide covers deploying BookFlow to production environments.

## Production Architecture

```
┌─────────────┐
│   Cloudflare│  CDN + DDoS Protection
│   (Frontend)│
└──────┬──────┘
       │
┌──────▼──────┐
│    Nginx    │  Reverse Proxy + SSL
└──────┬──────┘
       │
┌──────▼──────┐
│  PHP-FPM    │  Application (Multiple instances)
│  (Backend)  │
└──────┬──────┘
       │
┌──────▼──────┐
│   MariaDB   │  Database (Primary + Replicas)
└─────────────┘
```

## Prerequisites

- Docker & Docker Compose
- Domain name with DNS configured
- SSL certificate (Let's Encrypt recommended)
- Server with 2GB+ RAM

## Environment Variables

Create `.env` file:
```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bookflow.app

# Database
DB_HOST=db
DB_PORT=3306
DB_DATABASE=bookflow
DB_USERNAME=bookflow
DB_PASSWORD=<strong-password>

# JWT
JWT_SECRET=<generate-with-openssl-rand-base64-32>
JWT_EXPIRY=3600

# Google Calendar
GOOGLE_CLIENT_ID=<your-client-id>
GOOGLE_CLIENT_SECRET=<your-client-secret>

# Logging
LOG_LEVEL=error
SENTRY_DSN=<your-sentry-dsn>
```

## Production Docker Compose

Create `docker-compose.prod.yml`:
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    restart: always
    environment:
      - APP_ENV=production
    volumes:
      - ./:/var/www
    networks:
      - bookflow
    deploy:
      replicas: 3
      resources:
        limits:
          cpus: '1'
          memory: 512M

  db:
    image: mariadb:11.6
    restart: always
    environment:
      MARIADB_DATABASE: ${DB_DATABASE}
      MARIADB_USER: ${DB_USERNAME}
      MARIADB_PASSWORD: ${DB_PASSWORD}
      MARIADB_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
      - ./docker/mariadb/my.cnf:/etc/mysql/conf.d/my.cnf
    networks:
      - bookflow
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G

  nginx:
    image: nginx:alpine
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx/prod.conf:/etc/nginx/conf.d/default.conf
      - ./docker/nginx/ssl:/etc/nginx/ssl
    networks:
      - bookflow
    depends_on:
      - app

networks:
  bookflow:
    driver: bridge

volumes:
  db_data:
```

## SSL Configuration

### Using Let's Encrypt (Certbot)

```bash
# Install certbot
sudo apt-get install certbot python3-certbot-nginx

# Generate certificate
sudo certbot --nginx -d bookflow.app -d www.bookflow.app

# Auto-renewal (cron)
0 0 * * * certbot renew --quiet
```

### Nginx SSL Config

```nginx
server {
    listen 443 ssl http2;
    server_name bookflow.app;

    ssl_certificate /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    root /var/www/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## Database Migration

```bash
# Backup existing data
docker compose exec db mariadb-dump -u bookflow -p bookflow > backup.sql

# Run migrations
docker compose exec app php bin/migrate.php

# Verify
docker compose exec db mariadb -u bookflow -p bookflow -e "SHOW TABLES;"
```

## Deployment Steps

### 1. Initial Setup

```bash
# Clone repository
git clone https://github.com/ricvale/bookflow.git
cd bookflow

# Copy environment file
cp .env.example .env
# Edit .env with production values

# Build and start
docker compose -f docker-compose.prod.yml up -d --build

# Install dependencies
docker compose exec app composer install --no-dev --optimize-autoloader

# Run migrations
docker compose exec app php bin/migrate.php
```

### 2. Zero-Downtime Deployment

```bash
# Pull latest code
git pull origin main

# Build new image
docker compose -f docker-compose.prod.yml build app

# Rolling update (one container at a time)
docker compose -f docker-compose.prod.yml up -d --no-deps --scale app=3 app

# Run migrations (if any)
docker compose exec app php bin/migrate.php

# Health check
curl https://bookflow.app/health
```

## Health Checks

Create `public/health.php`:
```php
<?php
header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => time(),
    'checks' => [],
];

// Database check
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']}",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    $health['checks']['database'] = 'ok';
} catch (PDOException $e) {
    $health['status'] = 'error';
    $health['checks']['database'] = 'failed';
}

// Disk space check
$free = disk_free_space('/');
$total = disk_total_space('/');
$health['checks']['disk'] = [
    'free_gb' => round($free / 1024 / 1024 / 1024, 2),
    'total_gb' => round($total / 1024 / 1024 / 1024, 2),
];

http_response_code($health['status'] === 'ok' ? 200 : 503);
echo json_encode($health);
```

## Monitoring

See [MONITORING.md](./MONITORING.md) for detailed monitoring setup.

## Backup Strategy

### Automated Daily Backups

```bash
#!/bin/bash
# /usr/local/bin/backup-bookflow.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR=/backups/bookflow

# Database backup
docker compose exec -T db mysqldump -u bookflow -p$DB_PASSWORD bookflow | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# File backup (uploads, etc.)
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/storage

# Retention: Keep last 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

# Upload to S3 (optional)
aws s3 sync $BACKUP_DIR s3://bookflow-backups/
```

Add to cron:
```bash
0 2 * * * /usr/local/bin/backup-bookflow.sh
```

## Rollback Procedure

```bash
# Stop current version
docker compose -f docker-compose.prod.yml down

# Checkout previous version
git checkout <previous-commit>

# Rebuild and start
docker compose -f docker-compose.prod.yml up -d --build

# Restore database (if needed)
gunzip < backup.sql.gz | docker compose exec -T db mariadb -u bookflow -p bookflow
```

## Performance Optimization

### PHP OpCache

Add to `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
```

### MySQL Tuning

```ini
# /etc/mysql/conf.d/my.cnf
[mysqld]
innodb_buffer_pool_size=1G
innodb_log_file_size=256M
max_connections=200
query_cache_size=0
query_cache_type=0
```

## Security Checklist

- [ ] SSL/TLS enabled (A+ rating on SSL Labs)
- [ ] Firewall configured (only ports 80, 443, 22 open)
- [ ] Database not exposed to public internet
- [ ] Strong passwords for all services
- [ ] JWT secret is random and secure
- [ ] File upload directory not executable
- [ ] Error reporting disabled in production
- [ ] CORS properly configured
- [ ] Rate limiting enabled
- [ ] Security headers configured (CSP, HSTS, etc.)

## Troubleshooting

### High CPU Usage
```bash
# Check PHP-FPM processes
docker compose exec app top

# Check slow queries
docker compose exec db mysql -e "SHOW FULL PROCESSLIST;"
```

### Out of Memory
```bash
# Check memory usage
docker stats

# Increase PHP memory limit
# In php.ini: memory_limit=512M
```

### Database Connection Errors
```bash
# Check MariaDB status
docker compose exec db mariadb-admin -u root -p status

# Check connections
docker compose exec db mariadb -e "SHOW STATUS LIKE 'Threads_connected';"
```

## See Also
- [MONITORING.md](./MONITORING.md)
- [DEBUGGING.md](./DEBUGGING.md)
- [Docker Documentation](https://docs.docker.com/)
