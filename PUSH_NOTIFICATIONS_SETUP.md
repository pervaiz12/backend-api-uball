# Push Notifications Setup Guide

## Overview
This guide explains how to set up and use the push notification system for UBall. When an admin uploads a clip of a player, all followers of that player will receive a push notification.

---

## 🚀 Features Implemented

### **Backend Components**

1. **Database Migration**
   - Added `fcm_token` and `fcm_token_updated_at` columns to `users` table
   - Location: `database/migrations/2025_10_18_000001_add_fcm_token_to_users_table.php`

2. **FCM Service**
   - Centralized service for sending push notifications via Firebase Cloud Messaging
   - Location: `app/Services/FcmService.php`
   - Methods:
     - `sendToDevice($token, $notification, $data)` - Send to single device
     - `sendToDevices($tokens, $notification, $data)` - Send to multiple devices
     - `sendToTopic($topic, $notification, $data)` - Send to a topic

3. **Notification Channel**
   - Custom FCM channel for Laravel notifications
   - Location: `app/Channels/FcmChannel.php`

4. **Notification Class**
   - `NewClipNotification` - Sent when a clip is uploaded/approved
   - Location: `app/Notifications/NewClipNotification.php`
   - Channels: `database`, `broadcast`, `fcm`

5. **API Endpoints**
   - `POST /api/me/fcm-token` - Register/update FCM token
   - `DELETE /api/me/fcm-token` - Remove FCM token (on logout)

6. **Updated Controllers**
   - `ClipController@upload` - Sends notification when admin uploads clip
   - `ClipController@update` - Sends notification when clip is approved
   - `NotificationController` - Added FCM token management

---

## 📋 Prerequisites

### **1. Firebase Project Setup**

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Create a new project or select existing project
3. Navigate to **Project Settings** → **Cloud Messaging**
4. Copy the **Server Key** (also called Legacy Server Key)

### **2. Environment Configuration**

Add the following to your `.env` file:

```env
# Firebase Cloud Messaging
FCM_SERVER_KEY=your_firebase_server_key_here
```

### **3. Run Migration**

Execute the database migration to add FCM token columns:

```bash
php artisan migrate
```

---

## 🔧 How It Works

### **Flow Diagram**

```
Admin Uploads Clip
       ↓
Clip Created (Auto-approved for admins)
       ↓
System Checks: Does clip have a player tagged?
       ↓ YES
Get Player's Followers
       ↓
For Each Follower:
    - Store notification in database
    - Broadcast notification (WebSocket)
    - Send FCM push notification (if user has token)
       ↓
User's Device Receives Push Notification
```

### **Trigger Scenarios**

1. **Admin Uploads Clip with Player Tag**
   - Clip status is automatically set to `approved`
   - Notification sent immediately to all followers

2. **Clip Status Changes to Approved**
   - When an admin approves a pending clip
   - Notification sent to all followers of the tagged player

### **Notification Conditions**

✅ Notification is sent when:
- Clip has a `player_id` (player is tagged)
- Clip status is `approved`
- Player has followers
- Uploader is admin (auto-approved) OR clip status changes to approved

❌ Notification is NOT sent when:
- No player is tagged in the clip
- Clip is pending or rejected
- Player has no followers

---

## 📱 Frontend Integration

### **1. Register FCM Token (React Native/Flutter)**

When a user logs in, register their device token:

```javascript
// Example: React Native with Firebase
import messaging from '@react-native-firebase/messaging';
import axios from 'axios';

async function registerFcmToken() {
  try {
    // Request permission
    const authStatus = await messaging().requestPermission();
    const enabled = 
      authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
      authStatus === messaging.AuthorizationStatus.PROVISIONAL;

    if (enabled) {
      // Get FCM token
      const fcmToken = await messaging().getToken();
      
      // Register with backend
      await axios.post('/api/me/fcm-token', {
        fcm_token: fcmToken
      }, {
        headers: {
          'Authorization': `Bearer ${userToken}`
        }
      });
      
      console.log('FCM token registered successfully');
    }
  } catch (error) {
    console.error('Failed to register FCM token:', error);
  }
}
```

### **2. Handle Incoming Notifications**

```javascript
// Background/Quit state notification handler
messaging().setBackgroundMessageHandler(async (remoteMessage) => {
  console.log('Message handled in background:', remoteMessage);
  
  // Extract data
  const { clip_id, player_name, type } = remoteMessage.data;
  
  // Handle navigation when user taps notification
  if (type === 'new_clip') {
    // Navigate to clip details screen
    navigation.navigate('ClipDetails', { clipId: clip_id });
  }
});

// Foreground notification handler
messaging().onMessage(async (remoteMessage) => {
  console.log('Notification received in foreground:', remoteMessage);
  
  // Show in-app notification
  showInAppNotification({
    title: remoteMessage.notification.title,
    body: remoteMessage.notification.body,
    data: remoteMessage.data
  });
});
```

### **3. Remove Token on Logout**

```javascript
async function logout() {
  try {
    // Remove FCM token from backend
    await axios.delete('/api/me/fcm-token', {
      headers: {
        'Authorization': `Bearer ${userToken}`
      }
    });
    
    // Clear local auth
    await AsyncStorage.removeItem('auth_token');
    
    // Navigate to login
    navigation.navigate('Login');
  } catch (error) {
    console.error('Logout failed:', error);
  }
}
```

---

## 🧪 Testing

### **1. Test FCM Token Registration**

```bash
curl -X POST http://localhost:8001/api/me/fcm-token \
  -H "Authorization: Bearer YOUR_AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "fcm_token": "test_fcm_token_123456789"
  }'
```

**Expected Response:**
```json
{
  "message": "FCM token registered successfully",
  "token_updated_at": "2025-10-18T14:30:00.000000Z"
}
```

### **2. Test Clip Upload (Admin)**

```bash
# Upload a clip with player tag as admin
curl -X POST http://localhost:8001/api/clips/upload \
  -H "Authorization: Bearer ADMIN_AUTH_TOKEN" \
  -F "video=@/path/to/video.mp4" \
  -F "player_id=5" \
  -F "title=Amazing Dunk" \
  -F "description=Check out this incredible play!"
```

**What happens:**
1. Clip is created with `status=approved` (admin auto-approve)
2. System finds player with `id=5`
3. Gets all followers of that player
4. Sends notifications to followers who have FCM tokens
5. Followers receive push notification on their devices

### **3. Check Logs**

Monitor Laravel logs for FCM activity:

```bash
tail -f storage/logs/laravel.log | grep FCM
```

**Example Log Output:**
```
[2025-10-18 14:30:15] local.INFO: FCM: Notification sent successfully {"success":3,"failure":0}
```

---

## 📊 Notification Data Structure

### **Push Notification Payload**

```json
{
  "notification": {
    "title": "🏀 New Clip Alert!",
    "body": "LeBron James just posted a new highlight clip!",
    "sound": "default",
    "badge": 1,
    "icon": "ic_notification",
    "click_action": "OPEN_CLIP"
  },
  "data": {
    "type": "new_clip",
    "clip_id": "123",
    "player_id": "5",
    "player_name": "LeBron James",
    "clip_title": "Amazing Dunk",
    "thumbnail_url": "https://example.com/thumb.jpg",
    "timestamp": "2025-10-18T14:30:00Z"
  },
  "priority": "high",
  "content_available": true
}
```

### **Database Notification Record**

Stored in `notifications` table:

```json
{
  "id": "9c5b35a1-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
  "type": "App\\Notifications\\NewClipNotification",
  "notifiable_type": "App\\Models\\User",
  "notifiable_id": 10,
  "data": {
    "type": "new_clip",
    "clip_id": 123,
    "player_id": 5,
    "player_name": "LeBron James",
    "clip_title": "Amazing Dunk",
    "thumbnail_url": "https://example.com/thumb.jpg",
    "message": "LeBron James has a new clip!"
  },
  "read_at": null,
  "created_at": "2025-10-18 14:30:00"
}
```

---

## 🔍 Troubleshooting

### **Issue: Notifications not received**

**Check:**
1. ✅ FCM Server Key is correctly set in `.env`
2. ✅ User has registered FCM token in database
3. ✅ User's FCM token is not expired
4. ✅ Firebase project has Cloud Messaging enabled
5. ✅ Device has internet connection
6. ✅ App has notification permissions

**Debug:**
```bash
# Check if user has FCM token
php artisan tinker
>>> \App\Models\User::find(USER_ID)->fcm_token;

# Check recent notifications
>>> \App\Models\User::find(USER_ID)->notifications()->latest()->take(5)->get();
```

### **Issue: FCM Service returns error**

**Common Errors:**

1. **401 Unauthorized**
   - Invalid or missing FCM Server Key
   - Check `.env` file and Firebase console

2. **400 Bad Request**
   - Invalid FCM token format
   - Token may have been deleted/refreshed on client
   - Ask user to re-login

3. **404 Not Found**
   - Token no longer valid
   - Device uninstalled app
   - Clean up invalid tokens

---

## 🎯 Best Practices

### **1. Token Management**

- ✅ Refresh tokens periodically (every 2-3 months)
- ✅ Remove tokens on logout
- ✅ Handle token refresh events in the app
- ✅ Clean up expired/invalid tokens from database

### **2. Notification Content**

- ✅ Keep titles short and descriptive
- ✅ Include player name and action
- ✅ Add emoji for visual appeal (🏀)
- ✅ Include deep link data for navigation

### **3. Performance**

- ✅ Notifications are queued (implements `ShouldQueue`)
- ✅ Batch send to multiple devices
- ✅ Log errors for monitoring
- ✅ Handle rate limits gracefully

### **4. User Experience**

- ✅ Allow users to manage notification preferences
- ✅ Don't spam - limit frequency
- ✅ Respect do-not-disturb hours
- ✅ Provide in-app notification history

---

## 📚 API Reference

### **Register FCM Token**

```http
POST /api/me/fcm-token
Authorization: Bearer {token}
Content-Type: application/json

{
  "fcm_token": "string (max: 255)"
}
```

**Response:**
```json
{
  "message": "FCM token registered successfully",
  "token_updated_at": "2025-10-18T14:30:00.000000Z"
}
```

### **Remove FCM Token**

```http
DELETE /api/me/fcm-token
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "FCM token removed successfully"
}
```

### **Get Notifications**

```http
GET /api/me/notifications?per_page=20
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "type": "App\\Notifications\\NewClipNotification",
      "data": {
        "type": "new_clip",
        "clip_id": 123,
        "player_name": "LeBron James",
        "message": "LeBron James has a new clip!"
      },
      "read_at": null,
      "created_at": "2025-10-18T14:30:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

---

## 🔐 Security Considerations

1. **Never expose FCM Server Key** in client-side code
2. **Validate tokens** on the server before storing
3. **Use HTTPS** for all API communication
4. **Implement rate limiting** for token registration
5. **Clean up old tokens** periodically
6. **Log suspicious activity** (multiple token changes)

---

## 📈 Monitoring & Analytics

### **Metrics to Track**

- Total active FCM tokens
- Notification delivery success rate
- Failed notification attempts
- Average notification response time
- Users with expired/invalid tokens

### **Query Examples**

```sql
-- Count active FCM tokens
SELECT COUNT(*) FROM users WHERE fcm_token IS NOT NULL;

-- Tokens updated in last 30 days
SELECT COUNT(*) FROM users 
WHERE fcm_token_updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Recent notifications sent
SELECT COUNT(*) FROM notifications 
WHERE type = 'App\\Notifications\\NewClipNotification'
AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

---

## ✨ Future Enhancements

1. **Notification Preferences**
   - Allow users to choose notification types
   - Mute notifications from specific players
   - Set quiet hours

2. **Rich Notifications**
   - Include thumbnail images
   - Add action buttons (View, Dismiss, Share)
   - Video preview in notification

3. **Smart Notifications**
   - Group notifications by player
   - Limit frequency (e.g., max 5 per hour)
   - Send digest notifications

4. **Analytics Dashboard**
   - Track notification engagement
   - Monitor delivery rates
   - User preferences insights

---

## 📞 Support

For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Review Firebase Console for delivery reports
- Test with Firebase Cloud Messaging Tester
- Contact development team

---

**Version:** 1.0  
**Last Updated:** October 18, 2025  
**Author:** UBall Development Team
