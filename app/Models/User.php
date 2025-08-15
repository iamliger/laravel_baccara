<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'banned_at',
        'parent_id', // ★ 추가
        'recommendation_code', // ★ 추가
        'profit_percentage', // ★ 추가
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * 나의 상위 추천인 (부모) 정보를 가져오는 관계
     */
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * 내가 추천한 모든 하위 사용자 (자식) 목록을 가져오는 관계
     */
    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * 나의 모든 하위 조직원 (자손) 목록을 가져오는 관계
     */
    public function descendants()
    {
        // children 관계를 가져오고, 그 children의 children을 다시 재귀적으로 가져옵니다.
        return $this->children()->with('descendants');
    }

    /**
     * 나의 모든 상위 추천인 (조상) 목록을 가져옵니다.
     */
    public function ancestors()
    {
        $ancestors = collect([]); // 빈 컬렉션을 만듭니다.
        $parent = $this->parent; // 나의 직속 부모부터 시작합니다.

        // 부모가 더 이상 없을 때까지 (최상위 조직에 도달할 때까지) 반복합니다.
        while ($parent) {
            $ancestors->push($parent); // 현재 부모를 목록에 추가합니다.
            $parent = $parent->parent; // 그 부모의 부모를 찾아서 다음 반복을 준비합니다.
        }

        return $ancestors;
    }
}
