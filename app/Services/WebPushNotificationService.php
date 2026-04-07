<?php

namespace App\Services;

use App\Models\ClubWallMessage;
use App\Models\WebPushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Sends browser push notifications (Web Push API) to authenticated users.
 */
class WebPushNotificationService
{
    private const MAX_CLUB_WALL_BODY_LENGTH = 220;
    private const GMP_BCMATH_WARNING_TEXT = 'highly recommended to install the GMP or BCMath extension';

    /**
     * Returns true only when all required VAPID settings exist.
     */
    public function isEnabled(): bool
    {
        if (!(bool) config('webpush.enabled')) {
            return false;
        }

        return trim((string) config('webpush.vapid.public_key')) !== ''
            && trim((string) config('webpush.vapid.private_key')) !== ''
            && trim((string) config('webpush.vapid.subject')) !== '';
    }

    /**
     * Push notification for new message on club wall.
     */
    public function sendClubWallMessageNotification(ClubWallMessage $message): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $author = trim((string) $message->author_name);
        if ($author === '') {
            $author = 'Nepoznati korisnik';
        }

        $text = $this->normalizeText((string) $message->message, self::MAX_CLUB_WALL_BODY_LENGTH);
        if ($text === '') {
            return;
        }

        $payload = [
            'title' => 'Klupski zid - nova poruka',
            'body' => $author . ': ' . $text,
            'url' => route('javno.naslovna'),
            'data' => [
                'type' => 'club_wall',
                'messageId' => (int) $message->id,
            ],
        ];

        $this->broadcastPayload($payload);
    }

    /**
     * Broadcast JSON payload to all stored browser subscriptions.
     */
    private function broadcastPayload(array $payload): void
    {
        $subscriptions = WebPushSubscription::query()
            ->select(['id', 'endpoint', 'endpoint_hash', 'content_encoding', 'public_key', 'auth_token'])
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($jsonPayload) || $jsonPayload === '') {
            return;
        }

        $webPush = $this->createWebPushClient();

        foreach ($subscriptions as $subscription) {
            try {
                $endpointHost = (string) (parse_url((string) $subscription->endpoint, PHP_URL_HOST) ?: '');
                $payloadForSubscription = $jsonPayload;

                // Edge/WNS can be unreliable with encrypted payloads on some setups.
                // Fall back to payload-less push so the notification still reaches Edge.
                if ($endpointHost !== '' && str_contains(strtolower($endpointHost), 'notify.windows.com')) {
                    $payloadForSubscription = null;
                }

                $webPush->queueNotification(
                    $this->toLibrarySubscription($subscription),
                    $payloadForSubscription
                );
            } catch (Throwable $exception) {
                Log::warning('Neuspjelo queueanje web push pretplate.', [
                    'subscription_id' => (int) $subscription->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        foreach ($webPush->flush() as $report) {
            $endpoint = (string) $report->getRequest()->getUri();
            $endpointHost = parse_url($endpoint, PHP_URL_HOST) ?: '';
            $reason = (string) $report->getReason();
            $expired = $report->isSubscriptionExpired();
            $success = $report->isSuccess();

            if ((bool) config('app.debug')) {
                Log::info('Web push delivery report.', [
                    'endpoint' => $endpoint,
                    'endpoint_host' => $endpointHost,
                    'success' => $success,
                    'reason' => $reason,
                    'expired' => $expired,
                ]);
            }

            if ($report->isSuccess()) {
                continue;
            }

            if ($endpoint !== '' && $report->isSubscriptionExpired()) {
                WebPushSubscription::query()
                    ->where('endpoint_hash', hash('sha256', $endpoint))
                    ->delete();
            }

            Log::warning('Web push slanje nije uspjelo.', [
                'endpoint' => $endpoint,
                'endpoint_host' => $endpointHost,
                'reason' => $reason,
                'expired' => $expired,
            ]);
        }
    }

    /**
     * Build WebPush client.
     */
    private function createWebPushClient(): WebPush
    {
        $createClient = function (): WebPush {
            return new WebPush($this->vapidConfig(), [
                'TTL' => 300,
                'urgency' => 'high',
            ]);
        };

        if (extension_loaded('gmp') || extension_loaded('bcmath')) {
            return $createClient();
        }

        Log::warning('GMP/BCMath ekstenzija nije dostupna; web push ce raditi sporije.');

        set_error_handler(static function (int $severity, string $message): bool {
            return str_contains(strtolower($message), self::GMP_BCMATH_WARNING_TEXT);
        });

        try {
            return $createClient();
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Convert DB row to minishlink/web-push Subscription object.
     */
    private function toLibrarySubscription(WebPushSubscription $subscription): Subscription
    {
        $encoding = trim((string) ($subscription->content_encoding ?? ''));
        if (!in_array($encoding, ['aesgcm', 'aes128gcm'], true)) {
            $encoding = 'aes128gcm';
        }

        return Subscription::create([
            'endpoint' => (string) $subscription->endpoint,
            'publicKey' => (string) $subscription->public_key,
            'authToken' => (string) $subscription->auth_token,
            'contentEncoding' => $encoding,
        ]);
    }

    /**
     * Build VAPID auth payload for minishlink/web-push.
     */
    private function vapidConfig(): array
    {
        return [
            'VAPID' => [
                'subject' => (string) config('webpush.vapid.subject'),
                'publicKey' => (string) config('webpush.vapid.public_key'),
                'privateKey' => (string) config('webpush.vapid.private_key'),
            ],
        ];
    }

    /**
     * Normalize and shorten plain text for notification body.
     */
    private function normalizeText(string $text, int $maxLength): string
    {
        $normalized = str_replace(["\r\n", "\r", "\n"], ' ', $text);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $normalized = trim($normalized);

        if ($normalized === '') {
            return '';
        }

        if (mb_strlen($normalized) <= $maxLength) {
            return $normalized;
        }

        return mb_substr($normalized, 0, $maxLength - 3) . '...';
    }

    /**
     * Resolve absolute asset URL (works in web and CLI contexts).
     */
    private function assetUrl(string $path): string
    {
        try {
            return asset($path);
        } catch (Throwable) {
            return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
        }
    }
}
