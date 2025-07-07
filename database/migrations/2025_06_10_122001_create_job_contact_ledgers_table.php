<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_contact_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housing_job_id')->constrained()->onDelete('cascade');
            $table->datetime('contact_date');
            $table->enum('contact_type', [
                'phone_call',
                'email',
                'in_person',
                'site_visit',
                'mail',
                'text',
                'other'
            ]);
            $table->enum('direction', ['incoming', 'outgoing']);
            $table->string('contact_person')->nullable()->comment('Who was contacted');
            $table->string('contact_method_details')->nullable()->comment('Phone number, email address, etc.');
            $table->enum('contact_purpose', [
                'initial_inquiry',
                'status_update',
                'scheduling',
                'inspection',
                'approval',
                'issue_resolution',
                'documentation',
                'payment',
                'other'
            ])->nullable();
            $table->text('notes');
            $table->string('outcome')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('follow_up_action')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index('housing_job_id');
            $table->index('contact_date');
            $table->index('contact_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_contact_ledgers');
    }
};
