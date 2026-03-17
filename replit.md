# VectorScope - Security Training Lab

## Overview
VectorScope is an intentionally vulnerable PHP web application designed for security training (CTF-style challenges). It demonstrates common web vulnerabilities including SQL Injection, XSS, IDOR, and Debug Information Exposure.

## Architecture
- **Language:** PHP 8.2 (built-in web server)
- **Database:** SQLite (via PDO) - adapted from original MySQL/Docker setup
- **Frontend:** Plain PHP/HTML with Tailwind CSS (CDN)

## Project Structure
```
Task-1/
  app/          - PHP application files (document root)
    config.php  - Database connection + mysqli compatibility shim (SQLite)
    index.php   - Home page
    login.php   - Login page (intentional SQL injection)
    search.php  - Asset search (intentional XSS)
    orders.php  - Records lookup (intentional blind SQLi)
    profile.php - User profile (intentional IDOR)
    admin.php   - Admin panel
    debug.php   - Debug info exposure
    xss_flag.php - XSS flag endpoint
    style.css   - Stylesheet
  db/
    init.sql    - Original MySQL schema (reference only)
    app.sqlite  - SQLite database (auto-created at runtime)
  Dockerfile    - Original Docker config (not used in Replit)
  docker-compose.yml - Original Docker Compose (not used in Replit)
```

## Running
- Workflow: `php -S 0.0.0.0:5000 -t Task-1/app`
- Port: 5000

## Database Setup
The SQLite database is auto-initialized on first run by `config.php`. No manual setup required.

## Intentional Vulnerabilities (DO NOT FIX)
- `login.php` - SQL Injection in login form
- `search.php` - Reflected XSS in search output
- `orders.php` - Blind SQL Injection in record lookup
- `profile.php` - IDOR via user ID parameter
- `debug.php` - Debug information exposure

## Flags
- `THM{SQLI_LOGIN_BYPASS}` - SQL injection on login
- `THM{XSS_REFLECTED}` - XSS on search
- `THM{BLIND_SQL_INJECTION}` - Blind SQLi on orders
- `THM{IDOR_ACCESS}` - IDOR on profile
- `THM{DEBUG_EXPOSED}` - Debug page exposure
