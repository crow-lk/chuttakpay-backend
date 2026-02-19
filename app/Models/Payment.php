<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'cart_id',
        'order_id',
        'amount_paid',
        'payment_method_id',
        'gateway',
        'gateway_payload',
        'reference_number',
        'payment_date',
        'notes',
        'discount_available',
        'discount',
        'payment_status',
        'gateway_response',
        'receipt_path',
    ];

    protected function casts(): array
    {
        return [
            'discount_available' => 'boolean',
            'gateway_payload' => 'array',
            'gateway_response' => 'array',
            'payment_date' => 'datetime',
        ];
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
