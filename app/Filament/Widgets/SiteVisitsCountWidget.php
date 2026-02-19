<?php

namespace App\Filament\Widgets;

use App\Models\SiteVisit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteVisitsCountWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = ['lg' => 4, 'xl' => 4];

    protected function getStats(): array
    {
        $start = now()->subDays(29)->startOfDay();

        $visits = SiteVisit::query()
            ->where('visited_at', '>=', $start)
            ->count();

        return [
            Stat::make('Site visits', number_format($visits))
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-o-eye')
                ->color($visits > 0 ? 'success' : 'neutral'),
        ];
    }
}
