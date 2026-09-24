<?php

declare(strict_types=1);

namespace App\Models\Cms;

use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;

/**
 * Key/value settings store.
 *
 * Mirrors pikionis/cms-core's Setting model (table `settings`, columns
 * key/value) so the public site can swap to the package model untouched.
 * Reads/caching are handled by {@see Settings}; this model
 * just persists rows and busts the cache on write.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        $bust = fn () => Settings::flush();
        static::saved($bust);
        static::deleted($bust);
    }
}
