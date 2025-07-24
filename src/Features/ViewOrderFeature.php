<?php

namespace Ingenius\Orders\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class ViewOrderFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'view-order';
    }

    public function getName(): string
    {
        return 'View order';
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
