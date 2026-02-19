<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\StockMovement;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardMetricsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $startOfMonth = now()->startOfMonth();

        $salesThisMonth = (float) StockMovement::query()
            ->where('reason', 'sale')
            ->where('created_at', '>=', $startOfMonth)
            ->selectRaw('COALESCE(SUM(ABS(quantity)), 0) as total')
            ->value('total');

        $expensesThisMonth = (float) StockMovement::query()
            ->join('product_variants', 'product_variants.id', '=', 'stock_movements.variant_id')
            ->where('stock_movements.reason', 'purchase')
            ->where('stock_movements.created_at', '>=', $startOfMonth)
            ->selectRaw('COALESCE(SUM(ABS(stock_movements.quantity) * product_variants.cost_price), 0) as total')
            ->value('total');

        $hotProduct = Product::query()
            ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->join('stock_movements', 'stock_movements.variant_id', '=', 'product_variants.id')
            ->where('stock_movements.reason', 'sale')
            ->where('stock_movements.created_at', '>=', $startOfMonth)
            ->select('products.name')
            ->selectRaw('COALESCE(SUM(ABS(stock_movements.quantity)), 0) as total_sold')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->first();

        $hotProductName = $hotProduct?->name ?? 'No sales yet';
        $hotProductUnits = (int) ($hotProduct->total_sold ?? 0);

        return [
            Stat::make('Hot Product', $hotProductName)
                ->description($hotProductUnits > 0 ? ($hotProductUnits.' units sold') : 'Awaiting first sale')
                ->descriptionIcon('heroicon-o-fire')
                ->color($hotProductUnits > 0 ? 'warning' : 'neutral'),
            Stat::make('This Month Sales', number_format($salesThisMonth).' units')
                ->description('Since '.$startOfMonth->format('M d'))
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color($salesThisMonth > 0 ? 'success' : 'neutral'),
            Stat::make('Monthly Expenses', 'Rs '.number_format($expensesThisMonth, 2))
                ->description('Purchase movements this month')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($expensesThisMonth > 0 ? 'danger' : 'neutral'),
        ];
    }
}
