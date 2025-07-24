<?php

namespace Ingenius\Orders\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class ViewInvoiceFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'view-invoice';
    }

    public function getName(): string
    {
        return 'View invoice';
    }

    public function getPackage(): string
    {
        return 'orders';
    }

    public function isBasic(): bool
    {
        return true;
    }
}
