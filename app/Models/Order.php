<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model {
    use HasFactory, SoftDeletes;

    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PAID       = 'paid';
    const STATUS_FAILED     = 'failed';
    const STATUS_REFUNDED   = 'refunded';

    protected $fillable = [
        'user_id',
        'orderable_type',
        'orderable_id',
        'stripe_payment_intent_id',
        'amount',
        'currency',
        'status',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'paid_at'  => 'datetime',
        'amount'   => 'integer',
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function orderable(): MorphTo {
        return $this->morphTo();
    }

    public function items(): HasMany {
        return $this->hasMany(OrderItem::class);
    }

    public function getFormattedAmountAttribute(): float {
        return $this->amount / 100;
    }

    public function scopePending($query) {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query) {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeFailed($query) {
        return $query->where('status', self::STATUS_FAILED);
    }
}
