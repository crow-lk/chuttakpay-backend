<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid as SchemaGrid;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Collection;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaSection::make('Order summary')
                    ->schema([
                        SchemaGrid::make([
                            'default' => 1,
                            'md' => 2,
                            'lg' => 4,
                        ])
                            ->schema([
                                TextEntry::make('order_number')
                                    ->label('Order #')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::SemiBold)
                                    ->copyable(),
                                TextEntry::make('created_at')
                                    ->label('Placed at')
                                    ->dateTime(),
                                TextEntry::make('user.name')
                                    ->label('Customer account')
                                    ->placeholder('Guest checkout')
                                    ->badge()
                                    ->color('gray'),
                                TextEntry::make('paymentMethod.name')
                                    ->label('Payment method')
                                    ->placeholder('N/A'),
                            ]),
                        SchemaGrid::make([
                            'default' => 1,
                            'md' => 3,
                        ])
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Order status')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('payment_status')
                                    ->label('Payment status')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'paid' => 'success',
                                        'failed' => 'danger',
                                        'refunded' => 'info',
                                        default => 'warning',
                                    }),
                                TextEntry::make('fulfillment_status')
                                    ->label('Fulfillment')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'fulfilled' => 'success',
                                        'partial' => 'info',
                                        'returned' => 'danger',
                                        default => 'gray',
                                    }),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                SchemaSection::make('Totals')
                    ->schema([
                        SchemaGrid::make([
                            'default' => 1,
                            'md' => 2,
                            'lg' => 5,
                        ])
                            ->schema([
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->formatStateUsing(fn (?string $state, Order $record): string => self::formatMoney($state, $record)),
                                TextEntry::make('tax_total')
                                    ->label('Tax')
                                    ->formatStateUsing(fn (?string $state, Order $record): string => self::formatMoney($state, $record)),
                                TextEntry::make('discount_total')
                                    ->label('Discounts')
                                    ->formatStateUsing(fn (?string $state, Order $record): string => self::formatMoney($state, $record)),
                                TextEntry::make('shipping_total')
                                    ->label('Shipping')
                                    ->formatStateUsing(fn (?string $state, Order $record): string => self::formatMoney($state, $record)),
                                TextEntry::make('grand_total')
                                    ->label('Grand total')
                                    ->weight(FontWeight::Medium)
                                    ->size(TextSize::Large)
                                    ->formatStateUsing(fn (?string $state, Order $record): string => self::formatMoney($state, $record)),
                            ]),
                    ])
                    ->columnSpanFull(),
                SchemaSection::make('Addresses')
                    ->schema([
                        SchemaGrid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                TextEntry::make('shipping_address')
                                    ->label('Shipping address')
                                    ->formatStateUsing(fn (mixed $state): ?string => self::formatAddress($state))
                                    ->html()
                                    ->placeholder('Not provided'),
                                TextEntry::make('billing_address')
                                    ->label('Billing address')
                                    ->formatStateUsing(fn (mixed $state): ?string => self::formatAddress($state))
                                    ->html()
                                    ->placeholder('Not provided'),
                            ]),
                    ])
                    ->columnSpanFull(),
                SchemaSection::make('Additional details')
                    ->schema([
                        SchemaGrid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                TextEntry::make('customer_email')
                                    ->label('Customer email')
                                    ->placeholder('N/A')
                                    ->copyable(),
                                TextEntry::make('customer_phone')
                                    ->label('Customer phone')
                                    ->placeholder('N/A'),
                            ]),
                        TextEntry::make('notes')
                            ->label('Customer notes')
                            ->columnSpanFull()
                            ->prose()
                            ->hidden(fn (?string $state): bool => blank($state)),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function formatMoney(?string $value, Order $order): string
    {
        $currency = $order->currency ?? 'LKR';

        return $currency.' '.number_format((float) ($value ?? 0), 2);
    }

    private static function formatAddress(mixed $address): ?string
    {
        if (! $address) {
            return null;
        }

        if (is_string($address)) {
            return e($address);
        }

        if (! is_array($address)) {
            return null;
        }

        $lines = Collection::make([
            trim(($address['first_name'] ?? '').' '.($address['last_name'] ?? '')),
            $address['address_line1'] ?? null,
            $address['address_line2'] ?? null,
            Collection::make([
                $address['city'] ?? null,
                $address['country'] ?? null,
            ])
                ->filter()
                ->implode(', '),
            $address['postal_code'] ? 'Postal code: '.$address['postal_code'] : null,
            $address['phone'] ? 'Phone: '.$address['phone'] : null,
            $address['email'] ? 'Email: '.$address['email'] : null,
        ])
            ->filter()
            ->map(fn (string $line): string => e($line));

        return $lines->implode('<br>');
    }
}
