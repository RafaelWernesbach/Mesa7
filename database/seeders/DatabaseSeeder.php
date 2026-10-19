<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password', 'email_verified_at' => now()],
        );

        $restaurant = Restaurant::query()->firstOrCreate(
            ['slug' => 'mesa7-demo'],
            ['name' => 'Mesa7 Demo'],
        );

        $restaurant->users()->syncWithoutDetaching([$user->id => ['role' => 'owner']]);

        if (! $restaurant->products()->exists()) {
            Product::factory()->count(3)->for($restaurant)->create();
        }
    }
}
