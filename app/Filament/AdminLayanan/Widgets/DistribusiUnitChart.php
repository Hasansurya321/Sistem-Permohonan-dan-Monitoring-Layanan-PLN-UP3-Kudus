<?php

namespace App\Filament\AdminLayanan\Widgets;

use Filament\Widgets\Widget;

class DistribusiUnitChart extends Widget
{
    protected static string $view = 'filament.admin-layanan.widgets.chart-distribusi-unit';
    protected int | string | array $columnSpan = 1;
}