<?php

namespace App\Services;

use App\Models\BacaraDb;
use App\Models\BaccaraConfig;
use App\Models\Ticket3;
use App\Models\Ticket4;
use App\Models\Ticket5;
use App\Models\Ticket6;

class PredictionService
{
    /** N매에 따른 Ticket 모델 매핑 */
    const TICKET_MODELS = [
        3 => Ticket3::class,
        4 => Ticket4::class,
        5 => Ticket5::class,
        6 => Ticket6::class,
    ];

    /**
     * 메인 처리 메소드 (BacaraGame 컴포넌트에서 호출)
     */
    public function processTurn(BacaraDb $userDbState, string $selectedLogic, bool $isVirtualBetting): array
    {
        $updates = ['BacaraDb' => [], 'Ticket' => []];
        $predictionResult = null;

        if ($selectedLogic === 'logic1') {
            $logic1Result = $this->processLogic1($userDbState);
            $updates['BacaraDb'] = $logic1Result['db_updates']['BacaraDb'] ?? [];
            $updates['Ticket'] = $logic1Result['db_updates']['Ticket'] ?? [];
            $predictionResult = $logic1Result['prediction'];
        } 
        // --- ▼▼▼ 로직 2 처리 분기 추가 ▼▼▼ ---
        elseif ($selectedLogic === 'logic2') {
            // 새로 만들 processLogic2 메소드를 호출합니다.
            $logic2Result = $this->processLogic2($userDbState);
            // 로직 2는 현재 DB 업데이트 로직이 없으므로 prediction 결과만 처리합니다.
            $predictionResult = $logic2Result['prediction'];
        }
        // --- ▲▲▲ 로직 2 처리 분기 추가 ▲▲▲ ---

        return ['updates' => $updates, 'prediction' => $predictionResult];
    }

    // --- ▼▼▼ 로직 2 처리를 위한 새로운 메소드 추가 ▼▼▼ ---
    /**
     * 로직 2의 전체 과정을 처리
     */
    private function processLogic2(BacaraDb $userDbState): array
    {
        $jokbo = $userDbState->bcdata ?? '';
        $slen = strlen($jokbo);
        $allPredictions = [];

        // 1. DB의 baccara_config 테이블에서 로직 2 패턴들을 가져옵니다.
        $config = BaccaraConfig::first();
        $logic2_config = $config->logic2_patterns ?? [];
        $sequences = $logic2_config['sequences'] ?? [];

        // 2. 패턴이 하나도 설정되어 있지 않거나, 족보가 너무 짧으면 예측 없이 종료합니다.
        if (empty($sequences) || $slen < 1) {
            return ['prediction' => ['type' => 'logic2', 'predictions' => []]];
        }
        
        // 3. 모든 저장된 패턴을 순회하며 현재 족보와 일치하는지 확인합니다.
        foreach ($sequences as $index => $sequence) {
            $patternLength = count($sequence);

            // 현재 족보가 패턴보다 짧으면 확인할 필요가 없습니다.
            if ($slen < $patternLength) {
                continue;
            }

            // 족보의 마지막 부분에서 패턴 길이만큼 잘라내어 비교할 부분을 만듭니다.
            $jokboEndPart = substr($jokbo, -$patternLength);
            
            // 패턴 배열을 문자열로 변환합니다. (1 -> P, -1 -> B)
            $patternString = implode('', array_map(function($val) {
                return $val == 1 ? 'P' : 'B';
            }, $sequence));

            // 4. 족보의 끝 부분과 패턴이 일치하는지 확인합니다.
            if ($jokboEndPart === $patternString) {
                // 5. 일치하면, "꺽"는 원칙에 따라 다음 베팅을 추천합니다.
                //    (패턴의 마지막 결과와 반대로 추천)
                $lastPatternResult = substr($patternString, -1);
                $recommendation = $this->reverse($lastPatternResult);

                // 예측 결과 배열에 추가합니다.
                $allPredictions[] = [
                    'sub_type' => '패턴 '.($index + 1), // 예: "패턴 1"
                    'recommend' => $recommendation,
                    'step' => 1, // 로직 2는 단계 개념이 없으므로 1로 고정
                    'amount' => 1000, // 금액은 우선 고정값
                    'mae' => 0, // 로직 2는 매수 개념이 없으므로 0
                ];
            }
        }
        
        return [
            'prediction' => ['type' => 'logic2', 'predictions' => $allPredictions]
        ];
    }
    // --- ▲▲▲ 로직 2 처리를 위한 새로운 메소드 추가 ▲▲▲ ---

    /**
     * 로직 1의 전체 과정을 처리 (이 부분은 변경 없음)
     */
    private function processLogic1(BacaraDb $userDbState): array
    {
        $allDbUpdates = ['BacaraDb' => [], 'Ticket' => []];
        $allPredictions = [];
        $jokbo = $userDbState->bcdata ?? '';
        $slen = strlen($jokbo);

        if ($slen < 6) {
            return [
                'db_updates' => [],
                'prediction' => ['type' => 'logic1', 'predictions' => []]
            ];
        }

        for ($mae = 3; $mae <= 6; $mae++) {
            $patternField = "pattern_{$mae}";
            $currentPatterns = json_decode($userDbState->$patternField, true);
            if (!is_array($currentPatterns)) $currentPatterns = $this->getDefaultPatternState();
            
            $resultUpdates = $this->processExistingPattern($currentPatterns, $jokbo, $mae, $userDbState->memberid);
            $currentPatterns = $resultUpdates['updated_patterns'];
            if (!empty($resultUpdates['ticket_updates'])) {
                $allDbUpdates['Ticket'] = array_merge($allDbUpdates['Ticket'], $resultUpdates['ticket_updates']);
            }
            
            $newlyActivated = $this->checkForPatternActivation($jokbo, $mae);
            foreach ($newlyActivated as $index => $patternInfo) {
                $currentPatterns[$index] = $patternInfo;
            }

            foreach ($currentPatterns as $p) {
                if (($p['bettringround'] ?? 0) === ($slen + 1)) {
                    $allPredictions[] = [
                        'sub_type' => $p['bettingtype'],
                        'recommend' => $p['bettingpos'],
                        'step' => ($p['lose'] ?? 0) + 1,
                        'amount' => 1000,
                        'mae' => $p['measu'],
                    ];
                }
            }
            $allDbUpdates['BacaraDb'][$patternField] = json_encode($currentPatterns);
        }
        
        return [
            'db_updates' => $allDbUpdates,
            'prediction' => ['type' => 'logic1', 'predictions' => $allPredictions]
        ];
    }
    
    /**
     * 기존에 활성화된 패턴의 승/패를 처리 (이 부분은 변경 없음)
     */
    private function processExistingPattern(array $patterns, string $jokbo, int $mae, string $memberId): array
    {
        $ticketUpdates = [];
        $slen = strlen($jokbo);
        $lastResult = substr($jokbo, -1);
        if ($slen === 0) return ['updated_patterns' => $patterns, 'ticket_updates' => []];

        foreach ($patterns as $index => &$p) {
            if (($p['bettingtype'] ?? 'none') !== 'none' && ($p['bettringround'] ?? 0) === $slen) {
                $isWin = ($lastResult === $p['bettingpos']);
                
                $ticketUpdates[] = [
                    'model' => self::TICKET_MODELS[$mae],
                    'field' => $p['bettingtype'],
                    'is_win' => $isWin
                ];

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

    /**
     * calbaccara.php의 로직에 따라 새로운 패턴 발동 여부 검사 (이 부분은 변경 없음)
     */
    private function checkForPatternActivation(string $jokbo, int $mae): array
    {
        $slen = strlen($jokbo);
        if ($slen == 0) return [];
        $pos = $jokbo[$slen - 1];
        $activated = [];
        
        if ($mae === 3 && $slen >= $mae) {
            $compareIndex = $slen - 1 - $mae;
            if (isset($jokbo[$compareIndex]) && $pos === $jokbo[$compareIndex]) {
                $activated[0] = $this->createPatternState('_pattern', $pos, $mae, $slen + 1);
            }
        }

        $remain = $slen % $mae;

        if ($remain >= 1 && $slen >= ($mae * 2 + $remain)) {
            $mustPosIdx = $slen - ($mae * 2) -1;
            if (isset($jokbo[$mustPosIdx]) && $pos !== $jokbo[$mustPosIdx]) {
                $arrT = [$slen - ($mae*2)-2, $slen-$mae-2, $slen-$mae-1, $slen-2];
                $isMatch = true;
                foreach ($arrT as $idx) { if (!isset($jokbo[$idx]) || $jokbo[$idx] !== $pos) { $isMatch = false; break; } }
                if ($isMatch) $activated[1] = $this->createPatternState('tpattern', $this->reverse($pos), $mae, $slen + 1);
            }
        }
        
        if (($remain >= 2 || $remain === 0) && $slen >= ($mae*2 + ($remain == 0 ? $mae : $remain))) {
            $mustPosUIdx = $slen - $mae - 2;
            if (isset($jokbo[$mustPosUIdx]) && $pos !== $jokbo[$mustPosUIdx]) {
                $arrU = [$slen-($mae*2)-3, $slen-($mae*2)-2, $slen-$mae-2, $slen-3, $slen-2];
                $isMatch = true;
                foreach($arrU as $idx) { if(!isset($jokbo[$idx]) || $jokbo[$idx] !== $pos) { $isMatch = false; break; } }
                if ($isMatch) $activated[2] = $this->createPatternState('upattern', $this->reverse($pos), $mae, $slen + 1);
            }

            $mustPosNIdx = $slen - $mae - 1;
            if(isset($jokbo[$mustPosNIdx]) && $pos !== $jokbo[$mustPosNIdx]) {
                $arrN = [$slen-($mae*2)-3, $slen-($mae*2)-2, $slen-$mae-3, $slen-3, $slen-2];
                $isMatch = true;
                foreach($arrN as $idx) { if(!isset($jokbo[$idx]) || $jokbo[$idx] !== $pos) { $isMatch = false; break; } }
                if ($isMatch) $activated[3] = $this->createPatternState('npattern', $this->reverse($pos), $mae, $slen + 1);
            }
        }
        
        return $activated;
    }

    /**
     * 헬퍼 함수들 (이 부분은 변경 없음)
     */
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