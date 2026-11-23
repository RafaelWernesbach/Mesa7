<?php

use App\Actions\Orders\CreateOrder;
use App\Actions\Orders\TransitionOrderStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function createOperationsOrder(Restaurant $restaurant, RestaurantTable $table): Order
{
    $product = $restaurant->products()->create([
        'name' => 'Prato do dia',
        'price' => '26.50',
        'is_available' => true,
    ]);

    return app(CreateOrder::class)->execute($restaurant, $table, [$product->getKey() => 1]);
}

function bootOrderResourceTenant(Restaurant $restaurant): void
{
    Filament::setTenant($restaurant);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();
}

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create();
    $this->table = $this->restaurant->tables()->create(['name' => 'Mesa 03', 'code' => 'M03']);
    $this->owner = User::factory()->create();
    $this->kitchen = User::factory()->create();
    $this->attendant = User::factory()->create();
    $this->outsider = User::factory()->create();
    $this->restaurant->users()->attach($this->owner, ['role' => 'owner']);
    $this->restaurant->users()->attach($this->kitchen, ['role' => 'kitchen']);
    $this->restaurant->users()->attach($this->attendant, ['role' => 'attendant']);
    $this->order = createOperationsOrder($this->restaurant, $this->table);
});

it('records each allowed transition, actor and completion timestamp', function () {
    $transition = app(TransitionOrderStatus::class);

    $transition->execute($this->order, OrderStatus::Preparing, $this->kitchen);
    $transition->execute($this->order, OrderStatus::Ready, $this->owner);
    $completed = $transition->execute($this->order, OrderStatus::Completed, $this->owner);

    expect($completed->fresh()->status)->toBe(OrderStatus::Completed)
        ->and($completed->fresh()->completed_at)->not->toBeNull()
        ->and($completed->statusHistories()->count())->toBe(4)
        ->and($completed->statusHistories()->pluck('status')->all())->toBe([
            OrderStatus::New,
            OrderStatus::Preparing,
            OrderStatus::Ready,
            OrderStatus::Completed,
        ])
        ->and($completed->statusHistories()->reorder()->latest('id')->first()->changed_by_user_id)->toBe($this->owner->getKey());
});

it('rejects invalid transitions without changing status or history', function () {
    expect(fn () => app(TransitionOrderStatus::class)->execute(
        $this->order,
        OrderStatus::Ready,
        $this->owner,
    ))->toThrow(ValidationException::class);

    expect($this->order->fresh()->status)->toBe(OrderStatus::New)
        ->and($this->order->statusHistories()->count())->toBe(1);
});

it('allows only members with an operational role to change order status', function () {
    expect(fn () => app(TransitionOrderStatus::class)->execute(
        $this->order,
        OrderStatus::Preparing,
        $this->outsider,
    ))->toThrow(AuthorizationException::class);

    expect($this->order->fresh()->status)->toBe(OrderStatus::New)
        ->and($this->order->statusHistories()->count())->toBe(1);
});

it('allows an attendant to operate orders without managing the catalog', function () {
    $this->actingAs($this->attendant);
    bootOrderResourceTenant($this->restaurant);

    $transitioned = app(TransitionOrderStatus::class)->execute(
        $this->order,
        OrderStatus::Preparing,
        $this->attendant,
    );

    expect($transitioned->fresh()->status)->toBe(OrderStatus::Preparing)
        ->and($this->attendant->can('viewAny', Order::class))->toBeTrue()
        ->and($this->attendant->can('viewAny', Product::class))->toBeFalse();

    Livewire::test(ListOrders::class)
        ->assertCanSeeTableRecords([$this->order]);
});

it('rejects invalid cart data, foreign tables and totals above the monetary limit', function () {
    $action = app(CreateOrder::class);

    expect(fn () => $action->execute($this->restaurant, $this->table, ['invalid-id' => 1]))
        ->toThrow(ValidationException::class);

    $otherRestaurant = Restaurant::factory()->create();
    $foreignTable = $otherRestaurant->tables()->create(['name' => 'Mesa externa', 'code' => 'M01']);

    expect(fn () => $action->execute($this->restaurant, $foreignTable, [$this->order->items()->first()->product_id => 1]))
        ->toThrow(ValidationException::class);

    $expensiveProduct = $this->restaurant->products()->create([
        'name' => 'Produto de valor máximo',
        'price' => '99999999.99',
        'is_available' => true,
    ]);

    expect(fn () => $action->execute(
        $this->restaurant,
        $this->table,
        [$expensiveProduct->getKey() => 2],
    ))->toThrow(ValidationException::class);
});

it('allows an operator to cancel an active order and records the cancellation', function () {
    $cancelled = app(TransitionOrderStatus::class)->execute(
        $this->order,
        OrderStatus::Cancelled,
        $this->owner,
    );

    expect($cancelled->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($cancelled->statusHistories()->count())->toBe(2)
        ->and($cancelled->statusHistories()->reorder()->latest('id')->first()->changedBy->is($this->owner))->toBeTrue();
});

it('lists active and completed order history only for the selected tenant', function () {
    $completedOrder = app(TransitionOrderStatus::class)->execute($this->order, OrderStatus::Preparing, $this->owner);
    $completedOrder = app(TransitionOrderStatus::class)->execute($completedOrder, OrderStatus::Ready, $this->owner);
    $completedOrder = app(TransitionOrderStatus::class)->execute($completedOrder, OrderStatus::Completed, $this->owner);

    $otherRestaurant = Restaurant::factory()->create();
    $otherTable = $otherRestaurant->tables()->create(['name' => 'Mesa alheia', 'code' => 'M03']);
    $otherOrder = createOperationsOrder($otherRestaurant, $otherTable);

    $this->actingAs($this->owner);
    bootOrderResourceTenant($this->restaurant);

    Livewire::test(ListOrders::class)
        ->assertCanSeeTableRecords([$this->order, $completedOrder])
        ->assertCanNotSeeTableRecords([$otherOrder]);
});

it('executes permitted order transitions from the Filament queue', function () {
    $this->actingAs($this->owner);
    bootOrderResourceTenant($this->restaurant);

    Livewire::test(ListOrders::class)
        ->assertTableActionVisible('start', $this->order)
        ->callTableAction('start', $this->order)
        ->assertTableActionHidden('start', $this->order)
        ->assertTableActionVisible('markReady', $this->order)
        ->callTableAction('markReady', $this->order)
        ->callTableAction('complete', $this->order);

    expect($this->order->fresh()->status)->toBe(OrderStatus::Completed)
        ->and($this->order->statusHistories()->count())->toBe(4);
});

it('shows the order details and status history in the Filament queue', function () {
    $this->actingAs($this->owner);
    bootOrderResourceTenant($this->restaurant);

    Livewire::test(ListOrders::class)
        ->assertTableActionExists('details', null, $this->order);

    $this->view('admin.orders.details', [
        'order' => $this->order->load(['items', 'statusHistories.changedBy']),
    ])
        ->assertSee('Mesa 03')
        ->assertSee('Prato do dia')
        ->assertSee('Pendente')
        ->assertSee('Sistema');
});
