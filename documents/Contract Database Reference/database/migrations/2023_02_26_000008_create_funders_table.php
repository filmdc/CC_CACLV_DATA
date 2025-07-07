<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundersTable extends Migration
{
    public function up()
    {
        Schema::create('funders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('funder_name')->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
