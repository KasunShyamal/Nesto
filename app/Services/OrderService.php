<?php

namespace App\Services;

use App\Contracts\Repositories\BranchRepositoryInterface;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Events\OrderCreated;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

class OrderService
{
    protected OrderRepositoryInterface $orderRepository;
    protected CustomerRepositoryInterface $customerRepository;
    protected BranchRepositoryInterface $branchRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CustomerRepositoryInterface $customerRepository,
        BranchRepositoryInterface $branchRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->branchRepository = $branchRepository;
    }

    // create a new order and trigger points calculation event
    public function createOrder(array $data): Order
    {
        // 1. Find branch by code
        $branch = $this->branchRepository->findByCode($data['branch_code']);
        if (!$branch) {
            throw ValidationException::withMessages([
                'branch_code' => ['The provided branch code is invalid.'],
            ]);
        }

        // 2. Find customer by NIC/Passport
        $customer = $this->customerRepository->findByNicPassport($data['nic_passport']);
        if (!$customer) {
            throw ValidationException::withMessages([
                'nic_passport' => ['No customer profile found matching this NIC/Passport.'],
            ]);
        }

        // 3. Resolve or generate invoice number
        $invoiceNumber = $data['invoice_number'] ?? $this->generateInvoiceNumber();

        // 4. Double-check duplicate submissions (invoice number + branch constraint)
        $existingOrder = $this->orderRepository->findByInvoiceAndBranch($invoiceNumber, $branch->id);
        if ($existingOrder) {
            throw ValidationException::withMessages([
                'invoice_number' => ['An order with this invoice number has already been recorded for this branch.'],
            ]);
        }

        // 5. Persist order
        $order = $this->orderRepository->create([
            'customer_id' => $customer->id,
            'invoice_number' => $invoiceNumber,
            'branch_id' => $branch->id,
            'transaction_date' => $data['transaction_date'],
            'amount' => $data['amount'],
        ]);

        // 6. Fire event for async points calculation
        OrderCreated::dispatch($order);

        return $order;
    }

    // generate next sequential invoice number based on prefix from env
    private function generateInvoiceNumber(): string
    {
        $prefix = env('INVOICE_PREFIX', 'INV');

        // Find the latest order in the system with this prefix
        $latestOrder = Order::where('invoice_number', 'like', $prefix . '-%')
            ->latest('id')
            ->first();

        if (!$latestOrder) {
            return $prefix . '-00001';
        }

        // Extract numeric suffix and increment
        $lastNumber = (int) str_replace($prefix . '-', '', $latestOrder->invoice_number);
        $nextNumber = $lastNumber + 1;

        return $prefix . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
