<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVirtualStatsToBacaradbTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bacaradb', function (Blueprint $table) {
            $table->json('virtual_stats')->nullable()->after('analytics_data');
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
            $table->dropColumn('virtual_stats');
        });
    }
}
