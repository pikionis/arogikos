<?php

declare(strict_types=1);

namespace App\Models\Cms;

use App\Support\Categories;
use App\Support\SitemapCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'title', 'slug', 'excerpt', 'body', 'cover_preset',
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

    /**
     * Everyone who signed it.
     *
     * Ordered by the Συντάκτες screen rather than by when they were ticked:
     * the centre already decides who comes first there, and a second order
     * living on the pivot would be a second answer to the same question.
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class)
            ->orderBy('authors.position')
            ->orderBy('authors.name');
    }

    /**
     * Everything the article is about.
     *
     * In the order they were chosen: the first one colours the card and
     * supplies the drawing an article without a cover falls back to, so which
     * one leads is a decision rather than an accident of alphabet.
     */
    public function categoryLinks(): HasMany
    {
        return $this->hasMany(ArticleCategory::class)->orderBy('position');
    }

    /** @return array<int, string> */
    public function categoryKeys(): array
    {
        return $this->categoryLinks->pluck('category_key')->all();
    }

    /**
     * The categories, resolved to label, colour and drawing.
     *
     * Through App\Support\Categories rather than a join, so a key that only
     * exists in config, or one that has been withdrawn, still describes the
     * articles that carry it.
     *
     * @return array<string, array{label: string, color: string, motif: string, archived: bool}>
     */
    public function categoryList(): array
    {
        $all = app(Categories::class)->all();

        $list = [];
        foreach ($this->categoryKeys() as $key) {
            if (isset($all[$key])) {
                $list[$key] = $all[$key];
            }
        }

        return $list;
    }

    /** The byline as one line: "Α, Β και Γ". */
    public function byline(): string
    {
        $names = $this->authors->pluck('name')->filter()->values();

        if ($names->count() < 2) {
            return (string) $names->first();
        }

        return $names->slice(0, -1)->implode(', ').' και '.$names->last();
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
            fn (Builder $q) => $q->whereHas(
                'categoryLinks',
                fn (Builder $c) => $c->where('category_key', $category)
            )
        );
    }

    /**
     * What to read next, newest first.
     *
     * Anything else this article's authors have written comes first — if you
     * liked how somebody writes, the next thing you want is more of them —
     * and the rest of the blog follows behind it, most recent first.
     *
     * Deliberately not filtered by category any more. A category with two
     * articles in it left a reader at the end of a page with one card and
     * nowhere to go, which is the opposite of what this section is for.
     *
     * @return Collection<int, static>
     */
    public function related(int $limit = 9)
    {
        $authorIds = $this->authors->modelKeys();

        $newest = fn () => static::published()
            ->with(['authors', 'categoryLinks', 'media'])
            ->latest('published_at');

        // An Eloquent collection even when empty: a plain one has no
        // modelKeys(), and the next query asks it for exactly that.
        $mine = $authorIds === []
            ? new Collection
            : $newest()
                ->whereKeyNot($this->getKey())
                ->whereHas('authors', fn (Builder $q) => $q->whereIn('authors.id', $authorIds))
                ->take($limit)
                ->get();

        if ($mine->count() >= $limit) {
            return $mine;
        }

        $rest = $newest()
            ->whereNotIn($this->getKeyName(), [$this->getKey(), ...$mine->modelKeys()])
            ->take($limit - $mine->count())
            ->get();

        return $mine->concat($rest);
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
    /**
     * The category that leads: the one the card wears.
     *
     * Read-only and derived — the categories themselves live in the pivot, so
     * there is nowhere for a second answer to drift into.
     */
    public function getCategoryAttribute(): ?string
    {
        return $this->categoryKeys()[0] ?? null;
    }

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
