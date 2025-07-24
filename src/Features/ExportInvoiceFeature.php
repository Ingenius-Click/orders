<?php

namespace Ingenius\Orders\Features;

use Ingenius\Core\Interfaces\FeatureInterface;

class ExportInvoiceFeature implements FeatureInterface
{
    public function getIdentifier(): string
    {
        return 'export-invoice';
    }

    public function getName(): string
    {
        return 'Export invoice';
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
