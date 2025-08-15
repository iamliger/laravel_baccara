<?php

namespace App\Services;

use App\Models\BacaraDb;
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

        return ['updates' => $updates, 'prediction' => $predictionResult];
    }

    /**
     * 로직 1의 전체 과정을 처리
     */
    private function processLogic1(BacaraDb $userDbState): array
    {
        $allDbUpdates = ['BacaraDb' => [], 'Ticket' => []];
        $allPredictions = [];
        $jokbo = $userDbState->bcdata ?? '';
        $slen = strlen($jokbo);

        // ★★★ calbaccara.php와 동일한 최소 조건 검사 추가 ★★★
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
                    // ★★★ JavaScript가 사용하는 키 이름으로 변환하여 추가 ★★★
                    $allPredictions[] = [
                        'sub_type' => $p['bettingtype'],
                        'recommend' => $p['bettingpos'],
                        'step' => ($p['lose'] ?? 0) + 1,
                        'amount' => 1000, // 금액은 우선 고정값
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
     * 기존에 활성화된 패턴의 승/패를 처리
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
     * calbaccara.php의 로직에 따라 새로운 패턴 발동 여부 검사
     */
    private function checkForPatternActivation(string $jokbo, int $mae): array
    {
        $slen = strlen($jokbo);
        if ($slen == 0) return [];
        $pos = $jokbo[$slen - 1];
        $activated = [];

        // ★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★
        // ★★★ 바로 이 부분! 인덱스 계산을 정확하게 수정했습니다. ★★★
        // ★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★★
        
        // _pattern (3매 전용)
        if ($mae === 3 && $slen >= $mae) {
            // calbaccara: $bcdata[($slen-1) - $sidx] -> (현재길이-1)-3 = 현재길이-4
            $compareIndex = $slen - 1 - $mae;
            if (isset($jokbo[$compareIndex]) && $pos === $jokbo[$compareIndex]) {
                $activated[0] = $this->createPatternState('_pattern', $pos, $mae, $slen + 1);
            }
        }

        $remain = $slen % $mae;

        // tpattern
        if ($remain >= 1 && $slen >= ($mae * 2 + $remain)) {
            $mustPosIdx = $slen - ($mae * 2) -1;
            if (isset($jokbo[$mustPosIdx]) && $pos !== $jokbo[$mustPosIdx]) {
                $arrT = [$slen - ($mae*2)-2, $slen-$mae-2, $slen-$mae-1, $slen-2];
                $isMatch = true;
                foreach ($arrT as $idx) { if (!isset($jokbo[$idx]) || $jokbo[$idx] !== $pos) { $isMatch = false; break; } }
                if ($isMatch) $activated[1] = $this->createPatternState('tpattern', $this->reverse($pos), $mae, $slen + 1);
            }
        }
        
        // upattern & npattern
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
     * 헬퍼 함수들
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