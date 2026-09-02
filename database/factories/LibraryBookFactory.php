<?php

namespace Database\Factories;

use App\Models\LibraryBook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LibraryBook>
 */
class LibraryBookFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'isbn' => (string) fake()->unique()->numerify('978##########'),
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'publisher' => fake()->company(),
            'year' => fake()->numberBetween(1950, 2026),
            'edition' => null,
            'condition' => null,
            'acquired_at' => fake()->optional()->dateTimeBetween('-2 years'),
        ];
    }
}
