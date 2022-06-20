<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Exception;
use Illuminate\Support\Facades\Validator;

class ProductService extends BaseService {

    /**
     * @param ProductRepository $productRepository
     */
    public function __construct(
        private ProductRepository $productRepository
    ) {
        parent::__construct();
    }

    /**
     * @param array $data
     * @return Product|null
     */
    public function create($data = []) {
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'code' => 'required|string|unique:products',
            'price' => 'required|numeric'
        ]);
        $product = null;
        if ($validator->fails()) {
            $this->errors->merge($validator->getMessageBag());
        } else {
            try {
                $product = Product::create($data);
            } catch (Exception $e) {
                $this->errors->add('not-save', $e->getMessage());
            }
        }
        return $product;
    }

    /**
     * @return Product[]
     */
    public function get($pageSize, $currentPage, $col, $dir) {
        $limit = $pageSize;
        $offset = $currentPage * $limit;
        $order[] = ['col' => $col, 'dir' => $dir];
        $productList = [];
        try {
            $productList = $this->productRepository->get($limit, $offset, $order);
        } catch (Exception $e) {
            $this->errors->add('query', $e->getMessage());
        }
        return $productList;
    }

    /**
     * @param $id
     * @return Product
     */
    public function getById($id) {
        $product = $this->productRepository->getById($id);
        if (is_null($product)) {
            $this->errors->add('not-found', 'The product is not found');
        }
        return $product;
    }

}
