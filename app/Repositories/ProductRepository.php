<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductRepository {

    /**
     * @return Collection
     */
    public function get($limit, $offset, $order) {
        /**
         * @var Builder $query
         */
        $query = Product::from('products AS p');
        $query->select(
            DB::raw('SQL_CALC_FOUND_ROWS id'),
            'name',
            'code',
            'price',
            'dimensions',
            'colors',
            'tags',
            'stock',
            'created_at',
            'updated_at'
        );
        foreach ($order as $orderItem) {
            $query->orderBy($orderItem['col'], $orderItem['dir']);
        }
        $query->limit($limit)->offset($offset);
        return $query->get();
    }

    /**
     * @param int $id
     * @return Product
     */
    public function getById($id) {
        return Product::find($id);
    }

}
