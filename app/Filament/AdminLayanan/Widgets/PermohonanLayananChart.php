<?php

namespace App\Filament\AdminLayanan\Widgets;

use Filament\Widgets\Widget;

class PermohonanLayananChart extends Widget
{
    protected static string $view = 'filament.admin-layanan.widgets.chart-permohonan-layanan';
    protected int | string | array $columnSpan = 1;
}