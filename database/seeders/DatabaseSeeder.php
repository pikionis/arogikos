<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the demo content used for development and the client presentation.
     * Order matters: authors before articles (FK), albums before photos.
     */
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            CategorySeeder::class,
            AuthorSeeder::class,
            ArticleSeeder::class,
            EventSeeder::class,
            PageSeeder::class,
            GallerySeeder::class,
        ]);
    }
}
