<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['restaurant_id', 'name', 'code', 'is_active'])]
class RestaurantTable extends Model
{
    protected $table = 'restaurant_tables';

    protected static function booted(): void
    {
        static::creating(function (self $table): void {
            $table->uuid ??= (string) Str::uuid();
        });
    }

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
