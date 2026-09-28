<?php

declare(strict_types=1);

namespace App\Models\Cms;

use App\Support\Categories;
use App\Support\SitemapCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Blog article. Mirrors pikionis/cms-core's Article model so the public site
 * can swap to the package model untouched.
 */
class Article extends Model implements HasMedia
{
    use HasFactory;
    use HasSlug;
    use InteractsWithMedia;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'author_id', 'category', 'cover_preset',
        'published_at', 'is_published', 'seo_title', 'seo_description',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Slug, published_at, is_published and updated_at all feed the cached
        // sitemap XML. Mass updates skip model events — bulk actions must call
        // SitemapCache::flush() themselves.
        $bust = fn () => SitemapCache::flush();
        static::saved($bust);
        static::deleted($bust);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
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

    /* ---------------------------------------------------------------- scopes */

    /** Published and past its publish date. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeInCategory(Builder $query, ?string $category): Builder
    {
        return $query->when(
            $category && app(Categories::class)->has($category),
            fn (Builder $q) => $q->where('category', $category)
        );
    }

    /**
     * Up to $limit published articles in the same category, newest first,
     * excluding this one. Falls back to latest published if too few.
     *
     * @return Collection<int, static>
     */
    public function related(int $limit = 3)
    {
        return static::published()
            ->with('author')
            ->whereKeyNot($this->getKey())
            ->where('category', $this->category)
            ->latest('published_at')
            ->take($limit)
            ->get();
    }

    /* ------------------------------------------------------------- accessors */

    /**
     * Label, pill colour and cover motif for this article's category.
     *
     * Reads the FULL list, archived categories included: withdrawing a
     * category must never change how the articles that already carry it look.
     *
     * @return array{label: string, color: string, motif: string}
     */
    public function categoryMeta(): array
    {
        $meta = $this->category ? app(Categories::class)->get($this->category) : null;

        if ($meta === null) {
            // Category row gone entirely (hard-deleted while unused, or data
            // written by hand). Degrade to the raw key rather than blanking.
            return [
                'label' => $this->category ?? '',
                'color' => 'green',
                'motif' => 'book',
            ];
        }

        // `archived` is bookkeeping for the admin; it is not part of what a
        // template asks for here.
        return [
            'label' => $meta['label'],
            'color' => $meta['color'],
            'motif' => $meta['motif'],
        ];
    }

    /**
     * What Google and a shared link show under the title.
     *
     * Never null. Passing null to @section makes Blade open an output buffer
     * and wait for an @endsection that an inline section never has — the
     * buffer leaks and the description silently comes out empty, which is
     * the one thing this field exists to prevent.
     *
     * Falls through to the opening of the article itself, so a piece written
     * in a hurry still says something in the results list.
     */
    /**
     * The picture a social platform shows when this article's link is posted.
     *
     * An uploaded cover first. Otherwise the chosen scene, as the PNG drawn
     * by `php artisan brand:images` — the scenes themselves are SVG, which
     * Facebook, WhatsApp and LinkedIn all refuse to read, so without the
     * raster copy every article would share the same generic card however
     * carefully its cover was picked.
     */
    public function shareImageUrl(): ?string
    {
        if (filled($cover = $this->getFirstMediaUrl('cover', 'web'))) {
            return $cover;
        }

        $scene = $this->cover_scene;

        if ($scene === null) {
            return null;
        }

        $path = "images/covers/{$scene}.png";

        return is_file(public_path($path)) ? asset($path) : null;
    }

    public function metaDescription(): string
    {
        foreach ([$this->seo_description, $this->excerpt] as $candidate) {
            if (filled($candidate)) {
                return trim((string) $candidate);
            }
        }

        $opening = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $this->body)));

        return $opening === '' ? '' : Str::limit($opening, 155);
    }

    public function getCategoryLabelAttribute(): string
    {
        return $this->categoryMeta()['label'];
    }

    public function getCategoryColorAttribute(): string
    {
        return $this->categoryMeta()['color'];
    }

    /** Branded SVG motif used when the article has neither cover nor scene. */
    public function getCoverMotifAttribute(): string
    {
        return $this->categoryMeta()['motif'];
    }

    /**
     * The chosen cover scene, or null.
     *
     * Guarded against a scene that has since been retired from the registry:
     * the column keeps whatever was picked, but only a scene that still has a
     * Blade partial may reach the page.
     */
    public function getCoverSceneAttribute(): ?string
    {
        $key = $this->cover_preset;

        return filled($key) && array_key_exists($key, config('cms-covers', []))
            ? $key
            : null;
    }
}
