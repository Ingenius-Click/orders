<?php

namespace Ingenius\Orders\Services;

use Ingenius\Core\Interfaces\IOrderable;
use Ingenius\Orders\Interfaces\InvoiceCreationStrategyInterface;
use Ingenius\Orders\Models\Invoice;

class InvoiceCreationManager
{
    /**
     * Collection of registered strategies
     *
     * @var array<InvoiceCreationStrategyInterface>
     */
    protected array $strategies = [];

    /**
     * Register a new invoice creation strategy
     *
     * @param InvoiceCreationStrategyInterface $strategy
     * @return void
     */
    public function register(InvoiceCreationStrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
        $this->sortStrategies();
    }

    /**
     * Attempt to create an invoice using the appropriate strategy
     *
     * @param IOrderable $orderable
     * @param string $paid_at
     * @param string $status
     * @return Invoice|null
     */
    public function attemptToCreateInvoice(IOrderable $orderable, string $paid_at, string $status): ?Invoice
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->shouldCreateInvoice($orderable, $status)) {
                return $strategy->createInvoice($orderable, $paid_at);
            }
        }

        return null;
    }

    /**
     * Get all registered strategies
     *
     * @return array<InvoiceCreationStrategyInterface>
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * Sort strategies by priority
     *
     * @return void
     */
    protected function sortStrategies(): void
    {
        usort($this->strategies, function (InvoiceCreationStrategyInterface $a, InvoiceCreationStrategyInterface $b) {
            return $a->getPriority() <=> $b->getPriority();
        });
    }
}
