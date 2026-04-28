<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P-17 (Phase 3A): Laravel's built-in database-channel notifications
     * table. Scaffolded via `php artisan notifications:table`. Existing
     * Notification classes in app/Notifications/ deliver via 'mail' only
     * and target other subsystems; this table stays unused until the new
     * bell + dispatch service writes to it. Phase 3B extends each
     * relevant Notification's via() to add 'mail' alongside 'database'.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
