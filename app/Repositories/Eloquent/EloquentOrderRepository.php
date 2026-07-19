<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Models\Order;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    // create new order
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    // find order by invoice and branch
    public function findByInvoiceAndBranch(string $invoiceNumber, int $branchId): ?Order
    {
        return Order::where('invoice_number', $invoiceNumber)
            ->where('branch_id', $branchId)
            ->first();
    }
}
