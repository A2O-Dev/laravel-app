<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Exception;

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
