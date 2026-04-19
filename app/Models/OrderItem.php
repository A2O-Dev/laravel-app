<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderItem extends Model {
    use HasFactory;

    protected $fillable = [
        'order_id',
        'orderable_type',
        'orderable_id',
        'description',
        'unit_amount',
        'quantity',
        'subtotal',
        'metadata',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'unit_amount' => 'integer',
        'quantity'    => 'integer',
        'subtotal'    => 'integer',
    ];

    public function order(): BelongsTo {
        return $this->belongsTo(Order::class);
    }

    public function orderable(): MorphTo {
        return $this->morphTo();
    }
}
