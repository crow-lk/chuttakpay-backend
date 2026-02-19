<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'session_id' => ['nullable', 'string', 'max:120'],
            'items' => ['nullable', 'string', 'max:255'],
            'customer.first_name' => ['required', 'string', 'max:120'],
            'customer.last_name' => ['required', 'string', 'max:120'],
            'customer.email' => ['required', 'email', 'max:255'],
            'customer.phone' => ['required', 'string', 'max:50'],
            'customer.address' => ['required', 'string', 'max:255'],
            'customer.city' => ['required', 'string', 'max:120'],
            'customer.country' => ['required', 'string', 'max:120'],
            'customer.postal_code' => ['nullable', 'string', 'max:30'],
            'customer.postcode' => ['nullable', 'string', 'max:30'],
            'shipping' => ['nullable', 'array'],
            'shipping.first_name' => ['nullable', 'string', 'max:120'],
            'shipping.last_name' => ['nullable', 'string', 'max:120'],
            'shipping.address_line1' => ['nullable', 'string', 'max:255'],
            'shipping.address_line2' => ['nullable', 'string', 'max:255'],
            'shipping.city' => ['nullable', 'string', 'max:120'],
            'shipping.country' => ['nullable', 'string', 'max:120'],
            'shipping.postal_code' => ['nullable', 'string', 'max:30'],
            'shipping.email' => ['nullable', 'email', 'max:255'],
            'shipping.phone' => ['nullable', 'string', 'max:50'],
            'return_url' => ['nullable', 'url'],
            'cancel_url' => ['nullable', 'url'],
            'notify_url' => ['nullable', 'url'],
            'success_url' => ['nullable', 'url'],
            'fail_url' => ['nullable', 'url'],
        ];
    }
}
