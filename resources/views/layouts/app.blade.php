<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
        <link rel="stylesheet" href="{{ mix('css/app.css') }}">
        <link rel="stylesheet" href="{{ asset('css/baccara.css') }}?v={{ time() }}">
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-gray-900">
        
        @if (Route::is('bacara.create'))

            {{-- 바카라 게임 전용 레이아웃 --}}
            <div class="main-layout">
                @role('Level 2')
                    @include('layouts.sidebar')
                @endrole
                <div class="main-content-area">
                    <main>
                        {{ $slot }}
                    </main>
                </div>
            </div>

        @else

            {{-- 그 외 모든 표준 페이지 레이아웃 --}}
            <div class="min-h-screen">
                @include('layouts.navigation')
                @if (isset($header))
                    <header class="bg-white dark:bg-gray-800 shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif
                <main>
                    {{ $slot }}
                </main>
            </div>

        @endif

        @livewireScripts
        <script src="{{ mix('js/app.js') }}" defer></script>
        @stack('scripts')
        
        <script>
            // 하트비트 스크립트는 변경 없이 그대로 유지됩니다.
            document.addEventListener('DOMContentLoaded', function() {
                @auth
                    @unlessrole('Admin')
                        setInterval(checkStatus, 30000); 
                        function checkStatus() {
                            if (typeof axios !== 'undefined') {
                                axios.get('{{ route("check.status") }}')
                                    .catch(function (error) {
                                        if (error.response && error.response.status === 401) {
                                            forceLogout();
                                        }
                                    });
                            }
                        }
                        function forceLogout() {
                            alert('관리자에 의해 로그아웃되었거나 세션이 만료되었습니다. 다시 로그인해주세요.');
                            const logoutForm = document.createElement('form');
                            logoutForm.method = 'POST';
                            logoutForm.action = '{{ route("logout") }}';
                            const csrfToken = document.createElement('input');
                            csrfToken.type = 'hidden';
                            csrfToken.name = '_token';
                            csrfToken.value = '{{ csrf_token() }}';
                            logoutForm.appendChild(csrfToken);
                            document.body.appendChild(logoutForm);
                            logoutForm.submit();
                        }
                    @endunlessrole
                @endauth
            });
        </script>
    </body>
</html>