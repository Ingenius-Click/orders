<?php

namespace Ingenius\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Ingenius\Orders\Database\Factories\OrderProductFactory;
use Ingenius\Coins\Services\CurrencyServices;

class OrderProduct extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'productible_id',
        'productible_type',
        'quantity',
        'base_price_per_unit_in_cents',
        'base_total_in_cents',
        'metadata',
    ];

    protected $appends = [
        'productible_name',
        'price_per_unit_in_cents',
        'total_in_cents',
        'price_per_unit_formatted',
        'total_formatted',
    ];

    /**
     * Get the order that owns the product.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the productible model.
     */
    public function productible(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): OrderProductFactory
    {
        return OrderProductFactory::new();
    }

    public function getProductibleNameAttribute(): string
    {
        if ($this->productible()->first()?->name) {
            return $this->productible()->first()?->name;
        }

        return 'No name';
    }

    /**
     * Get the price per unit converted to order currency.
     */
    public function getPricePerUnitInCentsAttribute(): int
    {
        return $this->base_price_per_unit_in_cents * $this->order()->first()->exchange_rate;
    }

    /**
     * Get the total price converted to order currency.
     */
    public function getTotalInCentsAttribute(): int
    {
        return $this->base_total_in_cents * $this->order()->first()->exchange_rate;
    }

    /**
     * Get the formatted price per unit in order currency.
     */
    public function getPricePerUnitFormattedAttribute(): string
    {
        return CurrencyServices::formatCurrency($this->price_per_unit_in_cents, $this->order()->first()->currency);
    }

    /**
     * Get the formatted total price in order currency.
     */
    public function getTotalFormattedAttribute(): string
    {
        return CurrencyServices::formatCurrency($this->total_in_cents, $this->order()->first()->currency);
    }
}
