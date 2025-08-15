<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clslog extends Model
{
    use HasFactory;

    protected $table = 'clslog';
    protected $primaryKey = 'idx';

    // log_datetime 컬럼만 사용하고, updated_at은 사용하지 않음
    const CREATED_AT = 'log_datetime';
    const UPDATED_AT = null;

    protected $fillable = [
        'gubun',
        'log',
    ];
}
