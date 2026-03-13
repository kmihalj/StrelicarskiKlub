<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY rola ENUM('1','2','3','4') NOT NULL DEFAULT '3'");
    }

    public function down(): void
    {
        DB::table('users')->where('rola', '4')->update(['rola' => '3']);
        DB::statement("ALTER TABLE users MODIFY rola ENUM('1','2','3') NOT NULL DEFAULT '3'");
    }
};

