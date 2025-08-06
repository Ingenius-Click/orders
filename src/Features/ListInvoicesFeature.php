<?php

namespace Ingenius\Orders\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class ListInvoicesFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'list-invoices';
    }

    public function getName(): string
    {
        return __('List invoices');
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
