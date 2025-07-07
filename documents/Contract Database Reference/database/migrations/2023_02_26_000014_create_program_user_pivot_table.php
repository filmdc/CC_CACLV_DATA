<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramUserPivotTable extends Migration
{
    public function up()
    {
        Schema::create('program_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id', 'user_id_fk_7962704')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('program_id');
            $table->foreign('program_id', 'program_id_fk_7962704')->references('id')->on('programs')->onDelete('cascade');
        });
    }
}
