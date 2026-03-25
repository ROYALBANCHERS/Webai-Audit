# Production Deployment Guide

This guide covers deploying WebAI Auditor to production environments.

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- MySQL 8.0+ or PostgreSQL 14+
- Redis (recommended for production)
- Web server (Nginx or Apache)

## Environment Configuration

### 1. Copy Environment File

```bash
cp backend/.env.example backend/.env
```

### 2. Generate Application Key

```bash
php artisan key:generate
```

### 3. Configure Required Environment Variables

Edit `backend/.env` and set the following:

```bash
# Application
APP_NAME="WebAI Auditor"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Security
# Generate a secure random string (32+ chars)
ADMIN_SECRET=your_very_secure_random_string_here

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webai_auditor
DB_USERNAME=webai_user
DB_PASSWORD=your_secure_password

# Redis (recommended for production)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# External APIs
GITHUB_TOKEN=your_github_token
GOOGLE_SEARCH_API_KEY=your_google_api_key
GOOGLE_SEARCH_ENGINE_ID=your_search_engine_id

# TinyFish/AgentQL
TINYFISH_API_KEY=your_tinyfish_api_key
TINYFISH_ENDPOINT=https://api.agentql.com/v1/query
```

### 4. Generate Secure Admin Secret

```bash
openssl rand -base64 32
```

## Database Setup

### 1. Create Database

```sql
CREATE DATABASE webai_auditor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'webai_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON webai_auditor.* TO 'webai_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Run Migrations

```bash
cd backend
php artisan migrate --force
```

### 3. Seed Data (Optional)

```bash
php artisan db:seed --force
```

## Building Assets

### Frontend Assets

```bash
cd assets
npm install
npm run build
```

## Web Server Configuration

### Nginx Example

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;
    root /var/www/webai-audit/public;

    # SSL Configuration
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Laravel
    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
}
```

## Process Management

### Using Supervisor (for Queue Workers)

Create `/etc/supervisor/conf.d/webai-auditor.conf`:

```ini
[program:webai-auditor-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/webai-audit/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/webai-audit/storage/logs/worker.log
stopwaitsecs=3600
```

### Using Supervisor (for Horizon - optional)

```ini
[program:webai-auditor-horizon]
process_name=%(program_name)s
command=php /var/www/webai-audit/backend/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/webai-audit/storage/logs/horizon.log
stopwaitsecs=3600
```

## Monitoring & Logging

### Log Locations

- Application logs: `backend/storage/logs/laravel.log`
- API logs: `backend/storage/logs/api.log`
- Auth logs: `backend/storage/logs/auth.log`
- Security logs: `backend/storage/logs/security.log`
- Crawl logs: `backend/storage/logs/crawl.log`

### Log Rotation

Configure logrotate for Laravel logs:

```
/var/www/webai-audit/backend/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

## Security Checklist

- [ ] Set `APP_DEBUG=false` in production
- [ ] Set a secure `ADMIN_SECRET`
- [ ] Configure HTTPS with valid SSL certificate
- [ ] Set up firewall rules
- [ ] Configure rate limiting
- [ ] Set up proper file permissions (644 for files, 755 for directories)
- [ ] Disable directory listing
- [ ] Configure CORS properly
- [ ] Set up regular backups
- [ ] Monitor security logs

## Performance Optimization

### 1. Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Optimize Composer

```bash
composer install --optimize-autoloader --no-dev
```

### 3. Enable OPcache

Ensure OPcache is enabled in `php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

## Backup Strategy

### Database Backup

```bash
# Daily backup script
0 2 * * * /usr/bin/mysqldump -u webai_user -p'password' webai_auditor | gzip > /backups/db_$(date +\%Y\%m\%d).sql.gz
```

### File Backup

```bash
# Backup storage and uploads
rsync -avz /var/www/webai-audit/backend/storage/ /backups/storage/
```

## Deployment Platforms

### Railway

1. Connect your GitHub repository
2. Configure environment variables in Railway dashboard
3. Deploy automatically on push to main branch

### Vercel

1. Connect your GitHub repository
2. Set root directory to `frontend`
3. Configure build command and output directory
4. Add environment variables

### Cloudflare Workers

See `cloudflare-backend/worker.js` for Cloudflare Workers deployment.

## Health Checks

The application exposes a health check endpoint:

```
GET /health
```

Expected response:

```json
{
    "status": "healthy",
    "timestamp": "2024-01-15T10:30:00Z"
}
```

## Troubleshooting

### Common Issues

1. **Permission Errors**
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   chmod -R 775 storage bootstrap/cache
   ```

2. **Queue Not Processing**
   ```bash
   php artisan queue:restart
   sudo supervisorctl restart webai-auditor-worker:*
   ```

3. **Cache Issues**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

## Support

For issues and questions, please refer to the project's GitHub repository.
