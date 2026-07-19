<?php

namespace App\Contracts\Repositories;

use App\Models\Branch;

interface BranchRepositoryInterface
{
    public function findByCode(string $code): ?Branch;
}
