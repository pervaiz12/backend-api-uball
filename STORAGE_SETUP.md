# UBall Storage Setup Guide - Images aur Clips ke liye Complete Setup

Yeh document aapko UBall backend API mein images aur clips ke liye complete storage setup karne ke liye hai.

## 🚀 Quick Start (Single Command)

### Development Environment:
```bash
docker-compose --profile setup run --rm storage-setup
docker-compose exec app php artisan storage:link
```

### Production Environment:
```bash
docker-compose -f docker-compose.prod.yml --profile setup run --rm storage-setup
docker-compose -f docker-compose.prod.yml exec app php artisan storage:link
```

**Bas yeh 2 commands run karo aur sara storage setup ho jayega!** ✅

## Table of Contents
- [Storage Structure](#storage-structure)
- [Folder Creation Commands](#folder-creation-commands)
- [Permissions Setup](#permissions-setup)
- [Laravel Configuration](#laravel-configuration)
- [Docker Setup](#docker-setup)
- [File Upload Testing](#file-upload-testing)
- [Production Deployment](#production-deployment)

## Storage Structure

Yeh hai aapka complete storage structure:

```
storage/
├── app/
│   ├── public/
│   │   ├── clips/           # Video clips storage
│   │   │   ├── thumbnails/  # Video thumbnails
│   │   │   └── processed/   # Processed videos
│   │   ├── images/          # Profile images
│   │   │   ├── profiles/    # User profile photos
│   │   │   ├── covers/      # Cover images
│   │   │   └── thumbnails/  # Image thumbnails
│   │   └── temp/            # Temporary uploads
│   └── private/
│       ├── clips/           # Private/processing clips
│       └── backups/         # File backups
└── logs/                    # Application logs
```

## Folder Creation Commands

### Local Development ke liye:

```bash
# Backend API directory mein jao
cd /Users/macbookpro/code/uball/backend-api

# Storage folders create karo
mkdir -p storage/app/public/clips
mkdir -p storage/app/public/clips/thumbnails
mkdir -p storage/app/public/clips/processed
mkdir -p storage/app/public/images
mkdir -p storage/app/public/images/profiles
mkdir -p storage/app/public/images/covers
mkdir -p storage/app/public/images/thumbnails
mkdir -p storage/app/public/temp
mkdir -p storage/app/private/clips
mkdir -p storage/app/private/backups

# Public folder mein symbolic link create karo
php artisan storage:link

# Permissions set karo
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

### Docker Environment ke liye (One Command Setup):

```bash
# 🚀 SINGLE COMMAND - Sara storage setup ek hi command mein!
docker-compose --profile setup run --rm storage-setup

# Storage link create karo (bas ek baar)
docker-compose exec app php artisan storage:link

# Final permissions (bas ek baar)
docker-compose exec app chown -R www-data:www-data storage/
```

### Manual Docker Setup (agar chahiye):

```bash
# Docker container mein folders create karo
docker-compose exec app mkdir -p storage/app/public/clips
docker-compose exec app mkdir -p storage/app/public/clips/thumbnails
docker-compose exec app mkdir -p storage/app/public/clips/processed
docker-compose exec app mkdir -p storage/app/public/images
docker-compose exec app mkdir -p storage/app/public/images/profiles
docker-compose exec app mkdir -p storage/app/public/images/covers
docker-compose exec app mkdir -p storage/app/public/images/thumbnails
docker-compose exec app mkdir -p storage/app/public/temp
docker-compose exec app mkdir -p storage/app/private/clips
docker-compose exec app mkdir -p storage/app/private/backups

# Storage link create karo
docker-compose exec app php artisan storage:link

# Permissions set karo
docker-compose exec app chown -R www-data:www-data storage/
docker-compose exec app chmod -R 775 storage/
```

## Permissions Setup

### Local Development:
```bash
# Owner aur group permissions
sudo chown -R $USER:www-data storage/
sudo chown -R $USER:www-data bootstrap/cache/

# Directory permissions
find storage/ -type d -exec chmod 775 {} \;
find bootstrap/cache/ -type d -exec chmod 775 {} \;

# File permissions
find storage/ -type f -exec chmod 664 {} \;
find bootstrap/cache/ -type f -exec chmod 664 {} \;
```

### Docker Environment:
```bash
# Docker container mein permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
docker-compose exec app chmod -R 775 /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/bootstrap/cache
```

## Laravel Configuration

### 1. Filesystem Configuration Update

`config/filesystems.php` mein yeh configuration add karo:

```php
'disks' => [
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app/private'),
        'serve' => true,
        'throw' => false,
    ],

    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
        'throw' => false,
    ],

    // Custom disks for different file types
    'clips' => [
        'driver' => 'local',
        'root' => storage_path('app/public/clips'),
        'url' => env('APP_URL').'/storage/clips',
        'visibility' => 'public',
    ],

    'images' => [
        'driver' => 'local',
        'root' => storage_path('app/public/images'),
        'url' => env('APP_URL').'/storage/images',
        'visibility' => 'public',
    ],

    'temp' => [
        'driver' => 'local',
        'root' => storage_path('app/public/temp'),
        'url' => env('APP_URL').'/storage/temp',
        'visibility' => 'public',
    ],
],
```

### 2. Environment Variables (.env file mein add karo)

```env
# File Storage Configuration
FILESYSTEM_DISK=public

# File Upload Limits
UPLOAD_MAX_FILESIZE=512M
POST_MAX_SIZE=512M

# Storage URLs
STORAGE_URL=${APP_URL}/storage
CLIPS_URL=${APP_URL}/storage/clips
IMAGES_URL=${APP_URL}/storage/images
```

## Docker Setup

### 1. Dockerfile mein storage setup add karo:

```dockerfile
# Storage directories create karo
RUN mkdir -p storage/app/public/clips/thumbnails \
    storage/app/public/clips/processed \
    storage/app/public/images/profiles \
    storage/app/public/images/covers \
    storage/app/public/images/thumbnails \
    storage/app/public/temp \
    storage/app/private/clips \
    storage/app/private/backups

# Storage link create karo
RUN php artisan storage:link

# Permissions set karo
RUN chown -R www-data:www-data storage/ bootstrap/cache/
RUN chmod -R 775 storage/ bootstrap/cache/
```

### 2. Docker Compose mein volumes add karo:

```yaml
services:
  app:
    volumes:
      - ./storage:/var/www/html/storage
      - ./public/storage:/var/www/html/public/storage
    environment:
      - UPLOAD_MAX_FILESIZE=512M
      - POST_MAX_SIZE=512M
```

### 3. Nginx configuration update (docker/nginx/conf.d/app.conf):

```nginx
server {
    listen 80;
    index index.php index.html;
    root /var/www/html/public;
    client_max_body_size 512M;

    # Storage files ke liye
    location /storage/ {
        alias /var/www/html/storage/app/public/;
        expires 30d;
        add_header Cache-Control "public, no-transform";
        
        # Video files ke liye special handling
        location ~* \.(mp4|mov|avi|webm)$ {
            expires 7d;
            add_header Cache-Control "public, no-transform";
            add_header Accept-Ranges bytes;
        }
        
        # Image files ke liye
        location ~* \.(jpg|jpeg|png|gif|webp|svg)$ {
            expires 30d;
            add_header Cache-Control "public, no-transform";
        }
    }

    # PHP files processing
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        
        # File upload ke liye
        fastcgi_param PHP_VALUE "upload_max_filesize = 512M \n post_max_size=512M \n max_execution_time=300";
        fastcgi_read_timeout 300;
    }
}
```

## File Upload Testing

### 1. Test Script banao (`test_storage.php`):

```php
<?php
// Test storage setup
use Illuminate\Support\Facades\Storage;

// Test clip upload
$testClipPath = Storage::disk('clips')->put('test_clip.mp4', 'test content');
echo "Clip stored at: " . $testClipPath . "\n";

// Test image upload  
$testImagePath = Storage::disk('images')->put('profiles/test_image.jpg', 'test content');
echo "Image stored at: " . $testImagePath . "\n";

// Test URLs
echo "Clip URL: " . Storage::disk('clips')->url('test_clip.mp4') . "\n";
echo "Image URL: " . Storage::disk('images')->url('profiles/test_image.jpg') . "\n";

// Cleanup
Storage::disk('clips')->delete('test_clip.mp4');
Storage::disk('images')->delete('profiles/test_image.jpg');

echo "Storage test completed successfully!\n";
?>
```

### 2. Test command:

```bash
# Local testing
php artisan tinker
>>> Storage::disk('public')->put('test.txt', 'Hello World');
>>> Storage::disk('public')->url('test.txt');

# Docker testing
docker-compose exec app php artisan tinker
>>> Storage::disk('public')->put('test.txt', 'Hello World');
>>> Storage::disk('public')->url('test.txt');
```

## Production Deployment

### 1. Production server pe folders create karo:

```bash
# Production server pe SSH karo
ssh user@your-server.com

# Application directory mein jao
cd /var/www/uball/backend-api

# Production folders create karo
sudo mkdir -p storage/app/public/{clips/{thumbnails,processed},images/{profiles,covers,thumbnails},temp}
sudo mkdir -p storage/app/private/{clips,backups}

# Ownership set karo
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/

# Storage link create karo
sudo -u www-data php artisan storage:link
```

### 2. Production Docker deployment:

```bash
# 🚀 SINGLE COMMAND - Production storage setup
docker-compose -f docker-compose.prod.yml --profile setup run --rm storage-setup

# Storage link (ek baar)
docker-compose -f docker-compose.prod.yml exec app php artisan storage:link

# Final permissions (ek baar)
docker-compose -f docker-compose.prod.yml exec app chown -R www-data:www-data storage/
```

### Manual Production Setup (agar chahiye):

```bash
# Production deployment script mein add karo
#!/bin/bash

# Storage setup
docker-compose -f docker-compose.prod.yml exec app mkdir -p storage/app/public/clips/thumbnails
docker-compose -f docker-compose.prod.yml exec app mkdir -p storage/app/public/clips/processed
docker-compose -f docker-compose.prod.yml exec app mkdir -p storage/app/public/images/profiles
docker-compose -f docker-compose.prod.yml exec app mkdir -p storage/app/public/images/covers
docker-compose -f docker-compose.prod.yml exec app mkdir -p storage/app/public/images/thumbnails
docker-compose -f docker-compose.prod.yml exec app mkdir -p storage/app/public/temp
docker-compose -f docker-compose.prod.yml exec app mkdir -p storage/app/private/clips
docker-compose -f docker-compose.prod.yml exec app mkdir -p storage/app/private/backups

# Storage link
docker-compose -f docker-compose.prod.yml exec app php artisan storage:link

# Permissions
docker-compose -f docker-compose.prod.yml exec app chown -R www-data:www-data storage/
docker-compose -f docker-compose.prod.yml exec app chmod -R 775 storage/
```

## Complete Setup Script

Yeh script run karo complete setup ke liye:

```bash
#!/bin/bash
# complete_storage_setup.sh

echo "🚀 UBall Storage Setup Starting..."

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Please run this script from the backend-api directory"
    exit 1
fi

# Create storage directories
echo "📁 Creating storage directories..."
mkdir -p storage/app/public/clips/{thumbnails,processed}
mkdir -p storage/app/public/images/{profiles,covers,thumbnails}
mkdir -p storage/app/public/temp
mkdir -p storage/app/private/{clips,backups}

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link

# Test storage
echo "🧪 Testing storage..."
php artisan tinker --execute="
    \$test = Storage::disk('public')->put('test.txt', 'Storage working!');
    echo 'Test file created: ' . \$test . PHP_EOL;
    echo 'Test URL: ' . Storage::disk('public')->url('test.txt') . PHP_EOL;
    Storage::disk('public')->delete('test.txt');
    echo 'Test completed successfully!' . PHP_EOL;
"

echo "✅ Storage setup completed successfully!"
echo "📂 Your storage structure is ready for images and clips!"
```

## Usage Examples

### Controller mein file upload:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    public function uploadClip(Request $request)
    {
        $request->validate([
            'clip' => 'required|file|mimes:mp4,mov,avi,webm|max:512000', // 500MB
        ]);

        // Clip upload
        $clipPath = $request->file('clip')->store('clips', 'public');
        
        // URL generate karo
        $clipUrl = Storage::disk('public')->url($clipPath);
        
        return response()->json([
            'success' => true,
            'path' => $clipPath,
            'url' => $clipUrl
        ]);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB
        ]);

        // Image upload
        $imagePath = $request->file('image')->store('images/profiles', 'public');
        
        // URL generate karo
        $imageUrl = Storage::disk('public')->url($imagePath);
        
        return response()->json([
            'success' => true,
            'path' => $imagePath,
            'url' => $imageUrl
        ]);
    }
}
```

## Troubleshooting

### Common Issues:

1. **Permission denied error:**
   ```bash
   sudo chown -R www-data:www-data storage/
   sudo chmod -R 775 storage/
   ```

2. **Storage link not working:**
   ```bash
   rm public/storage
   php artisan storage:link
   ```

3. **File upload size error:**
   - Check `php.ini` settings
   - Update nginx `client_max_body_size`
   - Check Laravel validation rules

4. **Docker permission issues:**
   ```bash
   docker-compose exec app chown -R www-data:www-data storage/
   ```

---

**Complete setup ke liye yeh script run karo:**

```bash
chmod +x complete_storage_setup.sh
./complete_storage_setup.sh
```

Yeh document follow karne ke baad aapka complete storage system ready ho jayega images aur clips ke liye! 🚀
