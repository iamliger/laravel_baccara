<?php

namespace App\Services;

use App\Models\BacaraDb;
use App\Models\BaccaraConfig;
use App\Models\Ticket3;
use App\Models\Ticket4;
use App\Models\Ticket5;
use App\Models\Ticket6;
use App\Services\BaccaratScorer;
use Illuminate\Support\Facades\Auth;
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
        $jokbo = $userDbState->bcdata ?? '';
        $lastPos = substr($jokbo, -1);
        $updates = ['BacaraDb' => []]; // Ticket 업데이트는 이제 사용하지 않음

        // 가상 배팅이 켜져 있을 때 (다중 분석 모드)
        if ($isVirtualBetting && strlen($jokbo) > 1) {
            if (config('app.baccara_debug')) {
                Log::debug("==================================================");
                Log::debug("가상 배팅 모드 실행 | 현재 족보: {$jokbo}");
                Log::debug("==================================================");
            }
            
            $allLogics = ['logic1', 'logic2', 'logic3', 'logic4'];
            $mainPredictionResult = null;
            $previousJokbo = substr($jokbo, 0, -1);
            
            // 1. 이전 족보 상태를 기준으로 모든 로직의 '지난 예측'을 미리 계산합니다.
            $previousPredictions = [];
            foreach($allLogics as $logicName) {
                $tempUserDbState = clone $userDbState;
                $tempUserDbState->bcdata = $previousJokbo; 
                $result = $this->runSingleLogic($tempUserDbState, $logicName, false);
                $previousPredictions[$logicName] = $result['prediction']['predictions'] ?? [];
            }

            // 2. 'AI 예측'의 지난 예측도 logic_state에서 가져옵니다.
            $all_logic_states = $userDbState->logic_state ?? [];
            $ai_last_pred = $all_logic_states['ai_prediction']['last_recommend'] ?? null;
            if ($ai_last_pred) {
                 // 다른 로직들과 동일한 구조로 맞춰서 배열에 추가
                 $previousPredictions['AI_Prediction'][] = [
                     'recommend' => $ai_last_pred,
                     'sub_type' => 'AI' // 차트 라벨을 위한 이름 지정
                 ];
            }

            $userDbState->bcdata = $jokbo; // 족보를 다시 원상 복구합니다.

            // 2. '지난 예측'과 '현재 결과'를 비교하여 가상 통계를 업데이트합니다.
            $currentVirtualStats = $userDbState->virtual_stats ?? [];
            $lastPos = substr($jokbo, -1);
            foreach($previousPredictions as $logicName => $predictions) {
                foreach($predictions as $prediction) {
                    $this->updateVirtualStats($currentVirtualStats, $logicName, $prediction, $lastPos);
                }
            }
            $updates['BacaraDb']['virtual_stats'] = $currentVirtualStats;

            $logicResult = $this->runSingleLogic($userDbState, $selectedLogic, true);
            $mainPredictionResult = $logicResult['prediction'];
            if ($selectedLogic === 'logic1' && isset($logicResult['db_updates']['BacaraDb'])) {
                 unset($logicResult['db_updates']['BacaraDb']['virtual_stats']);
            }
            $updates = array_merge_recursive($updates, $logicResult['db_updates']);

            if (config('app.baccara_debug')) Log::debug("==================================================");
            return ['updates' => $updates, 'prediction' => $mainPredictionResult];

        // 가상 배팅이 꺼져 있을 때 (단일 모드)
        } else {
            return $this->runSingleLogic($userDbState, $selectedLogic, true);
        }
    }

    private function runSingleLogic(BacaraDb $userDbState, string $logicName, bool $should_update_stats): array
    {
        if ($logicName === 'logic1') {
            return $this->processLogic1($userDbState);
        } elseif ($logicName === 'logic2') {
            return $this->processLogic2($userDbState, $should_update_stats);
        } elseif ($logicName === 'logic3') {
            return $this->processLogic3($userDbState, $should_update_stats);
        } elseif ($logicName === 'logic4') {
            return $this->processLogic4($userDbState, $should_update_stats);
        }
        return ['updates' => [], 'prediction' => null];
    }

    private function updateVirtualStats(array &$stats, string $logicName, array $prediction, string $lastPos)
    {
        $predictedPos = $prediction['recommend'] ?? null;
        if (!$predictedPos) return;

        // 로직1,4는 sub_type을 포함하여 더 상세하게 기록
        $statKey = ($logicName === 'logic1' || $logicName === 'logic4') 
                    ? "{$logicName}_{$prediction['sub_type']}" 
                    : $logicName;

        // 해당 키가 없으면 초기화
        if (!isset($stats[$statKey])) {
            $stats[$statKey] = ['wins' => 0, 'losses' => 0];
        }

        $isWin = ($predictedPos === $lastPos);
        if ($isWin) {
            $stats[$statKey]['wins']++;
            if (config('app.baccara_debug')) Log::debug(" [가상 통계] {$statKey}: 승리 (예측:{$predictedPos}, 실제:{$lastPos})");
        } else {
            $stats[$statKey]['losses']++;
            if (config('app.baccara_debug')) Log::debug(" [가상 통계] {$statKey}: 패배 (예측:{$predictedPos}, 실제:{$lastPos})");
        }
    }
    
    private function processLogic2(BacaraDb $userDbState): array
    {
        $jokbo = $userDbState->bcdata ?? '';
        $updates = [];

        $pb_jokbo_chars = str_split(str_replace('T', '', $jokbo));
        if (empty($pb_jokbo_chars)) {
            return ['prediction' => ['type' => 'logic2', 'predictions' => []], 'db_updates' => []];
        }
        $anchor_char = end($pb_jokbo_chars);

        $patterns = [
            'A' => ['name' => 'pattern1', 'sequence' => [1, 1, -1, -1, 1, 1, -1]],
            'B' => ['name' => 'pattern2', 'sequence' => [-1, -1, 1, 1, -1, -1, 1]]
        ];

        $all_logic_states_before = $userDbState->logic_state;
        // ★★★ 안전장치: $all_logic_states_before가 배열이 아니면, 빈 배열로 강제 초기화합니다.
        if (!is_array($all_logic_states_before)) {
            $all_logic_states_before = [];
        }
        $logic2_states = $all_logic_states_before['logic2'] ?? [];
        
        $next_states = [];
        $allPredictions = [];
        $coininfo = $userDbState->coininfo;
        $moneySteps = is_string($coininfo) ? json_decode($coininfo, true) : $coininfo;
        if (!is_array($moneySteps)) $moneySteps = [];

        foreach ($patterns as $key => $pattern_info) {
            // ★★★ 각 패턴의 lose 카운터도 불러옵니다.
            $current_state = $logic2_states[$key] ?? ['step' => 0, 'lose' => 0, 'last_prediction' => null];
            
            $updated_info = $this->calculateNextStateLogic2(
                $current_state, $anchor_char, $pattern_info['sequence'], $jokbo
            );
            
            // ★★★ 다음 상태에 lose 카운터도 저장합니다.
            $next_states[$key] = [
                'step' => $updated_info['step'],
                'lose' => $updated_info['lose'],
                'last_prediction' => $updated_info['next_prediction']
            ];
            
            // ★★★ lose 카운터를 기준으로 amount와 step을 계산합니다.
            $current_lose = $updated_info['lose'];
            $amount = $moneySteps[$current_lose] ?? 1000;

            $allPredictions[] = [
                "sub_type" => $pattern_info['name'],
                "recommend" => $updated_info['next_prediction'],
                "step" => $current_lose + 1, // 단계는 lose + 1
                "amount" => $amount,
                "mae" => 0,
            ];
        }

        $all_logic_states_before['logic2'] = $next_states;
        $updates['BacaraDb']['logic_state'] = $all_logic_states_before;
        
        return [
            'prediction' => ['type' => 'logic2', 'predictions' => $allPredictions],
            'db_updates' => $updates
        ];
    }
    
    /**
     * 로직 2의 다음 상태를 계산하는 헬퍼 함수 (새로운 코드 완벽 포팅)
     */
    private function calculateNextStateLogic2(array $current_state, string $anchor_char, array $sequence, string $jokbo): array
    {
        $lastPos = substr($jokbo, -1);
        $current_step = $current_state['step'] ?? 0;
        $current_lose = $current_state['lose'] ?? 0; // ★★★ 현재 lose 값을 불러옵니다.
        $last_prediction = $current_state['last_prediction'] ?? null;
        
        $next_step = $current_step;
        $next_lose = $current_lose; // ★★★ 다음 lose 값을 초기화합니다.

        if ($last_prediction !== null && $lastPos !== 'T') {
            if ($last_prediction === $lastPos) {
                // 승리: step과 lose를 0으로 리셋
                $next_step = 0;
                $next_lose = 0;
            } else {
                // 패배: step을 1 증가, lose도 1 증가
                $next_step = $current_step + 1;
                $next_lose = $current_lose + 1;
            }
        }
        
        if ($next_step >= count($sequence)) {
            $next_step = 0;
        }
    
        $next_prediction_value = $sequence[$next_step];
        $next_prediction_char = ($next_prediction_value === 1) ? $anchor_char : $this->reverse($anchor_char);
    
        // ★★★ 반환값에 'lose'를 추가합니다. ★★★
        return [
            'step' => $next_step,
            'lose' => $next_lose, 
            'next_prediction' => $next_prediction_char
        ];
    }

    /**
     * 로직 3: 저장된 10개 패턴 중, 확률(과거 승률)이 가장 높은 것 하나만 추천
     */
    private function processLogic3(BacaraDb $userDbState, bool $should_update_stats): array
    {
        if (config('app.baccara_debug')) Log::debug("--- 로직 3 검사 시작 ---");

        $jokbo = $userDbState->bcdata ?? '';
        $updates = [];
        
        $pb_jokbo_chars = str_split(str_replace('T', '', $jokbo));
        if (empty($pb_jokbo_chars)) {
            if (config('app.baccara_debug')) Log::debug(" [logic3] 족보가 비어있어 예측을 생성하지 않습니다.");
            return ['prediction' => ['type' => 'logic3', 'predictions' => []], 'db_updates' => []];
        }
        $anchor_char = end($pb_jokbo_chars);
        $lastPos = substr($jokbo, -1);

        $config = BaccaraConfig::first();
        $logic_config = $config->logic3_patterns ?? [];
        $sequences = $logic_config['sequences'] ?? [];
        if (empty($sequences)) {
            if (config('app.baccara_debug')) Log::debug(" [logic3] DB에 설정된 패턴이 없습니다.");
            return ['prediction' => ['type' => 'logic3', 'predictions' => []], 'db_updates' => []];
        }
        $pattern_count = count($sequences);

        //$all_logic_states_before = $userDbState->logic_state ?? [];
        $all_logic_states_before = $userDbState->logic_state;
        // ★★★ 안전장치: $all_logic_states_before가 배열이 아니면, 빈 배열로 강제 초기화합니다.
        if (!is_array($all_logic_states_before)) {
            $all_logic_states_before = [];
        }
        $logic3_states = $all_logic_states_before['logic3'] ?? [];
        
        // 1. '이전' 상태를 정확히 불러옵니다.
        $last_final_prediction = $logic3_states['final_prediction'] ?? null;
        $current_lose = $logic3_states['lose'] ?? 0;
        
        if (config('app.baccara_debug')) Log::debug(" [logic3] [1. 시작 전 상태]: lose={$current_lose}, last_prediction={$last_final_prediction}");
        
        // 2. 종합 예측의 승/패를 판단하고, 다음 lose 카운트를 결정합니다.
        $next_lose = $current_lose;
        if ($last_final_prediction !== null && $lastPos !== 'T') {
            if ($last_final_prediction === $lastPos) {
                // 승리: lose 카운트를 0으로 리셋
                $next_lose = 0;
                if (config('app.baccara_debug')) Log::debug(" [logic3] [승패 판정]: 승리! lose 카운트를 0으로 리셋합니다.");
            } else {
                // 패배: lose 카운트를 1 증가
                $next_lose = $current_lose + 1;
                if (config('app.baccara_debug')) Log::debug(" [logic3] [승패 판정]: 패배! lose 카운트가 " . ($next_lose) . "(으)로 증가합니다.");
            }
        }

        // 3. 모든 개별 패턴의 다음 예측과 다음 상태를 계산합니다.
        $all_next_predictions = [];
        $next_individual_states = []; 

        foreach ($sequences as $index => $sequence) {
            $pattern_key = "pattern_{$index}";
            $current_state = $logic3_states[$pattern_key] ?? ['step' => 0, 'last_prediction' => null];
            
            // --- 헬퍼 함수 로직을 여기에 직접 구현 ---
            $current_step = $current_state['step'] ?? 0;
            $last_prediction = $current_state['last_prediction'] ?? null;
            $next_step = $current_step;

            if ($last_prediction !== null && $lastPos !== 'T') {
                if ($last_prediction === $lastPos) $next_step = 0; // 승리 시 step 리셋
                else $next_step = $current_step + 1; // 패배 시 step 증가
            }
            if ($next_step >= count($sequence)) $next_step = 0;
        
            $next_prediction_value = $sequence[$next_step];
            $next_prediction_char = ($next_prediction_value == 1) ? $anchor_char : $this->reverse($anchor_char);
            // --- 로직 구현 끝 ---

            $all_next_predictions[] = $next_prediction_char;
            $next_individual_states[$pattern_key] = [
                'step' => $next_step,
                'last_prediction' => $next_prediction_char
            ];
        }
        if (config('app.baccara_debug')) Log::debug(" [logic3] [개별 예측 목록]: " . implode(', ', $all_next_predictions));
        
        $predictions = [];
        $next_final_prediction = null;

        if (!empty($all_next_predictions)) {
            $counts = array_count_values(array_filter($all_next_predictions));
            if (!empty($counts)) {
                arsort($counts);
                $next_final_prediction = key($counts);
                $prediction_percentage = ($counts[$next_final_prediction] / $pattern_count) * 100;
                
                // 4. 확률 100% 방지 로직
                if ($prediction_percentage === 100.0) {
                    $prediction_percentage = 99.9;
                }

                if (config('app.baccara_debug')) Log::debug(" [logic3] [다수결 결과]: {$next_final_prediction} ( {$counts[$next_final_prediction]} / {$pattern_count}표 )");
                
                // 5. 단계별 금액 적용
                $coininfo = $userDbState->coininfo;
                $moneySteps = is_string($coininfo) ? json_decode($coininfo, true) : $coininfo;
                if (!is_array($moneySteps)) $moneySteps = [];
                $amount = $moneySteps[$next_lose] ?? 1000; // '다음' lose 카운트에 맞는 금액

                $predictions[] = [
                    "sub_type" => sprintf("종합 예측 (%d/%d, %.1f%%)", $counts[$next_final_prediction], $pattern_count, $prediction_percentage),
                    "recommend" => $next_final_prediction,
                    "step" => $next_lose + 1, // '다음' 단계는 lose + 1
                    "amount" => $amount,
                    "mae" => 0,
                ];
            }
        }
        
        // 6. DB에 저장할 최종 상태를 준비합니다.
        $next_states_to_save = $next_individual_states;
        $next_states_to_save['final_prediction'] = $next_final_prediction;
        $next_states_to_save['lose'] = $next_lose; // 업데이트된 lose 카운트 저장
        $all_logic_states_before['logic3'] = $next_states_to_save;
        $updates['BacaraDb']['logic_state'] = $all_logic_states_before;
        
        return [
            'prediction' => ['type' => 'logic3', 'predictions' => $predictions],
            'db_updates' => $updates
        ];
    }  
    
    private function processLogic4(BacaraDb $userDbState, bool $should_update_stats): array
    {
        if (config('app.baccara_debug')) Log::debug("--- 로직 4 검사 시작 ---");

        $jokbo = $userDbState->bcdata ?? '';
        $updates = [];

        $pb_string = str_replace('T', '', $jokbo);
        $pb_len = strlen($pb_string);
        $lastPos = substr($jokbo, -1);

        if ($pb_len < 1) {
            if (config('app.baccara_debug')) Log::debug(" [logic4] 순수 족보가 비어있어 예측을 생성하지 않습니다.");
            return ['prediction' => ['type' => 'logic4', 'predictions' => []], 'db_updates' => []];
        }

        // 1. DB에서 로직 4의 '이전 상태'를 불러옵니다.
        //$all_logic_states_before = $userDbState->logic_state ?? [];
        $all_logic_states_before = $userDbState->logic_state;
        // ★★★ 안전장치: $all_logic_states_before가 배열이 아니면, 빈 배열로 강제 초기화합니다.
        if (!is_array($all_logic_states_before)) {
            $all_logic_states_before = [];
        }
        $logic4_states = $all_logic_states_before['logic4'] ?? [];
        
        // ★★★ 2. '매'별로 이전 예측과 lose 카운터를 개별적으로 가져옵니다. ★★★        
        $last_pred_3mae = $logic4_states['pred_3mae'] ?? null;
        $lose_3mae = $logic4_states['lose_3mae'] ?? 0;

        $last_pred_4mae = $logic4_states['pred_4mae'] ?? null;
        $lose_4mae = $logic4_states['lose_4mae'] ?? 0;
        
        $last_pred_5mae = $logic4_states['pred_5mae'] ?? null;
        $lose_5mae = $logic4_states['lose_5mae'] ?? 0;

        if (config('app.baccara_debug')) {
            Log::debug(" [logic4] [이전 상태]: 3매(lose={$lose_3mae}, pred={$last_pred_3mae}), 4매(lose={$lose_4mae}, pred={$last_pred_4mae}), 5매(lose={$lose_5mae}, pred={$last_pred_5mae})");
        }
        
        // ★★★ 3. '매'별로 승/패를 독립적으로 판단하고 다음 lose 카운터를 계산합니다. ★★★
        $next_lose_3mae = $lose_3mae;
        if ($last_pred_3mae && $lastPos !== 'T') {
            $next_lose_3mae = ($last_pred_3mae === $lastPos) ? 0 : $lose_3mae + 1;
        }

        $next_lose_4mae = $lose_4mae;
        if ($last_pred_4mae && $lastPos !== 'T') {
            $next_lose_4mae = ($last_pred_4mae === $lastPos) ? 0 : $lose_4mae + 1;
        }

        $next_lose_5mae = $lose_5mae;
        if ($last_pred_5mae && $lastPos !== 'T') {
            $next_lose_5mae = ($last_pred_5mae === $lastPos) ? 0 : $lose_5mae + 1;
        }
        
        if (config('app.baccara_debug')) {
             Log::debug(" [logic4] [다음 상태]: 3매(lose={$next_lose_3mae}), 4매(lose={$next_lose_4mae}), 5매(lose={$next_lose_5mae})");
        }

        // 4. '매'별로 새로운 예측을 생성합니다.
        $allPredictions = [];
        $next_states = [];
        $coininfo = $userDbState->coininfo;
        $moneySteps = is_string($coininfo) ? json_decode($coininfo, true) : $coininfo;
        if (!is_array($moneySteps)) $moneySteps = [];

        // 3매 예측
        if ($pb_len >= 2) {
            $pred_3mae = ($pb_string[$pb_len - 1] === $pb_string[$pb_len - 2]) ? 'B' : 'P';
            $next_states['pred_3mae'] = $pred_3mae;
            $amount_3mae = $moneySteps[$next_lose_3mae] ?? 1000;
            $allPredictions[] = [ "sub_type" => "3매", "recommend" => $pred_3mae, "step" => $next_lose_3mae + 1, "amount" => $amount_3mae, "mae" => 3 ];
        }
        
        // 4매 예측
        if ($pb_len >= 3) {
            $pred_4mae = ($pb_string[$pb_len - 1] === $pb_string[$pb_len - 3]) ? 'B' : 'P';
            $next_states['pred_4mae'] = $pred_4mae;
            $amount_4mae = $moneySteps[$next_lose_4mae] ?? 1000;
            $allPredictions[] = [ "sub_type" => "4매", "recommend" => $pred_4mae, "step" => $next_lose_4mae + 1, "amount" => $amount_4mae, "mae" => 4 ];
        }

        // 5매 예측
        if ($pb_len >= 4) {
            $pred_5mae = ($pb_string[$pb_len - 1] === $pb_string[$pb_len - 4]) ? 'B' : 'P';
            $next_states['pred_5mae'] = $pred_5mae;
            $amount_5mae = $moneySteps[$next_lose_5mae] ?? 1000;
            $allPredictions[] = [ "sub_type" => "5매", "recommend" => $pred_5mae, "step" => $next_lose_5mae + 1, "amount" => $amount_5mae, "mae" => 5 ];
        }
        if (config('app.baccara_debug')) Log::debug(" [logic4] [최종 예측 결과]: " . json_encode($allPredictions));

        // ★★★ 5. '매'별로 계산된 모든 상태를 DB에 저장할 준비를 합니다. ★★★
        $next_states['lose_3mae'] = $next_lose_3mae;
        $next_states['lose_4mae'] = $next_lose_4mae;
        $next_states['lose_5mae'] = $next_lose_5mae;
        
        $all_logic_states_before['logic4'] = $next_states;
        $updates['BacaraDb']['logic_state'] = $all_logic_states_before;
        
        return [
            'prediction' => ['type' => 'logic4', 'predictions' => $allPredictions],
            'db_updates' => $updates
        ];
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
            $saved_patterns = $userDbState->$patternField;
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
                    
                    $coininfo = $userDbState->coininfo;
                    $moneySteps = is_string($coininfo) ? json_decode($coininfo, true) : $coininfo;
                    if (!is_array($moneySteps)) $moneySteps = [];

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