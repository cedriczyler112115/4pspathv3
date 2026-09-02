<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'division_name',
    'division_head',
    'division_signatory',
    'div_status',
    'head_pos',
])]
class Division extends Model
{
    protected $table = 'lib_division';

    /** @return HasMany<Section, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'division_id');
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'division_id');
    }
}
