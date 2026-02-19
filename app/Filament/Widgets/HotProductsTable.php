<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class HotProductsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        $startOfMonth = now()->startOfMonth();

        return Product::query()
            ->with('brand')
            ->select('products.id', 'products.name', 'products.brand_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN stock_movements.reason = ? THEN ABS(stock_movements.quantity) ELSE 0 END), 0) AS total_sold', ['sale'])
            ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->join('stock_movements', 'stock_movements.variant_id', '=', 'product_variants.id')
            ->where('stock_movements.created_at', '>=', $startOfMonth)
            ->groupBy('products.id', 'products.name', 'products.brand_id')
            ->orderByDesc('total_sold')
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Product')
                ->wrap(),
            Tables\Columns\TextColumn::make('brand.name')
                ->label('Brand'),
            Tables\Columns\TextColumn::make('total_sold')
                ->label('Units Sold')
                ->numeric()
                ->sortable(),
        ];
    }
}
