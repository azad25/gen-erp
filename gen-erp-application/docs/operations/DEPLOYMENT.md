# Deployment Guide

## Table of Contents
- [Overview](#overview)
- [System Requirements](#system-requirements)
- [Environment Configuration](#environment-configuration)
- [Installation Steps](#installation-steps)
- [Web Server Configuration](#web-server-configuration)
- [Queue Workers](#queue-workers)
- [Scheduler Configuration](#scheduler-configuration)
- [SSL/TLS Setup](#ssltls-setup)
- [Performance Optimization](#performance-optimization)
- [Monitoring & Logging](#monitoring--logging)
- [Backup Strategy](#backup-strategy)
- [Troubleshooting](#troubleshooting)

---

## Overview

Gen-ERP is a Laravel 12 application that requires proper server configuration for production deployment. This guide covers deployment on Linux servers (Ubuntu/Debian recommended) with Nginx, PHP-FPM, MySQL, and Redis.

### Deployment Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Load Balancer (Optional)              │
└─────────────────────────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
┌───────▼────────┐  ┌──────▼───────┐  ┌───────▼────────┐
│  Web Server 1  │  │ Web Server 2 │  │  Web Server N  │
│  Nginx + PHP   │  │ Nginx + PHP  │  │  Nginx + PHP   │
└────────────────┘  └──────────────┘  └────────────────┘
        │                   │                   │
        └───────────────────┼───────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
┌───────▼────────┐  ┌──────▼───────┐  ┌───────▼────────┐
│   MySQL DB     │  │  Redis Cache │  │  Queue Workers │
│   (Primary)    │  │              │  │  (Supervisor)  │
└────────────────┘  └──────────────┘  └────────────────┘
```

---

## System Requirements

### Server Specifications

**Minimum (Small Business):**
- CPU: 2 cores
- RAM: 4 GB
- Storage: 50 GB SSD
- Concurrent Users: 10-20

**Recommended (Medium Business):**
- CPU: 4 cores
- RAM: 8 GB
- Storage: 100 GB SSD
- Concurrent Users: 50-100

**Enterprise:**
- CPU: 8+ cores
- RAM: 16+ GB
- Storage: 200+ GB SSD
- Concurrent Users: 200+

### Software Requirements

| Component | Version | Purpose |
|-----------|---------|---------|
| **PHP** | 8.2+ | Application runtime |
| **Composer** | 2.x | Dependency management |
| **Node.js** | 20.x LTS | Frontend build |
| **NPM** | 10.x | Package management |
| **MySQL** | 8.0+ | Primary database |
| **Redis** | 7.x | Cache & sessions |
| **Nginx** | 1.24+ | Web server |
| **Supervisor** | 4.x | Process management |

### PHP Extensions

Required extensions:
```bash
php8.2-cli
php8.2-fpm
php8.2-mysql
php8.2-redis
php8.2-mbstring
php8.2-xml
php8.2-curl
php8.2-zip
php8.2-gd
php8.2-intl
php8.2-bcmath
php8.2-soap
php8.2-imagick
```

---

## Environment Configuration

### Environment Variables

**File:** `.env`


#### Application Settings

```bash
# Application
APP_NAME="GenERP BD"
APP_ENV=production
APP_KEY=base64:GENERATE_WITH_php_artisan_key:generate
APP_DEBUG=false
APP_URL=https://your-domain.com

# Localization
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

# Maintenance
APP_MAINTENANCE_DRIVER=file

# Security
BCRYPT_ROUNDS=12
```

#### Database Configuration

```bash
# Primary Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=generp_production
DB_USERNAME=generp_user
DB_PASSWORD=STRONG_PASSWORD_HERE

# Read Replica (Optional)
DB_READ_HOST=replica.example.com
```

#### Cache & Session

```bash
# Cache
CACHE_STORE=redis
CACHE_PREFIX=generp_cache

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=.your-domain.com

# Redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=REDIS_PASSWORD_HERE
REDIS_PORT=6379
REDIS_DB=0
```

#### Queue Configuration

```bash
# Queue
QUEUE_CONNECTION=redis

# Queue Connections
REDIS_QUEUE_CONNECTION=default
REDIS_QUEUE=default
REDIS_QUEUE_RETRY_AFTER=90
```

#### Mail Configuration

```bash
# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

#### Broadcasting (Laravel Reverb)

```bash
# Broadcasting
BROADCAST_CONNECTION=reverb

# Reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=https

# Frontend (Vite)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=your-domain.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

#### File Storage

```bash
# Filesystem
FILESYSTEM_DISK=s3

# AWS S3 (Production)
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=generp-production
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_URL=https://your-bucket.s3.amazonaws.com
```

#### Logging

```bash
# Logging
LOG_CHANNEL=stack
LOG_STACK=daily,slack
LOG_LEVEL=error
LOG_DEPRECATIONS_CHANNEL=null

# Slack Logging (Optional)
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
```

---

## Installation Steps

### 1. Server Preparation

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 and extensions
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-redis \
    php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd \
    php8.2-intl php8.2-bcmath php8.2-soap php8.2-imagick

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20.x
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install MySQL 8.0
sudo apt install -y mysql-server

# Install Redis
sudo apt install -y redis-server

# Install Nginx
sudo apt install -y nginx

# Install Supervisor
sudo apt install -y supervisor
```

### 2. Create Application User

```bash
# Create dedicated user
sudo useradd -m -s /bin/bash generp
sudo usermod -aG www-data generp
```

### 3. Clone Repository

```bash
# Switch to application user
sudo su - generp

# Clone repository
cd /var/www
git clone https://github.com/your-org/gen-erp.git generp
cd generp

# Set permissions
sudo chown -R generp:www-data /var/www/generp
sudo chmod -R 755 /var/www/generp
sudo chmod -R 775 /var/www/generp/storage
sudo chmod -R 775 /var/www/generp/bootstrap/cache
```

### 4. Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm ci

# Build frontend assets
npm run build
```

### 5. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env with production values
nano .env
```

### 6. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE generp_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'generp_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON generp_production.* TO 'generp_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
php artisan migrate --force

# Seed initial data (optional)
php artisan db:seed --force
```

### 7. Optimize Application

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache

# Optimize autoloader
composer dump-autoload --optimize
```


---

## Web Server Configuration

### Nginx Configuration

**File:** `/etc/nginx/sites-available/generp`

```nginx
# Upstream PHP-FPM
upstream php-fpm {
    server unix:/var/run/php/php8.2-fpm.sock;
}

# HTTP to HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    
    return 301 https://$server_name$request_uri;
}

# HTTPS server
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    
    root /var/www/generp/public;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # Logging
    access_log /var/log/nginx/generp-access.log;
    error_log /var/log/nginx/generp-error.log;
    
    # Client body size (for file uploads)
    client_max_body_size 100M;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript 
               application/x-javascript application/xml+rss 
               application/json application/javascript;
    
    # Laravel public directory
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass php-fpm;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Increase timeouts for long-running requests
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }
    
    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
    
    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}

# WebSocket server (Laravel Reverb)
server {
    listen 8080 ssl http2;
    listen [::]:8080 ssl http2;
    server_name your-domain.com;
    
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # WebSocket timeouts
        proxy_read_timeout 86400;
        proxy_send_timeout 86400;
    }
}
```

**Enable site:**
```bash
sudo ln -s /etc/nginx/sites-available/generp /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### PHP-FPM Configuration

**File:** `/etc/php/8.2/fpm/pool.d/generp.conf`

```ini
[generp]
user = generp
group = www-data
listen = /var/run/php/php8.2-fpm-generp.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500

; PHP settings
php_admin_value[error_log] = /var/log/php-fpm/generp-error.log
php_admin_flag[log_errors] = on
php_value[memory_limit] = 256M
php_value[upload_max_filesize] = 100M
php_value[post_max_size] = 100M
php_value[max_execution_time] = 300
```

**Restart PHP-FPM:**
```bash
sudo systemctl restart php8.2-fpm
```

---

## Queue Workers

Queue workers process background jobs asynchronously.

### Supervisor Configuration

**File:** `/etc/supervisor/conf.d/generp-worker.conf`

```ini
[program:generp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/generp/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --queue=notifications,default,imports,audit
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=generp
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/generp/storage/logs/worker.log
stopwaitsecs=3600
startsecs=0
```

**Start workers:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start generp-worker:*
```

**Monitor workers:**
```bash
# Check status
sudo supervisorctl status generp-worker:*

# View logs
sudo supervisorctl tail -f generp-worker:generp-worker_00 stdout

# Restart workers
sudo supervisorctl restart generp-worker:*
```

### Laravel Reverb (WebSocket Server)

**File:** `/etc/supervisor/conf.d/generp-reverb.conf`

```ini
[program:generp-reverb]
command=php /var/www/generp/artisan reverb:start
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=generp
redirect_stderr=true
stdout_logfile=/var/www/generp/storage/logs/reverb.log
stopwaitsecs=10
```

**Start Reverb:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start generp-reverb
```


---

## Scheduler Configuration

Laravel's task scheduler requires a single cron entry.

### Cron Setup

```bash
# Edit crontab for generp user
sudo crontab -u generp -e

# Add this line:
* * * * * cd /var/www/generp && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Tasks

Gen-ERP runs these tasks automatically:

| Task | Schedule | Purpose |
|------|----------|---------|
| `queue:prune-batches` | Daily | Clean old job batches |
| `queue:prune-failed` | Daily | Clean old failed jobs |
| `cache:prune-stale-tags` | Hourly | Clean stale cache tags |
| `model:prune` | Daily | Prune soft-deleted models |
| `backup:run` | Daily 2 AM | Database backup |
| `reports:generate` | Daily 6 AM | Generate daily reports |

---

## SSL/TLS Setup

### Let's Encrypt (Certbot)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

### Manual Certificate

If using a purchased SSL certificate:

```bash
# Copy certificate files
sudo cp your-domain.crt /etc/ssl/certs/
sudo cp your-domain.key /etc/ssl/private/
sudo cp ca-bundle.crt /etc/ssl/certs/

# Update Nginx configuration
ssl_certificate /etc/ssl/certs/your-domain.crt;
ssl_certificate_key /etc/ssl/private/your-domain.key;
ssl_trusted_certificate /etc/ssl/certs/ca-bundle.crt;
```

---

## Performance Optimization

### 1. OPcache Configuration

**File:** `/etc/php/8.2/fpm/conf.d/10-opcache.ini`

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

### 2. Redis Configuration

**File:** `/etc/redis/redis.conf`

```conf
# Memory
maxmemory 2gb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# Performance
tcp-backlog 511
timeout 0
tcp-keepalive 300
```

### 3. MySQL Optimization

**File:** `/etc/mysql/mysql.conf.d/mysqld.cnf`

```ini
[mysqld]
# InnoDB
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Query Cache (MySQL 5.7 only)
query_cache_type = 1
query_cache_size = 128M

# Connections
max_connections = 200
max_connect_errors = 100

# Slow Query Log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2
```

### 4. Application Optimization

```bash
# Enable OPcache
sudo systemctl restart php8.2-fpm

# Cache everything
php artisan optimize

# Use Redis for sessions
# Set SESSION_DRIVER=redis in .env

# Use CDN for static assets
# Configure AWS CloudFront or similar
```

### 5. Database Indexing

```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_company_id ON invoices(company_id);
CREATE INDEX idx_customer_id ON invoices(customer_id);
CREATE INDEX idx_invoice_date ON invoices(invoice_date);
CREATE INDEX idx_status ON invoices(status);

-- Composite indexes
CREATE INDEX idx_company_status ON invoices(company_id, status);
CREATE INDEX idx_company_date ON invoices(company_id, invoice_date);
```

---

## Monitoring & Logging

### Application Monitoring

**Laravel Telescope (Development/Staging):**
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Laravel Pulse (Production):**
```bash
composer require laravel/pulse
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
php artisan migrate
```

### Log Management

**Centralized Logging with Papertrail:**

```bash
# Install rsyslog
sudo apt install -y rsyslog

# Configure remote logging
echo "*.*          @logs.papertrailapp.com:XXXXX" | sudo tee -a /etc/rsyslog.conf

# Restart rsyslog
sudo systemctl restart rsyslog
```

**Log Rotation:**

**File:** `/etc/logrotate.d/generp`

```
/var/www/generp/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 generp www-data
    sharedscripts
    postrotate
        php /var/www/generp/artisan cache:clear > /dev/null 2>&1
    endscript
}
```

### Health Checks

**File:** `routes/web.php`

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::has('health_check') ? 'working' : 'not_working',
    ]);
});
```

### Uptime Monitoring

Use services like:
- **UptimeRobot** - Free tier available
- **Pingdom** - Comprehensive monitoring
- **StatusCake** - Multiple check locations


---

## Backup Strategy

### Database Backups

**Automated Backup Script:**

**File:** `/usr/local/bin/backup-generp.sh`

```bash
#!/bin/bash

# Configuration
DB_NAME="generp_production"
DB_USER="generp_user"
DB_PASS="YOUR_PASSWORD"
BACKUP_DIR="/var/backups/generp"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup directory
mkdir -p $BACKUP_DIR

# Dump database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup storage directory
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz /var/www/generp/storage

# Upload to S3 (optional)
aws s3 cp $BACKUP_DIR/db_$DATE.sql.gz s3://your-backup-bucket/generp/
aws s3 cp $BACKUP_DIR/storage_$DATE.tar.gz s3://your-backup-bucket/generp/

# Remove old backups
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +$RETENTION_DAYS -delete
find $BACKUP_DIR -name "storage_*.tar.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: $DATE"
```

**Make executable:**
```bash
sudo chmod +x /usr/local/bin/backup-generp.sh
```

**Schedule backup:**
```bash
# Add to crontab
0 2 * * * /usr/local/bin/backup-generp.sh >> /var/log/generp-backup.log 2>&1
```

### Laravel Backup Package

```bash
# Install package
composer require spatie/laravel-backup

# Publish config
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"

# Configure in config/backup.php
```

**File:** `config/backup.php`

```php
return [
    'backup' => [
        'name' => env('APP_NAME', 'generp'),
        'source' => [
            'files' => [
                'include' => [
                    base_path(),
                ],
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                ],
            ],
            'databases' => ['mysql'],
        ],
        'destination' => [
            'disks' => ['s3'],
        ],
    ],
];
```

**Run backup:**
```bash
php artisan backup:run
```

---

## Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error

**Check logs:**
```bash
tail -f /var/www/generp/storage/logs/laravel.log
tail -f /var/log/nginx/generp-error.log
```

**Common causes:**
- Missing `.env` file
- Incorrect file permissions
- PHP errors
- Database connection issues

**Fix:**
```bash
# Check permissions
sudo chown -R generp:www-data /var/www/generp
sudo chmod -R 755 /var/www/generp
sudo chmod -R 775 /var/www/generp/storage
sudo chmod -R 775 /var/www/generp/bootstrap/cache

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### 2. Queue Jobs Not Processing

**Check worker status:**
```bash
sudo supervisorctl status generp-worker:*
```

**Restart workers:**
```bash
sudo supervisorctl restart generp-worker:*
```

**Check failed jobs:**
```bash
php artisan queue:failed
php artisan queue:retry all
```

#### 3. Database Connection Failed

**Test connection:**
```bash
mysql -u generp_user -p -h 127.0.0.1 generp_production
```

**Check credentials in `.env`:**
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=generp_production
DB_USERNAME=generp_user
DB_PASSWORD=correct_password
```

#### 4. High Memory Usage

**Check PHP-FPM:**
```bash
# Monitor processes
ps aux | grep php-fpm

# Adjust pool settings
sudo nano /etc/php/8.2/fpm/pool.d/generp.conf
```

**Optimize:**
```bash
# Reduce pm.max_children
pm.max_children = 30

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

#### 5. Slow Page Load

**Enable query logging:**
```php
// In AppServiceProvider
DB::listen(function ($query) {
    if ($query->time > 1000) {
        Log::warning('Slow query', [
            'sql' => $query->sql,
            'time' => $query->time,
        ]);
    }
});
```

**Check slow queries:**
```bash
tail -f /var/log/mysql/slow-query.log
```

**Optimize:**
- Add database indexes
- Enable OPcache
- Use Redis for caching
- Optimize N+1 queries

### Deployment Checklist

**Pre-Deployment:**
- [ ] Backup database
- [ ] Backup storage files
- [ ] Test on staging environment
- [ ] Review code changes
- [ ] Update dependencies
- [ ] Run tests

**Deployment:**
- [ ] Enable maintenance mode: `php artisan down`
- [ ] Pull latest code: `git pull origin main`
- [ ] Install dependencies: `composer install --no-dev`
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Clear cache: `php artisan optimize`
- [ ] Build assets: `npm run build`
- [ ] Restart workers: `sudo supervisorctl restart generp-worker:*`
- [ ] Disable maintenance mode: `php artisan up`

**Post-Deployment:**
- [ ] Check application health
- [ ] Monitor error logs
- [ ] Test critical features
- [ ] Verify queue workers
- [ ] Check database connections
- [ ] Monitor performance metrics

### Zero-Downtime Deployment

Use **Laravel Envoy** or **Deployer** for automated deployments:

**File:** `Envoy.blade.php`

```php
@servers(['production' => 'generp@your-server.com'])

@task('deploy', ['on' => 'production'])
    cd /var/www/generp
    git pull origin main
    composer install --no-dev --optimize-autoloader
    npm ci && npm run build
    php artisan migrate --force
    php artisan optimize
    sudo supervisorctl restart generp-worker:*
    php artisan queue:restart
@endtask
```

**Run deployment:**
```bash
envoy run deploy
```

---

## Security Best Practices

### 1. Firewall Configuration

```bash
# Install UFW
sudo apt install -y ufw

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow MySQL (only from localhost)
sudo ufw allow from 127.0.0.1 to any port 3306

# Enable firewall
sudo ufw enable
```

### 2. Fail2Ban

```bash
# Install Fail2Ban
sudo apt install -y fail2ban

# Configure
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
sudo nano /etc/fail2ban/jail.local

# Enable for Nginx
[nginx-http-auth]
enabled = true
port = http,https
logpath = /var/log/nginx/generp-error.log

# Restart
sudo systemctl restart fail2ban
```

### 3. Regular Updates

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Update Composer dependencies
composer update --no-dev

# Update NPM packages
npm update
```

### 4. Security Headers

Already configured in Nginx (see Web Server Configuration section).

---

**Last Updated:** March 4, 2026  
**Version:** 1.0.0  
**Maintainer:** Gen-ERP DevOps Team
