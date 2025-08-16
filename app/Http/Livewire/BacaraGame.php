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

    protected $listeners = ['addResultRequest', 'undoRequest', 'resetRequest', 'setCoinInfoRequest'];

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

        $result = $predictionService->processTurn($userDbState, $selectedLogic, $isVirtualBetting);
        $this->applyUpdates($result['updates'], $user->name);
        
        foreach ($result['updates']['BacaraDb'] ?? [] as $field => $value) {
            $userDbState->$field = $value;
        }
        $userDbState->save();
        
        $this->updateCounts();

        // --- ▼▼▼ 핵심 변경 사항 ▼▼▼ ---
        // 1. 새로 추가된 아이템의 정보와 최신 카운트를 'itemAdded' 이벤트로 보냅니다.
        $this->emit('itemAdded', [
            'type' => $type,
            'counts' => [
                'player' => $this->playerCount,
                'banker' => $this->bankerCount,
                'total' => $this->totalCount,
            ]
        ]);
        
        // 2. 예측 결과는 별도의 'predictionUpdated' 이벤트로 보냅니다.
        if (!empty($result['prediction'])) {
            $this->emit('predictionUpdated', $result['prediction']);
        }
    }
    
    public function undoRequest()
    {
        if (strlen($this->jokboHistory) > 0 && Auth::check()) {
            $this->jokboHistory = substr($this->jokboHistory, 0, -1);
            $this->updateAndSaveJokbo();
            $this->updateCounts();
            
            // --- ▼▼▼ 핵심 변경 사항 ▼▼▼ ---
            // 'itemRemoved' 이벤트를 보내 클라이언트 UI를 업데이트합니다.
            $this->emit('itemRemoved', [
                'counts' => [
                    'player' => $this->playerCount,
                    'banker' => $this->bankerCount,
                    'total' => $this->totalCount,
                ]
            ]);
        }
    }
    
    public function resetRequest()
    {
        if (Auth::check()) {
            $this->jokboHistory = '';
            $this->moneyArrStep = [];
            $this->updateAndSaveJokbo(true);
            $this->updateCounts();

            // --- ▼▼▼ 핵심 변경 사항 ▼▼▼ ---
            // 'gameReset' 이벤트를 보내 클라이언트에서 모든 것을 초기화하도록 합니다.
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
            $updateData['pattern_3'] = '[]'; $updateData['pattern_4'] = '[]'; $updateData['pattern_5'] = '[]'; $updateData['pattern_6'] = '[]';
            $updateData['logic_state'] = null;
            $updateData['coininfo'] = '[]';
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
