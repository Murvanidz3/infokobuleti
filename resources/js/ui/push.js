import { csrfToken, postJson, toast } from './http';

/**
 * Web push on this device: asks for permission, subscribes through the service worker with the
 * site's VAPID key and stores the subscription (see PushSubscriptionController).
 */
export function registerPush(Alpine) {
    Alpine.data('pushToggle', (vapidKey, storeUrl) => ({
        supported: 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window,
        subscribed: false,
        denied: false,
        busy: false,

        async init() {
            if (!this.supported) {
                return;
            }

            this.denied = Notification.permission === 'denied';
            const registration = await navigator.serviceWorker.getRegistration('/');
            this.subscribed = Boolean(await registration?.pushManager.getSubscription());
        },

        async enable() {
            this.busy = true;

            try {
                const permission = await Notification.requestPermission();
                this.denied = permission === 'denied';

                if (permission !== 'granted') {
                    return;
                }

                const registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
                await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidKey),
                });
                const json = subscription.toJSON();
                const encodings = PushManager.supportedContentEncodings ?? ['aes128gcm'];
                const { ok, data } = await postJson(storeUrl, {
                    endpoint: json.endpoint,
                    keys: json.keys,
                    contentEncoding: encodings.includes('aes128gcm') ? 'aes128gcm' : 'aesgcm',
                });

                this.subscribed = ok;
                toast(data.message, ok ? 'success' : 'error');
            } catch {
                toast(document.documentElement.dataset.errorMessage, 'error');
            } finally {
                this.busy = false;
            }
        },

        async disable() {
            this.busy = true;

            try {
                const registration = await navigator.serviceWorker.getRegistration('/');
                const subscription = await registration?.pushManager.getSubscription();

                if (subscription) {
                    await fetch(storeUrl, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                        credentials: 'same-origin',
                        body: JSON.stringify({ endpoint: subscription.endpoint }),
                    }).then((response) => response.json()).then((data) => toast(data.message, 'success')).catch(() => {});
                    await subscription.unsubscribe();
                }

                this.subscribed = false;
            } finally {
                this.busy = false;
            }
        },
    }));
}

function urlBase64ToUint8Array(base64) {
    const padding = '='.repeat((4 - (base64.length % 4)) % 4);
    const raw = atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));

    return Uint8Array.from([...raw].map((char) => char.charCodeAt(0)));
}
