@echo off
echo ==========================================
echo  BookFlow Local CI Runner (Windows)
echo ==========================================

echo [1/6] Preparing Test Database...
docker compose exec db mariadb -u root -proot -e "CREATE DATABASE IF NOT EXISTS bookflow_test"
IF %ERRORLEVEL% NEQ 0 exit /b %ERRORLEVEL%
docker compose exec db mariadb -u root -proot bookflow_test -e "source /docker-entrypoint-initdb.d/01-schema.sql"
IF %ERRORLEVEL% NEQ 0 exit /b %ERRORLEVEL%

echo [2/6] Validating Composer...
docker compose exec app composer validate --strict
IF %ERRORLEVEL% NEQ 0 exit /b %ERRORLEVEL%

echo [3/6] Running Static Analysis (PHPStan)...
docker compose exec app ./vendor/bin/phpstan analyse src tests --level=8 --memory-limit=1G
IF %ERRORLEVEL% NEQ 0 exit /b %ERRORLEVEL%

echo [4/6] Checking Code Style (PHP-CS-Fixer)...
docker compose exec app ./vendor/bin/php-cs-fixer fix --dry-run --diff
IF %ERRORLEVEL% NEQ 0 exit /b %ERRORLEVEL%

echo [5/6] Auditing Dependencies...
docker compose exec app composer audit
IF %ERRORLEVEL% NEQ 0 exit /b %ERRORLEVEL%

echo [6/6] Running Tests (PHPUnit)...
docker compose exec app ./vendor/bin/phpunit
IF %ERRORLEVEL% NEQ 0 exit /b %ERRORLEVEL%

echo.
echo [SUCCESS] All checks passed!
