<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogic2PatternsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logic2_patterns', function (Blueprint $table) {
            $table->id(); // 각 패턴의 고유 ID
            $table->string('name')->unique(); // 패턴의 이름 (예: "패턴 A", "패턴 B")
            $table->string('description')->nullable(); // 패턴에 대한 설명
            $table->json('sequence'); // 패턴 규칙 (예: [1, -1, 1, 1])을 JSON 형태로 저장
            $table->boolean('is_active')->default(true); // 이 패턴을 사용할지 여부 (On/Off)
            $table->timestamps(); // 생성 및 수정 시간 자동 기록
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logic2_patterns');
    }
}
