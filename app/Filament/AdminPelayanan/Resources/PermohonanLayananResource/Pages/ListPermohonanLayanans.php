<?php

namespace App\Filament\AdminPelayanan\Resources\PermohonanLayananResource\Pages;

use App\Enums\PermohonanStatus;
use App\Filament\AdminPelayanan\Resources\PermohonanLayananResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPermohonanLayanans extends ListRecords
{
    protected static string $resource = PermohonanLayananResource::class;

    public function getDefaultActiveTab(): string|int|null
    {
        return 'masuk';
    }

    public function getTabs(): array
    {
        return [
            'masuk' => Tab::make('Permohonan Masuk')
                ->modifyQueryUsing(function (Builder $query) {
                    $query
                        ->where('is_draft', false)
                        ->whereNotNull('submitted_at')
                        ->where('status', PermohonanStatus::DITERIMA_PLN)
                        ->orderByDesc('submitted_at');
                }),

            'selesai' => Tab::make('Permohonan Selesai (History)')
                ->modifyQueryUsing(function (Builder $query) {
                    $query
                        ->where('status', PermohonanStatus::SELESAI)
                        ->orderByDesc('completed_at')
                        ->orderByDesc('updated_at');
                }),
        ];
    }
}
