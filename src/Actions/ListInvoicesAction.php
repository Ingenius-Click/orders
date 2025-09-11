<?php

namespace Ingenius\Orders\Actions;

use Illuminate\Pagination\LengthAwarePaginator;
use Ingenius\Orders\Models\Invoice;

class ListInvoicesAction
{
    public function __invoke(array $filters = []): LengthAwarePaginator
    {
        $query = Invoice::query();

        return table_handler_paginate($filters, $query);
    }
}
