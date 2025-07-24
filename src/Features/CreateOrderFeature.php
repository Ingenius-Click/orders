<?php

namespace Ingenius\Orders\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class CreateOrderFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'create-order';
    }

    public function getName(): string
    {
        return 'Create order';
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
