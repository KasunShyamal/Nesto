<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateAccountRequest;
use App\Http\Resources\CustomerResource;
use App\Services\AccountActivationService;
use Illuminate\Http\JsonResponse;

class ActivationController extends Controller
{
    protected AccountActivationService $activationService;

    public function __construct(AccountActivationService $activationService)
    {
        $this->activationService = $activationService;
    }

   /* Activate a registered customer profile by setting up credentials*/
    public function activate(ActivateAccountRequest $request): JsonResponse
    {
        $customer = $this->activationService->activate($request->validated());

        return response()->json([
            'message' => 'Account activated successfully. You can now log in.',
            'customer' => new CustomerResource($customer),
        ]);
    }
}
