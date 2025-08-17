<?php

namespace App\Services;

use App\Models\BacaraDb;
use App\Models\BaccaraConfig;
use App\Models\Ticket3;
use App\Models\Ticket4;
use App\Models\Ticket5;
use App\Models\Ticket6;
use Illuminate\Support\Facades\Log;

class PredictionService
{
    const TICKET_MODELS = [
        3 => Ticket3::class,
        4 => Ticket4::class,
        5 => Ticket5::class,
        6 => Ticket6::class,
    ];

    protected BaccaratScorer $scorer;

    public function __construct(BaccaratScorer $scorer)
    {
        $this->scorer = $scorer;
    }

    public function processTurn(BacaraDb $userDbState, string $selectedLogic, bool $isVirtualBetting): array
    {
        $updates = ['BacaraDb' => [], 'Ticket' => []];
        $predictionResult = null;

        if ($selectedLogic === 'logic1') {
            $logicResult = $this->processLogic1($userDbState);
            $updates = $logicResult['db_updates'] ?? [];
            $predictionResult = $logicResult['prediction'];
        } elseif ($selectedLogic === 'logic2') {
            $logicResult = $this->processLogic2($userDbState);
            $predictionResult = $logicResult['prediction'];
        } elseif ($selectedLogic === 'logic3') {
            $logicResult = $this->processLogic3($userDbState);
            $predictionResult = $logicResult['prediction'];
        } elseif ($selectedLogic === 'logic4') {
            $logicResult = $this->processLogic4($userDbState);
            $predictionResult = $logicResult['prediction'];
        }

        return ['updates' => $updates, 'prediction' => $predictionResult];
    }
    
    // (로직 2, 3, 4 관련 메소드는 변경 없음)
    private function processLogic2(BacaraDb $userDbState): array
    {
        return $this->processConfigBasedLogicPrefixTracking($userDbState, 'logic2');
    }

    private function processLogic3(BacaraDb $userDbState): array
    {
        return $this->processConfigBasedLogicPrefixTracking($userDbState, 'logic3');
    }

    private function processConfigBasedLogicPrefixTracking(BacaraDb $userDbState, string $logicType): array
    {
        $jokbo = $userDbState->bcdata ?? '';
        $slen = strlen($jokbo);
        $allPredictions = [];

        $config = BaccaraConfig::first();
        $logic_config = $config->{$logicType.'_patterns'} ?? [];
        $sequences = $logic_config['sequences'] ?? [];

        if (empty($sequences) || $slen < 1) {
            return ['prediction' => ['type' => $logicType, 'predictions' => []]];
        }
        
        foreach ($sequences as $index => $sequence) {
            $patternLength = count($sequence);
            for ($matchLength = 1; $matchLength < $patternLength; $matchLength++) {
                if ($slen < $matchLength) continue;
                $jokboEndPart = substr($jokbo, -$matchLength);
                $patternStartPart = array_slice($sequence, 0, $matchLength);
                $patternStartString = implode('', array_map(fn($val) => $val == 1 ? 'P' : 'B', $patternStartPart));

                if ($jokboEndPart === $patternStartString) {
                    $nextStepValue = $sequence[$matchLength];
                    $recommendation = ($nextStepValue == 1) ? 'P' : 'B';

                    $allPredictions[] = [
                        'sub_type' => '패턴 '.($index + 1),
                        'recommend' => $recommendation,
                        'step' => $matchLength + 1,
                        'amount' => 1000,
                        'mae' => 0,
                    ];
                }
            }
        }
        
        return ['prediction' => ['type' => $logicType, 'predictions' => $allPredictions]];
    }
    
    private function processLogic4(BacaraDb $userDbState): array
    {
        $jokbo = $userDbState->bcdata ?? '';
        $allPredictions = [];
        $roadsForP = $this->scorer->getAllRoads($jokbo . 'P');
        $roadsForB = $this->scorer->getAllRoads($jokbo . 'B');
        $derivedRoads = ['big_eye' => '3매', 'small' => '4매', 'cockroach' => '5매'];
        foreach ($derivedRoads as $key => $name) {
            $lastColorForP = !empty($roadsForP[$key]) ? end($roadsForP[$key])['color'] : null;
            $lastColorForB = !empty($roadsForB[$key]) ? end($roadsForB[$key])['color'] : null;
            if ($lastColorForP === 'red' && $lastColorForB === 'blue') {
                $allPredictions[] = ['sub_type' => $name, 'recommend' => 'P', 'step' => 1, 'amount' => 1000, 'mae' => 0];
            } elseif ($lastColorForP === 'blue' && $lastColorForB === 'red') {
                $allPredictions[] = ['sub_type' => $name, 'recommend' => 'B', 'step' => 1, 'amount' => 1000, 'mae' => 0];
            }
        }
        return ['prediction' => ['type' => 'logic4', 'predictions' => $allPredictions]];
    }

    private function processLogic1(BacaraDb $userDbState): array
    {
        $allDbUpdates = ['BacaraDb' => [], 'Ticket' => []];
        $allPredictions = [];
        $jokbo = $userDbState->bcdata ?? '';
        $slen = strlen($jokbo);

        if ($slen < 6) {
            return [ 'db_updates' => [], 'prediction' => ['type' => 'logic1', 'predictions' => []] ];
        }
        
        if (config('app.baccara_debug')) {
            Log::debug("==================================================");
            Log::debug("로직 1 검사 시작 | 족보: {$jokbo} (길이: {$slen})");
            Log::debug("==================================================");
        }

        for ($sidx = 3; $sidx <= 6; $sidx++) {
            if (($sidx === 4 && $slen < 8) || ($sidx === 5 && $slen < 10) || ($sidx === 6 && $slen < 12)) {
                continue;
            }

            $patternField = "pattern_{$sidx}";
            $patterndb = $this->getDefaultPatternState();
            $saved_patterns = json_decode($userDbState->$patternField, true);
            if (is_array($saved_patterns)) {
                foreach ($saved_patterns as $i => $pattern) {
                    if (isset($patterndb[$i])) $patterndb[$i] = $pattern;
                }
            }
            
            if (config('app.baccara_debug')) {
                Log::debug("--- {$sidx}매 검사 ---");
                Log::debug(" [1. 시작 전 상태]: " . json_encode($patterndb));
            }

            $resultUpdates = $this->processExistingPattern($patterndb, $jokbo, $sidx, $userDbState->memberid);
            $patterndb = $resultUpdates['updated_patterns'];
            if (config('app.baccara_debug')) {
                Log::debug(" [2. 승/패 처리 후]: " . json_encode($patterndb));
            }

            $newlyActivated = $this->checkForPatternActivation($jokbo, $sidx);
            if (config('app.baccara_debug')) {
                Log::debug(" [3. 신규 발동 패턴]: " . json_encode($newlyActivated));
            }
            
            foreach ($newlyActivated as $index => $patternInfo) {
                if (isset($patterndb[$index]) && ($patterndb[$index]['bettingtype'] ?? 'none') === 'none') {
                    $patterndb[$index] = $patternInfo;
                }
            }
            if (config('app.baccara_debug')) {
                Log::debug(" [4. 최종 상태]: " . json_encode($patterndb));
            }

            foreach ($patterndb as $p) {
                if (($p['bettringround'] ?? 0) === ($slen + 1)) {
                    $stepIndex = $p['lose'] ?? 0;
                    $moneySteps = json_decode($userDbState->coininfo, true) ?? [];
                    $amount = $moneySteps[$stepIndex] ?? 1000; // 단계에 맞는 금액, 없으면 기본값 1000

                    $allPredictions[] = [
                        'sub_type' => $p['bettingtype'], 'recommend' => $p['bettingpos'],
                        'step' => $stepIndex + 1, 'amount' => $amount, 'mae' => $p['measu'],
                    ];
                }
            }
            $allDbUpdates['BacaraDb'][$patternField] = json_encode($patterndb);
        }
        
        if (config('app.baccara_debug')) {
            Log::debug(" [최종 예측 결과]: " . json_encode($allPredictions));
            Log::debug("==================================================");
        }
        
        return [ 'db_updates' => $allDbUpdates, 'prediction' => ['type' => 'logic1', 'predictions' => $allPredictions]];
    }
    
    // --- ▼▼▼ 여기가 이번 수정의 핵심입니다. checkForPatternActivation의 _pattern 부분을 수정합니다. ▼▼▼ ---
    private function checkForPatternActivation(string $jokbo, int $sidx): array
    {
        $slen = strlen($jokbo);
        $pos = substr($jokbo, -1);
        $last_idx = $slen - 1;
        $activated = [];
        
        // _pattern (독립 검사)
        if ($sidx === 3 && ($slen % $sidx === 0) && $slen >= ($sidx * 2)) {
            $compareIndex = $last_idx - $sidx;
            if (isset($jokbo[$compareIndex]) && $jokbo[$compareIndex] === $pos) {
                $activated[0] = $this->createPatternState('_pattern', $pos, $sidx, $slen + 1);
            }
        }
        
        // (tpattern, upattern, npattern 로직은 이전과 동일하게 유지)
        $remain = $slen % $sidx;
        if ($remain == 0) $remain = $sidx;

        if ($remain >= 1 && $slen >= ($sidx * 2 + $remain)) {
            $mustPosT = $slen - ($sidx * 2);
            if (isset($jokbo[$mustPosT]) && $jokbo[$mustPosT] !== $pos) {
                 $arrT = [$slen - ($sidx * 2) - 1, $slen - $sidx - 1, $slen - $sidx, $slen - 1];
                 $isMatch = true;
                 foreach ($arrT as $idx) { if (!isset($jokbo[$idx]) || $jokbo[$idx] != $pos) { $isMatch = false; break; } }
                 if ($isMatch) {
                    $activated[1] = $this->createPatternState('tpattern', $this->reverse($pos), $sidx, $slen + 1);
                 }
            }
        }
        
        if (($remain >= 2 || $remain === 0) && $slen >= ($sidx * 2 + $remain)) {
            // upattern
            $mustPosU = $slen - $sidx - 2;
            if (isset($jokbo[$mustPosU]) && $jokbo[$mustPosU] !== $pos) {
                $arrU = [$slen - ($sidx * 2) - 2, $slen - ($sidx * 2) - 1, $slen - $sidx - 1, $slen - 2, $slen - 1];
                $isMatch = true;
                foreach ($arrU as $idx) { if (!isset($jokbo[$idx]) || $jokbo[$idx] != $pos) { $isMatch = false; break; } }
                if ($isMatch) {
                    $activated[2] = $this->createPatternState('upattern', $this->reverse($pos), $sidx, $slen + 1);
                }
            }

            // npattern
            $mustPosN = $slen - $sidx - 1;
             if (isset($jokbo[$mustPosN]) && $jokbo[$mustPosN] !== $pos) {
                $arrN = [$slen - ($sidx * 2) - 2, $slen - ($sidx * 2) - 1, $slen - $sidx - 2, $slen - 2, $slen - 1];
                $isMatch = true;
                foreach ($arrN as $idx) { if (!isset($jokbo[$idx]) || $jokbo[$idx] != $pos) { $isMatch = false; break; } }
                if ($isMatch) {
                    $activated[3] = $this->createPatternState('npattern', $this->reverse($pos), $sidx, $slen + 1);
                }
            }
        }

        return $activated;
    }
    
    
    
    // (나머지 헬퍼 메소드들은 변경 없음)
    private function processExistingPattern(array $patterns, string $jokbo, int $mae, string $memberId): array
    {
        $ticketUpdates = [];
        $slen = strlen($jokbo);
        $lastResult = substr($jokbo, -1);
        if ($slen === 0) return ['updated_patterns' => $patterns, 'ticket_updates' => []];

        foreach ($patterns as $index => &$p) {
            if (($p['bettingtype'] ?? 'none') !== 'none' && ($p['bettringround'] ?? 0) === $slen) {
                $isWin = ($lastResult === $p['bettingpos']);
                
                $ticketUpdates[] = [ 'model' => self::TICKET_MODELS[$mae], 'field' => $p['bettingtype'], 'is_win' => $isWin ];

                if ($isWin) {
                    $p = $this->getDefaultPatternState(1)[0];
                } else {
                    $p['lose'] = ($p['lose'] ?? 0) + 1;
                    if ($p['lose'] >= 9) $p['lose'] = 0;
                    $p['bettringround'] = $slen + 1;
                }
            }
        }
        return ['updated_patterns' => $patterns, 'ticket_updates' => $ticketUpdates];
    }    
    
    private function getDefaultPatternState(int $count = 4): array {
        return array_fill(0, $count, ["bettingtype" => "none", "bettringround" => 0, "bettingpos" => "", "isshow" => false, "lose" => 0, "measu" => 0, "icon" => ""]);
    }

    private function createPatternState(string $type, string $pos, int $mae, int $round): array {
        return ["bettingtype" => $type, "bettingpos" => $pos, "isshow" => false, "measu" => $mae, "bettringround" => $round, "lose" => 0, "icon" => ""];
    }

    private function reverse(string $value): string {
        return ($value === 'P') ? 'B' : 'P';
    }
}