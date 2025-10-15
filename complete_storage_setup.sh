#!/bin/bash
# UBall Complete Storage Setup Script
# Yeh script images aur clips ke liye complete storage setup karega

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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

log_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

# Check if we're in the right directory
check_directory() {
    if [ ! -f "composer.json" ]; then
        log_error "Please run this script from the backend-api directory"
        log_error "Current directory: $(pwd)"
        exit 1
    fi
    log_info "✅ Correct directory confirmed"
}

# Create storage directories
create_directories() {
    log_step "📁 Creating storage directories..."
    
    # Public directories for web-accessible files
    mkdir -p storage/app/public/clips
    mkdir -p storage/app/public/clips/thumbnails
    mkdir -p storage/app/public/clips/processed
    mkdir -p storage/app/public/images
    mkdir -p storage/app/public/images/profiles
    mkdir -p storage/app/public/images/covers
    mkdir -p storage/app/public/images/thumbnails
    mkdir -p storage/app/public/temp
    
    # Private directories for processing/secure files
    mkdir -p storage/app/private/clips
    mkdir -p storage/app/private/backups
    
    log_info "✅ All storage directories created successfully"
}

# Set proper permissions
set_permissions() {
    log_step "🔐 Setting proper permissions..."
    
    # Set directory permissions
    chmod -R 775 storage/
    chmod -R 775 bootstrap/cache/
    
    # If running as root, set proper ownership
    if [ "$EUID" -eq 0 ]; then
        chown -R www-data:www-data storage/
        chown -R www-data:www-data bootstrap/cache/
        log_info "✅ Ownership set to www-data:www-data"
    else
        log_warn "⚠️  Not running as root, ownership not changed"
        log_warn "   You may need to run: sudo chown -R www-data:www-data storage/"
    fi
    
    log_info "✅ Permissions set successfully"
}

# Create storage symbolic link
create_storage_link() {
    log_step "🔗 Creating storage symbolic link..."
    
    # Remove existing link if it exists
    if [ -L "public/storage" ]; then
        rm public/storage
        log_info "Removed existing storage link"
    fi
    
    # Create new storage link
    php artisan storage:link
    
    if [ -L "public/storage" ]; then
        log_info "✅ Storage link created successfully"
    else
        log_error "❌ Failed to create storage link"
        exit 1
    fi
}

# Test storage functionality
test_storage() {
    log_step "🧪 Testing storage functionality..."
    
    # Test basic storage
    php artisan tinker --execute="
        try {
            \$testFile = 'test_' . time() . '.txt';
            \$result = Storage::disk('public')->put(\$testFile, 'UBall Storage Test - ' . date('Y-m-d H:i:s'));
            
            if (\$result) {
                \$url = Storage::disk('public')->url(\$testFile);
                echo '✅ Test file created: ' . \$testFile . PHP_EOL;
                echo '🔗 Test URL: ' . \$url . PHP_EOL;
                
                // Test different directories
                Storage::disk('public')->put('clips/test_clip.txt', 'Test clip file');
                Storage::disk('public')->put('images/test_image.txt', 'Test image file');
                
                echo '✅ Clips directory test: OK' . PHP_EOL;
                echo '✅ Images directory test: OK' . PHP_EOL;
                
                // Cleanup
                Storage::disk('public')->delete(\$testFile);
                Storage::disk('public')->delete('clips/test_clip.txt');
                Storage::disk('public')->delete('images/test_image.txt');
                
                echo '✅ Storage test completed successfully!' . PHP_EOL;
            } else {
                echo '❌ Storage test failed!' . PHP_EOL;
                exit(1);
            }
        } catch (Exception \$e) {
            echo '❌ Storage test error: ' . \$e->getMessage() . PHP_EOL;
            exit(1);
        }
    "
    
    if [ $? -eq 0 ]; then
        log_info "✅ Storage test passed"
    else
        log_error "❌ Storage test failed"
        exit 1
    fi
}

# Update filesystem configuration
update_config() {
    log_step "⚙️  Checking filesystem configuration..."
    
    if grep -q "'clips' =>" config/filesystems.php; then
        log_info "✅ Custom disk configurations already exist"
    else
        log_warn "⚠️  Custom disk configurations not found in config/filesystems.php"
        log_warn "   Please manually add the custom disk configurations as shown in STORAGE_SETUP.md"
    fi
}

# Create .gitkeep files to preserve directory structure
create_gitkeep() {
    log_step "📝 Creating .gitkeep files..."
    
    # Create .gitkeep files in empty directories
    touch storage/app/public/clips/.gitkeep
    touch storage/app/public/clips/thumbnails/.gitkeep
    touch storage/app/public/clips/processed/.gitkeep
    touch storage/app/public/images/.gitkeep
    touch storage/app/public/images/profiles/.gitkeep
    touch storage/app/public/images/covers/.gitkeep
    touch storage/app/public/images/thumbnails/.gitkeep
    touch storage/app/public/temp/.gitkeep
    touch storage/app/private/clips/.gitkeep
    touch storage/app/private/backups/.gitkeep
    
    log_info "✅ .gitkeep files created to preserve directory structure"
}

# Display storage structure
show_structure() {
    log_step "📂 Final storage structure:"
    
    echo ""
    echo "storage/"
    echo "├── app/"
    echo "│   ├── public/"
    echo "│   │   ├── clips/           # Video clips (up to 500MB)"
    echo "│   │   │   ├── thumbnails/  # Video thumbnails"
    echo "│   │   │   └── processed/   # Processed videos"
    echo "│   │   ├── images/          # Images (up to 5MB)"
    echo "│   │   │   ├── profiles/    # User profile photos"
    echo "│   │   │   ├── covers/      # Cover images"
    echo "│   │   │   └── thumbnails/  # Image thumbnails"
    echo "│   │   └── temp/            # Temporary uploads"
    echo "│   └── private/"
    echo "│       ├── clips/           # Private/processing clips"
    echo "│       └── backups/         # File backups"
    echo "└── logs/                    # Application logs"
    echo ""
    
    log_info "🔗 Public access via: ${APP_URL:-http://localhost}/storage/"
}

# Main execution
main() {
    echo ""
    echo "🚀 UBall Storage Setup Starting..."
    echo "=================================="
    echo ""
    
    check_directory
    create_directories
    set_permissions
    create_storage_link
    create_gitkeep
    update_config
    test_storage
    show_structure
    
    echo ""
    echo "=================================="
    log_info "🎉 Storage setup completed successfully!"
    echo ""
    log_info "📋 Next steps:"
    echo "   1. Check STORAGE_SETUP.md for configuration details"
    echo "   2. Update your .env file with storage settings"
    echo "   3. Test file uploads through your application"
    echo ""
    log_info "🐳 For Docker environment, run:"
    echo "   docker-compose exec app ./complete_storage_setup.sh"
    echo ""
}

# Run main function
main
