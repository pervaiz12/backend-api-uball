# Redis "Class Not Found" Error - Fix Guide

## Problem
Laravel was configured to use Redis but the Redis PHP extension is not installed, causing the error:
```
Class "Redis" not found
```

## Solution
This application has been reconfigured to work **without Redis** using database and file-based alternatives.

## Changes Made

### 1. Cache Configuration (`config/cache.php`)
- **Before**: `'default' => env('CACHE_STORE', 'database')`
- **After**: `'default' => env('CACHE_STORE', 'file')`
- **Redis store**: Commented out to prevent errors

### 2. Database Configuration (`config/database.php`)
- **Redis section**: Completely commented out
- **Result**: No Redis dependency in database connections

### 3. Queue Configuration (`config/queue.php`)
- **Default**: Already set to `database` (no changes needed)
- **Redis queue**: Commented out to prevent errors

### 4. Session Configuration (`config/session.php`)
- **Default**: Already set to `database` (no changes needed)

## Environment Configuration

### Option 1: Update Your .env File
Add/update these lines in your `.env` file:
```env
# Cache (File-based)
CACHE_STORE=file

# Session (Database)
SESSION_DRIVER=database

# Queue (Database)
QUEUE_CONNECTION=database

# DO NOT include Redis configuration
```

### Option 2: Use Provided Template
Copy the provided `.env.no-redis` file to `.env`:
```bash
cp .env.no-redis .env
```

## Required Database Tables

The following tables are needed for database-based storage:

1. **Sessions table**: `php artisan make:session-table`
2. **Cache table**: `php artisan make:cache-table`
3. **Jobs table**: `php artisan make:queue-table`
4. **Failed jobs table**: `php artisan make:queue-failed-table`

Run migrations:
```bash
php artisan migrate
```

## Testing the Fix

### Method 1: Run Test Script
```bash
php test-no-redis.php
```

### Method 2: Start Laravel Server
```bash
php artisan serve
```

### Method 3: Check Configuration
```bash
php artisan config:show cache
php artisan config:show session
php artisan config:show queue
```

## Storage Locations

- **File Cache**: `storage/framework/cache/data/`
- **Sessions**: Database table `sessions`
- **Queue Jobs**: Database table `jobs`
- **Failed Jobs**: Database table `failed_jobs`

## Performance Notes

- **File Cache**: Good for development, acceptable for small production apps
- **Database Sessions**: Reliable and scalable
- **Database Queues**: Perfect for most applications

## If You Need Redis Later

To re-enable Redis in the future:

1. Install Redis server
2. Install PHP Redis extension: `pecl install redis`
3. Uncomment Redis configurations in config files
4. Update `.env` with Redis settings

## Troubleshooting

### Error: "Driver [redis] not supported"
- Ensure `CACHE_STORE=file` in `.env`
- Clear config cache: `php artisan config:clear`

### Error: "Connection refused"
- Check database connection in `.env`
- Ensure MySQL/database server is running

### Error: "Table doesn't exist"
- Run: `php artisan migrate`
- Check database permissions

## Summary

✅ **Redis dependency removed**  
✅ **File-based caching enabled**  
✅ **Database sessions configured**  
✅ **Database queues configured**  
✅ **All Redis references commented out**  

Your Laravel application now works completely without Redis while maintaining all functionality through database and file storage alternatives.
