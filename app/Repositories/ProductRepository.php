<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository {

    /**
     * @param int $id
     * @return Product
     */
    public function getById($id) {
        return Product::find($id);
    }

}
