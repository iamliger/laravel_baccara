<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaccaraConfig extends Model
{
    use HasFactory;

    use HasFactory;
    protected $table = 'baccara_config';
    protected $primaryKey = 'bc_id';
    public $timestamps = false;
    protected $fillable = [
        'logic3_patterns',
        'profit_rate',
        'another_setting',
    ];
    protected $casts = [
        'logic3_patterns' => 'array',
        'profit_rate' => 'array',
    ];
}
