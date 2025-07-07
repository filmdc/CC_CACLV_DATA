<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMicrosoftEventIdToReportDueDatesTable extends Migration
{
    public function up()
    {
        Schema::table('report_due_dates', function (Blueprint $table) {
            // Adding a nullable string field for the Microsoft event ID
            $table->string('microsoft_event_id')->nullable()->after('description');
        });
    }

    public function down()
    {
        Schema::table('report_due_dates', function (Blueprint $table) {
            // Dropping the column if the migration is rolled back
            $table->dropColumn('microsoft_event_id');
        });
    }
}
