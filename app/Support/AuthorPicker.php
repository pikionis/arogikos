<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Cms\Author;
use Illuminate\Support\Collection;

/**
 * Options for the article "συντάκτης" dropdown.
 *
 * The brief's requirement is that the byline can be chosen from the people who
 * have panel access — so those come first, in their own optgroup — while the
 * standalone author profiles that predate accounts stay selectable below.
 *
 * Deliberately NOT filtered by is_active: profiles auto-created for invited
 * members start inactive (that flag governs the public About page team grid,
 * not whether someone can be credited for an article).
 */
class AuthorPicker
{
    /** @return array<string, Collection> optgroup label => authors */
    public static function groups(): array
    {
        /** @var class-string<Author> $authorModel */
        $authorModel = cms_model('author');

        $authors = $authorModel::query()
            ->with('user')
            ->ordered()
            ->get();

        return array_filter([
            'Μέλη του panel' => $authors->filter(fn ($a) => $a->user_id !== null)->values(),
            'Άλλοι συντάκτες' => $authors->filter(fn ($a) => $a->user_id === null)->values(),
        ], fn (Collection $group) => $group->isNotEmpty());
    }
}
