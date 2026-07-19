<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Models\Customer;

class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    // Get customer by ID
    public function findById(int $id): ?Customer
    {
        return Customer::find($id);
    }

    // Get customer by NIC/Passport
    public function findByNicPassport(string $nicPassport): ?Customer
    {
        return Customer::where('nic_passport', $nicPassport)->first();
    }

    // create new customer
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    // Update customer
    public function update(Customer $customer, array $data): bool
    {
        return $customer->update($data);
    }
}
