<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'section_name',
    'division_id',
    'sec_acronym',
    'sec_status',
])]
class Section extends Model
{
    protected $table = 'lib_section';

    /** @return BelongsTo<Division, $this> */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'section_id');
    }
}
