<?php

namespace App\Contracts\Repositories;

use App\Models\LoyaltyTransaction;

interface LoyaltyTransactionRepositoryInterface
{
    public function create(array $data): LoyaltyTransaction;
}
