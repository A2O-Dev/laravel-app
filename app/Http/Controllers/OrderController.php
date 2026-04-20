<?php

namespace App\Http\Controllers;

use App\Contracts\Purchasable;
use App\Helpers\ApiResponse;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderController extends Controller {

    private const PRODUCT_TYPE_MAP = [
        'product' => Product::class,
    ];

    public function __construct(
        private OrderService $orderService,
        private PaymentService $paymentService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/orders",
     *     tags={"orders"},
     *     security={{"sanctum": {}}},
     *     summary="List orders of the authenticated user",
     *     @OA\Parameter(name="page_size", in="query", @OA\Schema(type="integer", example=10)),
     *     @OA\Parameter(name="current_page", in="query", @OA\Schema(type="integer", example=0)),
     *     @OA\Response(response=200, description="List of orders"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse {
        $apiRes = new ApiResponse('Order');

        $pageSize    = (int) $request->get('page_size', 10);
        $currentPage = (int) $request->get('current_page', 0);
        $offset      = $currentPage * $pageSize;
        $userId      = auth()->id();

        $orders      = $this->orderService->getByUser($userId, $pageSize, $offset);
        $totalCount  = $this->orderService->countByUser($userId);

        $apiRes->results     = $orders;
        $apiRes->filterCount = count($orders);
        $apiRes->totalCount  = $totalCount;

        return response()->json($apiRes, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/orders",
     *     tags={"orders"},
     *     security={{"sanctum": {}}},
     *     summary="Create a new order and payment intent",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_type","product_id"},
     *             @OA\Property(property="product_type", type="string", example="product"),
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="currency", type="string", example="usd")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Order created with client_secret"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Product not found")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $apiRes = new ApiResponse('Order');
        $statusCode = 201;
        $result = null;

        if (!$this->validateRequest($request, $apiRes)) {
            $statusCode = 400;

        } else {
            $product = $this->resolveProduct(
                $request->product_type,
                (int) $request->product_id
            );

            if ($product === null) {
                $apiRes->errors->add('not-found', 'Product not found');
                $statusCode = 404;

            } else {
                $result = $this->processPayment($product, $request, $apiRes);

                if ($result === null) {
                    $statusCode = 400;

                } else {
                    $apiRes->results = [
                        'order' => $result['order'],
                        'client_secret' => $result['client_secret'],
                    ];

                    $apiRes->filterCount = 1;
                    $apiRes->totalCount = 1;
                }
            }
        }

        return response()->json($apiRes, $statusCode);
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     tags={"orders"},
     *     security={{"sanctum": {}}},
     *     summary="Get a single order",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Order data"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Access denied"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function show(int $id): JsonResponse {
        $apiRes = new ApiResponse('Order');

        $order = $this->orderService->getByIdForUser($id, auth()->id());

        if ($this->orderService->hasErrors()) {
            $errorList = $this->orderService->getErrors();
            $status = $errorList->has('not-found') ? 404 : 403;
            $apiRes->errors->merge($errorList);
            return response()->json($apiRes, $status);
        }

        $apiRes->results     = $order;
        $apiRes->filterCount = 1;
        $apiRes->totalCount  = 1;

        return response()->json($apiRes, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/orders/{id}/confirm",
     *     tags={"orders"},
     *     security={{"sanctum": {}}},
     *     summary="Confirm payment of an order",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Order confirmed"),
     *     @OA\Response(response=400, description="Payment error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Access denied"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function confirm(int $id): JsonResponse {
        $apiRes = new ApiResponse('Order');

        $order = $this->paymentService->confirmOrder(auth()->user(), $id);

        if ($this->paymentService->hasErrors()) {
            $errorList = $this->paymentService->getErrors();
            $apiRes->errors->merge($errorList);
            Log::error($errorList);
            $apiRes->filterCount = 0;
            $apiRes->totalCount  = 0;

            $status = 400;
            if ($apiRes->errors->has('not-found')) {
                $status = 404;
            } elseif ($apiRes->errors->has('unauthorized')) {
                $status = 403;
            }

            return response()->json($apiRes, $status);
        }

        $apiRes->results     = $order;
        $apiRes->filterCount = 1;
        $apiRes->totalCount  = 1;

        return response()->json($apiRes, 200);
    }

    private function validateRequest(Request $request, ApiResponse $apiRes): bool {
        $validator = Validator::make($request->all(), [
            'product_type' => ['required', 'string', Rule::in(array_keys(self::PRODUCT_TYPE_MAP))],
            'product_id'   => 'required|integer|min:1',
            'currency'     => 'nullable|string|size:3',
        ]);

        if ($validator->fails()) {
            $apiRes->errors->merge($validator->getMessageBag());
            return false;
        }

        return true;
    }

    private function resolveProduct(string $type, int $id): ?Purchasable {
        if (!isset(self::PRODUCT_TYPE_MAP[$type])) {
            return null;
        }

        return self::PRODUCT_TYPE_MAP[$type]::find($id);
    }

    private function processPayment(Purchasable $product, Request $request, ApiResponse $apiRes): ?array {
        $currency = $request->get('currency', config('cashier.currency', 'usd'));
        $result   = $this->paymentService->createOrder(auth()->user(), $product, $currency);

        if ($this->paymentService->hasErrors()) {
            $errorList = $this->paymentService->getErrors();
            $apiRes->errors->merge($errorList);
            Log::error($errorList);
            $apiRes->filterCount = 0;
            $apiRes->totalCount  = 0;
            return null;
        }

        return $result;
    }
}
