<?php

namespace App\Services\Payments;

class PaymentNotification
{
    public function __construct(
        public readonly bool $verified,
        public readonly string $status,
        public readonly array $data = [],
        public readonly ?string $reference = null
    ) {}

    public function isSuccessful(): bool
    {
        return $this->verified && $this->status === 'paid';
    }
}
