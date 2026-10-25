<?php

use App\Filament\Resources\RestaurantTables\Pages\CreateRestaurantTable;
use App\Filament\Resources\RestaurantTables\Pages\EditRestaurantTable;
use App\Filament\Resources\RestaurantTables\Pages\ListRestaurantTables;
use App\Filament\Resources\RestaurantTables\Pages\ViewRestaurantTableQrCode;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create();
    $this->user = User::factory()->create();
    $this->restaurant->users()->attach($this->user, ['role' => 'owner']);

    $this->actingAs($this->user);
});

function useRestaurantTableTenant(Restaurant $restaurant): void
{
    Filament::setTenant($restaurant);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();
}

it('creates and edits tables within the active restaurant and assigns a UUID', function () {
    useRestaurantTableTenant($this->restaurant);

    Livewire::test(CreateRestaurantTable::class)
        ->fillForm([
            'name' => 'Mesa do jardim',
            'code' => 'M21',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $table = RestaurantTable::query()->where('code', 'M21')->firstOrFail();

    expect($table->restaurant_id)->toBe($this->restaurant->id)
        ->and($table->uuid)->toMatch('/^[0-9a-f-]{36}$/');

    Livewire::test(EditRestaurantTable::class, ['record' => $table->getKey()])
        ->fillForm(['name' => 'Mesa jardim', 'is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($table->fresh()->name)->toBe('Mesa jardim')
        ->and($table->fresh()->is_active)->toBeFalse();
});

it('lists only the active tenant tables and rejects another tenant table URL', function () {
    $ownTable = $this->restaurant->tables()->create(['name' => 'Mesa própria', 'code' => 'M01']);
    $otherRestaurant = Restaurant::factory()->create();
    $otherTable = $otherRestaurant->tables()->create(['name' => 'Mesa externa', 'code' => 'M01']);

    useRestaurantTableTenant($this->restaurant);

    Livewire::test(ListRestaurantTables::class)
        ->assertCanSeeTableRecords([$ownTable])
        ->assertCanNotSeeTableRecords([$otherTable]);

    Livewire::test(EditRestaurantTable::class, ['record' => $otherTable->getKey()])
        ->assertNotFound();
});

it('opens an authorized QR page containing the public UUID menu URL and SVG', function () {
    $table = $this->restaurant->tables()->create(['name' => 'Mesa QR', 'code' => 'M07']);

    useRestaurantTableTenant($this->restaurant);

    $url = route('public.menu.table', [$this->restaurant->slug, $table->uuid]);

    Livewire::test(ViewRestaurantTableQrCode::class, ['record' => $table->getKey()])
        ->assertSee($url)
        ->assertSee('<svg', false)
        ->assertSee('Mesa QR');

    $table->update(['is_active' => false]);

    Livewire::test(ViewRestaurantTableQrCode::class, ['record' => $table->getKey()])
        ->assertNotFound();
});

it('does not allow a kitchen member to manage tables or view their QR', function () {
    $table = $this->restaurant->tables()->create(['name' => 'Mesa cozinha', 'code' => 'M02']);
    $kitchenUser = User::factory()->create();
    $this->restaurant->users()->attach($kitchenUser, ['role' => 'kitchen']);

    useRestaurantTableTenant($this->restaurant);

    expect($kitchenUser->can('viewAny', RestaurantTable::class))->toBeFalse()
        ->and($kitchenUser->can('create', RestaurantTable::class))->toBeFalse()
        ->and($kitchenUser->can('view', $table))->toBeFalse();
});

it('validates duplicate table codes within the active restaurant', function () {
    $this->restaurant->tables()->create(['name' => 'Mesa um', 'code' => 'M01']);

    useRestaurantTableTenant($this->restaurant);

    Livewire::test(CreateRestaurantTable::class)
        ->fillForm(['name' => 'Duplicada', 'code' => 'M01'])
        ->call('create')
        ->assertHasFormErrors(['code']);
});
