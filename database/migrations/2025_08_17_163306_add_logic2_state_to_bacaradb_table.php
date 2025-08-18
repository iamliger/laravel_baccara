<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLogic2StateToBacaradbTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bacaradb', function (Blueprint $table) {
            $table->text('logic2_state')->nullable()->after('analytics_data');
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
            $table->dropColumn('logic2_state');
        });
    }
}
