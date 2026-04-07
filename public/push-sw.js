const toAbsoluteUrl = (urlCandidate) => {
    if (typeof urlCandidate !== 'string' || urlCandidate.trim() === '') {
        return self.location.origin + '/';
    }

    try {
        return new URL(urlCandidate, self.location.origin + '/').toString();
    } catch (error) {
        return self.location.origin + '/';
    }
};

self.addEventListener('push', (event) => {
    let payload = {};

    if (event.data) {
        try {
            const rawPayload = event.data.text();
            if (typeof rawPayload === 'string' && rawPayload.trim() !== '') {
                payload = JSON.parse(rawPayload.replace(/^\uFEFF/, ''));
            }
        } catch (error) {
            payload = {};
        }
    }

    const title = typeof payload.title === 'string' && payload.title.trim() !== ''
        ? payload.title.trim()
        : 'SK Dubrava';

    const body = typeof payload.body === 'string' && payload.body.trim() !== ''
        ? payload.body
        : 'Imate novu obavijest.';
    const targetUrl = toAbsoluteUrl(payload.url);

    const show = async () => {
        try {
            await self.registration.showNotification(title, {
                body: body,
                data: {
                    url: targetUrl,
                },
            });
        } catch (error) {
            await self.registration.showNotification('SK Dubrava', {
                body: 'Imate novu obavijest.',
                data: {
                    url: targetUrl,
                },
            });
        }
    };

    event.waitUntil(show());
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    let targetUrl = self.location.origin + '/';
    if (event.notification && event.notification.data && event.notification.data.url) {
        targetUrl = toAbsoluteUrl(event.notification.data.url);
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }

            return undefined;
        })
    );
});
