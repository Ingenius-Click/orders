<?php

namespace Ingenius\Orders\Models;

use Ingenius\Core\Interfaces\IOrderable;
use Ingenius\Core\Interfaces\IWithPayment;
use Ingenius\Core\Services\PackageHookManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Ingenius\Orders\Database\Factories\OrderFactory;
use Ingenius\Orders\Services\OrderExtensionManager;
use Ingenius\Orders\Traits\OrderAmountTrait;
use Ingenius\Orders\Traits\OrderPaymentTrait;
use Ingenius\Orders\Traits\OrderStatusTrait;

class Order extends Model implements IOrderable, IWithPayment
{
    use HasFactory;
    use OrderStatusTrait;
    use OrderPaymentTrait;
    use OrderAmountTrait;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'current_base_currency',
        'currency',
        'exchange_rate',
        'status',
        'metadata',
        'items_subtotal',
        'total_amount',
        'guest_token',
        'userable_id',
        'userable_type',
        'is_manual'
    ];

    protected $appends = [
        'base_total_amount_in_cents',
        'base_total_amount_formatted',
        'total_amount_in_cents',
        'total_amount_formatted',
        'items_subtotal_converted',
        'items_subtotal_formatted',
        'status_name',
        'allowed_next_statuses',
    ];

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    /**
     * Get the products for the order.
     */
    public function products(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    /**
     * Get the userable model.
     */
    public function userable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getOrderableCode(): string|int
    {
        return $this->order_number;
    }

    public function getOrderableId(): string|int
    {
        return $this->id;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getBaseCurrency(): string
    {
        return $this->current_base_currency;
    }

    public function getExchangeRate(): float
    {
        return $this->exchange_rate;
    }

    public function getItems(): array
    {
        return $this->products->map(function (OrderProduct $product) {
            return [
                'productible_id' => $product->productible_id,
                'productible_sku' => $product->productible?->sku ?? '',
                'productible_type' => $product->productible_type,
                'productible_name' => $product->productible?->name ?? 'N/A',
                'quantity' => $product->quantity,
                'base_price_per_unit_in_cents' => $product->base_price_per_unit_in_cents,
                'base_total_in_cents' => $product->base_total_in_cents,
                'metadata' => $product->metadata,
            ];
        })->toArray();
    }

    public function getItemsSubtotal(): int
    {
        return $this->items_subtotal;
    }

    public function getCustomerName(): string
    {
        return $this->customer_name;
    }

    public function getCustomerEmail(): string
    {
        return $this->customer_email;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customer_phone;
    }

    public function getCustomerAddress(): ?string
    {
        return $this->customer_address;
    }

    public function getShippingCost(): int
    {
        return (int) app(PackageHookManager::class)->execute('order.shipping_cost', 0, ['order' => $this]);
    }

    public function toArray(): array
    {
        $extensionManager = app(OrderExtensionManager::class);

        $array = parent::toArray();

        return $extensionManager->extendOrderArray($this, $array);
    }
}
