<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BacaraDb extends Model
{
    use HasFactory;

    /**
     * 모델과 연결된 테이블 이름
     * @var string
     */
    protected $table = 'bacaradb';

    /**
     * 테이블의 기본 키(Primary Key)
     * @var string
     */
    protected $primaryKey = 'idx';

    /**
     * created_at 및 updated_at 타임스탬프를 사용하지 않음
     * @var bool
     */
    public $timestamps = false;

    /**
     * 대량 할당이 가능한 속성
     * @var array
     */
    protected $fillable = [
        'memberid',
        'dayinfo',
        'bcdata',
        'basetable',
        'pattern_3',
        'pattern_4',
        'pattern_5',
        'pattern_6',
        'ptn',
        'ptnhistory',
        'baseresult',
        'coininfo',
        'chartResult',
        'pattern_stats',
        'logic_state',
        'logic3_patterns',
        'analytics_data',
        'virtual_stats',
        'logic2_state',
        'game_history',
    ];

    /**
     * 네이티브 타입으로 캐스팅해야 하는 속성
     * @var array
     */
    protected $casts = [
        // JSON으로 저장된 텍스트를 자동으로 PHP 배열/객체로 변환
        'coininfo' => 'array',
        'chartResult' => 'array',
        'pattern_stats' => 'array',
        'logic_state' => 'array',
        'logic3_patterns' => 'array',
        'analytics_data' => 'array',
        'virtual_stats' => 'array',
        'pattern_3' => 'array',
        'pattern_4' => 'array',
        'pattern_5' => 'array',
        'pattern_6' => 'array',
        'logic2_state' => 'array',
        'game_history' => 'array',
    ];

    /**
     * User 모델과의 관계 (BacaraDb는 User에 속한다)
     */
    public function user()
    {
        // memberid 컬럼과 users 테이블의 name 컬럼을 기준으로 연결
        return $this->belongsTo(User::class, 'memberid', 'name');
    }
}
