<?php

namespace App\Filament\Actions;

use App\Filament\Pages\PrintReceipt;
use App\Jobs\SendOrderPlacedSms;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProcessSaleAction
{
    public static function make(array $cart, callable $clearCartCallback): Action
    {
        return Action::make('createSale')
            ->label('Create Sale')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Finalize Sale')
            ->modalDescription('Are you sure you want to complete this sale?')

            ->schema([

                TextInput::make('customer_phone')
                    ->label('Customer Phone')
                    ->tel()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $user = User::where('mobile', $state)->first();

                        if ($user) {
                            $set('customer_name', $user->name);
                        }
                    }),

                TextInput::make('customer_name')
                    ->label('Customer Name')
                    ->reactive()
                    ->required(),

                Select::make('payment_method_id')
                    ->label('Payment Method')
                    ->options(fn () => PaymentMethod::pluck('name', 'id')->toArray())
                    ->required()
                    ->live()
                    ->searchable(),

                TextInput::make('reference_number')
                    ->label('Reference Number')
                    ->visible(fn (callable $get) => ($pm = PaymentMethod::find($get('payment_method_id')))
                            ? ! $pm->is_cash
                            : false
                    )
                    ->required(fn (callable $get) => ($pm = PaymentMethod::find($get('payment_method_id')))
                            ? ! $pm->is_cash
                            : false
                    ),

                Select::make('discount_type')
                    ->label('Discount Type')
                    ->options([
                        'none' => 'No Discount',
                        'percentage' => 'Percentage (%)',
                        'fixed' => 'Fixed Amount',
                    ])
                    ->default('none')
                    ->live(),

                TextInput::make('discount_value')
                    ->label('Discount Value')
                    ->numeric()
                    ->minValue(0)
                    ->visible(fn (callable $get) => $get('discount_type') !== 'none')
                    ->required(fn (callable $get) => $get('discount_type') !== 'none'),
            ])

            ->action(function (array $data) use ($cart, $clearCartCallback) {

                if (empty($cart)) {
                    Notification::make()
                        ->title('Cart is empty')
                        ->warning()
                        ->send();

                    return;
                }

                try {
                    DB::transaction(function () use ($data, $cart, $clearCartCallback) {

                        $user = User::where('mobile', $data['customer_phone'])->first();

                        if (! $user) {
                            $user = User::create([
                                'name' => $data['customer_name'],
                                'mobile' => $data['customer_phone'],
                                'password' => Hash::make('aaliyaa123'),
                            ]);
                        }

                        $subtotal = collect($cart)->sum(
                            fn ($item) => $item['price'] * $item['quantity']
                        );

                        $discountTotal = 0;

                        if ($data['discount_type'] === 'percentage') {
                            $discountTotal = round(
                                ($subtotal * $data['discount_value']) / 100,
                                2
                            );
                        }

                        if ($data['discount_type'] === 'fixed') {
                            $discountTotal = min($data['discount_value'], $subtotal);
                        }

                        $taxTotal = 0;
                        $shippingTotal = 0;

                        $grandTotal = max(0, $subtotal - $discountTotal);

                        $payment = Payment::create([
                            'amount_paid' => $grandTotal,
                            'payment_method_id' => $data['payment_method_id'],
                            'reference_number' => $data['reference_number'] ?? null,
                            'payment_date' => now(),
                            'discount_available' => $discountTotal > 0,
                            'discount' => $discountTotal,
                            'payment_status' => 'paid',
                        ]);

                        $orderNumber = strtoupper(Str::random(6))
                            .'-'.now()->timestamp.rand(1000, 9999);

                        $order = Order::create([
                            'order_number' => $orderNumber,
                            'user_id' => $user->id,
                            'customer_name' => $data['customer_name'],
                            'customer_phone' => $data['customer_phone'],
                            'payment_id' => $payment->id,
                            'payment_method_id' => $data['payment_method_id'],
                            'status' => 'completed',
                            'payment_status' => 'paid',
                            'fulfillment_status' => 'pending',
                            'currency' => 'USD',

                            'subtotal' => $subtotal,
                            'tax_total' => $taxTotal,
                            'discount_total' => $discountTotal,
                            'shipping_total' => $shippingTotal,
                            'grand_total' => $grandTotal,
                        ]);

                        // Link payment → order
                        $payment->update([
                            'order_id' => $order->id,
                        ]);

                        foreach ($cart as $item) {

                            $variant = isset($item['variant_id'])
                                ? ProductVariant::find($item['variant_id'])
                                : ProductVariant::where('product_id', $item['product_id'])->first();

                            if (! $variant) {
                                throw new \Exception('Product variant not found');
                            }

                            OrderItem::create([
                                'order_id' => $order->id,
                                'product_id' => $variant->product_id,
                                'product_variant_id' => $variant->id,
                                'product_name' => $variant->product?->name,
                                'variant_name' => $variant->displayName,
                                'sku' => $variant->sku,
                                'quantity' => $item['quantity'],
                                'unit_price' => $item['price'],
                                'line_total' => $item['price'] * $item['quantity'],
                                'meta' => [],
                            ]);

                            $variant->adjustStock(
                                1,
                                -$item['quantity'],
                                'sale',
                                [
                                    'reference_type' => Order::class,
                                    'reference_id' => $order->id,
                                    'notes' => "Sold {$item['quantity']} units",
                                    'created_by' => Auth::id(),
                                ]
                            );
                        }

                        $clearCartCallback();

                        SendOrderPlacedSms::dispatch($order)->afterCommit();

                        Notification::make()
                            ->title('Order created successfully')
                            ->success()
                            ->body('Click below to print receipt')
                            ->actions([
                                Action::make('print')
                                    ->url(PrintReceipt::getUrl(['orderId' => $order->id]))
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                    });

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error creating order')
                        ->danger()
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
