<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Support\PublicTableCart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicMenuController extends Controller
{
    public function show(Restaurant $restaurant, Request $request, PublicTableCart $cart): Response
    {
        return $this->menuResponse($restaurant, $request, $cart);
    }

    public function showForTable(Restaurant $restaurant, string $code, Request $request, PublicTableCart $cart): Response
    {
        abort_unless($restaurant->is_active, 404);

        $table = $restaurant->tables()
            ->where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        return $this->menuResponse($restaurant, $request, $cart, $table);
    }

    public function showForTableUuid(Restaurant $restaurant, string $uuid, Request $request, PublicTableCart $cart): Response
    {
        abort_unless($restaurant->is_active, 404);

        $table = $restaurant->tables()
            ->where('uuid', $uuid)
            ->where('is_active', true)
            ->firstOrFail();

        return $this->menuResponse($restaurant, $request, $cart, $table);
    }

    private function menuResponse(
        Restaurant $restaurant,
        Request $request,
        PublicTableCart $cart,
        ?RestaurantTable $table = null,
    ): Response {
        abort_unless($restaurant->is_active, 404);

        $filterAvailableProducts = static function (Builder $query) use ($restaurant): void {
            $query->where('products.restaurant_id', $restaurant->getKey())
                ->where('products.is_available', true);
        };
        $loadAvailableProducts = static function (HasMany $query) use ($restaurant): void {
            $query->where('products.restaurant_id', $restaurant->getKey())
                ->where('products.is_available', true)
                ->orderBy('products.name');
        };

        $categories = $restaurant->categories()
            ->where('is_active', true)
            ->whereHas('products', $filterAvailableProducts)
            ->with(['products' => $loadAvailableProducts])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $uncategorizedProducts = $restaurant->products()
            ->whereNull('category_id')
            ->where('is_available', true)
            ->orderBy('name')
            ->get();

        return response()->view('public.menu', [
            'restaurant' => $restaurant,
            'table' => $table,
            'categories' => $categories,
            'uncategorizedProducts' => $uncategorizedProducts,
            'primaryColor' => $restaurant->menuPrimaryColor(),
            'cartCount' => $table === null ? 0 : $cart->count($request, $restaurant, $table),
            'tableOrders' => $table !== null && $request->routeIs('public.menu.table')
                ? $restaurant->orders()->where('restaurant_table_id', $table->getKey())->latest('placed_at')->latest('id')->get()
                : collect(),
        ]);
    }
}
