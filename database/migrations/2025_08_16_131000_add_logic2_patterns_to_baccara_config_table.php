<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLogic2PatternsToBaccaraConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('baccara_config', function (Blueprint $table) {
            // logic3_patterns 컬럼 뒤에 로직 2 패턴을 저장할 텍스트(JSON) 컬럼을 추가합니다.
            $table->text('logic2_patterns')->nullable()->after('logic3_patterns');
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
            $table->dropColumn('logic2_patterns');
        });
    }
}
