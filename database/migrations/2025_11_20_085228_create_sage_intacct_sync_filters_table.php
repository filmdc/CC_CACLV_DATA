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
        if (!Schema::hasTable('sage_intacct_sync_filters')) {
            Schema::create('sage_intacct_sync_filters', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment('Descriptive name for this filter');
                $table->string('field_name')->comment('Gift field to filter on (gift_type, campaign, amount, etc.)');
                $table->enum('operator', ['equals', 'not_equals', 'contains', 'not_contains', 'greater_than', 'less_than', 'greater_or_equal', 'less_or_equal', 'is_empty', 'is_not_empty'])->comment('Comparison operator');
                $table->string('value')->nullable()->comment('Value to compare against (nullable for is_empty/is_not_empty)');
                $table->enum('action', ['exclude', 'include'])->default('exclude')->comment('Whether to exclude or include matching gifts');
                $table->integer('filter_group')->default(0)->comment('Group number for AND/OR logic (0 = ungrouped)');
                $table->enum('group_logic', ['AND', 'OR'])->default('OR')->comment('Logic to combine with other filters in same group');
                $table->boolean('is_active')->default(true)->comment('Whether this filter is currently active');
                $table->integer('priority')->default(0)->comment('Evaluation order (higher = evaluated first)');
                $table->text('description')->nullable()->comment('Explanation of why this filter exists');
                $table->timestamps();

                // Indexes
                $table->index(['is_active', 'priority']);
                $table->index('filter_group');
                $table->index('field_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sage_intacct_sync_filters');
    }
};
