<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Housing portal: per-user pins.
 *
 * The project manager can pin a call/intake (CommunicationLog) or a project
 * (HousingJob) so it stays in front of her — surfaced on Today and lifted
 * above its own list screen regardless of the active filter.
 *
 * pin_type is a portal-level key ('call' | 'project') rather than a morph
 * class name, matching HomesSavedView.screen: the portal reads both records
 * through HousingPortalData, and the key is what the UI actually filters on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homes_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pin_type', 16);
            $table->unsignedBigInteger('pinnable_id');
            $table->timestamps();

            $table->unique(['user_id', 'pin_type', 'pinnable_id'], 'uq_homes_pins_target');
            $table->index(['user_id', 'pin_type'], 'ix_homes_pins_user_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homes_pins');
    }
};
