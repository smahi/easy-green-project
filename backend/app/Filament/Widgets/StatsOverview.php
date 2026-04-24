<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make(__('Total Reports'), Report::count())
                ->description(__('All reports submitted'))
                ->icon('heroicon-o-document-text'),
            Stat::make(__('Resolved Reports'), Report::where('status', 'resolved')->count())
                ->description(__('Successfully addressed'))
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            Stat::make(__('Total Users'), User::count())
                ->description(__('Registered citizens and staff'))
                ->icon('heroicon-o-users'),
        ];
    }
}
