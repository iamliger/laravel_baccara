<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MypageController extends Controller
{
    /**
     * 마이페이지를 보여줍니다.
     * AJAX 요청일 경우, 콘텐츠 부분만 렌더링하여 반환합니다.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 마이페이지 뷰를 렌더링합니다.
        // with() 메소드를 사용하여 뷰에 사용자 정보를 전달합니다.
        $view = view('mypage', with(['user' => $user]));
        
        // 만약 요청 헤더에 'X-Requested-With'가 'XMLHttpRequest'로 설정되어 있다면
        // (즉, JavaScript의 fetch나 axios를 통한 AJAX 요청이라면)
        if ($request->ajax()) {
            // 레이아웃 없이 뷰의 순수 HTML 콘텐츠만 반환합니다.
            return $view;
        }

        // 일반적인 웹 브라우저 요청이라면, 전체 레이아웃과 함께 페이지를 보여줍니다.
        // (이 부분은 현재 시스템에서는 직접 사용되지 않지만, 만약을 위해 남겨둡니다.)
        return view('layouts.app_mypage', ['content' => $view]);
    }
}
