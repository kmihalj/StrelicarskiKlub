<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;
use Throwable;

/**
 * Generates VAPID keys for Web Push.
 */
class GenerateWebPushVapidKeys extends Command
{
    protected $signature = 'webpush:vapid-keys';

    protected $description = 'Generira VAPID kljuceve za browser push obavijesti';

    public function handle(): int
    {
        try {
            $keys = VAPID::createVapidKeys();
        } catch (Throwable $exception) {
            $this->error('Generiranje VAPID kljuceva nije uspjelo: ' . $exception->getMessage());
            $this->line('Probaj pokrenuti naredbu u Linux/WSL okruzenju gdje OpenSSL podrzava EC kljuceve.');

            return self::FAILURE;
        }

        $this->info('VAPID kljucevi su generirani. Kopiraj u .env:');
        $this->line('');
        $this->line('WEBPUSH_ENABLED=true');
        $this->line('WEBPUSH_VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('WEBPUSH_VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->line('WEBPUSH_VAPID_SUBJECT=mailto:info@skdubrava.hr');
        $this->line('WEBPUSH_SERVICE_WORKER_PATH=push-sw.js');

        return self::SUCCESS;
    }
}
