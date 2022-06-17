<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Documentación de Productos",
 *      description="Description de productos.",
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
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index() {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request) {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     * @OA\Get(
     *     path="/api/products/{product}",
     *     tags={"products"},
     *     summary="Mostrar información de un producto",
     *     @OA\Parameter(
     *         description="Parámetro necesario para la consulta de datos de un producto",
     *         in="path",
     *         name="product",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         @OA\Examples(example="int", value="1", summary="Introduce un número de id de un producto.")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mostrar info de un producto."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se ha encontrado el producto."
     *     ),
     *     @OA\Response(
     *         response="default",
     *         description="Ha ocurrido un error."
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
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Product $product
     * @return Response
     */
    public function update(Request $request, Product $product) {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Product $product
     * @return Response
     */
    public function destroy(Product $product) {
        //
    }
}
