<?php

use App\Models\Restaurant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('seeds users with access to multiple restaurants and multiple users per restaurant', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();
    $attendant = User::query()->where('email', 'carla@mesa7.demo')->firstOrFail();
    $restaurants = Restaurant::query()->orderBy('slug')->get();
    $demoRestaurant = Restaurant::query()->where('slug', 'mesa7-demo')->firstOrFail();

    expect($restaurants)->toHaveCount(2)
        ->and($user->restaurants)->toHaveCount(2)
        ->and($demoRestaurant->users()->whereKey($attendant->getKey())->first()?->pivot->role)->toBe('attendant');

    foreach ($restaurants as $restaurant) {
        expect($restaurant->users()->count())->toBeGreaterThanOrEqual(2);
    }
});

it('seeds at least twenty products for every demo restaurant', function () {
    $this->seed(DatabaseSeeder::class);

    $restaurants = Restaurant::query()->withCount('products')->get();

    expect($restaurants)->toHaveCount(2);

    foreach ($restaurants as $restaurant) {
        expect($restaurant->products_count)->toBeGreaterThanOrEqual(20);
    }
});

it('seeds categorized menus and active table codes for every demo restaurant', function () {
    $this->seed(DatabaseSeeder::class);

    foreach (Restaurant::query()->withCount(['categories', 'tables'])->get() as $restaurant) {
        expect($restaurant->categories_count)->toBeGreaterThan(0)
            ->and($restaurant->tables_count)->toBeGreaterThan(0)
            ->and($restaurant->products()->whereNull('category_id')->count())->toBe(0)
            ->and($restaurant->tables()->where('code', 'M07')->where('is_active', true)->whereNotNull('uuid')->exists())->toBeTrue();
    }
});

it('can be rerun without duplicating demo memberships or products', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Restaurant::query()->count())->toBe(2);

    foreach (Restaurant::query()->withCount(['users', 'products'])->get() as $restaurant) {
        expect($restaurant->users_count)->toBeGreaterThanOrEqual(2)
            ->and($restaurant->products_count)->toBe(20);
    }

    expect(User::query()->where('email', 'test@example.com')->firstOrFail()->restaurants()->count())->toBe(2);
});
