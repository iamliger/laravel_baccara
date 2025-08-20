<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BacaraDb;
use Illuminate\Support\Facades\Auth;

class ChartController extends Controller
{
    public function getVirtualStats()
    {
        $user = Auth::user();
        $bacaraData = BacaraDb::where('memberid', $user->name)->first();

        if (!$bacaraData || empty($bacaraData->virtual_stats)) {
            return response()->json([
                'success' => true, 'labels' => [],
                'datasets' => [['label' => '승률(%)', 'data' => [], 'wins' => [], 'losses' => [], 'backgroundColor' => []]]
            ]);
        }

        $stats = $bacaraData->virtual_stats;
        $labels = []; $winRates = []; $winsData = []; $lossesData = [];
        
        // --- ▼▼▼ 여기가 이번 수정의 핵심입니다. ▼▼▼ ---
        $logic1_wins = 0; $logic1_losses = 0;
        $logic4_wins = 0; $logic4_losses = 0;
        $other_stats = [];

        // 1. 로직 1, 4와 그 외 로직들을 분리합니다.
        foreach ($stats as $logicName => $result) {
            if (strpos($logicName, 'logic1_') === 0) {
                $logic1_wins += $result['wins'] ?? 0;
                $logic1_losses += $result['losses'] ?? 0;
            } elseif (strpos($logicName, 'logic4_') === 0) {
                $logic4_wins += $result['wins'] ?? 0;
                $logic4_losses += $result['losses'] ?? 0;
            }
            else {
                $other_stats[$logicName] = $result;
            }
        }

        // 2. 분리된 다른 로직들을 먼저 처리합니다.
        foreach ($other_stats as $logicName => $result) {
            $formattedName = str_replace(['_'], [' '], str_replace('logic', '로직 ', $logicName));
            $wins = $result['wins'] ?? 0;
            $losses = $result['losses'] ?? 0;
            $total = $wins + $losses;
            $winRate = ($total > 0) ? round(($wins / $total) * 100, 1) : 0;
            
            $labels[] = $formattedName;
            $winRates[] = $winRate;
            $winsData[] = $wins;
            $lossesData[] = $losses;
        }
        
        // 3. 누적된 로직 1의 평균 승률을 계산하고 추가합니다.
        $logic1_total = $logic1_wins + $logic1_losses;
        if ($logic1_total > 0) {
            $labels[] = "로직 1 (통합)";
            $winRates[] = round(($logic1_wins / $logic1_total) * 100, 1);
            $winsData[] = $logic1_wins;
            $lossesData[] = $logic1_losses;
        }

        // (로직 4 통합 로직은 이전 답변과 동일하게 유지)
        $logic4_total = $logic4_wins + $logic4_losses;
        if ($logic4_total > 0) {
            $labels[] = "로직 4 (통합)";
            $winRates[] = round(($logic4_wins / $logic4_total) * 100, 1);
            $winsData[] = $logic4_wins;
            $lossesData[] = $logic4_losses;
        }
        // --- ▲▲▲ 여기가 이번 수정의 핵심입니다. ---

        return response()->json([
            'success' => true,
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => '승률(%)',
                    'data' => $winRates, 'wins' => $winsData, 'losses' => $lossesData,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.7)', 'rgba(239, 68, 68, 0.7)',
                        'rgba(16, 185, 129, 0.7)', 'rgba(249, 115, 22, 0.7)',
                        'rgba(139, 92, 246, 0.7)', 'rgba(236, 72, 153, 0.7)'
                    ]
                ]
            ]
        ]);
    }
}
