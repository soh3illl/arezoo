<?php

namespace Database\Seeders;

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContributionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * Contributors never give to their own wish, and a few wishes are left
     * with a pending attempt so the unpaid case is visible locally too.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->count() < 2) {
            $users = $users->merge(User::factory(3)->create());
        }

        Wish::all()->each(function (Wish $wish) use ($users): void {
            $contributors = $users->where('id', '!=', $wish->user_id);

            if ($contributors->isEmpty()) {
                return;
            }

            $contributors->random(fake()->numberBetween(0, $contributors->count()))
                ->each(fn (User $contributor) => Contribution::factory()
                    ->forWish($wish)
                    ->forContributor($contributor)
                    ->paid()
                    ->create(['amount' => fake()->numberBetween(1, (int) ($wish->price / 1_000)) * 1_000])
                );

            if (fake()->boolean(20)) {
                Contribution::factory()
                    ->forWish($wish)
                    ->forContributor($contributors->random())
                    ->pending()
                    ->create();
            }
        });
    }
}
