<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Auth 파사드 사용

class CheckIfBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // 사용자가 로그인한 상태이고 (auth()->check()),
        // 그 사용자의 banned_at 컬럼에 값이 있다면 (차단되었다면)
        if (Auth::check() && Auth::user()->banned_at) {
            
            // 1. 현재 사용자를 강제 로그아웃 시킵니다.
            Auth::logout();

            // 2. 현재 요청의 세션을 무효화하고 토큰을 재생성합니다. (보안 강화)
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // 3. 'error'라는 이름의 일회용 메시지와 함께 로그인 페이지로 돌려보냅니다.
            return redirect()->route('login')->with('error', '귀하의 계정은 접근이 차단되었습니다.');
        }

        // 차단되지 않았다면, 원래 가려던 길로 그냥 보냅니다.
        return $next($request);
    }
}
