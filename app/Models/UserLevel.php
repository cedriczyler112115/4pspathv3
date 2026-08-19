<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $level_id
 * @property string $level_name
 * @property int|null $is_status
 */
#[Fillable([
    'level_name',
    'is_status',
])]
class UserLevel extends Model
{
    protected $table = 'user_level';
    protected $primaryKey = 'level_id';

    public $timestamps = false;

    protected $casts = [
        'is_status' => 'int',
    ];
}
