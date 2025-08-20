<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\BacaraDb;
use App\Services\PredictionService;
use App\Models\BaccaraConfig;
use App\Models\Ticket3;
use App\Models\Ticket4;
use App\Models\Ticket5;
use App\Models\Ticket6;


class BacaraGame extends Component
{
    public string $jokboHistory = '';
    public int $playerCount = 0;
    public int $bankerCount = 0;
    public int $totalCount = 0;
    public array $moneyArrStep = [];
    
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
        'logic2_state', // ★★★ 이 줄을 추가하세요. ★★★
    ];

    protected $casts = [
        // JSON으로 저장된 텍스트를 자동으로 PHP 배열/객체로 변환
        'coininfo' => 'array',
        'chartResult' => 'array',
        'pattern_stats' => 'array',
        'logic_state' => 'array',
        'logic3_patterns' => 'array',
        'analytics_data' => 'array',
        'pattern_3' => 'array',
        'pattern_4' => 'array',
        'pattern_5' => 'array',
        'pattern_6' => 'array',
        'virtual_stats' => 'array',
        'logic2_state' => 'array',
    ];

    // ★★★ undoRequest가 이제 파라미터를 받으므로 리스너에서 제거합니다. ★★★
    protected $listeners = ['addResultRequest', /*'undoRequest',*/ 'resetRequest', 'setCoinInfoRequest'];

    public function mount()
    {       
        $this->loadGameFromDB();
    }

    public function addResultRequest(string $type, string $selectedLogic, bool $isVirtualBetting, PredictionService $predictionService)
    {
        if (($type !== 'P' && $type !== 'B') || !Auth::check()) return;

        $user = Auth::user();
        $userDbState = BacaraDb::firstOrCreate(['memberid' => $user->name], ['bcdata' => '']);
        
        $userDbState->bcdata .= $type;
        $this->jokboHistory = $userDbState->bcdata;

        // 1. PredictionService를 호출하여 결과를 받습니다.
        $result = $predictionService->processTurn($userDbState, $selectedLogic, $isVirtualBetting);
        
        // ★★★ 2. 'updates' 또는 'db_updates' 키를 안전하게 가져옵니다. ★★★
        // $result['updates']가 있으면 그것을 사용하고, 없으면(??) $result['db_updates']를 사용합니다.
        // 둘 다 없으면 안전하게 빈 배열([])을 사용합니다.
        $updates = $result['updates'] ?? $result['db_updates'] ?? [];
        
        // 3. 안전하게 가져온 $updates 변수를 사용하여 DB 업데이트를 진행합니다.
        $this->applyUpdates($updates, $user->name);
        
        // (이후 로직은 동일)
        foreach ($updates['BacaraDb'] ?? [] as $field => $value) {
            $userDbState->setAttribute($field, $value)->save();
        }
        
        $this->updateCounts();

        $this->emit('itemAdded', [
            'type' => $type,
            'counts' => [
                'player' => $this->playerCount,
                'banker' => $this->bankerCount,
                'total' => $this->totalCount,
            ]
        ]);
        
        if (!empty($result['prediction'])) {
            $this->emit('predictionUpdated', $result['prediction']);
        }
    }
    
    public function undoRequest(string $selectedLogic, PredictionService $predictionService)
    {
        if (strlen($this->jokboHistory) > 0 && Auth::check()) {
            // 1. 족보를 한 글자 줄입니다.
            $this->jokboHistory = substr($this->jokboHistory, 0, -1);
            $this->updateAndSaveJokbo();
            $this->updateCounts();
            
            // 2. 클라이언트 UI 업데이트를 위해 이벤트를 보냅니다.
            $this->emit('itemRemoved', [
                'counts' => [
                    'player' => $this->playerCount,
                    'banker' => $this->bankerCount,
                    'total' => $this->totalCount,
                ]
            ]);

            // 3. ★★★ 짧아진 족보를 기준으로 예측을 다시 실행합니다. ★★★
            $userDbState = BacaraDb::firstWhere('memberid', Auth::user()->name);
            $result = $predictionService->processTurn($userDbState, $selectedLogic, false);

            /*$this->applyUpdates($result['updates'], Auth::user()->name);
            foreach ($result['updates']['BacaraDb'] ?? [] as $field => $value) {
                $userDbState->$field = $value;
            }
            $userDbState->save();*/
            
            // ★★★ 핵심 수정: 예측 결과가 비어있더라도, null 값을 담아 이벤트를 '반드시' 발생시킵니다.
            $this->emit('predictionUpdated', $result['prediction'] ?? null);
        }
    }
    
    public function resetRequest()
    {
        if (Auth::check()) {
            $this->jokboHistory = '';
            $this->moneyArrStep = [];
            $this->updateAndSaveJokbo(true);
            $this->updateCounts();
            $this->emit('gameReset');
        }
    }
    
    public function setCoinInfoRequest(int $startAmount)
    {
        if ($startAmount <= 0 || !Auth::check()) return;

        $config = BaccaraConfig::first();
        $rates = $config->profit_rate ?? [1, 2, 4, 8, 16, 32, 64]; 
        if(!is_array($rates) || empty($rates)) {
            $rates = [1, 2, 4, 8, 16, 32, 64];
        }

        $this->moneyArrStep = array_map(fn($rate) => $startAmount * $rate, $rates);

        BacaraDb::updateOrCreate(
            ['memberid' => Auth::user()->name],
            ['coininfo' => json_encode($this->moneyArrStep)]
        );
        
        $this->emit('coinInfoUpdated', $this->moneyArrStep);
    }
    
    private function updateAndSaveJokbo($isReset = false)
    {
        $updateData = ['bcdata' => $this->jokboHistory];
        
        if ($isReset) {
            $emptyPatterns = json_encode(array_fill(0, 4, [
                "bettingtype" => "none", "bettringround" => 0, "bettingpos" => "",
                "isshow" => false, "lose" => 0, "measu" => 0, "icon" => ""
            ]));

            $updateData['pattern_3'] = $emptyPatterns;
            $updateData['pattern_4'] = $emptyPatterns;
            $updateData['pattern_5'] = $emptyPatterns;
            $updateData['pattern_6'] = $emptyPatterns;

            // ★★★ 핵심 수정: NULL 대신 "빈 JSON 배열"을 할당합니다. ★★★
            $updateData['coininfo'] = '[]'; 
            
            // logic_state와 virtual_stats는 NULL을 허용한다고 가정하고 그대로 둡니다.
            // 만약 이 필드들도 같은 오류를 일으킨다면, 각각 '[]'로 변경해주어야 합니다.
            $updateData['logic_state'] = null;
            $updateData['virtual_stats'] = null;
            $updateData['logic2_state'] = null;
        }

        BacaraDb::updateOrCreate(['memberid' => Auth::user()->name], $updateData);
    }
    
    private function applyUpdates(array $updates, string $memberId): void
    {
        foreach ($updates['Ticket'] ?? [] as $update) {
            $modelClass = $update['model'];
            $fieldName = $update['field'];
            $isWin = $update['is_win'];

            if(class_exists($modelClass)) {
                $record = $modelClass::firstOrCreate(['memberid' => $memberId]);
                $stats = json_decode($record->$fieldName, true);
                if (!is_array($stats)) $stats = ['win' => 0, 'lose' => 0, 'remwin' => 0, 'remlose' => 0];

                if ($isWin) {
                    $stats['win']++; $stats['remwin']++; $stats['lose'] = 0;
                } else {
                    $stats['lose']++;
                    if ($stats['lose'] >= 9) { $stats['lose'] = 0; $stats['remlose']++; }
                }
                $record->$fieldName = json_encode($stats);
                $record->save();
            }
        }
    }

    private function loadGameFromDB()
    {
        $user = Auth::user();
        if ($user) {
            $savedGame = BacaraDb::firstWhere('memberid', $user->name);
            if ($savedGame) {
                $this->jokboHistory = $savedGame->bcdata ?? '';
                $this->moneyArrStep = json_decode($savedGame->coininfo, true) ?? [];
            }
            if (empty($this->moneyArrStep)) {
                $this->emit('showCoinInfoModal');
            }
        }
        $this->updateCounts();
    }
    
    private function updateCounts()
    {
        $this->playerCount = substr_count($this->jokboHistory, 'P');
        $this->bankerCount = substr_count($this->jokboHistory, 'B');
        $this->totalCount = strlen($this->jokboHistory);
    }

    public function render()
    {
        // 이제 이 render 메소드는 최초 페이지 로드 시에만 호출됩니다.
        // 버튼 클릭과 같은 상호작용에서는 더 이상 호출되지 않습니다.
        return view('livewire.bacara-game');
    }
}
