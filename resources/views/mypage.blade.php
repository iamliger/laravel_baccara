{{-- 이 파일은 레이아웃을 포함하지 않습니다. 순수 콘텐츠만 작성합니다. --}}
<div class="space-y-6 text-gray-300">
    <div>
        <h4 class="text-lg font-semibold text-gray-100 border-b border-gray-700 pb-2 mb-3">계정 정보</h4>
        <div class="grid grid-cols-3 gap-4 text-sm">
            <span class="font-medium text-gray-500">사용자 이름</span>
            <span class="col-span-2">{{ $user->name }}</span>

            <span class="font-medium text-gray-500">이메일</span>
            <span class="col-span-2">{{ $user->email }}</span>
            
            <span class="font-medium text-gray-500">가입일</span>
            <span class="col-span-2">{{ $user->created_at->format('Y-m-d H:i') }}</span>
        </div>
    </div>
    
    <div>
        <h4 class="text-lg font-semibold text-gray-100 border-b border-gray-700 pb-2 mb-3">게임 통계 (예시)</h4>
        <div class="grid grid-cols-3 gap-4 text-sm">
            <span class="font-medium text-gray-500">총 베팅 횟수</span>
            <span class="col-span-2">1,234 회</span>

            <span class="font-medium text-gray-500">최고 연승</span>
            <span class="col-span-2">12 연승</span>

            <span class="font-medium text-gray-500">주로 사용하는 로직</span>
            <span class="col-span-2">로직 1</span>
        </div>
    </div>

    {{-- 프로필 수정 페이지로 이동하는 링크 --}}
    <div class="pt-4">
        <a href="{{ route('profile.edit') }}" class="text-indigo-400 hover:text-indigo-300 text-sm font-medium">
            비밀번호 변경 및 프로필 수정하기 &rarr;
        </a>
    </div>
</div>