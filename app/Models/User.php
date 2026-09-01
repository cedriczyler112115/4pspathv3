<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'last_name',
    'first_name',
    'middle_name',
    'extension_name',
    'position',
    'designation',
    'division_id',
    'section_id',
    'division',
    'section',
    'contact_number',
    'supervisor_id',
    'is_supervisor',
    'email',
    'password',
    'google_id',
    'user_level_id',
    'can_scorecard',
    'avatar',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $appends = ['avatar_url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'user_level_id' => 'int',
            'is_supervisor' => 'boolean',
            'can_scorecard' => 'int',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! empty($this->avatar)) {
            if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://') || str_starts_with($this->avatar, '/')) {
                return $this->avatar;
            }

            return asset('storage/'.$this->avatar);
        }

        if (isset($this->attributes['google_avatar_url']) && ! empty($this->attributes['google_avatar_url'])) {
            return $this->attributes['google_avatar_url'];
        }

        return null;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /** @return BelongsTo<UserLevel, $this> */
    public function userLevel(): BelongsTo
    {
        return $this->belongsTo(UserLevel::class, 'user_level_id', 'level_id');
    }

    /** @return BelongsTo<User, $this> */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supervisor_id');
    }

    /** @return BelongsTo<Division, $this> */
    public function divisionRelation(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    /** @return BelongsTo<Section, $this> */
    public function sectionRelation(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /** @return MorphToMany<Role, $this> */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles');
    }

    public function isAdministrator(): bool
    {
        if ($this->id === 3) {
            return true;
        }

        if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
            return false;
        }

        return $this->roles()->where('name', 'admin')->exists();
    }
}
