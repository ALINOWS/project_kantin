<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'provider', 'provider_reference', 'payment_method', 'amount',
    'status', 'checkout_url', 'claimed_at', 'paid_at', 'expires_at', 'expired_at',
])]
class Payment extends Model
{
    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'claimed_at' => 'datetime',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }
}
