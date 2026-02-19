<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $appUrl = config('app.url') ?: 'http://localhost';

        $methods = [
            [
                'name' => 'Online Bank Transfer',
                'code' => 'BANK',
                'type' => 'offline',
                'gateway' => 'manual_bank',
                'description' => 'Customers transfer funds manually and share the receipt.',
                'instructions' => 'Please transfer the total to our bank account and upload the slip.',
                'sort_order' => 1,
                'settings' => [
                    'account_name' => 'Aaliya Fashion',
                    'account_number' => null,
                    'bank_name' => null,
                    'branch' => null,
                    'currency' => 'LKR',
                ],
            ],
            [
                'name' => 'PayHere',
                'code' => 'PAYHERE',
                'type' => 'online',
                'gateway' => 'payhere',
                'description' => 'Redirect customers to PayHere checkout.',
                'instructions' => null,
                'sort_order' => 2,
                'settings' => [
                    'merchant_id' => null,
                    'merchant_secret' => null,
                    'currency' => 'LKR',
                    'return_url' => $appUrl.'/payments/payhere/return',
                    'cancel_url' => $appUrl.'/payments/payhere/cancel',
                    'notify_url' => $appUrl.'/api/payments/payhere/notify',
                    'sandbox' => true,
                ],
            ],
            [
                'name' => 'Cash On Delivery',
                'code' => 'COD',
                'type' => 'offline',
                'gateway' => 'cod',
                'description' => 'Customer pays cash to the courier.',
                'instructions' => 'Have the exact amount ready for the courier.',
                'sort_order' => 3,
                'settings' => [],
            ],
            [
                'name' => 'Koko',
                'code' => 'KOKO',
                'type' => 'online',
                'gateway' => 'koko',
                'description' => 'Redirect to Koko for BNPL installments.',
                'instructions' => null,
                'sort_order' => 4,
                'settings' => [
                    'public_key' => null,
                    'secret_key' => null,
                ],
            ],
            [
                'name' => 'Mintpay',
                'code' => 'MINTPAY',
                'type' => 'online',
                'gateway' => 'mintpay',
                'description' => 'Mintpay Buy-Now-Pay-Later checkout.',
                'instructions' => null,
                'sort_order' => 5,
                'settings' => [
                    'merchant_id' => null,
                    'token' => null,
                    'sandbox' => true,
                    'success_url' => $appUrl.'/payments/mintpay/success',
                    'fail_url' => $appUrl.'/payments/mintpay/fail',
                ],
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $method['code']],
                [
                    'name' => $method['name'],
                    'type' => $method['type'],
                    'gateway' => $method['gateway'],
                    'description' => $method['description'],
                    'instructions' => $method['instructions'],
                    'sort_order' => $method['sort_order'],
                    'settings' => $method['settings'],
                    'active' => true,
                ]
            );
        }
    }
}
