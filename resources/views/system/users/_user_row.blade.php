{{-- 한 명의 사용자를 테이블의 행(tr)으로 그리는 부품 --}}
<tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
        {{-- 들여쓰기를 위해 왼쪽에 여백을 줍니다. --}}
        <span style="padding-left: {{ $depth * 20 }}px;">
            <a href="{{ route('system.users.impersonate', $user) }}" class="hover:underline" title="{{ $user->name }}(으)로 로그인">
                {{ $user->name }}
            </a>
        </span>
    </th>
    <td class="px-6 py-4">{{ $user->email }}</td>
    <td class="px-6 py-4">
        @if(!empty($user->getRoleNames()))
            <span class="px-2 ...">{{ $user->getRoleNames()->first() }}</span>
        @endif
    </td>
    <td class="px-6 py-4">{{ $user->profit_percentage }}%</td>
</tr>

{{-- ★★★ 재귀 호출의 핵심 ★★★ --}}
{{-- 만약 이 사용자에게 또 다른 자식(descendants)이 있다면 --}}
@if ($user->descendants->isNotEmpty())
    {{-- 그 자식들 각각에 대해, 이 파일(_user_row.blade.php)을 다시 한번 호출합니다. --}}
    {{-- 이때, 깊이(depth)를 1 증가시켜서 더 많이 들여쓰도록 합니다. --}}
    @foreach ($user->descendants as $descendant)
        @include('system.users._user_row', ['user' => $descendant, 'depth' => $depth + 1])
    @endforeach
@endif