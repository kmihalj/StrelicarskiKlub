<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subscription record for browser push notifications (Web Push API).
 */
class WebPushSubscription extends Model
{
    protected $table = 'web_push_subscriptions';

    protected $fillable = [
        'user_id',
        'endpoint',
        'endpoint_hash',
        'content_encoding',
        'public_key',
        'auth_token',
        'user_agent',
        'last_seen_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Account that owns this browser/device subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

