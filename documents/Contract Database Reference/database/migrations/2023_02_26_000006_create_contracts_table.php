<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractsTable extends Migration
{
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('contract_number')->nullable();
            $table->string('cfda_number')->nullable();
            $table->longText('purpose')->nullable();
            $table->date('date_of_award')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->longText('notes')->nullable();
            $table->string('grant_code')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
