<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_wall_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('author_clan_id')->nullable()->constrained('clanovis')->nullOnDelete();
            $table->string('author_name', 160);
            $table->text('message');
            $table->boolean('is_highlighted')->default(false);
            $table->foreignId('highlighted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['deleted_at', 'created_at'], 'club_wall_messages_deleted_created_idx');
            $table->index(['is_highlighted', 'created_at'], 'club_wall_messages_highlighted_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_wall_messages');
    }
};

