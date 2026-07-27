<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wish;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wish>
 */
class WishFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'thumbnail' => fake()->imageUrl(640, 480, 'products'),
            'purchase_link' => fake()->url(),
            'price' => fake()->numberBetween(5, 2_000) * 1_000,
        ];
    }

    /**
     * Indicate that the wish has no thumbnail image.
     */
    public function withoutThumbnail(): static
    {
        return $this->state(fn (array $attributes) => [
            'thumbnail' => null,
        ]);
    }
}
