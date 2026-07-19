<?php

namespace App\Services;

use App\Contracts\Repositories\BranchRepositoryInterface;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Events\OrderCreated;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

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
        // Resolve or generate invoice number first so it can be used for locking
        $invoiceNumber = $data['invoice_number'] ?? $this->generateInvoiceNumber();

        // lock to prevent concurrent double-submits
        $lockKey = "order-capture:{$data['branch_code']}:{$invoiceNumber}";
        $lock = Cache::lock($lockKey, 10);

        if (!$lock->get()) {
            throw new ConflictHttpException('This order is currently being processed. Please wait.');
        }

        try {
            // 2. Find branch by code
            $branch = $this->branchRepository->findByCode($data['branch_code']);
            if (!$branch) {
                throw ValidationException::withMessages([
                    'branch_code' => ['The provided branch code is invalid.'],
                ]);
            }

            // 3. Find customer by NIC/Passport
            $customer = $this->customerRepository->findByNicPassport($data['nic_passport']);
            if (!$customer) {
                throw ValidationException::withMessages([
                    'nic_passport' => ['No customer profile found matching this NIC/Passport.'],
                ]);
            }

            // 4. Double-check duplicate submissions (invoice number + branch constraint)
            $existingOrder = $this->orderRepository->findByInvoiceAndBranch($invoiceNumber, $branch->id);
            if ($existingOrder) {
                throw ValidationException::withMessages([
                    'invoice_number' => ['An order with this invoice number has already been recorded for this branch.'],
                ]);
            }

       
            $order = $this->orderRepository->create([
                'customer_id' => $customer->id,
                'invoice_number' => $invoiceNumber,
                'branch_id' => $branch->id,
                'transaction_date' => $data['transaction_date'],
                'amount' => $data['amount'],
            ]);

           
            OrderCreated::dispatch($order);

            return $order;
        } finally {
            $lock->release();
        }
    }

    // generate next sequential invoice number based on prefix from env
    private function generateInvoiceNumber(): string
    {
        $prefix = env('INVOICE_PREFIX', 'INV');

        
        $latestOrder = Order::where('invoice_number', 'like', $prefix . '-%')
            ->latest('id')
            ->first();

        if (!$latestOrder) {
            return $prefix . '-00001';
        }

        $lastNumber = (int) str_replace($prefix . '-', '', $latestOrder->invoice_number);
        $nextNumber = $lastNumber + 1;

        return $prefix . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
