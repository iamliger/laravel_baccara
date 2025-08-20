<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BacaraDb;

class MypageController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $bacaraData = BacaraDb::where('memberid', $user->name)->first();

        $logicUsageCounts = json_decode($request->input('logic_usage', '[]'), true);
        $primaryLogic = '데이터 없음';
        if (!empty($logicUsageCounts)) {
            arsort($logicUsageCounts);
            $primaryLogic = $this->formatLogicName(key($logicUsageCounts));
        }

        // ★★★ 1. 변수 이름을 'currentStreak'로 변경합니다. ★★★
        $statsData = [
            'user' => $user,
            'totalBets' => 0,
            'winRate' => '0.0%',
            'primaryLogic' => $primaryLogic,
            'bestLogic' => '데이터 없음',
            'maxWinStreak' => 0,
            'currentStreak' => ['type' => '-', 'count' => 0], // 기본값도 변경
        ];

        if ($bacaraData) {
            $pureJokbo = str_replace('T', '', $bacaraData->bcdata ?? '');
            $statsData['totalBets'] = strlen($pureJokbo);
            $virtualStats = $bacaraData->virtual_stats ?? [];
            
            if (!empty($virtualStats)) {
                list($integratedStats, $totalWins, $totalLosses) = $this->integrateStats($virtualStats);
                
                $grandTotal = $totalWins + $totalLosses;
                if ($grandTotal > 0) {
                    $statsData['winRate'] = number_format(($totalWins / $grandTotal) * 100, 1) . '%';
                }

                if (!empty($integratedStats)) {
                    uasort($integratedStats, function($a, $b) { return $b['winRate'] <=> $a['winRate']; });
                    $best = key($integratedStats);
                    $statsData['bestLogic'] = $best . ' (' . number_format($integratedStats[$best]['winRate'], 1) . '%)';
                }
            }
            
            $gameHistory = $bacaraData->game_history ?? [];
            if (!empty($gameHistory)) {
                $allStreaks = $this->calculateAllStreaks($gameHistory);
                $statsData['maxWinStreak'] = $allStreaks['maxWinStreak'];
                // ★★★ 2. 계산된 결과도 'currentStreak' 키에 할당합니다. ★★★
                $statsData['currentStreak'] = $allStreaks['currentStreak'];
            }
        }

        $view = view('mypage', $statsData);
        if ($request->ajax()) return $view;
        return view('layouts.app_mypage', ['content' => $view]);
    }

    /**
     * DB에 저장된 로직 이름을 보기 좋은 한글로 변환하는 헬퍼 함수
     */
    private function formatLogicName($logicName)
    {
        if (strpos($logicName, 'logic1_') === 0) return '로직 1 (통합)';
        if (strpos($logicName, 'logic4_') === 0) return '로직 4 (통합)';
        if ($logicName === 'AI_Prediction_AI') return 'AI 예측';
        return str_replace(['_'], [' '], str_replace('logic', '로직 ', $logicName));
    }

    private function integrateStats(array $stats): array
    {
        $logic1_wins = 0; $logic1_losses = 0;
        $logic4_wins = 0; $logic4_losses = 0;
        $other_stats = [];
        $integratedStats = [];

        foreach ($stats as $logicName => $result) {
            if (strpos($logicName, 'logic1_') === 0) {
                $logic1_wins += $result['wins'] ?? 0; $logic1_losses += $result['losses'] ?? 0;
            } elseif (strpos($logicName, 'logic4_') === 0) {
                $logic4_wins += $result['wins'] ?? 0; $logic4_losses += $result['losses'] ?? 0;
            } else {
                $other_stats[$logicName] = $result;
            }
        }

        $totalWins = 0; $totalLosses = 0;
        foreach ($other_stats as $logicName => $result) {
            $wins = $result['wins'] ?? 0; $losses = $result['losses'] ?? 0;
            $total = $wins + $losses;
            $winRate = ($total > 0) ? ($wins / $total) * 100 : 0;
            $totalWins += $wins; $totalLosses += $losses;
            $integratedStats[$this->formatLogicName($logicName)] = ['winRate' => $winRate];
        }

        if (($total = $logic1_wins + $logic1_losses) > 0) {
            $totalWins += $logic1_wins; $totalLosses += $logic1_losses;
            $integratedStats["로직 1 (통합)"] = ['winRate' => ($logic1_wins / $total) * 100];
        }
        if (($total = $logic4_wins + $logic4_losses) > 0) {
            $totalWins += $logic4_wins; $totalLosses += $logic4_losses;
            $integratedStats["로직 4 (통합)"] = ['winRate' => ($logic4_wins / $total) * 100];
        }
        
        return [$integratedStats, $totalWins, $totalLosses];
    }

    private function calculateAllStreaks(array $history): array
    {
        $maxWinStreak = 0;
        $currentStreakCount = 0;

        // 모든 로직의 승/패 기록을 하나의 배열로 합칩니다.
        $mergedHistory = [];
        foreach($history as $logicHistory) {
            $mergedHistory = array_merge($mergedHistory, $logicHistory);
        }
        
        // ★★★ 1. 데이터가 없을 때 반환하는 배열의 구조를 통일합니다. ★★★
        if(empty($mergedHistory)) {
            return [
                'maxWinStreak' => 0, 
                'currentStreak' => ['type' => '-', 'count' => 0]
            ];
        }

        // 최고 연승 계산
        $currentWinStreak = 0;
        foreach ($mergedHistory as $result) {
            if ($result === 'W') {
                $currentWinStreak++;
                $maxWinStreak = max($maxWinStreak, $currentWinStreak);
            } else {
                $currentWinStreak = 0;
            }
        }
        
        // 현재 연승/연패 계산
        $lastResult = end($mergedHistory);
        $reversedHistory = array_reverse($mergedHistory);
        foreach($reversedHistory as $result) {
            if($result === $lastResult) {
                $currentStreakCount++;
            } else {
                break;
            }
        }
        
        // ★★★ 2. 반환하는 배열의 키 이름을 'currentStreak'로 통일합니다. ★★★
        return [
            'maxWinStreak' => $maxWinStreak, 
            'currentStreak' => ['type' => ($lastResult === 'W' ? '승' : '패'), 'count' => $currentStreakCount]
        ];
    }
}
