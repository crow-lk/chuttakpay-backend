<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ShippingAddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'shippingAddresses';

    protected static ?string $recordTitleAttribute = 'recipient_name';

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Section::make('Shipping address')
                    ->schema([
                        Forms\Components\TextInput::make('label')
                            ->label('Label')
                            ->placeholder('Home, Office, etc.')
                            ->maxLength(120),
                        Forms\Components\TextInput::make('recipient_name')
                            ->label('Recipient name')
                            ->required()
                            ->maxLength(120),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->required()
                            ->maxLength(25),
                        Forms\Components\TextInput::make('address_line1')
                            ->label('Address line 1')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address_line2')
                            ->label('Address line 2')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->required()
                            ->maxLength(120),
                        Forms\Components\TextInput::make('state')
                            ->required()
                            ->maxLength(120),
                        Forms\Components\TextInput::make('postal_code')
                            ->label('Postal code')
                            ->required()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('country')
                            ->required()
                            ->maxLength(120),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Default')
                            ->inline(false),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('recipient_name')
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('Label')
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('recipient_name')
                    ->label('Recipient')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address_line1')
                    ->label('Address')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->address_line1),
                Tables\Columns\TextColumn::make('city')
                    ->sortable(),
                Tables\Columns\TextColumn::make('state')
                    ->sortable(),
                Tables\Columns\TextColumn::make('country')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
