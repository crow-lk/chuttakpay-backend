<?php

namespace App\Http\Requests\Checkout;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PlaceOrderRequest extends FormRequest
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
            'payment_id' => ['nullable', 'required_without:payment_method_id', 'exists:payments,id'],
            'payment_method_id' => ['nullable', 'required_without:payment_id', 'exists:payment_methods,id'],
            'session_id' => ['nullable', 'string', 'max:120'],
            'currency' => ['nullable', 'string', 'size:3'],
            'shipping_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'shipping.first_name' => ['required', 'string', 'max:120'],
            'shipping.last_name' => ['required', 'string', 'max:120'],
            'shipping.address_line1' => ['required', 'string', 'max:255'],
            'shipping.address_line2' => ['nullable', 'string', 'max:255'],
            'shipping.city' => ['required', 'string', 'max:120'],
            'shipping.country' => ['required', 'string', 'max:120'],
            'shipping.postal_code' => ['nullable', 'string', 'max:30'],
            'shipping.email' => ['required', 'email', 'max:255'],
            'shipping.phone' => ['required', 'string', 'max:50'],
            'billing' => ['nullable', 'array'],
            'billing.first_name' => ['required_with:billing', 'string', 'max:120'],
            'billing.last_name' => ['required_with:billing', 'string', 'max:120'],
            'billing.address_line1' => ['required_with:billing', 'string', 'max:255'],
            'billing.address_line2' => ['nullable', 'string', 'max:255'],
            'billing.city' => ['required_with:billing', 'string', 'max:120'],
            'billing.country' => ['required_with:billing', 'string', 'max:120'],
            'billing.postal_code' => ['nullable', 'string', 'max:30'],
            'billing.email' => ['nullable', 'email', 'max:255'],
            'billing.phone' => ['nullable', 'string', 'max:50'],
            'payment_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->filled('payment_id') || ! $this->filled('payment_method_id')) {
                return;
            }

            $paymentMethod = PaymentMethod::query()->find($this->integer('payment_method_id'));

            if ($paymentMethod && $paymentMethod->gateway === 'manual_bank' && ! $this->hasFile('payment_receipt')) {
                $validator->errors()->add('payment_receipt', 'Please upload the payment receipt for online transfers.');
            }
        });
    }
}
