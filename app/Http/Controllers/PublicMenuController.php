<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Response;

class PublicMenuController extends Controller
{
    public function show(Restaurant $restaurant): Response
    {
        return $this->menuResponse($restaurant);
    }

    public function showForTable(Restaurant $restaurant, string $code): Response
    {
        abort_unless($restaurant->is_active, 404);

        $table = $restaurant->tables()
            ->where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        return $this->menuResponse($restaurant, $table);
    }

    public function showForTableUuid(Restaurant $restaurant, string $uuid): Response
    {
        abort_unless($restaurant->is_active, 404);

        $table = $restaurant->tables()
            ->where('uuid', $uuid)
            ->where('is_active', true)
            ->firstOrFail();

        return $this->menuResponse($restaurant, $table);
    }

    private function menuResponse(Restaurant $restaurant, ?RestaurantTable $table = null): Response
    {
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

        $primaryColor = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $restaurant->primary_color)
            ? $restaurant->primary_color
            : '#E36A3C';

        return response()->view('public.menu', [
            'restaurant' => $restaurant,
            'table' => $table,
            'categories' => $categories,
            'uncategorizedProducts' => $uncategorizedProducts,
            'primaryColor' => $primaryColor,
        ]);
    }
}
