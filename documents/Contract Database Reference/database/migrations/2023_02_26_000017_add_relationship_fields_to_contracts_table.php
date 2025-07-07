<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipFieldsToContractsTable extends Migration
{
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('funder_id')->nullable();
            $table->foreign('funder_id', 'funder_fk_7872217')->references('id')->on('funders');
        });
    }
}
