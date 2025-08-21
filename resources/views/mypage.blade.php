{{-- 이 파일은 레이아웃을 포함하지 않습니다. 순수 콘텐츠만 작성합니다. --}}
<div class="space-y-8 text-gray-300">

    <!-- 1. 계정 정보 카드 -->
    <div class="bg-gray-800/50 rounded-lg p-6 border border-gray-700">
        <h4 class="text-xl font-bold text-gray-100 mb-4">계정 정보</h4>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="flex items-center text-gray-400 text-sm"><i class="ti ti-user mr-2"></i> 사용자 이름</span>
                <span class="font-mono text-gray-200">{{ $user->name }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="flex items-center text-gray-400 text-sm"><i class="ti ti-mail mr-2"></i> 이메일</span>
                <span class="font-mono text-gray-200">{{ $user->email }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="flex items-center text-gray-400 text-sm"><i class="ti ti-calendar-event mr-2"></i> 가입일</span>
                <span class="font-mono text-gray-200">{{ $user->created_at->format('Y-m-d') }}</span>
            </div>
        </div>
    </div>

    <!-- 2. 게임 통계 카드 -->
    <div class="bg-gray-800/50 rounded-lg p-6 border border-gray-700">
        <h4 class="text-xl font-bold text-gray-100 mb-4">게임 성과 분석</h4>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="flex items-center text-gray-400 text-sm"><i class="ti ti-hash mr-2"></i> 총 베팅 횟수 (가상)</span>
                <span class="font-semibold text-gray-200">{{ number_format($totalBets) }} 회</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="flex items-center text-gray-400 text-sm"><i class="ti ti-chart-line mr-2"></i> 전체 승률 (가상)</span>
                <span class="font-semibold text-lg {{ floatval($winRate) >= 50 ? 'text-green-400' : 'text-red-400' }}">{{ $winRate }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="flex items-center text-gray-400 text-sm"><i class="ti ti-trophy mr-2"></i> 최고 연승 기록</span>
                <span class="font-semibold text-gray-200">{{ $maxWinStreak }} 연승</span>
            </div>
             <div class="flex items-center justify-between">
                <span class="flex items-center text-gray-400 text-sm">
                    @if($currentStreak['type'] === '승')
                        <i class="ti ti-flame mr-2 text-orange-400"></i> 현재 연속 승리
                    @else
                        <i class="ti ti-snowflake mr-2 text-blue-400"></i> 현재 연속 패배
                    @endif
                </span>
                <span class="font-semibold text-gray-200">{{ $currentStreak['count'] }} 회</span>
            </div>

            <div class="flex items-center justify-between">
                <span class="flex items-center text-gray-400 text-sm"><i class="ti ti-wallet mr-2"></i> 총 수익 (가상)</span>
                <span class="font-semibold text-lg {{ $totalProfit >= 0 ? 'text-green-400' : 'text-red-400' }}">
                    {{ number_format($totalProfit) }} 원
                </span>
            </div>
        </div>
    </div>

    <div class="bg-gray-800/50 rounded-lg p-6 border border-gray-700">
        <h4 class="text-xl font-bold text-gray-100 mb-4">로직 성향 분석</h4>
        <div class="space-y-4">
            {{-- 주로 사용하는 로직은 변경 없음 --}}
            <div class="flex items-center justify-between">
                <span class="flex items-center text-gray-400 text-sm"><i class="ti ti-thumb-up mr-2"></i> 주로 사용하는 로직</span>
                <span class="font-semibold text-indigo-400">{{ $primaryLogic }}</span>
            </div>
            
            {{-- ★★★ 최고 승률 / 최고 수익 로직으로 분리하여 표시 ★★★ --}}
            <div class="flex items-center justify-between">
                <span class="flex items-center text-gray-400 text-sm"><i class="ti ti-crown mr-2"></i> 최고 승률 로직</span>
                <span class="font-semibold text-amber-400">{{ $bestLogicByRate }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="flex items-center text-gray-400 text-sm"><i class="ti ti-trending-up mr-2"></i> 최고 수익 로직</span>
                <span class="font-semibold text-lime-400">{{ $bestLogicByProfit }}</span>
            </div>
        </div>
    </div>

    <div class="space-y-8 text-gray-300">
        <!-- (계정 정보, 게임 성과, 로직 성향 분석 카드들은 변경 없음) -->
        {{-- ... --}}
    
        <!-- ▼▼▼ 4. 추천 아이템: 상세 로그 뷰어 ▼▼▼ -->
        <div class="bg-gray-800/50 rounded-lg p-6 border border-gray-700">
            <h4 class="text-xl font-bold text-gray-100 mb-4">상세 게임 로그</h4>
            
            <!-- 탭 버튼 -->
            <div class="border-b border-gray-700 mb-4">
                <nav id="log-tabs" class="-mb-px flex space-x-6" aria-label="Tabs">
                    <button data-log-type="all" class="log-tab active">전체</button>
                    <button data-log-type="system" class="log-tab">시스템</button>
                    <button data-log-type="player" class="log-tab">플레이어</button>
                    <button data-log-type="banker" class="log-tab">뱅커</button>
                    <button data-log-type="recommendation" class="log-tab">추천</button>
                    <button data-log-type="ai-prediction" class="log-tab">AI 예측</button>
                </nav>
            </div>
    
            <!-- 로그 콘텐츠 -->
            <div id="log-content" class="space-y-2 text-xs font-mono max-h-60 overflow-y-auto pr-2">
                @forelse($consoleLogs as $log)
                    {{-- data-log-type 속성에 로그 타입을 저장합니다. --}}
                    <div class="log-item" data-log-type="{{ $log['type'] }}">
                        <span class="log-timestamp">{{ \Carbon\Carbon::parse($log['timestamp'])->format('H:i:s') }}</span>
                        <span class="log-message log-type-{{ $log['type'] }}">
                            {!! $log['html'] !!} {{-- 아이콘을 위해 HTML을 그대로 출력 --}}
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500">표시할 로그가 없습니다.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const tabsContainer = document.getElementById('log-tabs');
        if (!tabsContainer) return;
        
        const logItems = document.querySelectorAll('#log-content .log-item');
        const tabs = tabsContainer.querySelectorAll('.log-tab');

        tabsContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('log-tab')) {
                const selectedType = e.target.dataset.logType;

                // 모든 탭의 active 클래스 제거 후, 클릭된 탭에만 추가
                tabs.forEach(tab => tab.classList.remove('active'));
                e.target.classList.add('active');

                // 로그 아이템 필터링
                logItems.forEach(item => {
                    if (selectedType === 'all' || item.dataset.logType === selectedType) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
        });
    })();
</script>