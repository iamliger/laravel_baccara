<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // 소프트 삭제된 사용자를 포함하여, 최신순으로, 10개씩 페이지네이션하여 가져옵니다.
        $users = User::withTrashed()->latest()->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::all();
        // 모든 사용자를 가져와서 'potential_parents' 라는 이름으로 전달합니다.
        $potential_parents = User::all();
        return view('admin.users.create', compact('roles', 'potential_parents'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required']
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        return redirect()->route('admin.users.index')->with('success', '새로운 회원을 추가했습니다.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        // 수정 대상 자신을 제외한 모든 사용자를 가져옵니다. (스스로를 추천할 수 없도록)
        $potential_parents = User::where('id', '!=', $user->id)->get();
        return view('admin.users.edit', compact('user', 'roles', 'potential_parents'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles($request->role);

        return redirect()->route('admin.users.index')->with('success', '회원 정보를 수정했습니다.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', '사용자를 강퇴 처리했습니다.');
    }
    
    /**
     * Ban the specified user.
     */
    public function ban(User $user)
    {
        $user->update(['banned_at' => now()]);
        return redirect()->route('admin.users.index')->with('success', '사용자를 차단했습니다.');
    }

    /**
     * Unban the specified user.
     */
    public function unban(User $user)
    {
        $user->update(['banned_at' => null]);
        return redirect()->route('admin.users.index')->with('success', '사용자 차단을 해제했습니다.');
    }
    
    /**
     * Restore the specified user.
     */
    public function restore($id)
    {
        User::withTrashed()->where('id', $id)->restore();
        return redirect()->route('admin.users.index')->with('success', '사용자를 복구했습니다.');
    }
}
