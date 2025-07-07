<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractProgramPivotTable extends Migration
{
    public function up()
    {
        Schema::create('contract_program', function (Blueprint $table) {
            $table->unsignedBigInteger('contract_id');
            $table->foreign('contract_id', 'contract_id_fk_7872285')->references('id')->on('contracts')->onDelete('cascade');
            $table->unsignedBigInteger('program_id');
            $table->foreign('program_id', 'program_id_fk_7872285')->references('id')->on('programs')->onDelete('cascade');
        });
    }
}
