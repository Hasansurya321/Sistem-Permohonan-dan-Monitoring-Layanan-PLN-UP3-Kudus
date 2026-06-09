<?php

namespace App\Filament\AdminLayanan\Widgets;

use Filament\Widgets\Widget;

class AkunPelangganChart extends Widget
{
    protected static string $view = 'filament.admin-layanan.widgets.chart-akun-pelanggan';

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = null;

    public static function canView(): bool
    {
        return true;
    }

    public function getViewData(): array
    {
        return [];
    }
}
