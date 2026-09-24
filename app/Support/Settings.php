<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Cms\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Site settings reader.
 *
 * Loads the whole `settings` table once into a forever-cache as a flat
 * key => value array. The Setting model busts this cache on write, so reads
 * are effectively free and never hit the database after the first request.
 */
class Settings
{
    public const CACHE_KEY = 'cms.settings';

    /** @return array<string, string|null> */
    public function all(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => Setting::query()->pluck('value', 'key')->all()
        );
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $value = $this->all()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
