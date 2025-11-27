<?php

namespace Ingenius\Orders\Traits;

use Ingenius\Coins\Services\CurrencyServices;

trait OrderAmountTrait
{
    /**
     * Get the total amount.
     */
    public function getTotalAmount(): int
    {
        return $this->getTotalAmountInCentsAttribute();
    }

    /**
     * Get the base total amount.
     */
    public function getBaseTotalAmount(): int
    {
        return $this->base_total_amount_in_cents;
    }

    /**
     * Get the base total amount in cents attribute.
     * Uses persisted total_amount field calculated during order creation.
     */
    public function getBaseTotalAmountInCentsAttribute(): int
    {
        return $this->total_amount ?? $this->items_subtotal;
    }

    /**
     * Get the base total amount formatted attribute.
     */
    public function getBaseTotalAmountFormattedAttribute(): string
    {
        return CurrencyServices::formatCurrency($this->base_total_amount_in_cents, $this->current_base_currency);
    }

    /**
     * Get the total amount in cents attribute.
     */
    public function getTotalAmountInCentsAttribute(): int
    {
        return $this->base_total_amount_in_cents * $this->exchange_rate;
    }

    /**
     * Get the total amount formatted attribute.
     */
    public function getTotalAmountFormattedAttribute(): string
    {
        return CurrencyServices::formatCurrency($this->total_amount_in_cents, $this->currency);
    }

    /**
     * Get the items subtotal converted to order currency.
     */
    public function getItemsSubtotalConvertedAttribute(): int
    {
        return $this->items_subtotal * $this->exchange_rate;
    }
}
