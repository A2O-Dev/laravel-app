<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_method_id"},
     *             @OA\Property(property="payment_method_id", type="string", example="pm_card_visa")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Subscription created"),
     *     @OA\Response(response=400, description="Validation error or already subscribed"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request): JsonResponse {
        $apiRes = new ApiResponse('Subscription');

        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            $apiRes->errors->merge($validator->getMessageBag());
            return response()->json($apiRes, 400);
        }

        $this->subscriptionService->subscribe(auth()->user(), $request->payment_method_id);

        if ($this->subscriptionService->hasErrors()) {
            $apiRes->errors->merge($this->subscriptionService->getErrors());
            return response()->json($apiRes, 400);
        }

        return response()->json($apiRes, 201);
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