<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Pages\PrintReceipt;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with([
                    'paymentMethod',
                    'user',
                ])
                ->withCount('items'))
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->badge()
                    ->copyable()
                    ->copyMessage('Order number copied')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->formatStateUsing(fn (mixed $state, Order $record): string => self::formatCustomerName($record, $state))
                    ->wrap()
                    ->copyable()
                    ->copyMessage('Email copied')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('customer_email')
                    ->label('Email')
                    ->placeholder('N/A')
                    ->copyable()
                    ->copyMessage('Email copied')
                    ->searchable(),
                TextColumn::make('grand_total')
                    ->label('Grand total')
                    ->formatStateUsing(fn (?string $state, Order $record): string => self::formatMoney($record, $state))
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->alignment(Alignment::Center)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paymentMethod.name')
                    ->label('Payment method')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('fulfillment_status')
                    ->label('Fulfillment')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'fulfilled' => 'success',
                        'partial' => 'info',
                        'returned' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Placed at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(OrderResource::statusOptions())
                    ->multiple()
                    ->label('Order status'),
                SelectFilter::make('payment_status')
                    ->options(OrderResource::paymentStatusOptions())
                    ->multiple()
                    ->label('Payment status'),
                SelectFilter::make('fulfillment_status')
                    ->options(OrderResource::fulfillmentStatusOptions())
                    ->multiple()
                    ->label('Fulfillment status'),
                SelectFilter::make('payment_method_id')
                    ->label('Payment method')
                    ->relationship('paymentMethod', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Order $record) =>
                        PrintReceipt::getUrl(['orderId' => $record->id])
                    )
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function formatCustomerName(Order $order, mixed $state = null): string
    {
        if (is_string($state) && $state !== '') {
            return $state;
        }

        $shipping = is_array($state) ? $state : $order->shipping_address;
        $first = data_get($shipping, 'first_name');
        $last = data_get($shipping, 'last_name');

        $name = trim(($first ?? '').' '.($last ?? ''));

        if ($name !== '') {
            return $name;
        }

        return $order->user?->name ?? 'Guest checkout';
    }

    private static function formatMoney(Order $order, ?string $value): string
    {
        $currency = $order->currency ?? 'LKR';

        return $currency.' '.number_format((float) ($value ?? 0), 2);
    }
}
