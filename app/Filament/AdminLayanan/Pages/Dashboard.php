<?php

namespace App\Filament\AdminLayanan\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\AdminLayanan\Widgets\KpiOverview;
use App\Filament\AdminLayanan\Widgets\AkunPelangganChart;
use App\Filament\AdminLayanan\Widgets\PermohonanLayananChart;
use App\Filament\AdminLayanan\Widgets\PembayaranChart;
use App\Filament\AdminLayanan\Widgets\DistribusiUnitChart;
use App\Filament\AdminLayanan\Widgets\StatistikSukses;
use App\Filament\AdminLayanan\Widgets\StatistikGagal;
use App\Filament\AdminLayanan\Widgets\StatistikTahun;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = '';

    protected function getHeaderWidgets(): array
    {
        return [
            KpiOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            AkunPelangganChart::class,
            PermohonanLayananChart::class,
            PembayaranChart::class,
            DistribusiUnitChart::class,
            StatistikSukses::class,
            StatistikGagal::class,
            StatistikTahun::class,
        ];
    }

    public function getWidgets(): array
    {
        return [];
    }
}