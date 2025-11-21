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
        // Based on the query results, only fix the ones that have NO ACTION
        
        // Fix custom_import_logs table
        if (Schema::hasTable('custom_import_logs') && Schema::hasColumn('custom_import_logs', 'user_id')) {
            Schema::table('custom_import_logs', function (Blueprint $table) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Throwable $e) {
                    // Foreign key might already be updated; ignore
                }
                // Make column nullable to support onDelete('set null')
                $table->unsignedBigInteger('user_id')->nullable()->change();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Fix contractor_documents table
        if (Schema::hasTable('contractor_documents') && Schema::hasColumn('contractor_documents', 'uploaded_by')) {
            Schema::table('contractor_documents', function (Blueprint $table) {
                try {
                    $table->dropForeign(['uploaded_by']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Fix contractors table
        if (Schema::hasTable('contractors')) {
            Schema::table('contractors', function (Blueprint $table) {
                foreach (['created_by', 'updated_by'] as $column) {
                    if (Schema::hasColumn('contractors', $column)) {
                        try {
                            $table->dropForeign([$column]);
                        } catch (\Throwable $e) {
                            // ignore
                        }
                        $table->foreign($column)->references('id')->on('users')->onDelete('set null');
                    }
                }
            });
        }

        // Fix housing_jobs table
        if (Schema::hasTable('housing_jobs')) {
            Schema::table('housing_jobs', function (Blueprint $table) {
                foreach (['approved_by', 'created_by', 'updated_by'] as $column) {
                    if (Schema::hasColumn('housing_jobs', $column)) {
                        try {
                            $table->dropForeign([$column]);
                        } catch (\Throwable $e) {
                            // ignore
                        }
                        $table->foreign($column)->references('id')->on('users')->onDelete('set null');
                    }
                }
            });
        }

        // Fix job_contact_ledgers table
        if (Schema::hasTable('job_contact_ledgers') && Schema::hasColumn('job_contact_ledgers', 'created_by')) {
            Schema::table('job_contact_ledgers', function (Blueprint $table) {
                try {
                    $table->dropForeign(['created_by']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Fix job_document_checklists table
        if (Schema::hasTable('job_document_checklists')) {
            Schema::table('job_document_checklists', function (Blueprint $table) {
                foreach (['created_by', 'uploaded_by', 'verified_by'] as $column) {
                    if (Schema::hasColumn('job_document_checklists', $column)) {
                        try {
                            $table->dropForeign([$column]);
                        } catch (\Throwable $e) {
                            // ignore
                        }
                        $table->foreign($column)->references('id')->on('users')->onDelete('set null');
                    }
                }
            });
        }

        // Fix properties table
        if (Schema::hasTable('properties')) {
            Schema::table('properties', function (Blueprint $table) {
                foreach (['created_by', 'updated_by'] as $column) {
                    if (Schema::hasColumn('properties', $column)) {
                        try {
                            $table->dropForeign([$column]);
                        } catch (\Throwable $e) {
                            // ignore
                        }
                        $table->foreign($column)->references('id')->on('users')->onDelete('set null');
                    }
                }
            });
        }

        // Fix property_documents table
        if (Schema::hasTable('property_documents') && Schema::hasColumn('property_documents', 'uploaded_by')) {
            Schema::table('property_documents', function (Blueprint $table) {
                try {
                    $table->dropForeign(['uploaded_by']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Fix property_notes table
        if (Schema::hasTable('property_notes') && Schema::hasColumn('property_notes', 'created_by')) {
            Schema::table('property_notes', function (Blueprint $table) {
                try {
                    $table->dropForeign(['created_by']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert all changes back to NO ACTION
        
        Schema::table('custom_import_logs', function (Blueprint $table) {
            if (Schema::hasColumn('custom_import_logs', 'user_id')) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->foreign('user_id')->references('id')->on('users');
            }
        });

        Schema::table('contractor_documents', function (Blueprint $table) {
            if (Schema::hasColumn('contractor_documents', 'uploaded_by')) {
                try {
                    $table->dropForeign(['uploaded_by']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->foreign('uploaded_by')->references('id')->on('users');
            }
        });

        Schema::table('contractors', function (Blueprint $table) {
            foreach (['created_by', 'updated_by'] as $column) {
                if (Schema::hasColumn('contractors', $column)) {
                    try {
                        $table->dropForeign([$column]);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                    $table->foreign($column)->references('id')->on('users');
                }
            }
        });

        Schema::table('housing_jobs', function (Blueprint $table) {
            foreach (['approved_by', 'created_by', 'updated_by'] as $column) {
                if (Schema::hasColumn('housing_jobs', $column)) {
                    try {
                        $table->dropForeign([$column]);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                    $table->foreign($column)->references('id')->on('users');
                }
            }
        });

        Schema::table('job_contact_ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('job_contact_ledgers', 'created_by')) {
                try {
                    $table->dropForeign(['created_by']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->foreign('created_by')->references('id')->on('users');
            }
        });

        Schema::table('job_document_checklists', function (Blueprint $table) {
            foreach (['created_by', 'uploaded_by', 'verified_by'] as $column) {
                if (Schema::hasColumn('job_document_checklists', $column)) {
                    try {
                        $table->dropForeign([$column]);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                    $table->foreign($column)->references('id')->on('users');
                }
            }
        });

        Schema::table('properties', function (Blueprint $table) {
            foreach (['created_by', 'updated_by'] as $column) {
                if (Schema::hasColumn('properties', $column)) {
                    try {
                        $table->dropForeign([$column]);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                    $table->foreign($column)->references('id')->on('users');
                }
            }
        });

        Schema::table('property_documents', function (Blueprint $table) {
            if (Schema::hasColumn('property_documents', 'uploaded_by')) {
                try {
                    $table->dropForeign(['uploaded_by']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->foreign('uploaded_by')->references('id')->on('users');
            }
        });

        Schema::table('property_notes', function (Blueprint $table) {
            if (Schema::hasColumn('property_notes', 'created_by')) {
                try {
                    $table->dropForeign(['created_by']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->foreign('created_by')->references('id')->on('users');
            }
        });
    }
};
