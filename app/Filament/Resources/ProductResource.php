<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\Tax;
use App\Services\IdentifierService;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid as SchemaGrid;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color as FilamentColor;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|UnitEnum|null $navigationGroup = 'Products';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Product')
                    ->tabs([
                        Tab::make('Details')
                            ->schema([
                                SchemaSection::make('Core information')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                                if (filled($get('slug'))) {
                                                    return;
                                                }

                                                if (blank($state)) {
                                                    return;
                                                }

                                                $set('slug', Str::slug($state));
                                            }),
                                        Forms\Components\TextInput::make('slug')
                                            ->maxLength(255)
                                            ->rule('nullable')
                                            ->unique(ignoreRecord: true)
                                            ->helperText('Leave blank to auto-generate on save.')
                                            ->dehydrateStateUsing(fn (?string $state): ?string => blank($state) ? null : Str::slug($state)),
                                        Forms\Components\TextInput::make('sku_prefix')
                                            ->label('SKU prefix')
                                            ->maxLength(12)
                                            ->helperText('Optional prefix used when generating variant SKUs.'),
                                    ])
                                    ->columns(3),
                                SchemaSection::make('Catalog placement')
                                    ->schema([
                                        Forms\Components\Select::make('brand_id')
                                            ->label('Brand')
                                            ->options(fn () => Brand::query()->orderBy('name')->pluck('name', 'id')->all())
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->native(false),
                                        Forms\Components\Select::make('category_id')
                                            ->label('Category')
                                            ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id')->all())
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->native(false),
                                        Forms\Components\Select::make('collection_id')
                                            ->label('Collection')
                                            ->options(fn () => Collection::query()->orderBy('name')->pluck('name', 'id')->all())
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->nullable(),
                                        Forms\Components\Select::make('default_tax_id')
                                            ->label('Default tax')
                                            ->options(fn () => Tax::query()->orderBy('name')->pluck('name', 'id')->all())
                                            ->native(false)
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),
                                        Forms\Components\Select::make('status')
                                            ->options([
                                                'draft' => 'Draft',
                                                'active' => 'Active',
                                                'discontinued' => 'Discontinued',
                                            ])
                                            ->required()
                                            ->native(false)
                                            ->default('draft'),
                                        Forms\Components\Toggle::make('inquiry_only')
                                            ->label('Inquiry only (hide prices, no purchase)')
                                            ->inline(false)
                                            ->helperText('When enabled, checkout is disabled. Prices stay hidden unless you enable the option below.'),
                                        Forms\Components\Toggle::make('show_price_inquiry_mode')
                                            ->label('Show price while inquiry only')
                                            ->inline(false)
                                            ->default(false)
                                            ->helperText('Display prices even though checkout is disabled.')
                                            ->disabled(fn (Get $get): bool => ! (bool) $get('inquiry_only')),
                                    ])
                                    ->columns(2),
                                SchemaSection::make('Descriptions')
                                    ->schema([
                                        Forms\Components\Textarea::make('description')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                        Forms\Components\Textarea::make('care_instructions')
                                            ->rows(3)
                                            ->label('Care instructions'),
                                        Forms\Components\TextInput::make('material_composition')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('season')
                                            ->maxLength(50)
                                            ->helperText('Optional season label (e.g. SS24).'),
                                        Forms\Components\TextInput::make('hs_code')
                                            ->maxLength(50)
                                            ->label('HS code'),
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make('Images')
                            ->schema([
                                Repeater::make('images')
                                    ->relationship()
                                    ->label('Product images')
                                    ->schema([
                                        Forms\Components\FileUpload::make('path')
                                            ->label('Image')
                                            ->image()
                                            ->disk('public')
                                            ->directory('product-images')
                                            ->required(),
                                        Forms\Components\Toggle::make('is_primary')
                                            ->label('Primary?')
                                            ->inline(false),
                                        Forms\Components\TextInput::make('sort_order')
                                            ->numeric()
                                            ->default(0)
                                            ->required(),
                                    ])
                                    ->orderable('sort_order')
                                    ->defaultItems(0)
                                    ->collapsed()
                                    ->createItemButtonLabel('Add image'),
                            ]),
                        Tab::make('Variants')
                            ->schema([
                                Repeater::make('variants')
                                    ->relationship()
                                    ->label('Variants')
                                    ->schema([
                                        Forms\Components\Select::make('size_id')
                                            ->label('Size')
                                            ->options(fn () => Size::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                                            ->searchable()
                                            ->native(false)
                                            ->nullable(),
                                        Forms\Components\Select::make('colors')
                                            ->label('Colors')
                                            ->relationship('colors', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->multiple(),
                                        Forms\Components\TextInput::make('sku')
                                            ->maxLength(50)
                                            ->helperText('Leave blank to auto-generate.')
                                            ->dehydrateStateUsing(fn (?string $state): ?string => blank($state) ? null : Str::upper($state)),
                                        Forms\Components\TextInput::make('barcode')
                                            ->maxLength(50)
                                            ->helperText('Leave blank to auto-generate.')
                                            ->suffixAction(
                                                Actions\Action::make('generateBarcode')
                                                    ->label('Generate')
                                                    ->color(FilamentColor::Amber)
                                                    ->icon(Heroicon::OutlinedQrCode)
                                                    ->action(function (Set $set): void {
                                                        $variant = ProductVariant::make();

                                                        $set('barcode', app(IdentifierService::class)->makeBarcode($variant));
                                                    })
                                                    ->disabled(fn (Get $get): bool => filled($get('barcode'))),
                                            )
                                            ->dehydrateStateUsing(fn (?string $state): ?string => blank($state) ? null : $state),
                                        Forms\Components\TextInput::make('cost_price')
                                            ->numeric()
                                            ->nullable()
                                            ->minValue(0)
                                            ->label('Cost price'),
                                        Forms\Components\TextInput::make('mrp')
                                            ->numeric()
                                            ->minValue(0)
                                            ->label('MRP')
                                            ->nullable(),
                                        Forms\Components\TextInput::make('selling_price')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->minValue(0)
                                            ->label('Selling price'),
                                        Forms\Components\TextInput::make('reorder_point')
                                            ->numeric()
                                            ->minValue(0)
                                            ->nullable(),
                                        Forms\Components\TextInput::make('reorder_qty')
                                            ->numeric()
                                            ->minValue(0)
                                            ->nullable(),
                                        Forms\Components\TextInput::make('weight_grams')
                                            ->numeric()
                                            ->minValue(0)
                                            ->nullable(),
                                        Forms\Components\Select::make('status')
                                            ->options([
                                                'active' => 'Active',
                                                'inactive' => 'Inactive',
                                            ])
                                            ->native(false)
                                            ->default('active')
                                            ->required(),
                                    ])
                                    ->columns(3)
                                    ->reorderable()
                                    ->defaultItems(1)
                                    ->collapsed()
                                    ->createItemButtonLabel('Add variant'),
                            ]),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaSection::make()
                    ->schema([
                        SchemaGrid::make([
                            'default' => 1,
                            'lg' => 12,
                        ])
                            ->schema([
                                ImageEntry::make('primaryImage.path')
                                    ->label('Primary image')
                                    ->disk('public')
                                    ->defaultImageUrl('https://placehold.co/600x400?text=No+Image')
                                    ->imageHeight('auto')
                                    ->imageWidth('100%')
                                    ->extraImgAttributes([
                                        'class' => 'w-full rounded-2xl bg-gray-100 object-contain dark:bg-gray-900',
                                        'style' => 'max-height: 400px;',
                                    ])
                                    ->columnSpan([
                                        'default' => 1,
                                        'lg' => 5,
                                    ]),
                                SchemaGrid::make([
                                    'default' => 1,
                                ])
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Product name')
                                            ->size(TextSize::Large)
                                            ->weight(FontWeight::SemiBold),
                                        TextEntry::make('brand.name')
                                            ->label('Brand')
                                            ->icon(Heroicon::OutlinedBuildingStorefront)
                                            ->hidden(fn (?string $state): bool => blank($state)),
                                        TextEntry::make('category.name')
                                            ->label('Category')
                                            ->icon(Heroicon::OutlinedRectangleStack)
                                            ->hidden(fn (?string $state): bool => blank($state)),
                                        TextEntry::make('collection.name')
                                            ->label('Collection')
                                            ->icon(Heroicon::OutlinedSparkles)
                                            ->hidden(fn (?string $state): bool => blank($state)),
                                        TextEntry::make('defaultTax.name')
                                            ->label('Default tax')
                                            ->icon(Heroicon::OutlinedBanknotes)
                                            ->hidden(fn (?string $state): bool => blank($state)),
                                        SchemaGrid::make([
                                            'default' => 1,
                                            'md' => 3,
                                        ])
                                            ->schema([
                                                TextEntry::make('status')
                                                    ->label('Status')
                                                    ->badge()
                                                    ->color(fn (?string $state): string => match ($state) {
                                                        'active' => 'success',
                                                        'inactive' => 'gray',
                                                        default => 'warning',
                                                    })
                                                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal),
                                                TextEntry::make('variants_count')
                                                    ->label('Variants')
                                                    ->formatStateUsing(fn (?int $state): string => number_format($state ?? 0))
                                                    ->icon(Heroicon::OutlinedSquares2x2),
                                                TextEntry::make('stock_levels_sum_on_hand')
                                                    ->label('On hand')
                                                    ->formatStateUsing(fn (?string $state): string => number_format((int) ($state ?? 0)))
                                                    ->icon(Heroicon::OutlinedArchiveBox),
                                            ])
                                            ->columnSpanFull(),
                                        TextEntry::make('description')
                                            ->label('Description')
                                            ->prose()
                                            ->hidden(fn (?string $state): bool => blank($state))
                                            ->columnSpanFull(),
                                        TextEntry::make('care_instructions')
                                            ->label('Care instructions')
                                            ->hidden(fn (?string $state): bool => blank($state))
                                            ->columnSpanFull(),
                                        TextEntry::make('material_composition')
                                            ->label('Material composition')
                                            ->hidden(fn (?string $state): bool => blank($state))
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpan([
                                        'default' => 1,
                                        'lg' => 7,
                                    ]),
                            ]),
                    ])
                    ->extraAttributes([
                        'class' => 'rounded-3xl border border-gray-200/70 bg-white p-6 shadow-sm dark:border-white/5 dark:bg-gray-950',
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                SchemaSection::make('Variants')
                    ->schema([
                        RepeatableEntry::make('variants')
                            ->label('Variants')
                            ->schema([
                                SchemaGrid::make([
                                    'default' => 1,
                                    'md' => 4,
                                ])
                                    ->schema([
                                        TextEntry::make('display_name')
                                            ->label('Variant')
                                            ->weight(FontWeight::Medium)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),
                                        TextEntry::make('sku')
                                            ->label('SKU')
                                            ->badge()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 1,
                                            ]),
                                        TextEntry::make('barcode')
                                            ->label('Barcode')
                                            ->badge()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 1,
                                            ]),
                                        TextEntry::make('size.name')
                                            ->label('Size')
                                            ->hidden(fn (?string $state): bool => blank($state)),
                                        TextEntry::make('color_names')
                                            ->label('Colors')
                                            ->hidden(fn (?string $state): bool => blank($state)),
                                        TextEntry::make('selling_price')
                                            ->label('Selling price')
                                            ->icon(Heroicon::OutlinedCurrencyDollar)
                                            ->formatStateUsing(fn (?string $state): string => filled($state) ? number_format((float) $state, 2) : '—'),
                                        TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->color(fn (?string $state): string => match ($state) {
                                                'active' => 'success',
                                                'inactive' => 'gray',
                                                default => 'warning',
                                            }),
                                    ]),
                            ])
                            ->grid([
                                'default' => 1,
                                'md' => 1,
                            ])
                            ->placeholder('No variants configured for this product.')
                            ->columnSpanFull(),
                    ])
                    ->extraAttributes([
                        'class' => 'rounded-3xl border border-gray-200/70 bg-white p-6 shadow-sm dark:border-white/5 dark:bg-gray-950',
                    ])
                    ->hidden(fn (Product $record): bool => $record->variants->isEmpty())
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['brand', 'category', 'primaryImage'])
                ->withCount('variants')
                ->withSum('stockLevels', 'on_hand')
            )
            ->columns([
                Tables\Columns\ImageColumn::make('primaryImage.path')
                    ->label('Image')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl('https://placehold.co/80x80?text=No+Image'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('variants_count')
                    ->label('Variants')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_levels_sum_on_hand')
                    ->label('On hand')
                    ->sortable()
                    ->numeric()
                    ->default('0'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->colors([
                        'primary' => 'draft',
                        'success' => 'active',
                        'danger' => 'discontinued',
                    ]),
                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->options(fn () => Brand::query()->orderBy('name')->pluck('name', 'id')->all()),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id')->all()),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'discontinued' => 'Discontinued',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Actions\Action::make('adjustStock')
                    ->label('Adjust stock')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->form(fn (Product $record) => [
                        Forms\Components\Select::make('variant_id')
                            ->label('Variant')
                            ->options(
                                $record->variants
                                    ->mapWithKeys(fn (ProductVariant $variant) => [$variant->getKey() => $variant->display_name ?? $variant->sku])
                                    ->all()
                            )
                            ->required()
                            ->searchable()
                            ->native(false),
                        Forms\Components\Select::make('location_id')
                            ->label('Location')
                            ->options(fn () => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->required()
                            ->searchable()
                            ->native(false),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->label('Quantity (use negative for deductions)'),
                        Forms\Components\Select::make('reason')
                            ->options(collect(ProductVariant::STOCK_REASONS)->reject(fn ($reason) => $reason === 'opening')->mapWithKeys(fn ($reason) => [$reason => Str::title(str_replace('_', ' ', $reason))])->all())
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('notes')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->action(function (Product $record, array $data): void {
                        /** @var ProductVariant $variant */
                        $variant = $record->variants()->findOrFail($data['variant_id']);
                        $variant->adjustStock(
                            locationId: (int) $data['location_id'],
                            quantity: (int) $data['quantity'],
                            reason: $data['reason'],
                            meta: [
                                'notes' => $data['notes'] ?? null,
                            ],
                        );
                    })
                    ->modalSubmitActionLabel('Adjust')
                    ->successNotificationTitle('Stock adjusted'),
                Actions\Action::make('openingStock')
                    ->label('Opening stock')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->form(fn (Product $record) => [
                        Forms\Components\Repeater::make('rows')
                            ->schema([
                                Forms\Components\Select::make('variant_id')
                                    ->label('Variant')
                                    ->options(
                                        $record->variants
                                            ->mapWithKeys(fn (ProductVariant $variant) => [$variant->getKey() => $variant->display_name ?? $variant->sku])
                                            ->all()
                                    )
                                    ->required()
                                    ->searchable()
                                    ->native(false),
                                Forms\Components\Select::make('location_id')
                                    ->label('Location')
                                    ->options(fn () => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                                    ->required()
                                    ->searchable()
                                    ->native(false),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->label('Opening quantity'),
                            ])
                            ->createItemButtonLabel('Add variant')
                            ->columns(3)
                            ->defaultItems(0),
                    ])
                    ->action(function (Product $record, array $data): void {
                        if (blank($data['rows'] ?? [])) {
                            return;
                        }

                        foreach ($data['rows'] as $row) {
                            if ((int) ($row['quantity'] ?? 0) <= 0) {
                                continue;
                            }

                            $variant = $record->variants()->findOrFail($row['variant_id']);

                            $variant->adjustStock(
                                locationId: (int) $row['location_id'],
                                quantity: (int) $row['quantity'],
                                reason: 'opening',
                                meta: [
                                    'notes' => 'Opening stock via admin panel',
                                ],
                            );
                        }
                    })
                    ->modalSubmitActionLabel('Apply opening stock')
                    ->successNotificationTitle('Opening stock recorded'),
                Actions\Action::make('printBarcodes')
                    ->label('Print barcodes')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->form(fn (Product $record) => [
                        Forms\Components\CheckboxList::make('variant_ids')
                            ->label('Variants')
                            ->options(
                                $record->variants
                                    ->mapWithKeys(fn (ProductVariant $variant) => [$variant->getKey() => $variant->display_name ?? $variant->sku])
                                    ->all()
                            )
                            ->required()
                            ->columns(2),
                    ])
                    ->action(function (Product $record, array $data) {
                        $ids = Arr::wrap($data['variant_ids'] ?? []);

                        if ($ids === []) {
                            return;
                        }

                        return redirect()->to(
                            Pages\PrintBarcodes::getUrl([
                                'record' => $record,
                                'variants' => implode(',', $ids),
                            ])
                        );
                    })
                    ->modalSubmitActionLabel('Open print view'),
                Actions\ViewAction::make()
                    ->label('View')
                    ->button()
                    ->color('gray')
                    ->icon(Heroicon::OutlinedEye),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\ForceDeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with([
                'brand',
                'category',
                'collection',
                'defaultTax',
                'primaryImage',
                'images',
                'variants.size',
                'variants.colors',
            ])
            ->withCount('variants')
            ->withSum('stockLevels', 'on_hand');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'print-barcodes' => Pages\PrintBarcodes::route('/{record}/barcodes'),
        ];
    }
}
