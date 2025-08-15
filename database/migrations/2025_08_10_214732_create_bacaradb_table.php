<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBacaradbTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bacaradb', function (Blueprint $table) {
            $table->id('idx');
            $table->string('memberid', 50)->default('0')->unique('unique_memberid');
            $table->string('dayinfo', 10)->nullable()->default('0');
            $table->string('bcdata', 1000);
            $table->string('basetable', 1000);
            $table->string('pattern_3', 1000)->comment('3Pattern');
            $table->string('pattern_4', 1000)->comment('4Pattern');
            $table->string('pattern_5', 1000)->comment('5Pattern');
            $table->string('pattern_6', 1000)->comment('6Pattern');
            $table->string('ptn', 1000);
            $table->text('ptnhistory');
            $table->string('baseresult', 1000)->default('');
            $table->string('coininfo', 1000)->default('');
            $table->longText('chartResult')->nullable();
            $table->text('pattern_stats')->nullable();
            $table->text('logic_state')->nullable();
            $table->text('logic3_patterns')->nullable();
            $table->longText('analytics_data')->nullable()->comment('각 로직의 성과 분석 데이터 (JSON)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bacaradb');
    }
}
