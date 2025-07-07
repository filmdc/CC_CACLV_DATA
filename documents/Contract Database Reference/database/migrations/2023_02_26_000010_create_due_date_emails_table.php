<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDueDateEmailsTable extends Migration
{
    public function up()
    {
        Schema::create('due_date_emails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email')->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
