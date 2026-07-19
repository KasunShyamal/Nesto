<?php

namespace App\Contracts\Repositories;

use App\Models\Customer;

interface CustomerRepositoryInterface
{
    public function findById(int $id): ?Customer;
    
    public function findByNicPassport(string $nicPassport): ?Customer;

    public function create(array $data): Customer;

    public function update(Customer $customer, array $data): bool;
}
