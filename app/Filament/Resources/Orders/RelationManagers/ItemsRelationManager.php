<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\OrderItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Order items';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                TextColumn::make('product_name')
                    ->label('Product')
                    ->wrap()
                    ->sortable(),
                TextColumn::make('variant_name')
                    ->label('Variant')
                    ->placeholder('N/A')
                    ->wrap()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->badge()
                    ->placeholder('N/A')
                    ->copyable()
                    ->copyMessage('SKU copied')
                    ->color('gray'),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->alignment(Alignment::Center)
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->label('Unit price')
                    ->formatStateUsing(fn (?string $state, OrderItem $record): string => self::formatMoney($state, $record))
                    ->sortable(),
                TextColumn::make('line_total')
                    ->label('Line total')
                    ->formatStateUsing(fn (?string $state, OrderItem $record): string => self::formatMoney($state, $record))
                    ->weight('medium')
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    private static function formatMoney(?string $value, OrderItem $record): string
    {
        $currency = $record->order?->currency ?? 'LKR';

        return $currency.' '.number_format((float) ($value ?? 0), 2);
    }
}
