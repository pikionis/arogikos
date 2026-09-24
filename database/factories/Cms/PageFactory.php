<?php

declare(strict_types=1);

namespace Database\Factories\Cms;

use App\Models\Cms\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = rtrim($this->faker->sentence(3), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'body' => '<p>'.implode('</p><p>', $this->faker->paragraphs(3)).'</p>',
            'is_published' => true,
        ];
    }
}
