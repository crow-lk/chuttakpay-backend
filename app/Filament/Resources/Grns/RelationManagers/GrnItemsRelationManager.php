<?php

namespace App\Filament\Resources\Grns\RelationManagers;

use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class GrnItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'grnItems';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->components([
                Forms\Components\Select::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        if ($state) {
                            $set('variant_id', null);
                        }
                    })
                    ->required()
                    ->columnSpan(2),

                Forms\Components\Select::make('variant_id')
                    ->label('Variant')
                    ->options(function (Get $get): Collection {
                        $productId = $get('product_id');
                        if (! $productId) {
                            return collect();
                        }

                        return ProductVariant::where('product_id', $productId)
                            ->with(['size', 'colors'])
                            ->get()
                            ->mapWithKeys(function ($variant) {
                                $label = collect([
                                    $variant->sku,
                                    $variant->size?->name,
                                    $variant->color_names,
                                ])->filter()->implode(' - ');

                                return [$variant->id => $label];
                            });
                    })
                    ->searchable()
                    ->preload()
                    ->columnSpan(2),

                Forms\Components\TextInput::make('ordered_qty')
                    ->label('Ordered Qty')
                    ->numeric()
                    ->nullable(),

                Forms\Components\TextInput::make('received_qty')
                    ->label('Received Qty')
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                        $unitCost = $get('unit_cost');
                        if ($state && $unitCost) {
                            $set('total_cost', $state * $unitCost);
                        }
                    })
                    ->required(),

                Forms\Components\TextInput::make('unit_cost')
                    ->label('Unit Cost')
                    ->numeric()
                    ->step(0.01)
                    ->prefix('$')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                        $receivedQty = $get('received_qty');
                        if ($state && $receivedQty) {
                            $set('total_cost', $receivedQty * $state);
                        }
                    })
                    ->required(),

                Forms\Components\TextInput::make('total_cost')
                    ->label('Total Cost')
                    ->numeric()
                    ->step(0.01)
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull()
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('variant.sku')
                    ->label('Variant SKU')
                    ->searchable(),

                Tables\Columns\TextColumn::make('variant.size.name')
                    ->label('Size')
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('variant.color_names')
                    ->label('Colors')
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('ordered_qty')
                    ->label('Ordered')
                    ->numeric()
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('received_qty')
                    ->label('Received')
                    ->numeric(),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->money('USD'),
            ])
            ->filters([
                //
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
