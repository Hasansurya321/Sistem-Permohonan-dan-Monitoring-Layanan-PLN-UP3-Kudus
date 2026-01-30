<?php

namespace App\Filament\AdminPelayanan\Resources\PermohonanMasukResource\Pages;

use App\Filament\AdminPelayanan\Resources\PermohonanMasukResource;
use Filament\Resources\Pages\ListRecords;

class ListPermohonanMasuks extends ListRecords
{
    protected static string $resource = PermohonanMasukResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
