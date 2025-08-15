<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBaccaraConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('baccara_config', function (Blueprint $table) {
            $table->integer('bc_id')->default(1)->primary();
            $table->text('logic3_patterns')->nullable();
            $table->text('profit_rate')->nullable();
            $table->string('another_setting')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('baccara_config');
    }
}
