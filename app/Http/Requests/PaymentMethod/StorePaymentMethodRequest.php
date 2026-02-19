<?php

namespace App\Http\Requests\PaymentMethod;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:50', Rule::unique('payment_methods', 'code')],
            'type' => ['required', Rule::in(['online', 'offline'])],
            'gateway' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
