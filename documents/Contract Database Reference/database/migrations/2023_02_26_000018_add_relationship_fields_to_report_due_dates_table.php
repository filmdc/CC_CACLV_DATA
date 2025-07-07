<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipFieldsToReportDueDatesTable extends Migration
{
    public function up()
    {
        Schema::table('report_due_dates', function (Blueprint $table) {
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->foreign('contract_id', 'contract_fk_7872313')->references('id')->on('contracts');
        });
    }
}
