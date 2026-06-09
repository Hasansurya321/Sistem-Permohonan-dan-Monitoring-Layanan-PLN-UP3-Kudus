<?php

namespace App\Filament\AdminLayanan\Widgets;

use Filament\Widgets\Widget;

class PembayaranChart extends Widget
{
    protected static string $view = 'filament.admin-layanan.widgets.chart-pembayaran';
    protected int | string | array $columnSpan = 1;
}