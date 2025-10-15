# 🚀 UBall Backend API - Client Deployment Guide

**Complete step-by-step guide for deploying UBall backend API on any server**

## 📋 Table of Contents
- [System Requirements](#system-requirements)
- [Quick Setup (Development)](#quick-setup-development)
- [Production Deployment](#production-deployment)
- [Admin Access](#admin-access)
- [Troubleshooting](#troubleshooting)
- [Support](#support)

---

## 🖥️ System Requirements

### Minimum Server Specifications:
- **CPU:** 2 cores (4 cores recommended)
- **RAM:** 4GB (8GB recommended for production)
- **Storage:** 50GB SSD (100GB+ for production)
- **OS:** Ubuntu 20.04 LTS or later / CentOS 7+ / macOS / Windows 10+

### Required Software:
- **Docker:** Version 20.10+
- **Docker Compose:** Version 2.0+
- **Git:** Latest version

---

## ⚡ Quick Setup (Development)

### Step 1: Install Docker

**For Ubuntu/Debian:**
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Add user to docker group
sudo usermod -aG docker $USER
newgrp docker
```

**For macOS:**
```bash
# Install Docker Desktop from: https://www.docker.com/products/docker-desktop
# Or using Homebrew:
brew install --cask docker
```

**For Windows:**
```bash
# Install Docker Desktop from: https://www.docker.com/products/docker-desktop
# Enable WSL2 integration
```

### Step 2: Clone and Setup

```bash
# Clone the repository
git clone <your-repository-url>
cd uball/backend-api

# Create environment file
cp .env.example .env

# Edit .env file (optional - defaults work for development)
nano .env
```

### Step 3: Start Application

```bash
# Start all services
docker-compose up -d

# Setup storage folders (one command)
docker-compose --profile setup run --rm storage-setup

# Create storage link
docker-compose exec app php artisan storage:link

# Set permissions
docker-compose exec app chown -R www-data:www-data storage/

# Create admin user
docker-compose exec app php artisan db:seed
```

### Step 4: Verify Installation

```bash
# Check all services are running
docker-compose ps

# Test the application
curl http://localhost
```

**🎉 Development setup complete!**

**Access URLs:**
- **Main Application:** http://localhost
- **Alternative:** http://localhost:8000
- **Admin Login:** admin@uball.com / password

---

## 🏭 Production Deployment

### Step 1: Server Preparation

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y git nginx certbot python3-certbot-nginx ufw

# Configure firewall
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable

# Install Docker (same as development)
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER
```

### Step 2: Domain Setup

```bash
# Point your domain to server IP
# Example: api.yourdomain.com -> YOUR_SERVER_IP
```

### Step 3: Application Deployment

```bash
# Create application directory
sudo mkdir -p /var/www/uball
sudo chown $USER:$USER /var/www/uball

# Clone repository
cd /var/www/uball
git clone <your-repository-url> .
cd backend-api

# Create production environment
cp .env.prod.example .env.prod

# Edit production settings
nano .env.prod
```

**Required .env.prod settings:**
```env
APP_NAME=UBall
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_DATABASE=uball_prod
DB_USERNAME=uball_user
DB_PASSWORD=YOUR_SECURE_PASSWORD
DB_ROOT_PASSWORD=YOUR_SECURE_ROOT_PASSWORD

# Generate new app key
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
```

### Step 4: Deploy with Docker

```bash
# Start production services
docker-compose -f docker-compose.prod.yml up -d

# Setup storage
docker-compose -f docker-compose.prod.yml --profile setup run --rm storage-setup
docker-compose -f docker-compose.prod.yml exec app php artisan storage:link
docker-compose -f docker-compose.prod.yml exec app chown -R www-data:www-data storage/

# Run migrations and seed
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker-compose -f docker-compose.prod.yml exec app php artisan db:seed --force

# Optimize for production
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
```

### Step 5: SSL Setup

```bash
# Configure Nginx reverse proxy
sudo nano /etc/nginx/sites-available/uball
```

**Nginx Configuration:**
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

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
```

```bash
# Enable site and get SSL
sudo ln -s /etc/nginx/sites-available/uball /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Get SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

---

## 👤 Admin Access

### Default Admin Account:
- **Email:** `admin@uball.com`
- **Password:** `password`
- **Role:** Administrator
- **Permissions:** Full system access

### Staff Account:
- **Email:** `staff@uball.com`
- **Password:** `password`
- **Role:** Staff

**⚠️ Important:** Change default passwords after first login!

---

## 🔧 Management Commands

### Daily Operations:

```bash
# Check service status
docker-compose ps

# View logs
docker-compose logs -f app

# Restart services
docker-compose restart

# Update application
git pull origin main
docker-compose up -d --build

# Backup database
docker-compose exec db mysqldump -u root -p uball > backup_$(date +%Y%m%d).sql
```

### Monitoring:

```bash
# Check disk space
df -h

# Check memory usage
free -h

# Check Docker resources
docker system df
```

---

## 🚨 Troubleshooting

### Common Issues:

#### 1. **Port Already in Use**
```bash
# Check what's using the port
sudo lsof -i :80
sudo lsof -i :3306

# Stop conflicting services
sudo systemctl stop apache2  # If Apache is running
sudo systemctl stop mysql    # If MySQL is running locally
```

#### 2. **Permission Denied**
```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage/
docker-compose exec app chmod -R 775 storage/
```

#### 3. **Database Connection Failed**
```bash
# Check database container
docker-compose logs db

# Restart database
docker-compose restart db

# Wait for database to be ready
sleep 30
```

#### 4. **File Upload Issues**
```bash
# Check nginx configuration
docker-compose exec nginx nginx -t

# Verify upload limits
docker-compose exec app php -i | grep upload_max_filesize
```

#### 5. **SSL Certificate Issues**
```bash
# Check certificate status
sudo certbot certificates

# Renew certificate
sudo certbot renew

# Check nginx configuration
sudo nginx -t
```

### Emergency Recovery:

```bash
# Complete restart
docker-compose down
docker-compose up -d

# Reset database (⚠️ This will delete all data!)
docker-compose down
docker volume rm backend-api_dbdata
docker-compose up -d
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed --force
```

---

## 📊 System Features

### File Upload Support:
- **Videos:** Up to 500MB (MP4, MOV, AVI, WebM)
- **Images:** Up to 5MB (JPG, PNG, GIF, WebP)
- **Thumbnails:** Auto-generated
- **Storage:** Local filesystem (production can use AWS S3)

### Database Features:
- **Users:** Registration, authentication, profiles
- **Clips:** Video management with metadata
- **Games:** Game scheduling and management
- **Social:** Followers, likes, comments, messaging
- **Analytics:** Player statistics and audit logs

### Performance Features:
- **Caching:** Redis-based caching system
- **Sessions:** Redis session storage
- **Queues:** Background job processing
- **Optimization:** Production-ready caching

---

## 📞 Support

### Getting Help:

1. **Check Logs:**
   ```bash
   docker-compose logs app
   docker-compose logs db
   docker-compose logs nginx
   ```

2. **System Status:**
   ```bash
   docker-compose ps
   docker system df
   ```

3. **Application Status:**
   ```bash
   docker-compose exec app php artisan about
   ```

### Performance Monitoring:

```bash
# Check application performance
docker stats

# Monitor database
docker-compose exec db mysql -u root -p -e "SHOW PROCESSLIST;"

# Check Redis
docker-compose exec redis redis-cli info
```

---

## 📝 Deployment Checklist

### Pre-Deployment:
- [ ] Server meets minimum requirements
- [ ] Docker and Docker Compose installed
- [ ] Domain DNS configured
- [ ] Firewall configured
- [ ] SSL certificate ready

### Deployment:
- [ ] Repository cloned
- [ ] Environment configured
- [ ] Docker services started
- [ ] Database migrated
- [ ] Admin user created
- [ ] Storage configured
- [ ] SSL certificate installed

### Post-Deployment:
- [ ] Application accessible
- [ ] Admin login working
- [ ] File uploads working
- [ ] Database connections stable
- [ ] Monitoring configured
- [ ] Backups scheduled

---

## 🔄 Updates and Maintenance

### Regular Updates:
```bash
# Weekly maintenance
cd /var/www/uball/backend-api
git pull origin main
docker-compose up -d --build
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan config:cache
```

### Backup Strategy:
```bash
# Daily database backup
docker-compose exec db mysqldump -u root -p uball > daily_backup_$(date +%Y%m%d).sql

# Weekly full backup
tar -czf weekly_backup_$(date +%Y%m%d).tar.gz \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='storage/logs' \
    /var/www/uball/
```

---

**🎉 Congratulations! Your UBall backend API is now ready for production use.**

**For technical support or questions, please contact your development team.**
