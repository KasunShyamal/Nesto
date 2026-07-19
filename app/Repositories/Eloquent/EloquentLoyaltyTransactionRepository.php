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
}
