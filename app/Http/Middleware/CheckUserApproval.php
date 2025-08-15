<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class CheckUserApproval
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
        // 1. 로그인한 사용자가 'Level 1' 역할을 가지고 있는지 확인합니다.
        // 2. 그리고, 현재 접속하려는 페이지가 '승인 대기' 페이지가 아닌지 확인합니다. (무한 리디렉션 방지)
        if (Auth::check() && $request->user()->hasRole('Level 1') && !$request->routeIs('approval.pending')) {
            
            // 위 조건이 모두 참이면, '승인 대기' 페이지로 강제 이동시킵니다.
            return redirect()->route('approval.pending');
        }

        // 위 조건에 해당하지 않는 사용자(Admin, Level 2 이상)는 원래 가려던 길로 그냥 보냅니다.
        return $next($request);
    }
}
