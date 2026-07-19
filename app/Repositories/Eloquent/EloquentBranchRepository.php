<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\BranchRepositoryInterface;
use App\Models\Branch;

class EloquentBranchRepository implements BranchRepositoryInterface
{
    // find branch by its unique code
    public function findByCode(string $code): ?Branch
    {
        return Branch::where('code', $code)->first();
    }
}
