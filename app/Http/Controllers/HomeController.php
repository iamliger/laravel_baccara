<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class HomeController extends Controller
{
    /**
     * 사용자를 인증 후, 역할에 따라 올바른 대시보드로 안내합니다.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function index() // <-- : RedirectResponse|View 부분을 삭제했습니다.
    {
        $user = Auth::user();

        // 1. Level 3 ~ 9 사용자는 시스템 대시보드 '경로'로 리디렉션합니다.
        if ($user->hasAnyRole(['Level 3', 'Level 4', 'Level 5', 'Level 6', 'Level 7', 'Level 8', 'Level 9'])) {
            return redirect()->route('system.dashboard');
        }
        
        // 2. Level 2 사용자는 바카라 데이터 입력 페이지 '경로'로 리디렉션합니다.
        if ($user->hasRole('Level 2')) {
            return redirect()->route('bacara.create');
        }
        
        // 3. 위 두 조건에 해당하지 않는 모든 사용자(주로 Admin)는
        //    이곳에서 직접 일반 'dashboard' 뷰를 보여줍니다.
        //    (Level 1은 이 컨트롤러에 도달하기 전에 'check.approval' 미들웨어에 의해 걸러집니다.)
        return view('dashboard');
    }
}
