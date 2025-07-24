<?php

namespace Ingenius\Orders\Services;

use Ingenius\Orders\Interfaces\InvoiceDataProviderInterface;
use Ingenius\Orders\Models\Invoice;

class InvoiceDataManager
{
    /**
     * @var array<InvoiceDataProviderInterface>
     */
    protected array $providers = [];

    /**
     * Register a new invoice data provider.
     *
     * @param InvoiceDataProviderInterface $provider
     * @return void
     */
    public function register(InvoiceDataProviderInterface $provider): void
    {
        $this->providers[] = $provider;
        $this->sortProviders();
    }

    /**
     * Get all invoice data from registered providers.
     *
     * @param Invoice $invoice
     * @return array
     */
    public function getInvoiceData(Invoice $invoice): array
    {
        $data = [];

        foreach ($this->providers as $provider) {
            $providerData = $provider->getInvoiceData($invoice);
            $data = array_merge($data, $providerData);
        }

        return $this->sortSectionsByOrder($data);
    }

    /**
     * Sort providers by priority.
     *
     * @return void
     */
    protected function sortProviders(): void
    {
        usort($this->providers, function (InvoiceDataProviderInterface $a, InvoiceDataProviderInterface $b) {
            return $a->getPriority() <=> $b->getPriority();
        });
    }

    /**
     * Sort sections by order.
     *
     * @param array $sections
     * @return array
     */
    protected function sortSectionsByOrder(array $sections): array
    {
        usort($sections, function ($a, $b) {
            return $a->getOrder() <=> $b->getOrder();
        });

        return $sections;
    }
}
