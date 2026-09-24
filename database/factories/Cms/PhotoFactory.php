<?php

declare(strict_types=1);

namespace Database\Factories\Cms;

use App\Models\Cms\Album;
use App\Models\Cms\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        return [
            'album_id' => Album::factory(),
            'caption' => $this->faker->sentence(3),
            'sort_order' => 0,
        ];
    }
}
