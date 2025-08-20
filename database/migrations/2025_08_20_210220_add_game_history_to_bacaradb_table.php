<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGameHistoryToBacaradbTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bacaradb', function (Blueprint $table) {
            $table->json('game_history')->nullable()->after('virtual_stats');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bacaradb', function (Blueprint $table) {
            $table->dropColumn('game_history');
        });
    }
}
