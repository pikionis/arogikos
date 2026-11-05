<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Cms\Category;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Reader for blog categories.
 *
 * Replaces the old config('arogi.categories') map. Cached forever and busted
 * by the Category model on write, the same arrangement as {@see Settings}.
 *
 * Two distinct lists, and the difference matters:
 *   all()    — every category, archived included. Feeds the label/colour/motif
 *              of an article that already carries the key, so archiving never
 *              changes how an existing article renders.
 *   active() — what the picker offers and what /blog shows as filter chips.
 */
class Categories
{
    public const CACHE_KEY = 'cms.categories';

    /** @var array<string, array{label: string, color: string, motif: string, archived: bool}>|null */
    private ?array $map = null;

    /** @return array<string, array{label: string, color: string, motif: string, archived: bool}> */
    public function all(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        try {
            $this->map = Cache::rememberForever(self::CACHE_KEY, fn () => Category::query()
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get()
                ->mapWithKeys(fn (Category $c) => [$c->key => [
                    'label' => $c->label,
                    'color' => $c->color,
                    'motif' => $c->motif,
                    'archived' => $c->isArchived(),
                ]])
                ->all());
        } catch (Throwable) {
            // Before the table exists (fresh clone, mid-migration) fall back to
            // the shipped config so the public site still renders.
            $this->map = [];
        }

        if ($this->map === []) {
            $this->map = $this->fromConfig();
        }

        return $this->map;
    }

    /** @return array<string, array{label: string, color: string, motif: string, archived: bool}> */
    public function active(): array
    {
        return array_filter($this->all(), fn (array $c) => ! $c['archived']);
    }

    /**
     * What the article form should offer.
     *
     * The active list, plus the article's own category even when archived —
     * otherwise opening an old article would silently drop its category the
     * moment it was saved.
     *
     * @return array<string, array{label: string, color: string, motif: string, archived: bool}>
     */
    public function forPicker(string ...$current): array
    {
        $options = $this->active();

        // An article keeps a withdrawn category it already carries, so the
        // picker has to offer it back or saving would quietly drop it.
        foreach ($current as $key) {
            if ($key !== '' && ! isset($options[$key]) && $this->has($key)) {
                $options[$key] = $this->all()[$key];
            }
        }

        return $options;
    }

    /** @return array{label: string, color: string, motif: string, archived: bool}|null */
    public function get(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /** @return array<string, array{label: string, color: string, motif: string, archived: bool}> */
    private function fromConfig(): array
    {
        return array_map(
            fn (array $c) => $c + ['archived' => false],
            (array) config('arogi.categories', [])
        );
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        app()->forgetInstance(self::class);
    }
}
