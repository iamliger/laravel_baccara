<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BacaraAnalyticsController extends Controller
{
    public function analyze(Request $request)
    {
        // 1. 요청 유효성 검사
        $validated = $request->validate([
            'jokbo' => ['required', 'string'],
            'selectedLogic' => ['required', 'string', 'in:logic1,logic2,logic3,logic4'],
        ]);

        $jokbo = $validated['jokbo'];
        $logic = $validated['selectedLogic'];

        // 2. ★★★ 여기에 선생님의 핵심 분석 알고리즘이 들어갑니다. ★★★
        // 이 부분은 선생님의 기존 PHP 로직을 가져와서 변환해야 합니다.
        // 예시로, 간단한 더미(dummy) 결과를 반환하는 로직을 넣겠습니다.
        
        $recommendation = (rand(0, 1) == 0) ? 'P' : 'B'; // 랜덤으로 P 또는 B 추천
        $isWin = (substr($jokbo, -1) == $recommendation) ? 1 : -1; // 마지막 결과와 비교하여 승패 결정

        $dummyData = [
            'success' => true,
            'isWin' => $isWin,
            'data' => [
                // Logic 3 형태의 더미 데이터
                [
                    'isshow' => true,
                    'bettingpos' => $recommendation,
                    'sub_type' => '종합 예측'
                ]
            ]
        ];
        // ★★★ 분석 알고리즘 끝 ★★★

        // 3. 분석 결과를 JSON 형태로 반환합니다.
        return response()->json($dummyData);
    }
}
