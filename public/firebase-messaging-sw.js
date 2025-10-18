// public/firebase-messaging-sw.js
importScripts('https://www.gstatic.com/firebasejs/7.23.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/7.23.0/firebase-messaging.js');

firebase.initializeApp({
    apiKey: "AIzaSyAZCa6o-DPX4NWxjZJlBIzKnjrlDz3l7YM",
    authDomain: "flettonchatbot.firebaseapp.com",
    projectId: "flettonchatbot",
    storageBucket: "flettonchatbot.appspot.com",
    messagingSenderId: "691698478961",
    appId: "1:691698478961:web:293a80ed82b837e32210d0",
    measurementId: "G-E6G35MR752"
});

const messaging = firebase.messaging();

// Background notifications (tab not focused)
messaging.setBackgroundMessageHandler(function (payload) {
    const n = payload.notification || {};
    const d = payload.data || {};
    const title = n.title || d.title || 'Background Message';
    const options = {
        body: n.body || d.body || '',
        icon: n.icon || d.icon || '/favicon.ico',
        data: { url: (payload.webpush?.fcm_options?.link) || '/' }
    };
    return self.registration.showNotification(title, options);
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/';
    event.waitUntil(clients.openWindow(url));
});

