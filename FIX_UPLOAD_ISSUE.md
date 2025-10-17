# 🔧 Fix Video Upload Issue

## Problem Identified

Your PHP configuration files (`php.ini` and `.user.ini`) are set to 512M, but PHP runtime is only reading:
- `upload_max_filesize: 2M` ❌ (should be 512M)
- `post_max_size: 8M` ❌ (should be 512M)

This prevents video files larger than 2MB from uploading.

## Solution

### Option 1: Update System PHP Configuration (Recommended)

1. **Find your PHP configuration file:**
```bash
php --ini | grep "Loaded Configuration File"
```

2. **Edit the php.ini file** (replace 8.2 with your PHP version):
```bash
sudo nano /etc/php/8.2/fpm/php.ini
# or for Apache:
sudo nano /etc/php/8.2/apache2/php.ini
# or for CLI:
sudo nano /etc/php/8.2/cli/php.ini
```

3. **Update these values:**
```ini
upload_max_filesize = 512M
post_max_size = 512M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
```

4. **Restart your web server:**
```bash
# For PHP-FPM:
sudo systemctl restart php8.2-fpm

# For Apache:
sudo systemctl restart apache2

# For Nginx:
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

### Option 2: Use .htaccess (Apache only)

Create/edit `.htaccess` in your `public/` directory:
```apache
php_value upload_max_filesize 512M
php_value post_max_size 512M
php_value memory_limit 256M
php_value max_execution_time 300
php_value max_input_time 300
```

### Option 3: Runtime Configuration (Already in ClipController)

The `ClipController.php` already attempts runtime configuration:
```php
ini_set('upload_max_filesize', '512M');
ini_set('post_max_size', '512M');
```

However, these settings cannot be changed at runtime if PHP is in safe mode or if they're marked as `PHP_INI_SYSTEM`.

## Verification

After applying fixes, verify with:
```bash
php -r "echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL; echo 'post_max_size: ' . ini_get('post_max_size') . PHP_EOL;"
```

Should output:
```
upload_max_filesize: 512M
post_max_size: 512M
```

## Additional Checks

### 1. Storage Directories (✅ Already Fixed)
```bash
mkdir -p storage/app/public/clips storage/app/public/thumbnails
chmod -R 775 storage/app/public
php artisan storage:link
```

### 2. Database Tables (✅ Already Verified)
All required tables exist with proper columns.

### 3. Test Upload
Use the test script to verify uploads work:
```bash
php test_upload_api.php
```

## Common Issues

### Issue: "The video failed to upload"
**Cause:** File size exceeds PHP limits  
**Fix:** Apply Option 1 above

### Issue: "No clips found in database"
**Cause:** Uploads are failing before reaching database  
**Fix:** Check Laravel logs: `tail -f storage/logs/laravel.log`

### Issue: "Permission denied"
**Cause:** Storage directory not writable  
**Fix:** `chmod -R 775 storage/`

### Issue: Nginx 413 error
**Cause:** Nginx has its own upload limit  
**Fix:** Add to nginx config:
```nginx
client_max_body_size 512M;
```

## Testing Checklist

- [ ] PHP configuration updated
- [ ] Web server restarted
- [ ] `php -r` command shows 512M
- [ ] Storage directories exist and writable
- [ ] Storage symlink exists
- [ ] Test upload with small file (< 2MB)
- [ ] Test upload with large file (> 2MB)
- [ ] Check `storage/logs/laravel.log` for errors

## Next Steps

1. Apply Option 1 (system PHP configuration)
2. Restart web server
3. Run verification command
4. Test upload from frontend
5. Check Laravel logs if issues persist
