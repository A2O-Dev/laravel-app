<?php

namespace App\Models;

use App\Contracts\Purchasable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model implements Purchasable {
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'price',
        'dimensions',
        'colors',
        'tags',
        'stock'
    ];

    public function orders(): MorphMany {
        return $this->morphMany(Order::class, 'orderable');
    }

    public function getPriceInCents(): int {
        return (int) ($this->price * 100);
    }

    public function getDisplayName(): string {
        return $this->name;
    }

    public function getMetadata(): array {
        return [
            'product_id'   => $this->id,
            'product_name' => $this->name,
            'product_code' => $this->code,
        ];
    }
}
