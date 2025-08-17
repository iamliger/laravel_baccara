<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Logic 3 패턴 관리') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    @if (session('success'))
                        {{-- 생략된 클래스 복원 --}}
                        <div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form name="flogic3" id="flogic3" action="{{ route('admin.logic3.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        {{-- 생략된 클래스 복원 --}}
                        <div class="flex flex-wrap justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-4 mb-6 gap-4">
                            <div class="flex items-center gap-4">
                                <label for="pattern_count_input" class="font-semibold whitespace-nowrap">패턴 개수:</label>
                                <input type="number" id="pattern_count_input" min="1" max="50" value="{{ old('pattern_count', $config->logic3_patterns['pattern_count'] ?? 1) }}"
                                       class="w-20 p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600">
                                {{-- 생략된 클래스 복원 및 ID 확인 --}}
                                <button type="button" id="generate-table-btn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm">생성</button>
                            </div>
                            <div>
                                {{-- 생략된 클래스 복원 --}}
                                <button type="submit" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-md text-sm font-semibold">저장하기</button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            {{-- 생략된 클래스 복원 --}}
                            <table class="w-full min-w-[900px] text-sm text-left text-gray-500 dark:text-gray-400">
                                {{-- 생략된 클래스 복원 --}}
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3 w-16">No.</th>
                                        @for ($i = 1; $i <= 7; $i++)
                                            <th class="px-2 py-3 text-center">{{ $i }}단계</th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody id="pattern-tbody">
                                    @php
                                        $sequences = old('sequences', $config->logic3_patterns['sequences'] ?? [array_fill(0, 7, 1)]);
                                    @endphp
                                    @foreach ($sequences as $index => $sequence)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="font-semibold row-number text-center p-2">{{ $index + 1 }}</td>
                                        @for ($j = 0; $j < 7; $j++)
                                        <td class="p-2">
                                            <select name="sequences[{{ $index }}][{{ $j }}]" class="p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 w-full">
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
                        <input type="hidden" id="pattern_count" name="pattern_count" value="{{ old('pattern_count', $config->logic3_patterns['pattern_count'] ?? 1) }}">
                    </form>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
    // ▼▼▼ 핵심 수정 부분: 전체 스크립트를 이 코드로 교체합니다. ▼▼▼
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('pattern-tbody');
        const countInput = document.getElementById('pattern_count_input');
        const hiddenCountInput = document.getElementById('pattern_count');
        const generateBtn = document.getElementById('generate-table-btn'); // ID가 일치하는 버튼을 찾습니다.

        // 새로운 테이블 행(tr)을 생성하는 함수
        const createPatternRow = (rowIndex) => {
            const tr = document.createElement('tr');
            tr.className = 'bg-white border-b dark:bg-gray-800 dark:border-gray-700';
            
            let cellsHtml = `<td class="font-semibold row-number text-center p-2">${rowIndex + 1}</td>`;
            
            for (let j = 0; j < 7; j++) {
                cellsHtml += `
                <td class="p-2">
                    <select name="sequences[${rowIndex}][${j}]" class="p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600 w-full">
                        <option value="1" selected>붙</option>
                        <option value="-1">꺽</option>
                    </select>
                </td>`;
            }
            
            tr.innerHTML = cellsHtml;
            return tr;
        };
        
        // '생성' 버튼 클릭 시 테이블을 다시 그리는 이벤트 리스너
        generateBtn.addEventListener('click', function() {
            const count = parseInt(countInput.value, 10);
            if (isNaN(count) || count < 1 || count > 50) {
                alert('패턴 개수는 1에서 50 사이의 숫자로 입력해주세요.');
                return;
            }
            tbody.innerHTML = ''; // 기존 테이블 내용 모두 삭제
            for (let i = 0; i < count; i++) {
                tbody.appendChild(createPatternRow(i)); // 입력된 개수만큼 새로운 행 추가
            }
            hiddenCountInput.value = count; // 숨겨진 input 값도 동기화
        });

        // 페이지 로드 시, 또는 '생성' 버튼 클릭 후 폼 제출 실패로 되돌아왔을 때,
        // 보이는 input 값과 숨겨진 input 값을 일치시키기 위한 이벤트 리스너
        countInput.addEventListener('change', function() {
             hiddenCountInput.value = this.value;
        });
    });
</script>
@endpush
</x-app-layout>