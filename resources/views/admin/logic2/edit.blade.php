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
                            // 컨트롤러에서 전달받은 설정값을 변수로 저장
                            $logic2_patterns = $config->logic2_patterns ?? [];
                            $pattern_count = old('pattern_count', $logic2_patterns['pattern_count'] ?? 1);
                            $sequences = old('sequences', $logic2_patterns['sequences'] ?? [[1, -1, 1]]);
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
                                        <th class="px-4 py-3">패턴 규칙 (최대 10단계)</th>
                                        <th class="px-4 py-3 w-24">관리</th>
                                    </tr>
                                </thead>
                                <tbody id="pattern-tbody">
                                    @foreach ($sequences as $index => $sequence)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="font-semibold text-center row-number">{{ $index + 1 }}</td>
                                        <td class="p-2">
                                            <div class="flex items-center gap-2" data-row-index="{{ $index }}">
                                                @foreach ($sequence as $j => $value)
                                                <select name="sequences[{{ $index }}][{{ $j }}]" class="pattern-select p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600">
                                                    <option value="1" @if ($value == 1) selected @endif>붙</option>
                                                    <option value="-1" @if ($value == -1) selected @endif>꺽</option>
                                                </select>
                                                @endforeach
                                                <button type="button" class="add-step-btn text-green-500 hover:text-green-400 text-xl font-bold">+</button>
                                                <button type="button" class="remove-step-btn text-red-500 hover:text-red-400 text-xl font-bold">-</button>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="delete-row-btn px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs">삭제</button>
                                        </td>
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

    function createSelect(rowIndex, colIndex, selectedValue = 1) {
        const select = document.createElement('select');
        select.name = `sequences[${rowIndex}][${colIndex}]`;
        select.className = 'pattern-select p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600';
        select.innerHTML = `
            <option value="1" ${selectedValue == 1 ? 'selected' : ''}>붙</option>
            <option value="-1" ${selectedValue == -1 ? 'selected' : ''}>꺽</option>
        `;
        return select;
    }
    
    function createPatternRow(rowIndex) {
        const tr = document.createElement('tr');
        tr.className = 'bg-white border-b dark:bg-gray-800 dark:border-gray-700';
        tr.innerHTML = `
            <td class="font-semibold text-center row-number">${rowIndex + 1}</td>
            <td class="p-2">
                <div class="flex items-center gap-2" data-row-index="${rowIndex}">
                    <!-- JS로 select 채움 -->
                    <button type="button" class="add-step-btn text-green-500 hover:text-green-400 text-xl font-bold">+</button>
                    <button type="button" class="remove-step-btn text-red-500 hover:text-red-400 text-xl font-bold">-</button>
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="delete-row-btn px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs">삭제</button>
            </td>
        `;
        // 기본 3개 select 추가
        const sequenceContainer = tr.querySelector('.flex');
        const addBtn = sequenceContainer.querySelector('.add-step-btn');
        for (let i = 0; i < 3; i++) {
            sequenceContainer.insertBefore(createSelect(rowIndex, i), addBtn);
        }
        return tr;
    }

    function updateTableState() {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach((row, rowIndex) => {
            row.querySelector('.row-number').textContent = rowIndex + 1;
            const sequenceContainer = row.querySelector('.flex');
            sequenceContainer.dataset.rowIndex = rowIndex;
            sequenceContainer.querySelectorAll('select').forEach((select, colIndex) => {
                select.name = `sequences[${rowIndex}][${colIndex}]`;
            });
        });
        hiddenCountInput.value = rows.length;
        countInput.value = rows.length;
    }

    generateBtn.addEventListener('click', function() {
        const count = parseInt(countInput.value, 10);
        if (isNaN(count) || count < 1 || count > 50) {
            alert('패턴 개수는 1에서 50 사이의 숫자로 입력해주세요.');
            return;
        }
        tbody.innerHTML = '';
        for (let i = 0; i < count; i++) {
            tbody.appendChild(createPatternRow(i));
        }
        updateTableState();
    });

    tbody.addEventListener('click', function(e) {
        const target = e.target;
        const sequenceContainer = target.closest('.flex');
        
        if (target.classList.contains('delete-row-btn')) {
            if (tbody.rows.length <= 1) {
                alert('최소 하나 이상의 패턴이 필요합니다.');
                return;
            }
            target.closest('tr').remove();
            updateTableState();
        } else if (target.classList.contains('add-step-btn')) {
            const selects = sequenceContainer.querySelectorAll('select');
            if (selects.length >= 10) {
                alert('한 패턴의 단계는 최대 10개까지 가능합니다.');
                return;
            }
            const rowIndex = sequenceContainer.dataset.rowIndex;
            const newSelect = createSelect(rowIndex, selects.length);
            sequenceContainer.insertBefore(newSelect, target);
        } else if (target.classList.contains('remove-step-btn')) {
            const selects = sequenceContainer.querySelectorAll('select');
            if (selects.length <= 1) {
                alert('한 패턴은 최소 1개의 단계가 필요합니다.');
                return;
            }
            selects[selects.length - 1].remove();
        }
    });
});
</script>
@endpush

</x-app-layout>