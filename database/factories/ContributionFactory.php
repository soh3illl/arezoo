<?php

namespace Database\Factories;

use App\Enums\ContributionStatus;
use App\Enums\ContributionVisibility;
use App\Models\Contribution;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contribution>
 */
class ContributionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wish_id' => Wish::factory(),
            'contributor_id' => User::factory(),
            'payment_id' => fn (array $attributes) => Payment::factory()->state(['amount' => $attributes['amount']]),
            'amount' => fake()->numberBetween(5, 500) * 1_000,
            'message' => null,
            'visibility' => ContributionVisibility::Public,
            'status' => ContributionStatus::Pending,
        ];
    }

    /**
     * Indicate the wish the contribution was made towards.
     */
    public function forWish(Wish $wish): static
    {
        return $this->for($wish, 'wish');
    }

    /**
     * Indicate the user who made the contribution.
     */
    public function forContributor(User $contributor): static
    {
        return $this->for($contributor, 'contributor');
    }

    /**
     * Indicate that the contributor has not returned from the gateway yet.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContributionStatus::Pending,
        ]);
    }

    /**
     * Indicate that the money landed, paired with the verified payment that carried it.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_id' => fn (array $attributes) => Payment::factory()->verified()->state([
                'amount' => $attributes['amount'],
            ]),
            'status' => ContributionStatus::Paid,
        ]);
    }
}
