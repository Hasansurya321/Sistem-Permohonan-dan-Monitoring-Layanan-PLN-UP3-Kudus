<?php

namespace App\Filament\AdminPelayanan\Resources\PermohonanLayananResource\Pages;

use App\Filament\AdminPelayanan\Resources\PermohonanLayananResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPermohonanLayanans extends ListRecords
{
    protected static string $resource = PermohonanLayananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
