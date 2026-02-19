<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Actions\SendOrderSmsAction;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Components\Actions as ActionsComponent;
use Filament\Schemas\Components\Grid as SchemaGrid;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaSection::make('Statuses & fulfillment')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Order status')
                            ->options(OrderResource::statusOptions())
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('payment_status')
                            ->label('Payment status')
                            ->options(OrderResource::paymentStatusOptions())
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('fulfillment_status')
                            ->label('Fulfillment status')
                            ->options(OrderResource::fulfillmentStatusOptions())
                            ->required()
                            ->native(false),
                    ])
                    ->columns(3),
                SchemaSection::make('Customer contact')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Customer account')
                            ->relationship('user', 'name')
                            ->getOptionLabelFromRecordUsing(function (User $record): string {
                                $label = $record->name;

                                if (filled($record->email)) {
                                    $label .= ' - '.$record->email;
                                }

                                if (filled($record->mobile)) {
                                    $label .= ' - '.$record->mobile;
                                }

                                return $label;
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                if (blank($state)) {
                                    return;
                                }

                                $user = User::query()->find($state);

                                if (! $user) {
                                    return;
                                }

                                if (blank($get('customer_name'))) {
                                    $set('customer_name', $user->name);
                                }

                                if (blank($get('customer_email'))) {
                                    $set('customer_email', $user->email);
                                }

                                if (blank($get('customer_phone'))) {
                                    $set('customer_phone', $user->mobile);
                                }
                            })
                            ->columnSpanFull(),
                        SchemaGrid::make([
                            'default' => 1,
                            'md' => 3,
                        ])
                            ->schema([
                                Forms\Components\TextInput::make('customer_name')
                                    ->label('Customer name')
                                    ->maxLength(255)
                                    ->rule('nullable')
                                    ->dehydrateStateUsing(fn (?string $state): ?string => blank($state) ? null : $state),
                                Forms\Components\TextInput::make('customer_email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255)
                                    ->rule('nullable')
                                    ->dehydrateStateUsing(fn (?string $state): ?string => blank($state) ? null : $state),
                                Forms\Components\TextInput::make('customer_phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->maxLength(50)
                                    ->rule('nullable')
                                    ->dehydrateStateUsing(fn (?string $state): ?string => blank($state) ? null : $state),
                            ]),
                    ])
                    ->columns(1),
                SchemaSection::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Customer notes')
                            ->rows(4)
                            ->helperText('Visible to fulfillment and support teams.')
                            ->dehydrateStateUsing(fn (?string $state): ?string => blank($state) ? null : $state),
                    ])
                    ->columns(1),
                SchemaSection::make('Notify.lk SMS')
                    ->schema([
                        ActionsComponent::make([
                            SendOrderSmsAction::make(),
                        ])
                            ->fullWidth(),
                    ])
                    ->visible(fn (?Order $record): bool => (bool) $record?->exists)
                    ->columns(1),
            ]);
    }
}
