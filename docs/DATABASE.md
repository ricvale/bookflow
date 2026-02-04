# Database Configuration

## MariaDB vs MySQL

We use **MariaDB 11.6** instead of MySQL 8.0 for several reasons:

### Size Comparison
- **MySQL 8.0**: ~1GB Docker image
- **MariaDB 11.6 (noble)**: ~400MB Docker image (60% smaller)
- **MariaDB 11.6 (alpine)**: ~180MB Docker image (82% smaller) ⭐ **We use this**

### Security
- **MariaDB**: Faster security patches, community-driven
- **MySQL 8.0**: Known CVEs in older versions (CVE-2024-21102, CVE-2024-21096)
- **MariaDB 11.6**: No known high-severity CVEs as of Jan 2026

### Compatibility
- **100% MySQL compatible**: Drop-in replacement
- Same SQL syntax
- Same client libraries (PDO works identically)

### Performance
- MariaDB is often faster for read-heavy workloads
- Better query optimizer
- Improved connection handling

## Connection String

No code changes needed! PDO connects the same way:

```php
$pdo = new PDO(
    'mysql:host=db;dbname=bookflow',  // Still uses 'mysql:' prefix
    'bookflow',
    'bookflow'
);
```

## Security Best Practices

### 1. Strong Passwords (Production)
```yaml
environment:
  MARIADB_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}  # From .env
  MARIADB_PASSWORD: ${DB_PASSWORD}
```

### 2. Disable Root Remote Access
```sql
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
FLUSH PRIVILEGES;
```

### 3. Regular Updates
```bash
# Update to latest patch version
docker compose pull db
docker compose up -d db
```

### 4. Network Isolation
```yaml
# Don't expose port in production
# ports:
#   - "3306:3306"  # Comment out
```

## Migration from MySQL

If you were using MySQL 8.0:

```bash
# Backup from MySQL
docker compose exec db mysqldump -u root -p bookflow > backup.sql

# Switch to MariaDB in docker-compose.yml

# Restore to MariaDB
docker compose up -d db
docker compose exec -T db mariadb -u bookflow -p bookflow < backup.sql
```

## Monitoring

### Check Version
```bash
docker compose exec db mariadb --version
```

### Check for Updates
```bash
docker compose exec db mariadb -u root -p -e "SELECT VERSION();"
```

### Security Audit
```bash
docker compose exec db mariadb-secure-installation
```

## Alternative: SQLite (Even Lighter)

For **development only**, you could use SQLite:
- **Size**: ~10MB
- **No server**: Just a file
- **Fast**: For single-user scenarios

**Not recommended for production** (no concurrent writes).

## Recommended: MariaDB 11.6

✅ **60% smaller** than MySQL 8.0  
✅ **Better security** (faster patches)  
✅ **100% compatible** (no code changes)  
✅ **Production-ready** (used by Wikipedia, Google, etc.)  
