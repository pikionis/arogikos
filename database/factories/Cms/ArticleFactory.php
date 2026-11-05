<?php

declare(strict_types=1);

namespace Database\Factories\Cms;

use App\Models\Cms\Article;
use App\Models\Cms\Author;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = rtrim($this->faker->sentence(6), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'excerpt' => $this->faker->sentence(12),
            'body' => '<p>'.implode('</p><p>', $this->faker->paragraphs(4)).'</p>',
            'published_at' => $this->faker->dateTimeBetween('-6 months'),
            'is_published' => true,
        ];
    }

    /**
     * Understands the old shorthand: create(['category' => 'learning']).
     *
     * There is no `category` column any more — an article is filed under as
     * many as it is about — but "this article is in this category" is still
     * exactly what that line says, and rewriting it in a hundred tests would
     * have changed their meaning not at all.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        if (is_array($attributes) && array_key_exists('category', $attributes)) {
            $keys = array_filter((array) $attributes['category'], fn ($k) => filled($k));
            unset($attributes['category']);

            return $this->inCategory(...$keys)->create($attributes, $parent);
        }

        return parent::create($attributes, $parent);
    }

    /**
     * Filed under these categories, in this order.
     *
     * An article has none unless it is given some, so a test that cares about
     * a category has to say which — the same bargain as the byline.
     */
    public function inCategory(string ...$keys): static
    {
        return $this->afterCreating(function (Article $article) use ($keys) {
            foreach (array_values($keys) as $i => $key) {
                $article->categoryLinks()->firstOrCreate(
                    ['category_key' => $key],
                    ['position' => $i + 1],
                );
            }
        });
    }

    /**
     * Signed by these people.
     *
     * An article has no byline unless somebody is named, so a test that
     * cares about one has to say so.
     */
    public function by(Author|int ...$authors): static
    {
        return $this->afterCreating(function (Article $article) use ($authors) {
            $article->authors()->syncWithoutDetaching(
                array_map(fn (Author|int $a) => $a instanceof Author ? $a->getKey() : $a, $authors)
            );
        });
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false, 'published_at' => null]);
    }
}
