<?php

namespace App\Http\Controllers;

use App\Contracts\Purchasable;
use App\Helpers\ApiResponse;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderController extends Controller {

    private array $productTypeMap = [
        'product' => \App\Models\Product::class,
    ];

    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function index(Request $request): JsonResponse {
        $apiRes = new ApiResponse('Order');

        $pageSize    = (int) $request->get('page_size', 10);
        $currentPage = (int) $request->get('current_page', 0);
        $offset      = $currentPage * $pageSize;

        $orders     = Order::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit($pageSize)
            ->offset($offset)
            ->get();
        $totalCount  = Order::where('user_id', auth()->id())->count();
        $filterCount = count($orders);

        $apiRes->results     = $orders;
        $apiRes->filterCount = $filterCount;
        $apiRes->totalCount  = $totalCount;

        return response()->json($apiRes, 200);
    }

    public function store(Request $request): JsonResponse {
        $apiRes = new ApiResponse('Order');

        $validator = Validator::make($request->all(), [
            'product_type' => ['required', 'string', Rule::in(array_keys($this->productTypeMap))],
            'product_id'   => 'required|integer|min:1',
            'currency'     => 'nullable|string|size:3',
        ]);

        if ($validator->fails()) {
            $apiRes->errors->merge($validator->getMessageBag());
            return response()->json($apiRes, 400);
        }

        $product = $this->resolveProduct($request->product_type, (int) $request->product_id);

        if (is_null($product)) {
            $apiRes->errors->add('not-found', 'Product not found');
            return response()->json($apiRes, 404);
        }

        $currency = $request->get('currency', config('cashier.currency', 'usd'));
        $result   = $this->paymentService->createOrder(auth()->user(), $product, $currency);

        if ($this->paymentService->hasErrors()) {
            $errorList = $this->paymentService->getErrors();
            $apiRes->errors->merge($errorList);
            Log::error($errorList);
            $apiRes->filterCount = 0;
            $apiRes->totalCount  = 0;
            return response()->json($apiRes, 400);
        }

        $apiRes->results     = [
            'order'         => $result['order'],
            'client_secret' => $result['client_secret'],
        ];
        $apiRes->filterCount = 1;
        $apiRes->totalCount  = 1;

        return response()->json($apiRes, 201);
    }

    public function show(int $id): JsonResponse {
        $apiRes = new ApiResponse('Order');

        $order = Order::find($id);

        if (is_null($order)) {
            $apiRes->errors->add('not-found', 'Order not found');
            return response()->json($apiRes, 404);
        }

        if ($order->user_id !== auth()->id()) {
            $apiRes->errors->add('unauthorized', 'Access denied');
            return response()->json($apiRes, 403);
        }

        $apiRes->results     = $order;
        $apiRes->filterCount = 1;
        $apiRes->totalCount  = 1;

        return response()->json($apiRes, 200);
    }

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

    private function resolveProduct(string $type, int $id): ?Purchasable {
        if (!isset($this->productTypeMap[$type])) {
            return null;
        }

        return $this->productTypeMap[$type]::find($id);
    }
}
