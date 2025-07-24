<?php

namespace Ingenius\Orders\Enums;

enum InvoiceStatus: string
{
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case PAYMENT_PENDING = 'payment_pending';
}
