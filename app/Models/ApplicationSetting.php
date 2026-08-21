<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ApplicationSetting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    public static function valueFor(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        return Cache::rememberForever('application-setting.'.$key, fn (): mixed => static::query()->where('key', $key)->value('value') ?? $default);
    }

    public static function boolean(string $key, bool $default = false): bool
    {
        return filter_var(static::valueFor($key, $default ? '1' : '0'), FILTER_VALIDATE_BOOL);
    }

    public static function put(string $key, mixed $value, string $type, ?string $description = null): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'type' => $type, 'description' => $description],
        );

        Cache::forget('application-setting.'.$key);
    }
}
