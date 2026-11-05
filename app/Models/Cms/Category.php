<?php

declare(strict_types=1);

namespace App\Models\Cms;

use App\Support\Categories;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A blog category.
 *
 * Was a config array (config/arogi.php); now a table so staff can manage it
 * from the article form. `key` is the value stored on articles.category.
 */
class Category extends Model implements Arrayable
{
    use HasFactory;

    /**
     * Colour is not free text: each value maps to CSS the design ships
     * (.pill-green / .pill-coral / .pill-yellow and the soft tints in
     * resources/css/arogi.css).
     *
     * @var array<string, string>
     */
    public const COLORS = [
        'green' => 'Πράσινο',
        'coral' => 'Κοραλί',
        'yellow' => 'Κίτρινο',
    ];

    /**
     * Motif is the branded SVG drawn by resources/js/app.js when an article
     * has no cover image. Anything outside this list silently falls back to
     * 'figures', so the admin offers exactly these.
     *
     * @var array<string, string>
     */
    public const MOTIFS = [
        'book' => 'Βιβλίο',
        'one' => 'Ένα παιδί',
        'group' => 'Ομάδα',
        'sun' => 'Ήλιος',
        'figures' => 'Φιγούρες',
    ];

    protected $fillable = ['key', 'label', 'color', 'motif', 'sort_order', 'archived_at'];

    protected $casts = ['archived_at' => 'datetime'];

    protected static function booted(): void
    {
        $bust = fn () => Categories::flush();
        static::saved($bust);
        static::deleted($bust);
    }

    /**
     * Bind by `key`, not id.
     *
     * The admin panel addresses categories by key (it is what articles store
     * and what the JSON payload carries), and the key is immutable by design —
     * so it is a stable, readable route key.
     */
    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_category', 'category_key', 'article_id', 'key', 'id');
    }

    /* ---------------------------------------------------------------- scopes */

    /** Offered in the picker and shown as a filter chip on /blog. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /* ------------------------------------------------------------- helpers */

    /** @return array{label: string, color: string, motif: string} */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'color' => $this->color,
            'motif' => $this->motif,
        ];
    }

    public function colorLabel(): string
    {
        return self::COLORS[$this->color] ?? $this->color;
    }

    public function motifLabel(): string
    {
        return self::MOTIFS[$this->motif] ?? $this->motif;
    }
}
