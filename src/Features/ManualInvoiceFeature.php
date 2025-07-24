<?php

namespace Ingenius\Orders\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class ManualInvoiceFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'manual-invoice';
    }

    public function getName(): string
    {
        return 'Create manual invoices';
    }

    public function getPackage(): string
    {
        return 'orders';
    }

    public function isBasic(): bool
    {
        return false;
    }
}
