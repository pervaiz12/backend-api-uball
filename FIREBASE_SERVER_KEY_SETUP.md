# Firebase Server Key Setup (Backend)

## 🔑 Get Firebase Server Key

You need to add the Firebase Server Key to your backend `.env` file for push notifications to work.

---

## 📝 Steps to Get Server Key

### **Step 1: Go to Firebase Console**

1. Open [Firebase Console](https://console.firebase.google.com/)
2. Select your project: **fcm-messaging** (ID: fcm-messaging-4b5d8)

### **Step 2: Navigate to Cloud Messaging**

1. Click the ⚙️ (gear icon) → **Project settings**
2. Click the **Cloud Messaging** tab

### **Step 3: Find Server Key**

1. Scroll to **Cloud Messaging API (Legacy)**
2. Look for **Server key**
3. Copy the server key (it's a long string like `AAAAxxxxx...`)

**⚠️ Note:** If you see a message about enabling Cloud Messaging API:
- Click the **"Enable"** or **"Manage"** link
- Enable the Cloud Messaging API
- The server key will then be available

---

## 🔧 Add to Backend .env

### **File Location:**
```
/Users/macbookpro/code/uball/backend-api/.env
```

### **Add This Line:**

```env
FCM_SERVER_KEY=AAAAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Replace `AAAAxxxxx...` with your actual server key from Firebase Console.

---

## ✅ Verify Configuration

After adding the server key, your backend `.env` should have:

```env
# ... other configuration ...

# Firebase Cloud Messaging
FCM_SERVER_KEY=your_actual_server_key_here
```

---

## 🔄 Restart Backend

After updating `.env`:

```bash
cd /Users/macbookpro/code/uball/backend-api

# Clear config cache
php artisan config:clear

# Restart queue worker (if running)
# Stop existing worker (Ctrl+C) then:
php artisan queue:work
```

---

## 🧪 Test Push Notifications

### **Test 1: Message Notifications**

```bash
# Send a message from User A to User B
# User B should receive a push notification
```

### **Test 2: Clip Notifications**

```bash
# Upload a clip as admin with a player tag
# All followers of that player should receive notifications
```

### **Check Logs:**

```bash
tail -f storage/logs/laravel.log | grep FCM
```

Expected output:
```
[2025-10-18] local.INFO: FCM: Notification sent successfully {"success":1,"failure":0}
```

---

## 🔐 Security

**⚠️ IMPORTANT:**
- The **Server Key** is SECRET - never expose it in client-side code
- Only use it in backend `.env` file
- Never commit it to git
- Keep `.env` in `.gitignore`

---

## 📚 Your Firebase Project Details

**Project Name:** fcm-messaging  
**Project ID:** fcm-messaging-4b5d8  
**Project Number:** 5936871786  
**Web API Key:** AIzaSyAGFqys1EOJhwmRwTKADEFSuG0JPY11Zjs  

**Console Link:** https://console.firebase.google.com/project/fcm-messaging-4b5d8/settings/cloudmessaging

---

## ❓ Troubleshooting

### **"Server key not found"**

1. Check if Cloud Messaging API is enabled
2. Go to Cloud Messaging tab in Firebase Console
3. Look for "Cloud Messaging API (Legacy)"
4. Copy the Server key

### **"Notifications not sending"**

1. Check if `FCM_SERVER_KEY` is set in `.env`
2. Check if the key is correct (no extra spaces)
3. Run `php artisan config:clear`
4. Check Laravel logs for FCM errors
5. Ensure queue worker is running

---

## ✨ Summary

✅ **Frontend**: Already configured with your Firebase credentials  
⏳ **Backend**: Need to add FCM_SERVER_KEY to `.env`  
⏳ **VAPID Key**: Need to generate and add to frontend `.env.local`  

Once both are configured, push notifications will work perfectly! 🚀
