<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportDueDatesTable extends Migration
{
    public function up()
    {
        Schema::create('report_due_dates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('due_date');
            $table->string('status');
            $table->longText('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
