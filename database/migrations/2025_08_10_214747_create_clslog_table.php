<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClslogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clslog', function (Blueprint $table) {
            $table->id('idx');
            $table->string('gubun', 50)->nullable()->comment('로그 구분');
            $table->text('log')->nullable()->comment('로그 내용');
            $table->timestamp('log_datetime')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clslog');
    }
}
