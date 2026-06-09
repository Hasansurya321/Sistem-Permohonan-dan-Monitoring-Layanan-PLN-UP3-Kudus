<?php

namespace App\Filament\AdminLayanan\Widgets;

use Filament\Widgets\Widget;

class StatistikGagal extends Widget
{
    protected static string $view = 'filament.admin-layanan.widgets.statistik-gagal';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 2;
}