<?php

declare(strict_types=1);

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Editable page (About, Services …). Holds SEO fields and prose blocks; the
 * bespoke section layouts live in the Blade templates. Mirrors
 * pikionis/cms-core's Page model.
 */
class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'body', 'seo_title', 'seo_description', 'is_published',
    ];

    protected $casts = ['is_published' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
