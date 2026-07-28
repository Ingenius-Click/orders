<?php

namespace Ingenius\Orders\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class ExportOrderFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'export-order';
    }

    public function getName(): string
    {
        return __('Export order');
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
        return false;
    }
}
