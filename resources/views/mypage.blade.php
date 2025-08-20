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
        </div>
    </div>

    <!-- 3. 추천 아이템: 로직 분석 카드 -->
    <div class="bg-gray-800/50 rounded-lg p-6 border border-gray-700">
        <h4 class="text-xl font-bold text-gray-100 mb-4">로직 성향 분석</h4>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="flex items-center text-gray-400 text-sm"><i class="ti ti-thumb-up mr-2"></i> 주로 사용하는 로직</span>
                <span class="font-semibold text-indigo-400">{{ $primaryLogic }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="flex items-center text-gray-400 text-sm"><i class="ti ti-crown mr-2"></i> 최고 승률 로직</span>
                <span class="font-semibold text-amber-400">{{ $bestLogic }}</span>
            </div>
        </div>
    </div>
</div>