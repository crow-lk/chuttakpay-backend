<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class MonthlySalesChart extends ChartWidget
{
    protected ?string $heading = 'This Month Sales Trend';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(1, 6))->map(fn ($week) => 'Week '.$week)->toArray();

        // Dummy data for demonstration purposes.
        $values = [12, 18, 25, 22, 30, 28];

        return [
            'datasets' => [
                [
                    'label' => 'Units Sold',
                    'data' => $values,
                    'tension' => 0.4,
                    'backgroundColor' => 'rgba(251, 191, 36, 0.3)',
                    'borderColor' => '#f59e0b',
                    'fill' => true,
                ],
            ],
            'labels' => $days,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
