<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create();
    $this->user = User::factory()->create();
    $this->restaurant->users()->attach($this->user, ['role' => 'owner']);

    $this->actingAs($this->user);
});

function useProductTenant(Restaurant $restaurant): void
{
    Filament::setTenant($restaurant);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();
}

it('completes the product create update and delete flow for the active tenant', function () {
    useProductTenant($this->restaurant);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Hambúrguer da Casa',
            'description' => 'Pão, queijo e molho especial',
            'price' => '32.90',
            'is_available' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::query()->where('name', 'Hambúrguer da Casa')->firstOrFail();

    expect($product->restaurant_id)->toBe($this->restaurant->id)
        ->and($product->price)->toBe('32.90');

    Livewire::test(EditProduct::class, ['record' => $product->getKey()])
        ->fillForm(['name' => 'Hambúrguer Mesa7', 'price' => '35.00'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->fresh()->name)->toBe('Hambúrguer Mesa7')
        ->and($product->fresh()->price)->toBe('35.00');

    Livewire::test(EditProduct::class, ['record' => $product->getKey()])
        ->callAction(DeleteAction::class);

    $this->assertDatabaseMissing(Product::class, ['id' => $product->getKey()]);
});

it('validates required product name and positive price', function () {
    useProductTenant($this->restaurant);

    Livewire::test(CreateProduct::class)
        ->fillForm(['name' => '', 'price' => '-1'])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required', 'price' => 'min']);

    $this->assertDatabaseCount('products', 0);
});

it('shows only the active tenant products and rejects another tenant product URL', function () {
    $otherRestaurant = Restaurant::factory()->create();
    $ownProduct = Product::factory()->for($this->restaurant)->create();
    $otherProduct = Product::factory()->for($otherRestaurant)->create();

    useProductTenant($this->restaurant);

    Livewire::test(ListProducts::class)
        ->assertCanSeeTableRecords([$ownProduct])
        ->assertCanNotSeeTableRecords([$otherProduct]);

    Livewire::test(EditProduct::class, ['record' => $otherProduct->getKey()])
        ->assertNotFound();
});

it('does not allow a kitchen member to manage products', function () {
    $kitchenUser = User::factory()->create();
    $this->restaurant->users()->attach($kitchenUser, ['role' => 'kitchen']);

    useProductTenant($this->restaurant);

    expect($kitchenUser->can('viewAny', Product::class))->toBeFalse()
        ->and($kitchenUser->can('create', Product::class))->toBeFalse();
});

it('allows only linked users to access a restaurant tenant', function () {
    $otherUser = User::factory()->create();

    expect($this->user->canAccessTenant($this->restaurant))->toBeTrue()
        ->and($otherUser->canAccessTenant($this->restaurant))->toBeFalse()
        ->and($this->user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue()
        ->and($otherUser->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('serves the tenant panel only to linked users', function () {
    $this->get('/admin/'.$this->restaurant->slug)->assertOk();

    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->get('/admin/'.$this->restaurant->slug)
        ->assertForbidden();
});
