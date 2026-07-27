<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wish;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WishSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $users = User::factory(3)->create();
        }

        $users->each(function (User $user): void {
            Wish::factory()
                ->count(fake()->numberBetween(3, 8))
                ->forOwner($user)
                ->create();

            Wish::factory()
                ->withoutThumbnail()
                ->forOwner($user)
                ->create();
        });
    }
}
