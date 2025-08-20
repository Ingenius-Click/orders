<?php

namespace Ingenius\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Ingenius\Orders\Database\Factories\OrderProductFactory;

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
}
