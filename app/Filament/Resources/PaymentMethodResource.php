<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Models\PaymentMethod;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static string|UnitEnum|null $navigationGroup = 'Payments';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Method details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(120),

                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'offline' => 'Offline',
                                'online' => 'Online',
                            ])
                            ->default('offline')
                            ->native(false),

                        Forms\Components\Select::make('gateway')
                            ->label('Gateway driver')
                            ->required()
                            ->options([
                                'manual_bank' => 'Manual bank transfer',
                                'cod' => 'Cash on delivery',
                                'payhere' => 'PayHere',
                                'koko' => 'Koko',
                                'mintpay' => 'Mintpay',
                            ])
                            ->native(false),

                        Forms\Components\TextInput::make('description')
                            ->label('Description')
                            ->maxLength(255),

                        Forms\Components\FileUpload::make('icon_path')
                            ->label('Gateway icon')
                            ->image()
                            ->disk('public')
                            ->directory('payment-method-icons'),

                        Forms\Components\Textarea::make('instructions')
                            ->label('Customer instructions')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('settings')
                            ->label('Gateway settings')
                            ->columnSpanFull()
                            ->addActionLabel('Add setting')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->nullable(),

                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),

                        Forms\Components\Toggle::make('active')
                            ->label('Status')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon_path')
                    ->label('Icon')
                    ->disk('public')
                    ->imageWidth(150)
                    ->imageHeight(70)
                    ->extraImgAttributes([
                        'style' => 'object-fit: contain;',
                    ])
                    ->defaultImageUrl('https://placehold.co/150x70?text=No+Icon'),
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('gateway')
                    ->label('Gateway')
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentsMethod::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
