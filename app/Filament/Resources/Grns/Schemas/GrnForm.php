<?php

namespace App\Filament\Resources\Grns\Schemas;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class GrnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('grn_number')
                    ->label('GRN Number')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Auto-generated'),

                Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        if ($state) {
                            $set('purchase_order_id', null);
                        }
                    })
                    ->required(),

                Select::make('purchase_order_id')
                    ->label('Purchase Order')
                    ->options(function (Get $get): Collection {
                        $supplierId = $get('supplier_id');
                        if (! $supplierId) {
                            return collect();
                        }

                        return PurchaseOrder::where('supplier_id', $supplierId)
                            ->whereIn('status', ['confirmed', 'partially_received'])
                            ->pluck('po_number', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Select::make('location_id')
                    ->label('Receiving Location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                DatePicker::make('received_date')
                    ->label('Received Date')
                    ->default(now())
                    ->required(),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'received' => 'Received',
                        'verified' => 'Verified',
                    ])
                    ->default('pending')
                    ->required(),

                Textarea::make('remarks')
                    ->label('Remarks')
                    ->columnSpanFull()
                    ->rows(3),

                Repeater::make('grnItems')
                    ->label('GRN Items')
                    ->relationship()
                    ->columnSpanFull()
                    ->schema([
                        Select::make('product_id')
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

                        Select::make('variant_id')
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

                        TextInput::make('ordered_qty')
                            ->label('Ordered Qty')
                            ->numeric()
                            ->nullable()
                            ->columnSpan(1),

                        TextInput::make('received_qty')
                            ->label('Received Qty')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                $unitCost = $get('unit_cost');
                                if ($state && $unitCost) {
                                    $set('total_cost', $state * $unitCost);
                                }
                            })
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('unit_cost')
                            ->label('Unit Cost')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                $receivedQty = $get('received_qty');
                                if ($state && $receivedQty) {
                                    $set('total_cost', $receivedQty * $state);
                                }
                            })
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('total_cost')
                            ->label('Total Cost')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),

                        Textarea::make('notes')
                            ->label('Item Notes')
                            ->columnSpanFull()
                            ->rows(2),
                    ])
                    ->columns(6)
                    ->defaultItems(1)
                    ->addActionLabel('Add Item')
                    ->extraItemActions([
                        Actions\Action::make('addSizes')
                            ->label('Add sizes')
                            ->icon(Heroicon::OutlinedSquaresPlus)
                            ->tooltip('Create a line for each size')
                            ->visible(function (array $arguments, Repeater $component): bool {
                                $itemKey = $arguments['item'] ?? null;
                                $items = $component->getRawState();

                                if (! is_string($itemKey) || ! isset($items[$itemKey])) {
                                    return false;
                                }

                                $productId = $items[$itemKey]['product_id'] ?? null;
                                $variantId = $items[$itemKey]['variant_id'] ?? null;

                                if (! $productId) {
                                    return false;
                                }

                                if ($variantId) {
                                    return false;
                                }

                                return ProductVariant::query()
                                    ->where('product_id', $productId)
                                    ->whereNotNull('size_id')
                                    ->count() > 1;
                            })
                            ->action(function (array $arguments, Repeater $component): void {
                                $itemKey = $arguments['item'] ?? null;
                                $items = $component->getRawState();

                                if (! is_string($itemKey) || ! isset($items[$itemKey])) {
                                    return;
                                }

                                $itemState = $items[$itemKey];
                                $productId = $itemState['product_id'] ?? null;

                                if (! $productId) {
                                    return;
                                }

                                $variants = ProductVariant::query()
                                    ->where('product_id', $productId)
                                    ->whereNotNull('size_id')
                                    ->with(['size', 'colors'])
                                    ->get()
                                    ->sortBy(function (ProductVariant $variant): string {
                                        $sizeOrder = $variant->size?->sort_order ?? 9999;
                                        $colorOrder = $variant->colors->min('sort_order') ?? 9999;

                                        return str_pad((string) $sizeOrder, 4, '0', STR_PAD_LEFT)
                                            .'-'.str_pad((string) $colorOrder, 4, '0', STR_PAD_LEFT)
                                            .'-'.($variant->sku ?? '');
                                    });

                                if ($variants->isEmpty()) {
                                    return;
                                }

                                $baseItem = [
                                    'product_id' => $productId,
                                    'ordered_qty' => null,
                                    'received_qty' => null,
                                    'unit_cost' => $itemState['unit_cost'] ?? null,
                                    'total_cost' => null,
                                    'notes' => $itemState['notes'] ?? null,
                                ];

                                $updatedItems = [];

                                foreach ($items as $key => $item) {
                                    if ($key !== $itemKey) {
                                        $updatedItems[$key] = $item;

                                        continue;
                                    }

                                    foreach ($variants as $variant) {
                                        $newItem = [
                                            ...$baseItem,
                                            'variant_id' => $variant->getKey(),
                                        ];

                                        $newKey = $component->generateUuid();

                                        if ($newKey) {
                                            $updatedItems[$newKey] = $newItem;
                                        } else {
                                            $updatedItems[] = $newItem;
                                        }
                                    }
                                }

                                $component->rawState($updatedItems);
                            }),
                    ])
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['product_id']
                            ? Product::find($state['product_id'])?->name
                            : 'New Item'
                    ),
            ]);
    }
}
