<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Every test in Feature/ and Unit/ runs against the application TestCase
| with a fresh in-memory SQLite schema (RefreshDatabase). The testing DB is
| :memory: (see phpunit.xml), so tests never touch the seeded dev database.
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');
