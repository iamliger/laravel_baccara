{{-- 1. 회원 전용 레이아웃인 x-app-layout을 사용합니다. --}}
<x-app-layout>
    {{-- 2. 페이지의 헤더(제목) 부분을 정의합니다. --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('승인 대기 중') }}
        </h2>
    </x-slot>

    {{-- 3. 페이지의 메인 콘텐츠 부분을 정의합니다. --}}
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold">회원가입해 주셔서 감사합니다!</h3>
                    
                    <p class="mt-4 text-gray-600 dark:text-gray-400">
                        현재 관리자의 승인을 기다리고 있습니다. 승인이 완료된 후 모든 서비스를 이용하실 수 있습니다.
                    </p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">
                        승인까지는 최대 24시간이 소요될 수 있습니다.
                    </p>

                    <!-- 로그아웃 버튼 -->
                    <div class="mt-6 flex items-center justify-end">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 ...">
                                {{ __('로그아웃') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>