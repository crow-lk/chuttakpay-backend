<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'gateway',
        'description',
        'icon_path',
        'instructions',
        'sort_order',
        'settings',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'settings' => 'array',
            'sort_order' => 'integer',
        ];
    }
}
