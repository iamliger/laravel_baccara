<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BacaraController extends Controller
{
    /**
     * 새로운 바카라 슈(Shoe)를 기록하는 페이지를 보여줍니다.
     */
    public function create()
    {
        // 나중에 만들 'bacara.create' 뷰를 보여줍니다.
        return view('bacara.create');
    }

    /**
     * 사용자가 입력한 바카라 슈 데이터를 데이터베이스에 저장합니다.
     */
    public function store(Request $request)
    {
        // 이 함수의 로직은 다음 단계에서 채워나갈 것입니다.
        // 성공적으로 저장한 뒤, 결과 분석 페이지 등으로 리디렉션하게 됩니다.
    }
}
