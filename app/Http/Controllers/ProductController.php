<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Products Documentation"
 * )
 */
class ProductController extends Controller {

    /**
     * @param ProductService $productService
     */
    public function __construct(
        private ProductService $productService
    ) {
    }


    /**
     * @return JsonResponse
     * @OA\Get(
     *     path="/api/products",
     *     tags={"products"},
     *     security={{"passport": {}}},
     *     summary="Show a list of products",
     *     @OA\Parameter(
     *         description="Size of page",
     *         in="query",
     *         name="page_size",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         description="Current page",
     *         in="query",
     *         name="current_page",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         description="Column to order",
     *         in="query",
     *         name="column",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         description="Order direction",
     *         in="query",
     *         name="direction",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Show products."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated."
     *     )
     * )
     */
    public function index(Request $request) {
        $apiRes = new ApiResponse('Product');
        $results = $this->productService->get($request->all());
        $filterCount = count($results);
        $totalCount = count($results);
        $status = 200;
        if ($this->productService->hasErrors()) {
            $apiRes->errors->merge($this->productService->getErrors());
            $status = 400;
            $filterCount = 0;
            $totalCount = 0;
        }
        $apiRes->results = $results;
        $apiRes->filterCount = $filterCount;
        $apiRes->totalCount = $totalCount;
        return response()->json($apiRes, $status);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @OA\Post(
     *     path="/api/products",
     *     tags={"products"},
     *     security={{"passport": {}}},
     *     summary="Create a new product",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  required={"name", "code", "price"},
     *                  @OA\Property(
     *                      property="name",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="code",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="price",
     *                      type="number"
     *                  ),
     *                  @OA\Property(
     *                      property="dimensions",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="colors",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="tags",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="stock",
     *                      type="number"
     *                  )
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Show product created."
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated."
     *     )
     * )
     */
    public function store(Request $request) {
        $apiRes = new ApiResponse('Product');
        $results = $this->productService->create($request->all());
        $filterCount = 1;
        $totalCount = 1;
        $status = 200;
        if ($this->productService->hasErrors()) {
            $apiRes->errors->merge($this->productService->getErrors());
            $status = 400;
            $filterCount = 0;
            $totalCount = 0;
        }
        $apiRes->results = $results;
        $apiRes->filterCount = $filterCount;
        $apiRes->totalCount = $totalCount;
        return response()->json($apiRes, $status);
    }

    /**
     * @param int $id
     * @return JsonResponse
     * @OA\Get(
     *     path="/api/products/{product}",
     *     tags={"products"},
     *     security={{"passport": {}}},
     *     summary="Show information of a product",
     *     @OA\Parameter(
     *         description="Product ID",
     *         in="path",
     *         name="product",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Show product information."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found."
     *     )
     * )
     */
    public function show($id) {
        $apiRes = new ApiResponse('Product');
        $results = $this->productService->getById($id) ?? [];
        $filterCount = 1;
        $totalCount = 1;
        $status = 200;
        if ($this->productService->hasErrors()) {
            $apiRes->errors->merge($this->productService->getErrors());
            if ($apiRes->errors->has('not-found')) {
                $status = 404;
            }
            $filterCount = 0;
            $totalCount = 0;
        }
        $apiRes->results = $results;
        $apiRes->filterCount = $filterCount;
        $apiRes->totalCount = $totalCount;
        return response()->json($apiRes, $status);
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @OA\Put(
     *     path="/api/products/{product}",
     *     tags={"products"},
     *     security={{"passport": {}}},
     *     summary="Update a product",
     *     @OA\Parameter(
     *         description="Product ID",
     *         in="path",
     *         name="product",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  required={"name", "code", "price"},
     *                  @OA\Property(
     *                      property="name",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="code",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="price",
     *                      type="number"
     *                  ),
     *                  @OA\Property(
     *                      property="dimensions",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="colors",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="tags",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="stock",
     *                      type="number"
     *                  )
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Show product with data updated."
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found."
     *     )
     * )
     */
    public function update(Request $request, $id) {
        $apiRes = new ApiResponse('Product');
        $results = $this->productService->updateById($id, $request->all()) ?? [];
        $filterCount = 1;
        $totalCount = 1;
        $status = 200;
        if ($this->productService->hasErrors()) {
            $apiRes->errors->merge($this->productService->getErrors());
            if ($apiRes->errors->has('not-found')) {
                $status = 404;
            }
            $filterCount = 0;
            $totalCount = 0;
        }
        $apiRes->results = $results;
        $apiRes->filterCount = $filterCount;
        $apiRes->totalCount = $totalCount;
        return response()->json($apiRes, $status);
    }

    /**
     * @param int $id
     * @return JsonResponse
     * @OA\Delete(
     *     path="/api/products/{product}",
     *     tags={"products"},
     *     security={{"passport": {}}},
     *     summary="Delete a product",
     *     @OA\Parameter(
     *         description="Product ID",
     *         in="path",
     *         name="product",
     *         required=true,
     *         @OA\Schema(type="string"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product delete successfully."
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Error."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found."
     *     )
     * )
     */
    public function destroy($id) {
        $apiRes = new ApiResponse('Product');
        $results = $this->productService->deleteById($id) ?? [];
        $filterCount = 1;
        $totalCount = 1;
        $status = 200;
        if ($this->productService->hasErrors()) {
            $apiRes->errors->merge($this->productService->getErrors());
            if ($apiRes->errors->has('not-found')) {
                $status = 404;
            }
            $filterCount = 0;
            $totalCount = 0;
        }
        $apiRes->results = $results;
        $apiRes->filterCount = $filterCount;
        $apiRes->totalCount = $totalCount;
        return response()->json($apiRes, $status);
    }
}
