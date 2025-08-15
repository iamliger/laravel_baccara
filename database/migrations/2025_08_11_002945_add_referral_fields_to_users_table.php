<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferralFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // 나를 추천한 사람의 ID (상위 추천인)
            // users 테이블의 id를 참조하는 외래 키(Foreign Key)로 설정합니다.
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('users')->onDelete('set null');

            // 나의 고유 추천인 코드 (중복되면 안 됨)
            $table->string('recommendation_code')->unique()->nullable()->after('name');

            // 나의 수익률 (%)
            $table->decimal('profit_percentage', 5, 2)->default(0.00)->after('recommendation_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // 외래 키 제약 조건을 먼저 제거해야 컬럼 삭제가 가능합니다.
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'recommendation_code', 'profit_percentage']);
        });
    }
}
