<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDueDateEmailReportDueDatePivotTable extends Migration
{
    public function up()
    {
        Schema::create('due_date_email_report_due_date', function (Blueprint $table) {
            $table->unsignedBigInteger('report_due_date_id');
            $table->foreign('report_due_date_id', 'report_due_date_id_fk_7872292')->references('id')->on('report_due_dates')->onDelete('cascade');
            $table->unsignedBigInteger('due_date_email_id');
            $table->foreign('due_date_email_id', 'due_date_email_id_fk_7872292')->references('id')->on('due_date_emails')->onDelete('cascade');
        });
    }
}
