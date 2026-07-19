<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\LoyaltyTransactionRepositoryInterface;
use App\Models\LoyaltyTransaction;

class EloquentLoyaltyTransactionRepository implements LoyaltyTransactionRepositoryInterface
{
    // create loyalty ledger transaction
    public function create(array $data): LoyaltyTransaction
    {
        return LoyaltyTransaction::create($data);
    }

    // get paginated loyalty transactions for a customer, eager loading the branch
    public function getPaginatedForCustomer(int $customerId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return LoyaltyTransaction::where('customer_id', $customerId)
            ->with(['order.branch'])
            ->latest()
            ->paginate($perPage);
    }
}
