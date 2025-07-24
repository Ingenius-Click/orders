<?php

namespace Ingenius\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
        'payment_date'
    ];

    protected $casts = [
        'items' => 'array',
        'status' => InvoiceStatus::class,
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
}
