<?php

namespace App\Services;

use App\Repositories\ProductRepository;

class ProductService extends BaseService {

    /**
     * @param ProductRepository $productRepository
     */
    public function __construct(
        private ProductRepository $productRepository
    ) {
        parent::__construct();
    }

    public function getById($id) {
        $product = $this->productRepository->getById($id);

        if (is_null($product)) {
            $this->errors->add('not-found', 'The product is not found');
        }

        return $product;
    }

}
