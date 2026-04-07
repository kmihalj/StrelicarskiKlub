<?php

namespace App\Http\Controllers;

use App\Models\WebPushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles browser push subscription lifecycle for authenticated users.
 */
class WebPushSubscriptionController extends Controller
{
    /**
     * Store or update current browser push subscription for logged-in user.
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->webPushConfigured()) {
            return response()->json([
                'message' => 'Web push nije konfiguriran.',
            ], 503);
        }

        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        $validated = $request->validate([
            'subscription' => ['required', 'array'],
            'subscription.endpoint' => ['required', 'string', 'max:2048'],
            'subscription.contentEncoding' => ['nullable', 'string', 'max:32'],
            'subscription.keys' => ['required', 'array'],
            'subscription.keys.p256dh' => ['required', 'string', 'max:1024'],
            'subscription.keys.auth' => ['required', 'string', 'max:1024'],
        ], [
            'subscription.required' => 'Push subscription nedostaje.',
            'subscription.endpoint.required' => 'Push endpoint nedostaje.',
            'subscription.keys.p256dh.required' => 'Push public key nedostaje.',
            'subscription.keys.auth.required' => 'Push auth token nedostaje.',
        ]);

        $endpoint = trim((string) data_get($validated, 'subscription.endpoint'));
        $publicKey = trim((string) data_get($validated, 'subscription.keys.p256dh'));
        $authToken = trim((string) data_get($validated, 'subscription.keys.auth'));
        $contentEncoding = trim((string) data_get($validated, 'subscription.contentEncoding', ''));

        if ($endpoint === '' || $publicKey === '' || $authToken === '') {
            return response()->json([
                'message' => 'Push subscription nije potpuna.',
            ], 422);
        }

        if (!in_array($contentEncoding, ['aesgcm', 'aes128gcm'], true)) {
            $contentEncoding = 'aes128gcm';
        }

        WebPushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => hash('sha256', $endpoint)],
            [
                'user_id' => (int) $user->id,
                'endpoint' => $endpoint,
                'content_encoding' => $contentEncoding,
                'public_key' => $publicKey,
                'auth_token' => $authToken,
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Push subscription spremljen.',
        ]);
    }

    /**
     * Remove current browser push subscription for logged-in user.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
        ], [
            'endpoint.required' => 'Push endpoint nedostaje.',
        ]);

        $endpoint = trim((string) $validated['endpoint']);
        if ($endpoint !== '') {
            WebPushSubscription::query()
                ->where('user_id', (int) $user->id)
                ->where('endpoint_hash', hash('sha256', $endpoint))
                ->delete();
        }

        return response()->json([
            'message' => 'Push subscription uklonjen.',
        ]);
    }

    /**
     * Validate if VAPID configuration is present.
     */
    private function webPushConfigured(): bool
    {
        if (!(bool) config('webpush.enabled')) {
            return false;
        }

        $publicKey = trim((string) config('webpush.vapid.public_key'));
        $privateKey = trim((string) config('webpush.vapid.private_key'));
        $subject = trim((string) config('webpush.vapid.subject'));

        return $publicKey !== '' && $privateKey !== '' && $subject !== '';
    }
}

