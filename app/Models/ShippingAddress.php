<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::saving(function (ShippingAddress $address): void {
            if (! $address->is_default || ! $address->user_id) {
                return;
            }

            static::query()
                ->where('user_id', $address->user_id)
                ->whereKeyNot($address->getKey() ?? 0)
                ->update(['is_default' => false]);
        });
    }
}
