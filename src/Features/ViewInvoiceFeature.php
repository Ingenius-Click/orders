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
        return __('View invoice');
    }

    public function getGroup(): string
    {
        return __('Orders');
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
