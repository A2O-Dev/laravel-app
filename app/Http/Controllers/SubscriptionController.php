<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller {

    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/subscriptions",
     *     tags={"subscriptions"},
     *     security={{"sanctum": {}}},
     *     summary="Subscribe the authenticated user to the default plan",
     *     @OA\Response(response=201, description="Subscription created with client_secret"),
     *     @OA\Response(response=400, description="Already subscribed or subscription error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(): JsonResponse {
        $apiRes = new ApiResponse('Subscription');

        $clientSecret = $this->subscriptionService->subscribe(auth()->user());

        if ($this->subscriptionService->hasErrors()) {
            $apiRes->errors->merge($this->subscriptionService->getErrors());
            return response()->json($apiRes, 400);
        }

        $apiRes->results     = ['client_secret' => $clientSecret];
        $apiRes->filterCount = 1;
        $apiRes->totalCount  = 1;

        return response()->json($apiRes, 201);
    }

    /**
     * @OA\Post(
     *     path="/api/subscriptions/confirm",
     *     tags={"subscriptions"},
     *     security={{"sanctum": {}}},
     *     summary="Confirm payment and sync subscription status",
     *     @OA\Response(response=200, description="Subscription status synced"),
     *     @OA\Response(response=400, description="Sync error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Subscription not found")
     * )
     */
    public function confirm(): JsonResponse {
        $apiRes = new ApiResponse('Subscription');

        $subscription = $this->subscriptionService->confirm(auth()->user());

        if ($this->subscriptionService->hasErrors()) {
            $errorList = $this->subscriptionService->getErrors();
            $apiRes->errors->merge($errorList);
            $status = $errorList->has('not-found') ? 404 : 400;
            return response()->json($apiRes, $status);
        }

        $apiRes->results     = $subscription;
        $apiRes->filterCount = 1;
        $apiRes->totalCount  = 1;

        return response()->json($apiRes, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/subscriptions",
     *     tags={"subscriptions"},
     *     security={{"sanctum": {}}},
     *     summary="Cancel the authenticated user's active subscription",
     *     @OA\Response(response=200, description="Subscription cancelled"),
     *     @OA\Response(response=400, description="No active subscription"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(): JsonResponse {
        $apiRes = new ApiResponse('Subscription');

        $this->subscriptionService->cancel(auth()->user());

        if ($this->subscriptionService->hasErrors()) {
            $apiRes->errors->merge($this->subscriptionService->getErrors());
            return response()->json($apiRes, 400);
        }

        return response()->json($apiRes, 200);
    }
}