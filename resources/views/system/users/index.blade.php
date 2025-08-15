<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('조직 관리') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    @if (session('success'))
                        <div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="flex items-center justify-end mb-4">
                        <a href="{{ route('system.users.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            새 조직원 추가
                        </a>
                    </div>

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">이름</th>
                                    <th scope="col" class="px-6 py-3">이메일</th>
                                    <th scope="col" class="px-6 py-3">역할(레벨)</th>
                                    <th scope="col" class="px-6 py-3">수익률(%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- 1. 컨트롤러에서 받은 직속 하위 조직원($users) 목록을 반복합니다. --}}
                                @forelse ($users as $user)
                                    {{-- 각 조직원을 그리기 위해, _user_row 부품을 포함(@include)시킵니다. --}}
                                    {{-- 가장 첫 레벨이므로, 깊이(depth)는 0에서 시작합니다. --}}
                                    @include('system.users._user_row', ['user' => $user, 'depth' => 0])
                                @empty
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">직접 추가한 조직원이 없습니다.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- 페이지네이션은 트리 구조 전체를 보여줄 때는 적합하지 않으므로, 이 부분은 일단 주석 처리하거나 삭제하는 것이 좋습니다. --}}
                    {{-- <div class="mt-4">
                        {{ $users->links() }}
                    </div> --}}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>