<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Intentionally left blank.
        // Theme palette tuning should be done via admin UI or direct data update,
        // not by shipping project-specific colors in migrations.
    }

    public function down(): void
    {
        // No-op.
    }
};
