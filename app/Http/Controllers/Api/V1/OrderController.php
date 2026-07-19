<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderService $orderService;
    protected \App\Contracts\Repositories\OrderRepositoryInterface $orderRepository;

    public function __construct(
        OrderService $orderService,
        \App\Contracts\Repositories\OrderRepositoryInterface $orderRepository
    ) {
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
    }

    /* List all orders paginated (Cashier/Admin only) */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderRepository->getPaginated(15);

        return response()->json([
            'orders' => OrderResource::collection($orders)->response()->getData(true)
        ]);
    }

    // capture a new customer purchase order (Cashier/Admin only)
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder($request->validated());

        return response()->json([
            'message' => 'Order captured successfully. Loyalty points calculation is being processed.',
            'order' => new OrderResource($order->load('branch')),
        ], 201);
    }
}
