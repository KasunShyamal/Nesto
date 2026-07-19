<?php

namespace App\Contracts\Repositories;

use App\Models\LoyaltyTransaction;

interface LoyaltyTransactionRepositoryInterface
{
    public function create(array $data): LoyaltyTransaction;

    public function getPaginatedForCustomer(int $customerId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
