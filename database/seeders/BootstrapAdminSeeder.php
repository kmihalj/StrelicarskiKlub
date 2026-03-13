<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class BootstrapAdminSeeder extends Seeder
{
    public const ADMIN_NAME = 'Administrator';
    public const ADMIN_EMAIL = 'administrator@archery.local';
    public const ADMIN_PASSWORD = 'poklonOdSKDubrava';

    public function run(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'is_bootstrap_admin')) {
            User::query()->where('is_bootstrap_admin', true)->update(['is_bootstrap_admin' => false]);
        }

        $attributes = [
            'name' => self::ADMIN_NAME,
            'password' => Hash::make(self::ADMIN_PASSWORD),
            'rola' => 1,
            'clan_id' => null,
            'polaznik_id' => null,
            'je_roditelj' => false,
        ];

        if (Schema::hasColumn('users', 'oib')) {
            $attributes['oib'] = null;
        }

        if (Schema::hasColumn('users', 'br_telefona')) {
            $attributes['br_telefona'] = null;
        }

        if (Schema::hasColumn('users', 'email_verified_at')) {
            $attributes['email_verified_at'] = now();
        }

        if (Schema::hasColumn('users', 'theme_mode_preference')) {
            $attributes['theme_mode_preference'] = 'auto';
        }

        if (Schema::hasColumn('users', 'is_bootstrap_admin')) {
            $attributes['is_bootstrap_admin'] = true;
        }

        User::query()->updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            $attributes
        );
    }
}
