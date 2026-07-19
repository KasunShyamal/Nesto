<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected CustomerRegistrationService $registrationService;

    public function __construct(CustomerRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    /* Register a new customer in the system*/
    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        // Obtain authenticated user (cashier/admin)
        $cashierId = $request->user()->id;

        $customer = $this->registrationService->register($request->validated(), $cashierId);

        return response()->json([
            'message' => 'Customer registered successfully. Profile is pending activation.',
            'customer' => new CustomerResource($customer),
        ], 201);
    }

    /* Retrieve the authenticated customer's own profile*/
    public function me(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        if (!$customer) {
            return response()->json([
                'message' => 'No customer profile associated with this user.'
            ], 404);
        }

        return response()->json([
            'customer' => new CustomerResource($customer)
        ]);
    }
}
