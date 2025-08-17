<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Logic 2 패턴 관리') }}
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

                    <form name="flogic2" id="flogic2" action="{{ route('admin.logic2.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        @php
                            // 컨트롤러에서 전달받은 설정값을 변수로 저장합니다.
                            $logic2_patterns = $config->logic2_patterns ?? [];
                            // ★★★ 핵심 수정: 기본 패턴 개수를 2개로 설정합니다.
                            $pattern_count = old('pattern_count', $logic2_patterns['pattern_count'] ?? 2);
                            // ★★★ 핵심 수정: 기본 시퀀스를 7단계짜리 2개로 설정합니다.
                            $sequences = old('sequences', $logic2_patterns['sequences'] ?? [
                                array_fill(0, 7, 1),
                                array_fill(0, 7, 1)
                            ]);
                        @endphp

                        <div class="flex flex-wrap justify-between items-center border-b dark:border-gray-700 pb-4 mb-6 gap-4">
                            <div class="flex items-center gap-4">
                                <label for="pattern_count_input" class="font-semibold whitespace-nowrap">패턴 개수:</label>
                                <input type="number" id="pattern_count_input" min="1" max="50" value="{{ $pattern_count }}"
                                       class="w-20 p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600">
                                <button type="button" id="generate-table-btn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm">생성</button>
                            </div>
                            <div>
                                <button type="submit" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md text-sm font-semibold">
                                    <i class="ti ti-device-floppy mr-1"></i> 저장하기
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3 w-16">No.</th>
                                        @for ($i = 1; $i <= 7; $i++)
                                            <th class="px-2 py-3 text-center">{{ $i }}단계</th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody id="pattern-tbody">
                                    @foreach ($sequences as $index => $sequence)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="font-semibold text-center row-number">{{ $index + 1 }}</td>
                                        @for ($j = 0; $j < 7; $j++)
                                        <td class="p-2">
                                            <select name="sequences[{{ $index }}][{{ $j }}]" class="pattern-select p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 w-full">
                                                <option value="1" @if (($sequence[$j] ?? 1) == 1) selected @endif>붙</option>
                                                <option value="-1" @if (($sequence[$j] ?? 1) == -1) selected @endif>꺽</option>
                                            </select>
                                        </td>
                                        @endfor
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <input type="hidden" id="pattern_count" name="pattern_count" value="{{ $pattern_count }}">
                    </form>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('pattern-tbody');
    const countInput = document.getElementById('pattern_count_input');
    const hiddenCountInput = document.getElementById('pattern_count');
    const generateBtn = document.getElementById('generate-table-btn');

    // 고정된 7단계 select 요소를 포함하는 새로운 테이블 행(tr)을 생성하는 함수
    function createPatternRow(rowIndex) {
        const tr = document.createElement('tr');
        tr.className = 'bg-white border-b dark:bg-gray-800 dark:border-gray-700';
        
        let cellsHtml = `<td class="font-semibold text-center row-number">${rowIndex + 1}</td>`;
        
        for (let j = 0; j < 7; j++) {
            cellsHtml += `
            <td class="p-2">
                <select name="sequences[${rowIndex}][${j}]" class="pattern-select p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 w-full">
                    <option value="1" selected>붙</option>
                    <option value="-1">꺽</option>
                </select>
            </td>`;
        }
        
        tr.innerHTML = cellsHtml;
        return tr;
    }

    // '생성' 버튼 클릭 시 테이블을 다시 그리는 이벤트 리스너
    generateBtn.addEventListener('click', function() {
        const count = parseInt(countInput.value, 10);
        if (isNaN(count) || count < 1 || count > 50) {
            alert('패턴 개수는 1에서 50 사이의 숫자로 입력해주세요.');
            return;
        }
        tbody.innerHTML = ''; // 기존 테이블 내용 삭제
        for (let i = 0; i < count; i++) {
            tbody.appendChild(createPatternRow(i));
        }
        hiddenCountInput.value = count; // 숨겨진 input 값도 동기화
    });
    
    // 패턴 개수 input 값이 변경될 때 숨겨진 input 값도 동기화
    countInput.addEventListener('change', function() {
         hiddenCountInput.value = this.value;
    });
});
</script>
@endpush
</x-app-layout>