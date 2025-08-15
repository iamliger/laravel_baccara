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
                        <div class="mb-4 p-4 text-sm text-green-700 ...">{{ session('success') }}</div>
                    @endif

                    <form name="flogic3" id="flogic3" action="{{ route('admin.logic3.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="flex flex-wrap justify-between items-center border-b ... pb-4 mb-6 gap-4">
                            <div class="flex items-center gap-4">
                                <label for="pattern_count_input" class="font-semibold whitespace-nowrap">패턴 개수:</label>
                                <input type="number" id="pattern_count_input" min="1" max="50" value="{{ old('pattern_count', $config->logic3_patterns['pattern_count'] ?? 1) }}"
                                       class="w-20 p-2 border rounded-md dark:bg-gray-700 dark:border-gray-600">
                                <button type="button" id="generate-table-btn" class="px-4 py-2 bg-blue-600 ...">생성</button>
                            </div>
                            <div>
                                <button type="submit" class="px-4 py-2 bg-red-500 ...">저장하기</button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[900px] text-sm ...">
                                <thead class="text-xs text-gray-700 ...">
                                    <tr>
                                        <th class="w-16">No.</th>
                                        @for ($i = 1; $i <= 7; $i++)
                                            <th>{{ $i }}단계</th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody id="pattern-tbody">
                                    @php
                                        $sequences = old('sequences', $config->logic3_patterns['sequences'] ?? [[1,1,1,1,1,1,1]]);
                                    @endphp
                                    @foreach ($sequences as $index => $sequence)
                                    <tr class="bg-white border-b dark:bg-gray-800">
                                        <td class="font-semibold row-number text-center p-2">{{ $index + 1 }}</td>
                                        @for ($j = 0; $j < 7; $j++)
                                        <td>
                                            <select name="sequences[{{ $index }}][{{ $j }}]" class="p-2 border rounded-md dark:bg-gray-700 w-full">
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
</x-app-layout>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.getElementById('pattern-tbody');
        const countInput = document.getElementById('pattern_count_input');
        const hiddenCountInput = document.getElementById('pattern_count');
        const generateBtn = document.getElementById('generate-table-btn');

        const createPatternRow = (rowIndex) => {
            const tr = document.createElement('tr');
            tr.className = 'bg-white border-b dark:bg-gray-800';
            let cellsHtml = `<td class="font-semibold row-number text-center p-2">${rowIndex + 1}</td>`;
            for (let j = 0; j < 7; j++) {
                cellsHtml += `<td><select name="sequences[${rowIndex}][${j}]" class="p-2 border rounded-md dark:bg-gray-700 w-full"><option value="1" selected>붙</option><option value="-1">꺽</option></select></td>`;
            }
            tr.innerHTML = cellsHtml;
            return tr;
        };
        
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
            hiddenCountInput.value = count;
        });

        countInput.addEventListener('change', function() {
             hiddenCountInput.value = this.value;
        });
    });
</script>
@endpush