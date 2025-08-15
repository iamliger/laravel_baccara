<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLogic2SettingsToBaccaraConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('baccara_config', function (Blueprint $table) {
            $table->boolean('logic2_enabled')->default(true)->after('bc_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('baccara_config', function (Blueprint $table) {
            $table->dropColumn('logic2_enabled');
        });
    }
}
