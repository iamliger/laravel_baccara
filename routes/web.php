<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\System\UserController as SystemUserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\System\DashboardController as SystemDashboardController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\Logic3PatternController;
use App\Http\Controllers\Admin\Logic2PatternController;
use App\Http\Controllers\BacaraController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| 모든 웹 요청에 대한 경로를 정의합니다.
|
*/

// --- 1. 로그인한 사용자만 접근 가능한 경로 그룹 ---
Route::middleware('auth')->group(function () {

    // 1-1. 로그인 후 모든 사용자가 처음 도착하는 단일 진입점입니다.
    // HomeController가 사용자의 레벨을 보고 알아서 올바른 페이지로 보내거나, 직접 뷰를 보여줍니다.
    Route::get('/', [HomeController::class, 'index'])->middleware('check.approval')->name('home');
    Route::get('/dashboard', [HomeController::class, 'index'])->middleware('check.approval')->name('dashboard');

    // 1-2. Level 3~9 사용자들이 최종적으로 보게 될 페이지입니다.
    Route::get('/system', SystemDashboardController::class)->middleware('check.approval')->name('system.dashboard');

    // 1-3. '승인 대기' 페이지 (변경 없음)
    Route::get('/approval-pending', function() {
        if (!auth()->user()->hasRole('Level 1')) {
            return redirect()->route('dashboard');
        }
        return view('auth.approval-pending');
    })->name('approval.pending');
    
    // 1-4. 하트비트 경로 (변경 없음)
    Route::get('/check-status', function () {
        $user = auth()->user();
        if (!$user || $user->banned_at || $user->trashed()) {
            return response()->json(['status' => 'unauthenticated'], 401);
        }
        return response()->json(['status' => 'authenticated']);
    })->name('check.status');

    // 1-5. '시스템' 사용자 (Level 3~9) 전용 조직 관리 그룹 (변경 없음)
    Route::middleware(['role:Level 3|Level 4|Level 5|Level 6|Level 7|Level 8|Level 9'])
        ->prefix('system')->name('system.')->group(function () {
            Route::get('/users', [SystemUserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [SystemUserController::class, 'create'])->name('users.create');
            Route::post('/users', [SystemUserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/impersonate', [SystemUserController::class, 'impersonate'])->name('users.impersonate');
    });

    // 1-6. 'Admin' 역할 전용 관리자 그룹 (변경 없음)
    Route::middleware('role:Admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('/users', AdminUserController::class)->except(['show']);
        Route::post('/users/{user}/ban', [AdminUserController::class, 'ban'])->name('users.ban');
        Route::post('/users/{user}/unban', [AdminUserController::class, 'unban'])->name('users.unban');
        Route::post('/users/{user}/restore', [AdminUserController::class, 'restore'])->name('users.restore')->withTrashed();
        Route::get('/logic3', [Logic3PatternController::class, 'edit'])->name('logic3.edit');
        Route::put('/logic3', [Logic3PatternController::class, 'update'])->name('logic3.update');
        Route::resource('/logic2', Logic2PatternController::class);
    });

    // 1-7. 오직 'Level 2' 사용자만 접근 가능한 바카라 시스템 그룹 (변경 없음)
    Route::middleware(['check.approval', 'role:Level 2'])
        ->prefix('bacara')->name('bacara.')->group(function () {
            Route::get('/create', [BacaraController::class, 'create'])->name('create');
            Route::post('/', [BacaraController::class, 'store'])->name('store');
    });

});

// --- 2. 로그인하지 않은 사용자만 접근 가능한 경로들 (변경 없음) ---
Route::middleware('guest')->group(function() {
    Route::get('register', [RegisteredUserController::class, 'showCodeRequestForm'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'processCode']);
    Route::get('register/{code}', [RegisteredUserController::class, 'create'])->name('register.via_code');
    Route::post('registration', [RegisteredUserController::class, 'store'])->name('registration.store');
});


// --- 3. Breeze가 제공하는 나머지 인증 경로 (로그인, 로그아웃 등) ---
require __DIR__.'/auth.php';