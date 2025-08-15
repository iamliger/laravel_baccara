<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * 추천인 코드 입력 폼을 보여줍니다.
     */
    public function showCodeRequestForm()
    {
        return view('auth.request-code'); // 새로 만들 뷰
    }

    /**
     * 입력된 추천인 코드를 검증하고, 유효하면 가입 폼으로 리디렉션합니다.
     */
    public function processCode(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $code = $request->input('code');
        
        // 1. 코드를 가진 사용자를 찾습니다.
        $referrer = User::where('recommendation_code', $code)->first();

        // 2. ★★★ admin 제외 규칙 추가 ★★★
        // 사용자를 찾지 못했거나, 찾은 사용자가 'Admin' 역할을 가지고 있다면
        if (!$referrer || $referrer->hasRole('Admin')) {
            return back()->withErrors(['code' => '유효하지 않거나 사용할 수 없는 추천인 코드입니다.']);
        }

        return redirect()->route('register.via_code', ['code' => $code]);
    }

    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request, $code = null) // 1. $code 변수를 받도록 수정
    {
        // 1. 코드를 가진 사용자를 찾습니다.
        $referrer = User::where('recommendation_code', $code)->first();

        // 2. ★★★ admin 제외 규칙 추가 (URL 직접 접근 방지) ★★★
        if (!$referrer || $referrer->hasRole('Admin')) {
            abort(404, '유효하지 않은 추천인 코드입니다.');
        }
        
        return view('auth.register', ['referrer' => $referrer]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // 2. 폼으로 넘어온 referrer_id가 실제로 users 테이블에 존재하는지 확인 (보안)
            'referrer_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'parent_id' => $request->referrer_id, // 3. parent_id를 숨겨진 값으로 저장
        ]);

        // 신규 가입자는 무조건 'Level 1' 역할을 부여합니다.
        $user->assignRole('Level 1');

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
