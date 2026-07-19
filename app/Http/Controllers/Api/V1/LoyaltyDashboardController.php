<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoyaltyTransactionResource;
use App\Http\Resources\OrderResource;
use App\Contracts\Repositories\LoyaltyTransactionRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyDashboardController extends Controller
{
    protected LoyaltyTransactionRepositoryInterface $transactionRepository;

    public function __construct(LoyaltyTransactionRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    // get customer total loyalty points
    public function balance(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer) {
            return response()->json(['message' => 'No customer profile found.'], 404);
        }

        return response()->json([
            'points_balance' => $customer->points_balance,
        ]);
    }

    // get customer loyalty transaction history
    public function transactions(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer) {
            return response()->json(['message' => 'No customer profile found.'], 404);
        }

        $transactions = $this->transactionRepository->getPaginatedForCustomer($customer->id, 10);

        return response()->json([
            'transactions' => LoyaltyTransactionResource::collection($transactions)->response()->getData(true)
        ]);
    }

    // get combined dashboard statistics
    public function dashboard(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer) {
            return response()->json(['message' => 'No customer profile found.'], 404);
        }

        // Get paginated transactions
        $transactions = $this->transactionRepository->getPaginatedForCustomer($customer->id, 5);

        // Get paginated recent orders
        $orders = $customer->orders()->with('branch')->latest()->paginate(5);

        return response()->json([
            'customer_name' => $customer->name,
            'points_balance' => $customer->points_balance,
            'recent_transactions' => LoyaltyTransactionResource::collection($transactions)->response()->getData(true),
            'recent_orders' => OrderResource::collection($orders)->response()->getData(true),
        ]);
    }
}
