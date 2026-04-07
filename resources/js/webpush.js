const readMeta = (name) => {
    const value = document.querySelector(`meta[name="${name}"]`)?.getAttribute('content');
    return typeof value === 'string' ? value.trim() : '';
};

const supportsWebPush = () => {
    return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
};

const urlBase64ToUint8Array = (base64String) => {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let index = 0; index < rawData.length; index += 1) {
        outputArray[index] = rawData.charCodeAt(index);
    }

    return outputArray;
};

const postJson = async (url, body) => {
    const csrfToken = readMeta('csrf-token');
    if (!csrfToken || !url) {
        return null;
    }

    try {
        await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(body),
        });
    } catch (error) {
        // Best effort sync, no UI noise.
    }

    return null;
};

const deleteJson = async (url, body) => {
    const csrfToken = readMeta('csrf-token');
    if (!csrfToken || !url) {
        return null;
    }

    try {
        await fetch(url, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(body),
        });
    } catch (error) {
        // Best effort sync, no UI noise.
    }

    return null;
};

const syncSubscriptionToServer = async (subscription) => {
    const subscribeUrl = readMeta('webpush-subscribe-url');
    if (!subscribeUrl || !subscription) {
        return;
    }

    await postJson(subscribeUrl, {
        subscription: subscription.toJSON(),
    });
};

const removeSubscriptionFromServer = async (subscription) => {
    const unsubscribeUrl = readMeta('webpush-unsubscribe-url');
    if (!unsubscribeUrl || !subscription) {
        return;
    }

    const endpoint = typeof subscription.endpoint === 'string' ? subscription.endpoint : '';
    if (!endpoint) {
        return;
    }

    await deleteJson(unsubscribeUrl, { endpoint });
};

const loadCurrentSubscription = async () => {
    if (!supportsWebPush()) {
        return null;
    }

    const serviceWorkerUrl = readMeta('webpush-service-worker-url');
    if (!serviceWorkerUrl) {
        return null;
    }

    let registration = null;

    try {
        registration = await navigator.serviceWorker.getRegistration(serviceWorkerUrl);
    } catch (error) {
        registration = null;
    }

    if (!registration) {
        try {
            registration = await navigator.serviceWorker.ready;
        } catch (error) {
            registration = null;
        }
    }

    if (!registration) {
        return null;
    }

    try {
        return await registration.pushManager.getSubscription();
    } catch (error) {
        return null;
    }
};

const bindLogoutCleanup = () => {
    const logoutForms = Array.from(document.querySelectorAll('form[action*="/logout"]'));
    if (logoutForms.length === 0) {
        return;
    }

    for (const form of logoutForms) {
        if (!(form instanceof HTMLFormElement)) {
            continue;
        }

        form.addEventListener('submit', (event) => {
            const alreadyCleaned = form.dataset.webpushCleanupDone === '1';
            if (alreadyCleaned) {
                return;
            }

            event.preventDefault();

            void (async () => {
                const subscription = await loadCurrentSubscription();
                if (subscription) {
                    await removeSubscriptionFromServer(subscription);
                    try {
                        await subscription.unsubscribe();
                    } catch (error) {
                        // Ignore local unsubscribe errors.
                    }
                }
            })().finally(() => {
                form.dataset.webpushCleanupDone = '1';
                form.submit();
            });
        });
    }
};

const registerAndSyncWebPush = async () => {
    if (!supportsWebPush()) {
        return;
    }

    if (readMeta('webpush-enabled') !== '1') {
        return;
    }

    const publicKey = readMeta('webpush-public-key');
    const serviceWorkerUrl = readMeta('webpush-service-worker-url');
    if (!publicKey || !serviceWorkerUrl) {
        return;
    }

    let registration;
    try {
        registration = await navigator.serviceWorker.register(serviceWorkerUrl);
        await registration.update();
        await navigator.serviceWorker.ready;
    } catch (error) {
        return;
    }

    const existingSubscription = await registration.pushManager.getSubscription();

    if (Notification.permission === 'denied') {
        if (existingSubscription) {
            await removeSubscriptionFromServer(existingSubscription);
        }
        return;
    }

    const ensureGrantedPermissionAndSync = async () => {
        let permission = Notification.permission;
        if (permission === 'default') {
            try {
                permission = await Notification.requestPermission();
            } catch (error) {
                return;
            }
        }

        if (permission !== 'granted') {
            return;
        }

        let subscription = await registration.pushManager.getSubscription();
        if (!subscription) {
            try {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(publicKey),
                });
            } catch (error) {
                return;
            }
        }

        await syncSubscriptionToServer(subscription);
    };

    if (Notification.permission === 'default') {
        document.addEventListener('click', () => {
            void ensureGrantedPermissionAndSync();
        }, { once: true });
        return;
    }

    await ensureGrantedPermissionAndSync();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        bindLogoutCleanup();
        void registerAndSyncWebPush();
    }, { once: true });
} else {
    bindLogoutCleanup();
    void registerAndSyncWebPush();
}
