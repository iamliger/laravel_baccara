<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * 현재 로그인한 사용자의 하위 조직원 목록을 보여줍니다.
     */
    public function index()
    {
        // 현재 로그인한 사용자의 모든 자손들을 가져옵니다.
        // 페이지네이션은 트리 구조와 함께 쓰기 복잡하므로, 일단 모든 자손을 가져옵니다.
        $users = Auth::user()->descendants; 
        
        return view('system.users.index', compact('users'));
    }

    /**
     * 새로운 하위 조직원을 추가하는 폼(화면)을 보여줍니다.
     */
    public function create()
    {
        $currentUser = Auth::user();
        // 사용자는 하나의 레벨만 가진다고 가정합니다.
        $currentRoleName = $currentUser->getRoleNames()->first(); 
        // 'Level 9' 에서 숫자 9만 추출합니다.
        $currentUserLevel = (int) str_replace('Level ', '', $currentRoleName);

        // 현재 사용자 레벨보다 낮은 레벨들의 이름 목록을 만듭니다. (Level 2까지)
        $creatableRoleNames = [];
        for ($i = $currentUserLevel - 1; $i >= 2; $i--) {
            $creatableRoleNames[] = 'Level ' . $i;
        }

        // 위에서 만든 이름 목록에 해당하는 역할(Role) 모델들을 DB에서 가져옵니다.
        $roles = Role::whereIn('name', $creatableRoleNames)->get();

        // 나중에 만들 system.users.create 뷰를 보여줍니다.
        return view('system.users.create', compact('roles'));
    }

    /**
     * 새로운 하위 조직원을 실제로 생성하고 저장합니다.
     */
    public function store(Request $request)
    {
        // --- 1. 권한 확인을 위한 준비 ---
        $currentUser = Auth::user();
        $currentRoleName = $currentUser->getRoleNames()->first();
        $currentUserLevel = (int) str_replace('Level ', '', $currentRoleName);

        // 현재 사용자가 생성할 수 있는 역할(레벨) 이름 목록을 만듭니다.
        $creatableRoleNames = [];
        for ($i = $currentUserLevel - 1; $i >= 2; $i--) {
            $creatableRoleNames[] = 'Level ' . $i;
        }

        // --- 2. 제로섬 수익률 합계 검증 ---
        // a. 현재 로그인한 사용자(나)와 나의 모든 상위 조직(조상)들을 가져옵니다.
        $lineage = $currentUser->ancestors()->push($currentUser);
        
        // b. 그들의 현재 수익률(%)을 모두 더합니다.
        $currentTotalPercentage = $lineage->sum('profit_percentage');
        
        // c. config/services.php 파일에서 최대 수익률 상한선을 가져옵니다.
        $maxPercentage = config('services.referral.max_profit_percentage', 10.00); // 기본값 10%

        // d. 상한선에서 현재까지의 합계를 뺀 값, 즉 '새로 부여할 수 있는 최대 수익률'을 계산합니다.
        $availablePercentage = $maxPercentage - $currentTotalPercentage;
        // 소수점 2자리까지만 계산하도록 처리 (정확도를 위해)
        $availablePercentage = floor($availablePercentage * 100) / 100;

        // --- 3. 유효성 검사 (Validation) ---
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            
            // 사용자가 선택한 역할이, 정말로 생성 가능한 역할 목록 안에 있는지 검사합니다. (해킹 방지)
            'role' => ['required', Rule::in($creatableRoleNames)], 
            
            // profit_percentage 규칙에 max 규칙을 동적으로 추가합니다.
            'profit_percentage' => [
                'required', 
                'numeric', 
                'min:0',
                // 입력된 수익률은, 우리가 부여할 수 있는 최대 수익률($availablePercentage)보다 작거나 같아야 합니다.
                'max:' . $availablePercentage 
            ],
        ]);

        // --- 4. 새 사용자 생성 ---
        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profit_percentage' => $request->profit_percentage,
            'parent_id' => $currentUser->id, // 상위 추천인을 자기 자신으로 설정!
            // 추천인 코드 생성 (예: testuser-A8B2C1), Str::upper로 대문자로 변환하여 가독성 향상
            'recommendation_code' => $request->name . '-' . Str::upper(Str::random(6)), 
        ]);

        // --- 5. 역할 부여 ---
        $newUser->assignRole($request->role);
        
        // --- 6. 성공 후 리디렉션 ---
        return redirect()->route('system.users.index')->with('success', '새로운 조직원을 추가했습니다.');
    }

    public function impersonate(User $user)
    {
        // 보안 검사: 가장하려는 사용자가 정말 나의 하위 조직원인지 확인합니다.
        // Auth::user()->children()는 현재 로그인한 유저의 직속 하위 조직원 목록입니다.
        if (!Auth::user()->children()->find($user->id)) {
            // 만약 내 하위 조직원이 아니라면, 403 Forbidden (권한 없음) 에러를 발생시킵니다.
            abort(403, '자신의 하위 조직원만 접근할 수 있습니다.');
        }

        // 현재 로그인한 사용자(Auth::user())가, 선택한 사용자($user)로 변신합니다.
        Auth::user()->impersonate($user);

        // 변신 후, 해당 사용자가 보게 될 첫 화면(대시보드)으로 이동합니다.
        return redirect()->route('dashboard');
    }
}
