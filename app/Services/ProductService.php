<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
    public function get($data = []) {
        $validator = Validator::make($data, [
            'page_size' => 'numeric',
            'current_page' => 'numeric',
            'column' => [
                'string',
                Rule::in([
                    'name',
                    'code',
                    'price',
                    'stock'
                ])
            ],
            'direction' => [
                'string',
                Rule::in(['asc', 'desc'])
            ]
        ]);
        if ($validator->fails()) {
            $this->errors->merge($validator->getMessageBag());
            return [];
        }
        $pageSize = Arr::get($data, 'page_size', 5);
        $currentPage = Arr::get($data, 'current_page', 0);
        $col = Arr::get($data, 'column', 'id');
        $dir = Arr::get($data, 'direction', 'asc');
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

    /**
     * @param int $id
     * @param array $data
     * @return Product|null
     */
    public function updateById($id, $data) {
        $product = $this->getById($id);
        if ($this->hasErrors()) {
            return $product;
        }
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'code' => [
                'required',
                'string',
                Rule::unique('products')->ignore($product->id)
            ],
            'price' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            $this->errors->merge($validator->getMessageBag());
        } else {
            try {
                $product->update($data);
            } catch (Exception $e) {
                $this->errors->add('not-updated', $e->getMessage());
            }
        }
        return $product;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteById($id) {
        $successfully = false;
        $product = $this->getById($id);
        if ($this->hasErrors()) {
            return $successfully;
        }
        try {
            $successfully = $product->delete();
        } catch (Exception $e) {
            $this->errors->add('not-deleted', $e->getMessage());
        }
        return $successfully;
    }

}
