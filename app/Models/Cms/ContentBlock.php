<?php

declare(strict_types=1);

namespace App\Models\Cms;

use App\Support\Content;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One editable field on a public page.
 *
 * A row overrides the default declared in config/cms-content.php; no row means
 * the registry default is used. Reads and caching live in
 * {@see Content} — this model just persists and busts the cache,
 * the same arrangement as {@see Setting} and {@see Settings}.
 */
class ContentBlock extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['page_key', 'field_key', 'locale', 'value', 'updated_by'];

    protected static function booted(): void
    {
        $bust = fn () => Content::flush();
        static::saved($bust);
        static::deleted($bust);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // nonQueued is mandatory: production has QUEUE_CONNECTION=sync and no
        // worker, so a queued conversion would never run and the image would
        // upload but never render.
        $this->addMediaConversion('thumb')->width(400)->nonQueued();
        $this->addMediaConversion('web')->width(1600)->format('webp')->nonQueued();
    }

    public function imageUrl(string $conversion = 'web'): ?string
    {
        $url = $this->getFirstMediaUrl('image', $conversion);

        return $url !== '' ? $url : null;
    }
}
