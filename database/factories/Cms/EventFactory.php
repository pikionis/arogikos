<?php

declare(strict_types=1);

namespace Database\Factories\Cms;

use App\Models\Cms\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $title = rtrim($this->faker->sentence(5), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'starts_at' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
            'location' => $this->faker->randomElement(['Αίθουσα Αρωγής', 'Αρωγή, Κως']),
            'body' => $this->faker->paragraph(),
            'is_published' => true,
            // Announced already; a test that wants a scheduled one sets it.
            'published_at' => now()->subDay(),
        ];
    }

    /** Written, but the announcement has not gone live yet. */
    public function scheduled(): static
    {
        return $this->state(['is_published' => true, 'published_at' => now()->addWeek()]);
    }

    public function upcoming(): static
    {
        return $this->state(['starts_at' => $this->faker->dateTimeBetween('+1 week', '+3 months')]);
    }

    public function past(): static
    {
        return $this->state(['starts_at' => $this->faker->dateTimeBetween('-3 months', '-1 week')]);
    }
}
