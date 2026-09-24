<?php

declare(strict_types=1);

namespace Database\Factories\Cms;

use App\Models\Cms\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'message' => $this->faker->paragraph(),
            'read_at' => null,
        ];
    }
}
