<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">비밀번호 변경</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">보안을 위해 길고 무작위적인 비밀번호를 사용하세요.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <label for="current_password" class="block font-medium text-sm text-gray-700 dark:text-gray-300">현재 비밀번호</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                   class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
        </div>

        <div>
            <label for="password" class="block font-medium text-sm text-gray-700 dark:text-gray-300">새 비밀번호</label>
            <input id="password" name="password" type="password" autocomplete="new-password"
                   class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
        </div>

        <div>
            <label for="password_confirmation" class="block font-medium text-sm text-gray-700 dark:text-gray-300">새 비밀번호 확인</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                   class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-sm">
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">저장</button>
            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600 dark:text-gray-400">저장되었습니다.</p>
            @endif
        </div>
    </form>
</section>