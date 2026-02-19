<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Retrieve all settings from cache.
     *
     * @return array<string, string|null>
     */
    public static function cached(): array
    {
        return Cache::rememberForever('settings.cached', fn (): array => self::query()
            ->pluck('value', 'key')
            ->toArray());
    }

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return self::cached()[$key] ?? $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget('settings.cached');
    }
}
