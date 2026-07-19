<?php

namespace App\Contracts\Repositories;

use App\Models\Order;

interface OrderRepositoryInterface
{
    public function create(array $data): Order;

    public function findByInvoiceAndBranch(string $invoiceNumber, int $branchId): ?Order;

    public function getPaginated(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
