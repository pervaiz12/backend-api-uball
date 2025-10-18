# Frontend Integration Guide - Push Notifications

## 🎯 Quick Start for Frontend Developers

This guide provides ready-to-use code snippets for integrating push notifications in the UBall mobile app.

---

## 📱 React Native Integration

### **1. Install Dependencies**

```bash
npm install @react-native-firebase/app @react-native-firebase/messaging axios
```

### **2. Configure Firebase**

Add your `google-services.json` (Android) and `GoogleService-Info.plist` (iOS) to the project.

### **3. Request Permission & Register Token**

Create `src/services/pushNotifications.js`:

```javascript
import messaging from '@react-native-firebase/messaging';
import axios from 'axios';
import { API_BASE_URL } from './config';

export class PushNotificationService {
  
  /**
   * Request notification permission and register FCM token
   */
  static async initialize(authToken) {
    try {
      // Request permission (iOS)
      const authStatus = await messaging().requestPermission();
      const enabled = 
        authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
        authStatus === messaging.AuthorizationStatus.PROVISIONAL;

      if (!enabled) {
        console.log('Notification permission denied');
        return false;
      }

      // Get FCM token
      const fcmToken = await messaging().getToken();
      console.log('FCM Token:', fcmToken);

      // Register token with backend
      await this.registerToken(fcmToken, authToken);
      
      // Listen for token refresh
      messaging().onTokenRefresh(async (newToken) => {
        await this.registerToken(newToken, authToken);
      });

      return true;
    } catch (error) {
      console.error('Push notification initialization failed:', error);
      return false;
    }
  }

  /**
   * Register FCM token with backend
   */
  static async registerToken(fcmToken, authToken) {
    try {
      const response = await axios.post(
        `${API_BASE_URL}/me/fcm-token`,
        { fcm_token: fcmToken },
        {
          headers: {
            'Authorization': `Bearer ${authToken}`,
            'Content-Type': 'application/json'
          }
        }
      );
      console.log('FCM token registered:', response.data);
      return true;
    } catch (error) {
      console.error('Failed to register FCM token:', error);
      return false;
    }
  }

  /**
   * Remove FCM token on logout
   */
  static async removeToken(authToken) {
    try {
      await axios.delete(
        `${API_BASE_URL}/me/fcm-token`,
        {
          headers: {
            'Authorization': `Bearer ${authToken}`
          }
        }
      );
      console.log('FCM token removed');
    } catch (error) {
      console.error('Failed to remove FCM token:', error);
    }
  }

  /**
   * Setup notification handlers
   */
  static setupHandlers(navigation) {
    // Handle notification when app is in background
    messaging().setBackgroundMessageHandler(async (remoteMessage) => {
      console.log('Background notification:', remoteMessage);
      return Promise.resolve();
    });

    // Handle notification when app is in foreground
    messaging().onMessage(async (remoteMessage) => {
      console.log('Foreground notification:', remoteMessage);
      
      // Show in-app notification
      this.showInAppNotification(
        remoteMessage.notification,
        remoteMessage.data
      );
    });

    // Handle notification tap
    messaging().onNotificationOpenedApp((remoteMessage) => {
      console.log('Notification tapped:', remoteMessage);
      this.handleNotificationTap(remoteMessage.data, navigation);
    });

    // Check if app was opened from notification (killed state)
    messaging()
      .getInitialNotification()
      .then((remoteMessage) => {
        if (remoteMessage) {
          console.log('App opened from notification:', remoteMessage);
          this.handleNotificationTap(remoteMessage.data, navigation);
        }
      });
  }

  /**
   * Handle notification tap navigation
   */
  static handleNotificationTap(data, navigation) {
    if (data.type === 'new_clip' && data.clip_id) {
      // Navigate to clip details
      navigation.navigate('ClipDetails', {
        clipId: parseInt(data.clip_id),
        playerId: parseInt(data.player_id),
        playerName: data.player_name
      });
    }
  }

  /**
   * Show in-app notification (foreground)
   */
  static showInAppNotification(notification, data) {
    // Use your preferred notification library
    // Example: react-native-flash-message
    /*
    showMessage({
      message: notification.title,
      description: notification.body,
      type: 'info',
      icon: 'auto',
      onPress: () => {
        // Handle tap
        this.handleNotificationTap(data, navigation);
      }
    });
    */
    
    // Or use Alert for simple implementation
    /*
    Alert.alert(
      notification.title,
      notification.body,
      [
        { text: 'View', onPress: () => this.handleNotificationTap(data, navigation) },
        { text: 'Dismiss', style: 'cancel' }
      ]
    );
    */
  }
}
```

### **4. Use in App**

Update your `App.js` or authentication flow:

```javascript
import { PushNotificationService } from './services/pushNotifications';

// After successful login
async function handleLogin(email, password) {
  try {
    const response = await loginApi(email, password);
    const { token, user } = response.data;
    
    // Save token
    await AsyncStorage.setItem('auth_token', token);
    
    // Initialize push notifications
    await PushNotificationService.initialize(token);
    
    // Setup notification handlers
    PushNotificationService.setupHandlers(navigation);
    
    // Navigate to home
    navigation.replace('Home');
  } catch (error) {
    console.error('Login failed:', error);
  }
}

// On logout
async function handleLogout() {
  try {
    const token = await AsyncStorage.getItem('auth_token');
    
    // Remove FCM token from backend
    await PushNotificationService.removeToken(token);
    
    // Clear local storage
    await AsyncStorage.removeItem('auth_token');
    
    // Navigate to login
    navigation.replace('Login');
  } catch (error) {
    console.error('Logout failed:', error);
  }
}
```

### **5. AndroidManifest.xml Configuration**

Add to `android/app/src/main/AndroidManifest.xml`:

```xml
<manifest>
  <application>
    <!-- ... other configurations ... -->
    
    <!-- Firebase Messaging Service -->
    <service
      android:name="com.google.firebase.messaging.FirebaseMessagingService"
      android:exported="false">
      <intent-filter>
        <action android:name="com.google.firebase.MESSAGING_EVENT" />
      </intent-filter>
    </service>
    
    <!-- Notification icon -->
    <meta-data
      android:name="com.google.firebase.messaging.default_notification_icon"
      android:resource="@drawable/ic_notification" />
      
    <!-- Notification color -->
    <meta-data
      android:name="com.google.firebase.messaging.default_notification_color"
      android:resource="@color/notification_color" />
  </application>
</manifest>
```

---

## 🍎 iOS Configuration

### **1. Enable Capabilities**

In Xcode:
1. Select your target
2. Go to "Signing & Capabilities"
3. Add "Push Notifications" capability
4. Add "Background Modes" capability
   - Check "Remote notifications"

### **2. Request Permission**

iOS requires explicit permission request. The code above handles this automatically.

---

## 🎨 Notification UI Examples

### **In-App Notification (Foreground)**

```javascript
import { Toast } from 'react-native-toast-message';

PushNotificationService.showInAppNotification = (notification, data) => {
  Toast.show({
    type: 'info',
    text1: notification.title,
    text2: notification.body,
    position: 'top',
    visibilityTime: 4000,
    autoHide: true,
    onPress: () => {
      // Navigate to clip
      PushNotificationService.handleNotificationTap(data, navigation);
    }
  });
};
```

---

## 🧪 Testing

### **Test Notification from Backend**

```bash
# 1. Login and get auth token
curl -X POST http://localhost:8001/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "player@example.com",
    "password": "password"
  }'

# 2. Register FCM token
curl -X POST http://localhost:8001/api/me/fcm-token \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "fcm_token": "YOUR_DEVICE_FCM_TOKEN"
  }'

# 3. Follow a player
curl -X POST http://localhost:8001/api/players/5/follow \
  -H "Authorization: Bearer YOUR_TOKEN"

# 4. Upload clip as admin (triggers notification)
# Admin uploads clip for player ID 5
# You should receive a push notification!
```

### **Test with Firebase Console**

1. Go to Firebase Console → Cloud Messaging
2. Click "Send your first message"
3. Enter notification title and message
4. Add your device's FCM token
5. Send test notification

---

## 📊 Notification Data Structure

When handling notifications in your app:

```javascript
{
  notification: {
    title: "🏀 New Clip Alert!",
    body: "LeBron James just posted a new highlight clip!"
  },
  data: {
    type: "new_clip",           // Always "new_clip"
    clip_id: "123",             // ID to fetch clip details
    player_id: "5",             // Player who was tagged
    player_name: "LeBron James", // Display name
    clip_title: "Amazing Dunk",  // Clip title
    thumbnail_url: "https://...", // Thumbnail URL
    timestamp: "2025-10-18T14:30:00Z" // When notification was sent
  }
}
```

### **Navigation Logic**

```javascript
handleNotificationTap(data, navigation) {
  switch(data.type) {
    case 'new_clip':
      navigation.navigate('ClipDetails', {
        clipId: parseInt(data.clip_id)
      });
      break;
      
    case 'new_follower':
      navigation.navigate('Profile', {
        userId: parseInt(data.follower_id)
      });
      break;
      
    // Add more cases as needed
  }
}
```

---

## 🔔 Notification Permissions UI

Create a settings screen for notification preferences:

```javascript
import AsyncStorage from '@react-native-async-storage/async-storage';

function NotificationSettings() {
  const [notificationsEnabled, setNotificationsEnabled] = useState(false);

  useEffect(() => {
    checkNotificationPermission();
  }, []);

  async function checkNotificationPermission() {
    const authStatus = await messaging().hasPermission();
    setNotificationsEnabled(
      authStatus === messaging.AuthorizationStatus.AUTHORIZED
    );
  }

  async function toggleNotifications() {
    if (notificationsEnabled) {
      // Disable: Remove FCM token
      const token = await AsyncStorage.getItem('auth_token');
      await PushNotificationService.removeToken(token);
      setNotificationsEnabled(false);
    } else {
      // Enable: Register FCM token
      const token = await AsyncStorage.getItem('auth_token');
      const success = await PushNotificationService.initialize(token);
      setNotificationsEnabled(success);
    }
  }

  return (
    <View>
      <Text>Push Notifications</Text>
      <Switch
        value={notificationsEnabled}
        onValueChange={toggleNotifications}
      />
    </View>
  );
}
```

---

## ⚠️ Common Issues & Solutions

### **Issue 1: Token not registering**

**Problem:** FCM token registration fails

**Solution:**
```javascript
// Check network connection
// Verify API_BASE_URL is correct
// Ensure auth token is valid
// Check Laravel logs for errors
```

### **Issue 2: Notifications not received**

**Checklist:**
- ✅ App has notification permission
- ✅ FCM token is registered in database
- ✅ Device has internet connection
- ✅ Firebase Cloud Messaging is enabled in Firebase Console
- ✅ Backend FCM_SERVER_KEY is configured correctly

### **Issue 3: App crashes on notification**

**Solution:**
```javascript
// Wrap notification handlers in try-catch
messaging().onMessage(async (remoteMessage) => {
  try {
    console.log('Notification received:', remoteMessage);
    // Your handling code
  } catch (error) {
    console.error('Error handling notification:', error);
  }
});
```

---

## 📱 Platform-Specific Notes

### **Android**

- Notifications work automatically in background
- Icon must be white/transparent PNG
- Support for notification channels (Android 8+)

### **iOS**

- Requires Apple Push Notification service (APNs)
- Must have valid provisioning profile
- Test on real device (not simulator)
- Requires user permission

---

## 🎯 Best Practices

1. **Always request permission** at appropriate time (not immediately on app launch)
2. **Handle token refresh** to keep tokens up-to-date
3. **Remove tokens on logout** to prevent unwanted notifications
4. **Test on real devices** - simulators have limitations
5. **Handle offline scenarios** gracefully
6. **Provide notification settings** in app
7. **Log events** for debugging

---

## 📚 Additional Resources

- [React Native Firebase Docs](https://rnfirebase.io/)
- [Firebase Cloud Messaging](https://firebase.google.com/docs/cloud-messaging)
- [Apple Push Notifications](https://developer.apple.com/documentation/usernotifications)

---

**Need Help?** Contact the backend team with your error logs and device info.
