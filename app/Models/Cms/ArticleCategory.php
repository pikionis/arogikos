<?php

declare(strict_types=1);

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One link between an article and a category.
 *
 * A model of its own rather than a plain pivot, because what an article is
 * filed under has to be readable even when the categories table does not
 * hold that key — App\Support\Categories falls back to config/arogi.php, and
 * a join would have quietly dropped anything that lives only there.
 */
class ArticleCategory extends Model
{
    protected $table = 'article_category';

    public $timestamps = false;

    protected $fillable = ['article_id', 'category_key', 'position'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
