<section class="space-y-6" x-data="{ showModal: false }">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">계정 삭제</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">계정이 삭제되면 모든 데이터가 영구적으로 삭제됩니다.</p>
    </header>

    <button @click="showModal = true" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
        계정 삭제
    </button>

    <!-- Deletion Modal -->
    <div x-show="showModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showModal = false" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showModal" x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg">
                
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">정말로 계정을 삭제하시겠습니까?</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">계정이 삭제되면 복구할 수 없습니다. 비밀번호를 입력하여 확인해주세요.</p>

                    <div class="mt-6">
                        <label for="password_delete" class="sr-only">비밀번호</label>
                        <input id="password_delete" name="password" type="password" placeholder="비밀번호"
                               class="mt-1 block w-3/4 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="showModal = false" class="inline-flex items-center px-4 py-2 bg-white ...">취소</button>
                        <button type="submit" class="ml-3 inline-flex items-center px-4 py-2 bg-red-600 ...">계정 삭제</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>