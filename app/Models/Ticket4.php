<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket4 extends Model
{
    use HasFactory;

    protected $table = '4ticket';
    protected $primaryKey = 'idx';
    public $timestamps = false;

    protected $fillable = [
        'memberid',
        '_pattern',
        'tpattern',
        'upattern',
        'npattern',
    ];

    protected $casts = [
        '_pattern' => 'array',
        'tpattern' => 'array',
        'upattern' => 'array',
        'npattern' => 'array',
    ];
}
