<?php

use App\Models\Category;
use App\Models\Restaurant;
use App\Models\RestaurantTable;

it('shows available menu items in active categories and uncategorized section', function () {
    $restaurant = Restaurant::factory()->create([
        'name' => 'Cantina da Praça',
        'slug' => 'cantina-da-praca',
        'description' => 'Comida feita com cuidado.',
    ]);
    $activeCategory = Category::query()->create([
        'restaurant_id' => $restaurant->id,
        'name' => 'Pratos da casa',
        'slug' => 'pratos-da-casa',
        'sort_order' => 1,
        'is_active' => true,
    ]);
    $inactiveCategory = Category::query()->create([
        'restaurant_id' => $restaurant->id,
        'name' => 'Categoria oculta',
        'slug' => 'categoria-oculta',
        'sort_order' => 2,
        'is_active' => false,
    ]);

    $restaurant->products()->create([
        'category_id' => $activeCategory->id,
        'name' => 'Feijoada da casa',
        'description' => 'Com acompanhamentos.',
        'price' => '42.90',
        'is_available' => true,
    ]);
    $restaurant->products()->create([
        'category_id' => $activeCategory->id,
        'name' => 'Prato indisponível',
        'price' => '22.00',
        'is_available' => false,
    ]);
    $restaurant->products()->create([
        'category_id' => $inactiveCategory->id,
        'name' => 'Produto em categoria oculta',
        'price' => '18.00',
        'is_available' => true,
    ]);
    $restaurant->products()->create([
        'name' => 'Item sem categoria',
        'price' => '9.50',
        'is_available' => true,
    ]);

    $this->get(route('public.menu.show', $restaurant->slug))
        ->assertOk()
        ->assertSee('Cantina da Praça')
        ->assertSee('Comida feita com cuidado.')
        ->assertSee('Pratos da casa')
        ->assertSee('Feijoada da casa')
        ->assertSee('Item sem categoria')
        ->assertSee('R$ 42,90')
        ->assertDontSee('Prato indisponível')
        ->assertDontSee('Categoria oculta')
        ->assertDontSee('Produto em categoria oculta');
});

it('does not publish a menu for an inactive restaurant', function () {
    $restaurant = Restaurant::factory()->create(['is_active' => false]);

    $this->get(route('public.menu.show', $restaurant->slug))->assertNotFound();
});

it('resolves an active table only inside its restaurant and shows the table context', function () {
    $restaurant = Restaurant::factory()->create(['slug' => 'casa-teste']);
    $otherRestaurant = Restaurant::factory()->create(['slug' => 'outra-casa']);
    $table = $restaurant->tables()->create([
        'name' => 'Mesa 07',
        'code' => 'M07',
        'is_active' => true,
    ]);
    $otherRestaurant->tables()->create([
        'name' => 'Mesa de outra casa',
        'code' => 'M99',
        'is_active' => true,
    ]);

    $this->get(route('public.menu.table-code', [$restaurant->slug, $table->code]))
        ->assertOk()
        ->assertSee('Mesa 07')
        ->assertSee('CARDÁPIO DA MESA');

    $this->get(route('public.menu.table-code', [$restaurant->slug, 'M99']))->assertNotFound();
});

it('resolves table QR URLs by UUID within the restaurant and displays the table context', function () {
    $restaurant = Restaurant::factory()->create(['slug' => 'casa-uuid']);
    $otherRestaurant = Restaurant::factory()->create(['slug' => 'outra-casa-uuid']);
    $table = $restaurant->tables()->create([
        'name' => 'Mesa UUID',
        'code' => 'M12',
        'is_active' => true,
    ]);
    $otherTable = $otherRestaurant->tables()->create([
        'name' => 'Mesa de outro tenant',
        'code' => 'M13',
        'is_active' => true,
    ]);

    $this->get(route('public.menu.table', [$restaurant->slug, $table->uuid]))
        ->assertOk()
        ->assertSee('Mesa UUID')
        ->assertSee('CARDÁPIO DA MESA');

    $this->get(route('public.menu.table', [$restaurant->slug, $otherTable->uuid]))->assertNotFound();
    $this->get('/r/'.$restaurant->slug.'/cardapio/invalido')->assertNotFound();
});

it('does not resolve inactive tables or restaurants for table menus', function () {
    $restaurant = Restaurant::factory()->create();
    $table = RestaurantTable::query()->create([
        'restaurant_id' => $restaurant->id,
        'name' => 'Mesa inativa',
        'code' => 'M08',
        'is_active' => false,
    ]);

    $this->get(route('public.menu.table-code', [$restaurant->slug, $table->code]))->assertNotFound();
    $this->get(route('public.menu.table', [$restaurant->slug, $table->uuid]))->assertNotFound();

    $restaurant->update(['is_active' => false]);
    $this->get(route('public.menu.table-code', [$restaurant->slug, $table->code]))->assertNotFound();
    $this->get(route('public.menu.table', [$restaurant->slug, $table->uuid]))->assertNotFound();
});
