<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Create6ticketTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('6ticket', function (Blueprint $table) {
            $table->id('idx');
            $table->string('memberid', 50)->default('0')->unique();
            $table->string('_pattern', 100)->nullable()->default('0');
            $table->string('tpattern', 100)->nullable()->default('0');
            $table->string('upattern', 100)->nullable()->default('0');
            $table->string('npattern', 100)->nullable()->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('6ticket');
    }
}
