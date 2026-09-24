<?php

declare(strict_types=1);

namespace Database\Factories\Cms;

use App\Models\Cms\Article;
use App\Models\Cms\Author;
use Illuminate\Database\Eloquent\Factories\Factory;
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
            'author_id' => Author::factory(),
            'category' => $this->faker->randomElement(array_keys(config('arogi.categories'))),
            'published_at' => $this->faker->dateTimeBetween('-6 months'),
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false, 'published_at' => null]);
    }
}
