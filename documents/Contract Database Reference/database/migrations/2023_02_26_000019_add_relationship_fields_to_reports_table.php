<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipFieldsToReportsTable extends Migration
{
    public function up()
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->foreign('contract_id', 'contract_fk_8055093')->references('id')->on('contracts');
            $table->unsignedBigInteger('due_date_id')->nullable();
            $table->foreign('due_date_id', 'due_date_fk_8055490')->references('id')->on('report_due_dates');
        });
    }
}
