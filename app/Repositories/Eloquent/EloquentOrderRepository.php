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

    // get all orders paginated, eager loading branch and customer
    public function getPaginated(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Order::with(['branch', 'customer'])->latest()->paginate($perPage);
    }
}
