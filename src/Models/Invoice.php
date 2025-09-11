<?php

namespace Ingenius\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Ingenius\Coins\Services\CurrencyServices;
use Ingenius\Orders\Enums\InvoiceStatus;
use Ingenius\Orders\Services\InvoiceDataManager;

class Invoice extends Model
{
    protected $fillable = [
        'orderable_id',
        'orderable_type',
        'invoice_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'total_amount',
        'currency',
        'base_currency',
        'exchange_rate',
        'status',
        'payment_method',
        'items',
        'payment_date',
        'is_manual'
    ];

    protected $casts = [
        'items' => 'array',
        'status' => InvoiceStatus::class,
    ];

    protected $appends = [
        'total_amount_base_formatted',
        'total_amount_current_cents',
        'total_amount_current_formatted',
        'orderable_number'
    ];

    public function orderable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all invoice data sections from registered providers.
     *
     * @return array
     */
    public function getInvoiceData(): array
    {
        $manager = app(InvoiceDataManager::class);

        return $manager->getInvoiceData($this);
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $invoiceData = $this->getInvoiceData();

        $array['extra_data'] = $invoiceData;

        return $array;
    }

    /**
     * Get the total amount formatted in base currency.
     */
    public function getTotalAmountBaseFormattedAttribute(): string
    {
        return CurrencyServices::formatCurrency($this->total_amount, $this->base_currency);
    }

    /**
     * Get the total amount in cents for current currency.
     */
    public function getTotalAmountCurrentCentsAttribute(): int
    {
        return (int) ($this->total_amount * $this->exchange_rate);
    }

    /**
     * Get the total amount formatted in current currency.
     */
    public function getTotalAmountCurrentFormattedAttribute(): string
    {
        return CurrencyServices::formatCurrency($this->total_amount_current_cents, $this->currency);
    }

    public function getOrderableNumberAttribute(): string
    {
        $order_number = $this->orderable()->first()?->order_number;

        return $order_number ?? '-';
    }
}
