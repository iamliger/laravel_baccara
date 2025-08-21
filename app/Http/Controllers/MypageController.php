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
        if (!empty($logicUsageCounts) && is_array($logicUsageCounts)) {
            arsort($logicUsageCounts);
            $primaryLogic = $this->formatLogicName(key($logicUsageCounts));
        }

        $consoleLogs = json_decode($request->input('console_logs', '[]'), true);
        $statsData = [
            'user' => $user,
            'totalBets' => 0,
            'winRate' => '0.0%',
            'primaryLogic' => $primaryLogic,
            'bestLogicByRate' => '데이터 없음',
            'bestLogicByProfit' => '데이터 없음',
            'totalProfit' => 0,
            'maxWinStreak' => 0,
            'currentStreak' => ['type' => '-', 'count' => 0], // 기본값도 변경
            'consoleLogs' => is_array($consoleLogs) ? $consoleLogs : [],
        ];

        if ($bacaraData) {
            $statsData['totalBets'] = strlen(str_replace('T', '', $bacaraData->bcdata ?? ''));
            $virtualStats = $bacaraData->virtual_stats ?? [];
            
            if (!empty($virtualStats)) {
                list($integratedStats, $totalWins, $totalLosses) = $this->integrateStats($virtualStats);
                
                if (($grandTotal = $totalWins + $totalLosses) > 0) {
                    $statsData['winRate'] = number_format(($totalWins / $grandTotal) * 100, 1) . '%';
                }

                if (!empty($integratedStats)) {
                    // ★★★ 1. 윌슨 신뢰도 점수 기준으로 최고 승률 로직 계산 ★★★
                    uasort($integratedStats, function($a, $b) { return $b['wilson_score'] <=> $a['wilson_score']; });
                    $bestByRate = key($integratedStats);
                    $statsData['bestLogicByRate'] = $bestByRate . ' (' . number_format($integratedStats[$bestByRate]['winRate'], 1) . '%)';

                    // 최고 수익 로직 계산
                    uasort($integratedStats, function($a, $b) { return $b['profit'] <=> $a['profit']; });
                    $bestByProfit = key($integratedStats);
                    $statsData['bestLogicByProfit'] = $bestByProfit . ' (' . number_format($integratedStats[$bestByProfit]['profit']) . '원)';
                    $statsData['totalProfit'] = array_sum(array_column($integratedStats, 'profit'));
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
        $integratedStats = [];
        $totalWins = 0; $totalLosses = 0;

        // 1. 로직 그룹별로 승, 패, 수익을 임시 변수에 합산합니다.
        $logic1_wins = 0; $logic1_losses = 0; $logic1_profit = 0;
        $logic4_wins = 0; $logic4_losses = 0; $logic4_profit = 0;
        $other_stats = [];

        foreach ($stats as $logicName => $result) {
            if (strpos($logicName, 'logic1_') === 0) {
                $logic1_wins += $result['wins'] ?? 0;
                $logic1_losses += $result['losses'] ?? 0;
                $logic1_profit += $result['profit'] ?? 0;
            } elseif (strpos($logicName, 'logic4_') === 0) {
                $logic4_wins += $result['wins'] ?? 0;
                $logic4_losses += $result['losses'] ?? 0;
                $logic4_profit += $result['profit'] ?? 0;
            } else {
                $other_stats[$logicName] = $result;
            }
        }

        // 2. 분리된 다른 로직들(2, 3, AI)을 먼저 처리합니다.
        // ★★★ 변수 이름을 $other_stats로 정확하게 수정했습니다. ★★★
        foreach($other_stats as $name => $stat) {
            $wins = $stat['wins'] ?? 0;
            $losses = $stat['losses'] ?? 0;
            $profit = $stat['profit'] ?? 0;
            $total = $wins + $losses;
            
            $totalWins += $wins;
            $totalLosses += $losses;

            $integratedStats[$this->formatLogicName($name)] = [
                'winRate' => ($total > 0) ? ($wins / $total) * 100 : 0,
                'profit' => $profit,
                'wilson_score' => $this->calculateWilsonScore($wins, $total)
            ];
        }

        // 3. 그룹화된 로직 1의 통합 통계를 계산합니다.
        if (($total = $logic1_wins + $logic1_losses) > 0) {
            $totalWins += $logic1_wins;
            $totalLosses += $logic1_losses;
            $integratedStats["로직 1 (통합)"] = [
                'winRate' => ($logic1_wins / $total) * 100,
                'profit' => $logic1_profit,
                'wilson_score' => $this->calculateWilsonScore($logic1_wins, $total)
            ];
        }

        // 4. 그룹화된 로직 4의 통합 통계를 계산합니다.
        if (($total = $logic4_wins + $logic4_losses) > 0) {
            $totalWins += $logic4_wins;
            $totalLosses += $logic4_losses;
            $integratedStats["로직 4 (통합)"] = [
                'winRate' => ($logic4_wins / $total) * 100,
                'profit' => $logic4_profit,
                'wilson_score' => $this->calculateWilsonScore($logic4_wins, $total)
            ];
        }
        
        return [$integratedStats, $totalWins, $totalLosses];
    }

    private function calculateWilsonScore(int $wins, int $total): float
    {
        if ($total === 0) return 0.0;
        $z = 1.96;
        $p = (float) $wins / $total;
        $score = ($p + $z*$z/(2*$total) - $z*sqrt(($p*(1-$p)+$z*$z/(4*$total))/$total)) / (1 + $z*$z/$total);
        return $score;
    }

    private function calculateAllStreaks(array $history): array
    {
        $maxWinStreak = 0;
        $currentStreakCount = 0;
        $mergedHistory = [];
        foreach($history as $logicHistory) {
            $mergedHistory = array_merge($mergedHistory, $logicHistory);
        }
        
        if(empty($mergedHistory)) {
            return [
                'maxWinStreak' => 0, 
                'currentStreak' => ['type' => '-', 'count' => 0]
            ];
        }

        $currentWinStreak = 0;
        foreach ($mergedHistory as $result) {
            if ($result === 'W') {
                $currentWinStreak++;
                $maxWinStreak = max($maxWinStreak, $currentWinStreak);
            } else {
                $currentWinStreak = 0;
            }
        }
        
        $lastResult = end($mergedHistory);
        $reversedHistory = array_reverse($mergedHistory);
        foreach($reversedHistory as $result) {
            if($result === $lastResult) {
                $currentStreakCount++;
            } else {
                break;
            }
        }
        
        // ★★★ 3. 반환하는 배열의 키 이름을 'currentStreak'로 변경합니다. ★★★
        return [
            'maxWinStreak' => $maxWinStreak, 
            'currentStreak' => ['type' => ($lastResult === 'W' ? '승' : '패'), 'count' => $currentStreakCount]
        ];
    }
}
