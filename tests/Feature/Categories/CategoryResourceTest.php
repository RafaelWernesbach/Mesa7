<?php

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create();
    $this->user = User::factory()->create();
    $this->restaurant->users()->attach($this->user, ['role' => 'owner']);

    $this->actingAs($this->user);
});

function useCategoryTenant(Restaurant $restaurant): void
{
    Filament::setTenant($restaurant);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();
}

it('creates and edits categories for the active restaurant', function () {
    useCategoryTenant($this->restaurant);

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => 'Bebidas',
            'slug' => 'bebidas',
            'description' => 'Bebidas geladas e sucos.',
            'sort_order' => 2,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $category = Category::query()->where('slug', 'bebidas')->firstOrFail();

    expect($category->restaurant_id)->toBe($this->restaurant->id)
        ->and($category->name)->toBe('Bebidas');

    Livewire::test(EditCategory::class, ['record' => $category->getKey()])
        ->fillForm(['name' => 'Bebidas e sucos', 'sort_order' => 1])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->fresh()->name)->toBe('Bebidas e sucos')
        ->and($category->fresh()->sort_order)->toBe(1);
});

it('lists only the active restaurant categories and rejects other tenant category URLs', function () {
    $ownCategory = Category::query()->create([
        'restaurant_id' => $this->restaurant->id,
        'name' => 'Lanches',
        'slug' => 'lanches',
    ]);
    $otherRestaurant = Restaurant::factory()->create();
    $otherCategory = Category::query()->create([
        'restaurant_id' => $otherRestaurant->id,
        'name' => 'Bebidas',
        'slug' => 'bebidas',
    ]);

    useCategoryTenant($this->restaurant);

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords([$ownCategory])
        ->assertCanNotSeeTableRecords([$otherCategory]);

    Livewire::test(EditCategory::class, ['record' => $otherCategory->getKey()])
        ->assertNotFound();
});

it('does not allow a kitchen member to manage categories', function () {
    $kitchenUser = User::factory()->create();
    $this->restaurant->users()->attach($kitchenUser, ['role' => 'kitchen']);

    useCategoryTenant($this->restaurant);

    expect($kitchenUser->can('viewAny', Category::class))->toBeFalse()
        ->and($kitchenUser->can('create', Category::class))->toBeFalse();
});
