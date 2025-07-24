<?php

namespace Ingenius\Orders\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class UpdateOrderFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'update-order';
    }

    public function getName(): string
    {
        return 'Update order';
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
