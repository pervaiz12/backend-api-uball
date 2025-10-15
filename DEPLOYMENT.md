# UBall Backend API - Production Deployment Guide

This guide provides step-by-step instructions for deploying the UBall backend API on a production server using Docker.

## Table of Contents
- [Server Requirements](#server-requirements)
- [Prerequisites](#prerequisites)
- [Initial Server Setup](#initial-server-setup)
- [Application Deployment](#application-deployment)
- [SSL/HTTPS Configuration](#sslhttps-configuration)
- [Monitoring and Maintenance](#monitoring-and-maintenance)
- [Backup Strategy](#backup-strategy)
- [Troubleshooting](#troubleshooting)

## Server Requirements

### Minimum Hardware Requirements
- **CPU**: 2 cores (4 cores recommended)
- **RAM**: 4GB (8GB recommended)
- **Storage**: 50GB SSD (100GB+ recommended)
- **Network**: Stable internet connection with public IP

### Software Requirements
- **OS**: Ubuntu 20.04 LTS or later (recommended)
- **Docker**: Version 20.10+
- **Docker Compose**: Version 2.0+
- **Git**: Latest version

## Prerequisites

### 1. Domain and DNS Setup
- Register a domain name for your application
- Configure DNS A record to point to your server's public IP
- Optionally set up a subdomain (e.g., `api.yourdomain.com`)

### 2. Server Access
- SSH access to the server with sudo privileges
- Firewall configured to allow ports 22 (SSH), 80 (HTTP), and 443 (HTTPS)

## Initial Server Setup

### 1. Update System Packages
```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Install Docker
```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Add user to docker group
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify installations
docker --version
docker-compose --version
```

### 3. Install Additional Tools
```bash
sudo apt install -y git nginx certbot python3-certbot-nginx ufw
```

### 4. Configure Firewall
```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

## Application Deployment

### 1. Clone Repository
```bash
# Create application directory
sudo mkdir -p /var/www/uball
sudo chown $USER:$USER /var/www/uball

# Clone the repository
cd /var/www/uball
git clone <your-repository-url> .
cd backend-api
```

### 2. Environment Configuration
```bash
# Copy production environment file
cp .env.prod.example .env.prod

# Edit the production environment file
nano .env.prod
```

**Configure the following variables in `.env.prod`:**
```env
APP_NAME=UBall
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=uball_prod
DB_USERNAME=uball_user
DB_PASSWORD=your_secure_database_password
DB_ROOT_PASSWORD=your_secure_root_password

# Redis Configuration
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache and Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail Configuration (configure based on your email provider)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="UBall"

# File Storage (configure for production storage)
FILESYSTEM_DISK=local
# For AWS S3 (recommended for production)
# FILESYSTEM_DISK=s3
# AWS_ACCESS_KEY_ID=your-access-key
# AWS_SECRET_ACCESS_KEY=your-secret-key
# AWS_DEFAULT_REGION=us-east-1
# AWS_BUCKET=your-bucket-name
```

### 3. Generate Application Key
```bash
# Generate a new application key
php artisan key:generate --show

# Copy the generated key to your .env.prod file
```

### 4. Update Production Docker Compose
Create a production-specific docker-compose override:

```bash
# Create production override file
cat > docker-compose.prod.override.yml << 'EOF'
version: '3.8'

services:
  app:
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    volumes:
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache

  nginx:
    ports:
      - "8080:80"  # Change to avoid conflict with system nginx
    volumes:
      - ./public:/var/www/html/public:ro
      - ./docker/nginx/conf.d/:/etc/nginx/conf.d/:ro

  db:
    volumes:
      - ./docker/mysql/prod.cnf:/etc/mysql/conf.d/my.cnf:ro
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}

  redis:
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data

volumes:
  redis_data:
    driver: local
EOF
```

### 5. Deploy Application
```bash
# Build and start services
docker-compose -f docker-compose.prod.yml -f docker-compose.prod.override.yml up --build -d

# Wait for services to start
sleep 30

# Run database migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Clear and cache configuration
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache

# Set proper permissions
docker-compose -f docker-compose.prod.yml exec app chown -R www-data:www-data /var/www/html/storage
docker-compose -f docker-compose.prod.yml exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
```

## SSL/HTTPS Configuration

### 1. Configure System Nginx as Reverse Proxy
```bash
# Create nginx configuration
sudo tee /etc/nginx/sites-available/uball << 'EOF'
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # File upload size (matches your backend configuration)
    client_max_body_size 512M;

    location / {
        proxy_pass http://localhost:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 300s;
        proxy_connect_timeout 75s;
    }
}
EOF

# Enable the site
sudo ln -sf /etc/nginx/sites-available/uball /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 2. Obtain SSL Certificate
```bash
# Get SSL certificate using Certbot
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test automatic renewal
sudo certbot renew --dry-run
```

### 3. Update Application URL
```bash
# Update APP_URL in .env.prod
sed -i 's|APP_URL=.*|APP_URL=https://yourdomain.com|' .env.prod

# Restart application to apply changes
docker-compose -f docker-compose.prod.yml -f docker-compose.prod.override.yml restart app
```

## Monitoring and Maintenance

### 1. Create Monitoring Script
```bash
# Create monitoring script
sudo tee /usr/local/bin/uball-monitor.sh << 'EOF'
#!/bin/bash
cd /var/www/uball/backend-api

# Check if containers are running
if ! docker-compose -f docker-compose.prod.yml ps | grep -q "Up"; then
    echo "$(date): UBall containers not running, attempting restart..."
    docker-compose -f docker-compose.prod.yml -f docker-compose.prod.override.yml up -d
fi

# Check disk space
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "$(date): Warning - Disk usage is at ${DISK_USAGE}%"
fi

# Check memory usage
MEMORY_USAGE=$(free | awk 'NR==2{printf "%.2f", $3*100/$2}')
if (( $(echo "$MEMORY_USAGE > 90" | bc -l) )); then
    echo "$(date): Warning - Memory usage is at ${MEMORY_USAGE}%"
fi
EOF

sudo chmod +x /usr/local/bin/uball-monitor.sh

# Add to crontab (runs every 5 minutes)
(crontab -l 2>/dev/null; echo "*/5 * * * * /usr/local/bin/uball-monitor.sh >> /var/log/uball-monitor.log 2>&1") | crontab -
```

### 2. Log Rotation
```bash
# Configure log rotation
sudo tee /etc/logrotate.d/uball << 'EOF'
/var/www/uball/backend-api/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        docker-compose -f /var/www/uball/backend-api/docker-compose.prod.yml exec app php artisan queue:restart > /dev/null 2>&1 || true
    endscript
}
EOF
```

## Backup Strategy

### 1. Database Backup Script
```bash
# Create backup script
sudo tee /usr/local/bin/uball-backup.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/uball"
DATE=$(date +%Y%m%d_%H%M%S)
APP_DIR="/var/www/uball/backend-api"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
cd $APP_DIR
docker-compose -f docker-compose.prod.yml exec -T db mysqldump -u root -p${DB_ROOT_PASSWORD} uball_prod > $BACKUP_DIR/database_$DATE.sql

# Application files backup (excluding vendor and node_modules)
tar -czf $BACKUP_DIR/app_files_$DATE.tar.gz \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/logs' \
    --exclude='bootstrap/cache' \
    -C /var/www/uball .

# Keep only last 7 days of backups
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "$(date): Backup completed successfully"
EOF

sudo chmod +x /usr/local/bin/uball-backup.sh

# Schedule daily backups at 2 AM
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/uball-backup.sh >> /var/log/uball-backup.log 2>&1") | crontab -
```

### 2. Storage Backup (for uploaded files)
```bash
# If using local storage, backup uploaded files
# Add this to the backup script for storage directory
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz -C $APP_DIR storage/app/public
```

## Troubleshooting

### Common Issues and Solutions

#### 1. Application Not Starting
```bash
# Check container logs
docker-compose -f docker-compose.prod.yml logs app

# Check if all services are running
docker-compose -f docker-compose.prod.yml ps

# Restart services
docker-compose -f docker-compose.prod.yml restart
```

#### 2. Database Connection Issues
```bash
# Check database container
docker-compose -f docker-compose.prod.yml logs db

# Test database connection
docker-compose -f docker-compose.prod.yml exec app php artisan tinker
# In tinker: DB::connection()->getPdo();
```

#### 3. File Upload Issues
```bash
# Check storage permissions
docker-compose -f docker-compose.prod.yml exec app ls -la storage/

# Fix permissions
docker-compose -f docker-compose.prod.yml exec app chown -R www-data:www-data storage/
docker-compose -f docker-compose.prod.yml exec app chmod -R 775 storage/
```

#### 4. SSL Certificate Issues
```bash
# Check certificate status
sudo certbot certificates

# Renew certificate manually
sudo certbot renew

# Check nginx configuration
sudo nginx -t
```

### Performance Optimization

#### 1. Enable OPcache
Add to your PHP configuration in `Dockerfile.prod`:
```dockerfile
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini
RUN echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini
RUN echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini
```

#### 2. Database Optimization
Create `docker/mysql/prod.cnf`:
```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200
query_cache_size = 64M
```

#### 3. Redis Optimization
```bash
# Add to docker-compose.prod.override.yml
redis:
  command: redis-server --maxmemory 512mb --maxmemory-policy allkeys-lru
```

## Security Checklist

- [ ] Server firewall configured (UFW)
- [ ] SSH key-based authentication enabled
- [ ] Regular security updates scheduled
- [ ] SSL/TLS certificate installed and auto-renewal configured
- [ ] Database passwords are strong and unique
- [ ] Application key is properly generated
- [ ] File upload limits configured
- [ ] Security headers configured in Nginx
- [ ] Regular backups scheduled and tested
- [ ] Monitoring and alerting set up

## Deployment Commands Reference

```bash
# Deploy new version
cd /var/www/uball/backend-api
git pull origin main
docker-compose -f docker-compose.prod.yml -f docker-compose.prod.override.yml up --build -d
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache

# View logs
docker-compose -f docker-compose.prod.yml logs -f app

# Scale services (if needed)
docker-compose -f docker-compose.prod.yml up -d --scale app=2

# Emergency stop
docker-compose -f docker-compose.prod.yml down

# Complete restart
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml -f docker-compose.prod.override.yml up -d
```

---

**Note**: Replace `yourdomain.com` with your actual domain name throughout this guide. Always test the deployment process in a staging environment before applying to production.

For additional support or questions, refer to the Laravel documentation and Docker best practices.
