<?php

namespace App\Filament\AdminLayanan\Widgets;

use Filament\Widgets\Widget;

class StatistikTahun extends Widget
{
    protected static string $view = 'filament.admin-layanan.widgets.statistik-tahun';

    protected int | string | array $columnSpan = 2;

    protected static ?int $sort = 3;
}