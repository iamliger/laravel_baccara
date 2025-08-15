<x-guest-layout>
    <div class="mb-4 text-center">
        <a href="/">
            <x-application-logo class="w-20 h-20 fill-current text-gray-500 mx-auto" />
        </a>
    </div>

    <div class="mb-4 text-center">
        <p class="text-sm text-gray-500 dark:text-gray-400">추천인</p>
        <p class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ $referrer->name }}</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">위 추천인을 통해 가입을 진행합니다.</p>
    </div>
    
    <hr class="border-gray-200 dark:border-gray-600 my-6">

    {{-- ★★★ 이 div로 폼을 감싸서 너비를 조절합니다. ★★★ --}}
    <div class="max-w-md mx-auto">
        <form method="POST" action="{{ route('registration.store') }}">
            @csrf
            <input type="hidden" name="referrer_id" value="{{ $referrer->id }}">

            <!-- 이름 -->
            <div>
                <label for="name" class="block font-medium text-sm text-gray-700 dark:text-gray-300">이름</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
            </div>

            <!-- 이메일 주소 -->
            <div class="mt-4">
                <label for="email" class="block font-medium text-sm text-gray-700 dark:text-gray-300">이메일</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                       class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
            </div>
            
            <!-- 비밀번호 -->
            <div class="mt-4">
                <label for="password" class="block font-medium text-sm text-gray-700 dark:text-gray-300">비밀번호</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
            </div>

            <!-- 비밀번호 확인 -->
            <div class="mt-4">
                <label for="password_confirmation" class="block font-medium text-sm text-gray-700 dark:text-gray-300">비밀번호 확인</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                       class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
            </div>

            <div class="flex items-center justify-end mt-6">
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                    {{ __('이미 계정이 있으신가요?') }}
                </a>

                <button type="submit" class="ml-4 inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    {{ __('가입 완료하기') }}
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>