<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class DashboardController extends Controller
{
    /**
     * 시스템 대시보드를 표시합니다.
     * 이 함수는 로그인한 사용자의 직속 하위 조직원 목록을 가져와
     * 뷰(화면) 파일에 전달하는 역할을 합니다.
     *
     * @return \Illuminate\View\View
     */
    public function __invoke()
    {
        // 현재 로그인한 사용자(Auth::user())의
        // 직속 하위 조직원 목록(children)을 가져와 $children 변수에 담습니다.
        // with('children')은 N+1 문제를 방지하기 위한 '즉시 로딩' 기법입니다.
        $children = Auth::user()->children()->with('children')->get();

        // 'system.dashboard' 라는 이름의 뷰 파일을 보여주는데,
        // 방금 가져온 $children 데이터를 함께 전달합니다.
        return view('system.dashboard', compact('children'));
    }
}
