#!/bin/bash

# Exit on error
set -e

echo "🚀 Starting UBall Backend Deployment..."

# Check if .env.prod exists, if not create from example
if [ ! -f .env.prod ]; then
    echo "⚠️  .env.prod not found. Creating from .env.prod.example..."
    cp .env.prod.example .env.prod
    echo "⚠️  Please update .env.prod with your configuration and run this script again."
    exit 1
fi

# Load environment variables
echo "🔧 Loading environment variables..."
source .env.prod

# Build and start containers
echo "🐳 Building and starting Docker containers..."
docker-compose -f docker-compose.prod.yml up -d --build

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
docker-compose -f docker-compose.prod.yml exec app composer install --optimize-autoloader --no-dev

# Generate application key if not exists
if ! grep -q '^APP_KEY=base64:' .env.prod; then
    echo "🔑 Generating application key..."
    docker-compose -f docker-compose.prod.yml exec app php artisan key:generate
fi

# Run database migrations
echo "🔄 Running database migrations..."
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Clear and cache config
# echo "⚙️  Optimizing application..."
# docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
# docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
# docker-compose -f docker-compose.prod.yml exec app php artisan view:cache

echo "✅ Deployment completed successfully!"
echo "🌐 Your application should now be running at: ${APP_URL}"
