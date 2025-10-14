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
        // Add legacy sync fields to contracts table
        $this->addLegacyColumns('contracts');
        
        // Add legacy sync fields to programs table
        $this->addLegacyColumns('programs');
        
        // Add legacy sync fields to funders table
        $this->addLegacyColumns('funders');
        
        // Add legacy sync fields to contract_report_due_dates table
        $this->addLegacyColumns('contract_report_due_dates');
        
        // Add legacy sync fields to contract_reports table
        $this->addLegacyColumns('contract_reports');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove from contracts table
        Schema::table('contracts', function (Blueprint $table) {
            $this->dropLegacyColumns('contracts', $table);
        });
        
        // Remove from programs table
        Schema::table('programs', function (Blueprint $table) {
            $this->dropLegacyColumns('programs', $table);
        });
        
        // Remove from funders table
        Schema::table('funders', function (Blueprint $table) {
            $this->dropLegacyColumns('funders', $table);
        });
        
        // Remove from contract_report_due_dates table
        Schema::table('contract_report_due_dates', function (Blueprint $table) {
            $this->dropLegacyColumns('contract_report_due_dates', $table);
        });
        
        // Remove from contract_reports table
        Schema::table('contract_reports', function (Blueprint $table) {
            $this->dropLegacyColumns('contract_reports', $table);
        });
    }

    private function addLegacyColumns(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (!Schema::hasColumn($tableName, 'legacy_id')) {
                $table->unsignedBigInteger('legacy_id')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn($tableName, 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('updated_at');
            }
        });

        if (Schema::hasColumn($tableName, 'legacy_id') && !$this->hasIndex($tableName, "{$tableName}_legacy_id_index")) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index('legacy_id');
            });
        }
        if (Schema::hasColumn($tableName, 'last_synced_at') && !$this->hasIndex($tableName, "{$tableName}_last_synced_at_index")) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index('last_synced_at');
            });
        }
    }

    private function dropLegacyColumns(string $tableName, Blueprint $table): void
    {
        if ($this->hasIndex($tableName, "{$tableName}_legacy_id_index")) {
            $table->dropIndex("{$tableName}_legacy_id_index");
        }
        if ($this->hasIndex($tableName, "{$tableName}_last_synced_at_index")) {
            $table->dropIndex("{$tableName}_last_synced_at_index");
        }
        if ($this->hasIndex($tableName, "{$tableName}_legacy_id_unique")) {
            $table->dropUnique("{$tableName}_legacy_id_unique");
        }

        $columns = [];
        if (Schema::hasColumn($tableName, 'legacy_id')) {
            $columns[] = 'legacy_id';
        }
        if (Schema::hasColumn($tableName, 'last_synced_at')) {
            $columns[] = 'last_synced_at';
        }

        if (!empty($columns)) {
            $table->dropColumn($columns);
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $tableNameWithPrefix = $connection->getTablePrefix() . $table;
        $indexes = $connection->select("SHOW INDEX FROM `{$tableNameWithPrefix}` WHERE Key_name = ?", [$indexName]);

        return !empty($indexes);
    }
};
