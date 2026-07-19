<?php

namespace App\Services;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Models\Customer;

class CustomerRegistrationService
{
    protected CustomerRepositoryInterface $customerRepository;

    public function __construct(CustomerRepositoryInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }


     /* Register a new customer in the system */
    public function register(array $data, int $registeredById): Customer
    {
        $customerData = [
            'nic_passport' => $data['nic_passport'],
            'mobile_number' => $data['mobile_number'],
            'name' => $data['name'],
            'status' => 'pending',
            'registered_by' => $registeredById,
        ];

        return $this->customerRepository->create($customerData);
    }
}
