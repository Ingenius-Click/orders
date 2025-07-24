<?php

namespace Ingenius\Orders\Traits;

use Ingenius\Coins\Services\CurrencyServices;
use Ingenius\Orders\Services\OrderExtensionManager;

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
     */
    public function getBaseTotalAmountInCentsAttribute(): int
    {
        $itemsSubtotal = $this->items_subtotal;

        $extensionManager = app(OrderExtensionManager::class);

        return $extensionManager->calculateFinalSubtotal($this, $itemsSubtotal);
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
}
