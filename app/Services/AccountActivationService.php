<?php

namespace App\Services;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountActivationService
{
    protected CustomerRepositoryInterface $customerRepository;
    protected UserRepositoryInterface $userRepository;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->userRepository = $userRepository;
    }

     /* Activate a customer account by creating login credentials.*/
    public function activate(array $data): Customer
    {
        // fetch customer by NIC/Passport
        $customer = $this->customerRepository->findByNicPassport($data['nic_passport']);

        if (!$customer) {
            throw ValidationException::withMessages([
                'nic_passport' => ['No registered profile found matching this NIC/Passport.'],
            ]);
        }

        // check that customer already active or not
        if ($customer->status === 'active' || $customer->user_id !== null) {
            throw ValidationException::withMessages([
                'nic_passport' => ['This profile is already activated.'],
            ]);
        }

        // create user credentials and active customer in a transaction
        return DB::transaction(function () use ($customer, $data) {
            $user = $this->userRepository->create([
                'name' => $customer->name,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'customer',
            ]);

            $this->customerRepository->update($customer, [
                'user_id' => $user->id,
                'status' => 'active',
                'activated_at' => now(),
            ]);

            return $customer->fresh();
        });
    }
}
