{{-- 조직원 한 명을 그리는 부품 (Alpine.js로 아이콘 클릭 기능 추가) --}}

<li class="mt-2" x-data="{ open: true }">
    <div class="flex items-center group cursor-pointer" @click="open = !open"> {{-- ★ 1. 전체 div를 클릭 가능하게 만들고, 클릭 시 open 상태를 바꿉니다. --}}
        
        {{-- 들여쓰기를 위한 공간 --}}
        <span style="width: {{ $depth * 25 }}px;" class="inline-block flex-shrink-0"></span>

        {{-- 아이콘 영역 --}}
        <span class="mr-2 text-gray-500 dark:text-gray-400 flex-shrink-0">
            @if ($user->children->isNotEmpty())
                {{-- 하위 조직원이 있으면 '열린 폴더'와 '닫힌 폴더' 아이콘을 open 상태에 따라 보여줍니다. --}}
                <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                </svg>
                <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" style="display: none;">
                    <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zm14 2H4v6h12V8z" clip-rule="evenodd" />
                </svg>
            @else
                {{-- 하위 조직원이 없으면 '사람' 아이콘을 보여줍니다. --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
            @endif
        </span>

        {{-- 이름 및 수익률 정보 --}}
        <div class="truncate"> {{-- 2. 이름이 너무 길 경우 잘리도록 truncate 클래스 추가 --}}
            <a href="{{ route('system.users.impersonate', $user) }}" class="hover:underline font-semibold text-gray-800 dark:text-gray-200" title="{{ $user->name }}(으)로 로그인">
                {{ $user->name }}
            </a>
            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                ({{ $user->profit_percentage }}%)
            </span>
        </div>
    </div>

    {{-- 하위 조직원 목록 (펼침 효과 추가) --}}
    <div x-show="open" x-cloak class="pl-6 border-l border-gray-200 dark:border-gray-700 ml-2"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2">
        @if ($user->children->isNotEmpty())
            <ul>
                @foreach ($user->children as $child)
                    @include('system._user_tree_item', ['user' => $child, 'depth' => $depth + 1])
                @endforeach
            </ul>
        @endif
    </div>
</li>