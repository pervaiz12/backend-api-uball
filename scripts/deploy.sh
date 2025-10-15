#!/bin/bash

# UBall Production Deployment Script
# Usage: ./scripts/deploy.sh [--fresh] [--migrate] [--backup]

set -e

# Configuration
APP_DIR="/var/www/uball/backend-api"
BACKUP_DIR="/var/backups/uball"
COMPOSE_FILES="-f docker-compose.prod.yml -f docker-compose.prod.override.yml"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_prerequisites() {
    log_info "Checking prerequisites..."
    
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed"
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose is not installed"
        exit 1
    fi
    
    if [ ! -f ".env.prod" ]; then
        log_error ".env.prod file not found. Please create it from .env.prod.example"
        exit 1
    fi
    
    log_info "Prerequisites check passed"
}

backup_database() {
    if [ "$BACKUP" = true ]; then
        log_info "Creating database backup..."
        mkdir -p $BACKUP_DIR
        DATE=$(date +%Y%m%d_%H%M%S)
        
        docker-compose $COMPOSE_FILES exec -T db mysqldump \
            -u root -p${DB_ROOT_PASSWORD} ${DB_DATABASE} \
            > $BACKUP_DIR/pre_deploy_$DATE.sql
        
        log_info "Database backup created: pre_deploy_$DATE.sql"
    fi
}

deploy_application() {
    log_info "Starting deployment..."
    
    # Pull latest code
    if [ -d ".git" ]; then
        log_info "Pulling latest code from repository..."
        git pull origin main
    fi
    
    # Build and start services
    log_info "Building and starting Docker containers..."
    docker-compose $COMPOSE_FILES up --build -d
    
    # Wait for services to be ready
    log_info "Waiting for services to start..."
    sleep 30
    
    # Run migrations if requested
    if [ "$MIGRATE" = true ]; then
        log_info "Running database migrations..."
        docker-compose $COMPOSE_FILES exec app php artisan migrate --force
    fi
    
    # Clear and cache configuration
    log_info "Optimizing application..."
    docker-compose $COMPOSE_FILES exec app php artisan config:cache
    docker-compose $COMPOSE_FILES exec app php artisan route:cache
    docker-compose $COMPOSE_FILES exec app php artisan view:cache
    
    # Set proper permissions
    log_info "Setting file permissions..."
    docker-compose $COMPOSE_FILES exec app chown -R www-data:www-data /var/www/html/storage
    docker-compose $COMPOSE_FILES exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
    
    log_info "Deployment completed successfully!"
}

health_check() {
    log_info "Performing health check..."
    
    # Check if containers are running
    if ! docker-compose $COMPOSE_FILES ps | grep -q "Up"; then
        log_error "Some containers are not running properly"
        docker-compose $COMPOSE_FILES ps
        exit 1
    fi
    
    # Test database connection
    if ! docker-compose $COMPOSE_FILES exec app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database OK';" &> /dev/null; then
        log_warn "Database connection test failed"
    else
        log_info "Database connection: OK"
    fi
    
    # Test Redis connection
    if ! docker-compose $COMPOSE_FILES exec redis redis-cli ping | grep -q "PONG"; then
        log_warn "Redis connection test failed"
    else
        log_info "Redis connection: OK"
    fi
    
    log_info "Health check completed"
}

rollback() {
    log_warn "Rolling back to previous version..."
    
    # Stop current containers
    docker-compose $COMPOSE_FILES down
    
    # Restore from backup if available
    if [ -n "$ROLLBACK_BACKUP" ] && [ -f "$BACKUP_DIR/$ROLLBACK_BACKUP" ]; then
        log_info "Restoring database from backup: $ROLLBACK_BACKUP"
        docker-compose $COMPOSE_FILES up -d db
        sleep 10
        docker-compose $COMPOSE_FILES exec -T db mysql -u root -p${DB_ROOT_PASSWORD} ${DB_DATABASE} < $BACKUP_DIR/$ROLLBACK_BACKUP
    fi
    
    # Start services
    docker-compose $COMPOSE_FILES up -d
    
    log_info "Rollback completed"
}

# Parse command line arguments
FRESH=false
MIGRATE=false
BACKUP=false
ROLLBACK_BACKUP=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --fresh)
            FRESH=true
            shift
            ;;
        --migrate)
            MIGRATE=true
            shift
            ;;
        --backup)
            BACKUP=true
            shift
            ;;
        --rollback)
            ROLLBACK_BACKUP="$2"
            shift 2
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo "Options:"
            echo "  --fresh     Fresh deployment (rebuild everything)"
            echo "  --migrate   Run database migrations"
            echo "  --backup    Create database backup before deployment"
            echo "  --rollback  Rollback to specified backup file"
            echo "  --help      Show this help message"
            exit 0
            ;;
        *)
            log_error "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Main execution
cd $APP_DIR

# Load environment variables
if [ -f ".env.prod" ]; then
    export $(grep -v '^#' .env.prod | xargs)
fi

# Handle rollback
if [ -n "$ROLLBACK_BACKUP" ]; then
    rollback
    exit 0
fi

# Normal deployment flow
check_prerequisites
backup_database
deploy_application
health_check

log_info "🚀 UBall backend API deployment completed successfully!"
log_info "Application is available at: $APP_URL"
