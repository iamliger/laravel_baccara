<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('시스템 관리') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium">환영합니다, {{ Auth::user()->name }}님!</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        이곳은 Level 3 이상 사용자를 위한 전용 대시보드입니다.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 나의 정보 위젯 -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200">나의 정보</h4>
                        <div class="mt-4 space-y-2 text-sm">
                            <p><strong>나의 레벨:</strong> 
                                @foreach(Auth::user()->getRoleNames() as $roleName)
                                    <span class="px-2 py-1 font-semibold rounded-full bg-blue-100 text-blue-800">{{ $roleName }}</span>
                                @endforeach
                            </p>
                            <p><strong>나의 추천인 코드:</strong> <span class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ Auth::user()->recommendation_code ?? '아직 없음' }}</span></p>
                            <p><strong>나의 수익률:</strong> {{ Auth::user()->profit_percentage }}%</p>
                            <p><strong>나의 상위 추천인:</strong> {{ Auth::user()->parent->name ?? '없음' }}</p>
                        </div>
                    </div>
                </div>

                <!-- 나의 하위 조직 위젯 -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200">나의 하위 조직</h4>
                        <div class="mt-4">
                            {{-- 컨트롤러에서 전달받은 직속 자식($children) 목록을 사용하여 조직도를 그립니다. --}}
                            @if($children->isNotEmpty())
                                <ul>
                                    @foreach ($children as $child)
                                        {{-- 가장 첫 번째 레벨이므로, 깊이(depth)는 0에서 시작한다고 알려줍니다. --}}
                                        @include('system._user_tree_item', ['user' => $child, 'depth' => 0])
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-gray-500">직접 추가한 조직원이 없습니다.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>