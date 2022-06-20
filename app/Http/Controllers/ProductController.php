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
     * @return JsonResponse
     * @OA\Get(
     *     path="/api/products",
     *     tags={"products"},
     *     summary="Mostrar el listado de productos",
     *     @OA\Parameter(
     *         description="Size of page",
     *         in="query",
     *         name="page_size",
     *         @OA\Schema(type="string"),
     *         @OA\Examples(example="int", value=5, summary="Introduce un tamaño de la página.")
     *     ),
     *     @OA\Parameter(
     *         description="Current page",
     *         in="query",
     *         name="current_page",
     *         @OA\Schema(type="string"),
     *         @OA\Examples(example="int", value=0, summary="Introduce la página actual.")
     *     ),
     *     @OA\Parameter(
     *         description="Column order",
     *         in="query",
     *         name="column",
     *         @OA\Schema(type="string"),
     *         @OA\Examples(example="string", value="id", summary="Introduce la columna para ordernar.")
     *     ),
     *     @OA\Parameter(
     *         description="Order direction",
     *         in="query",
     *         name="direction",
     *         @OA\Schema(type="string"),
     *         @OA\Examples(example="string", value="asc", summary="Ordernar de forma ascendente.")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mostrar productos."
     *     ),
     *     @OA\Response(
     *         response="default",
     *         description="Ha ocurrido un error."
     *     )
     * )
     */
    public function index(Request $request) {
        $apiRes = new ApiResponse('Product');
        $results = $this->productService->get(
            $request->get('page_size', 5),
            $request->get('current_page', 0),
            $request->get('column', 'id'),
            $request->get('direction', 'asc')
        );
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
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
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
