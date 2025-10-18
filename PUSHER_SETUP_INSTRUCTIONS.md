# Pusher Real-Time Notifications Setup Instructions

## Backend Setup (Laravel)

### 1. Add Environment Variables
Add these lines to your `/Users/macbookpro/code/uball/backend-api/.env` file:

```env
# Broadcasting Configuration
BROADCAST_DRIVER=pusher

# Pusher Configuration
PUSHER_APP_ID=2012586
PUSHER_APP_KEY=41eece70cce8753137d0
PUSHER_APP_SECRET=f3d70d5a2024a60e7019
PUSHER_APP_CLUSTER=ap2
PUSHER_SCHEME=https
PUSHER_HOST=
PUSHER_PORT=443
```

### 2. Clear Configuration Cache
Run this command to clear Laravel's configuration cache:

```bash
cd /Users/macbookpro/code/uball/backend-api
php artisan config:clear
php artisan config:cache
```

### 3. Test Broadcasting Routes
The broadcasting auth endpoint is available at:
- `POST http://127.0.0.1:8001/api/broadcasting/auth`

## Frontend Setup (React)

The frontend is already configured with:
- Pusher JS client (`pusher-js` and `laravel-echo` packages installed)
- NotificationProvider context for managing real-time notifications
- NotificationBanner component for displaying notifications
- Proper authentication integration

## How It Works

### When Admin Uploads a Clip:
1. **Admin uploads clip** via dashboard → `ClipController::upload()`
2. **If player is tagged** → System finds all followers of that player
3. **If clip is auto-approved** (admin upload) → `NewClipUploaded` event is broadcast
4. **Real-time notification** sent to all followers via Pusher
5. **Frontend receives event** → NotificationBanner displays the notification

### Event Flow:
```
Admin Upload → Laravel Event → Pusher → Frontend Listener → Notification Banner
```

### Channels Used:
- **Private Channel**: `notifications.{userId}` for each follower
- **Event Name**: `NewClipUploaded`
- **Authentication**: Laravel Sanctum tokens

## Testing

### 1. Start Backend Server:
```bash
cd /Users/macbookpro/code/uball/backend-api
php artisan serve --host=127.0.0.1 --port=8001
```

### 2. Start Frontend Server:
```bash
cd /Users/macbookpro/code/uball/uball-main
npm run dev
```

### 3. Test Notification Flow:
1. Login as a regular user (follower)
2. Have admin upload a clip for a player that the user follows
3. User should see real-time notification banner appear instantly

## Debugging

### Check Pusher Connection:
- Open browser console in frontend
- Look for "Pusher connected successfully" message
- Check for any connection errors

### Check Laravel Logs:
```bash
tail -f /Users/macbookpro/code/uball/backend-api/storage/logs/laravel.log
```

### Verify Broadcasting:
- Check that `BROADCAST_DRIVER=pusher` in .env
- Ensure Pusher credentials are correct
- Verify user authentication is working

## Files Created/Modified:

### Backend:
- `config/broadcasting.php` - Pusher configuration
- `app/Events/NewClipUploaded.php` - Broadcasting event
- `app/Http/Controllers/ClipController.php` - Updated to broadcast events
- `routes/channels.php` - Private channel authorization

### Frontend:
- `src/lib/pusher.ts` - Pusher client configuration
- `src/lib/notification-context.tsx` - Real-time notification management
- `src/components/NotificationBanner.tsx` - Notification display component
- `src/App.tsx` - Updated to include NotificationProvider

The system is now ready for real-time socket-based notifications when admins upload clips for players!
