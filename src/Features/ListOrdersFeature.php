<?php

namespace Ingenius\Orders\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class ListOrdersFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'list-orders';
    }

    public function getName(): string
    {
        return 'List orders';
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
