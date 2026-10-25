<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoRestaurantTablesSeeder extends Seeder
{
    public function run(): void
    {
        $tables = [
            'mesa7-demo' => [
                ['name' => 'Mesa 01', 'code' => 'M01'],
                ['name' => 'Mesa 02', 'code' => 'M02'],
                ['name' => 'Mesa 07', 'code' => 'M07'],
            ],
            'cantina-da-praca' => [
                ['name' => 'Mesa 01', 'code' => 'M01'],
                ['name' => 'Mesa 02', 'code' => 'M02'],
                ['name' => 'Mesa 07', 'code' => 'M07'],
            ],
        ];

        foreach ($tables as $restaurantSlug => $restaurantTables) {
            $restaurant = Restaurant::query()->where('slug', $restaurantSlug)->firstOrFail();

            foreach ($restaurantTables as $table) {
                $restaurantTable = $restaurant->tables()->firstOrNew(['code' => $table['code']]);
                $restaurantTable->fill(['name' => $table['name'], 'is_active' => true]);
                $restaurantTable->uuid ??= (string) Str::uuid();
                $restaurantTable->save();
            }
        }
    }
}
