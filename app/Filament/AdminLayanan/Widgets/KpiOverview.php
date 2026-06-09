<?php

namespace App\Filament\AdminLayanan\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KpiOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Akun Pelanggan', '12.300')
                ->icon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Total Permohonan Layanan', '12.300')
                ->icon('heroicon-o-document-text')
                ->color('primary'),
        ];
    }

    protected function getColumns(): int
    {
        return 2;
    }

    protected ?string $heading = '';
}