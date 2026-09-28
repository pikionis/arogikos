<?php

declare(strict_types=1);

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Event / workshop. Mirrors pikionis/cms-core's Event model.
 */
class Event extends Model implements HasMedia
{
    use HasFactory;
    use HasSlug;
    use InteractsWithMedia;

    /** Greek 3-letter month abbreviations, indexed 1–12. */
    private const MONTHS_EL = [
        1 => 'Ιαν', 2 => 'Φεβ', 3 => 'Μαρ', 4 => 'Απρ', 5 => 'Μαϊ', 6 => 'Ιουν',
        7 => 'Ιουλ', 8 => 'Αυγ', 9 => 'Σεπ', 10 => 'Οκτ', 11 => 'Νοε', 12 => 'Δεκ',
    ];

    protected $fillable = [
        'title', 'slug', 'starts_at', 'location', 'body', 'is_published', 'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'starts_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->nonQueued();
        $this->addMediaConversion('web')->width(1600)->format('webp')->nonQueued();
    }

    /**
     * Announced, and the announcement's date has arrived.
     *
     * published_at is when the announcement goes live; starts_at is when the
     * event happens. A talk written in September for November is announced in
     * October and listed under November.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereNotNull('starts_at')
            ->where('starts_at', '>=', now()->startOfDay())
            ->orderBy('starts_at');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->whereNotNull('starts_at')
            ->where('starts_at', '<', now()->startOfDay())
            ->orderByDesc('starts_at');
    }

    public function getDayAttribute(): ?string
    {
        return $this->starts_at?->format('d');
    }

    public function getMonthShortAttribute(): ?string
    {
        return $this->starts_at ? self::MONTHS_EL[(int) $this->starts_at->format('n')] : null;
    }
}
