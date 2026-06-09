<?php

namespace App\Filament\UnitKonstruksi\Resources\PermohonanKonstruksiResource\Pages;

use App\Filament\UnitKonstruksi\Resources\PermohonanKonstruksiResource;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;

class ViewPermohonanKonstruksi extends ViewRecord
{
    protected static string $resource = PermohonanKonstruksiResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Data Permohonan')->schema([
                TextEntry::make('nomor_permohonan')->label('No. Permohonan'),
                TextEntry::make('jenis_layanan')->label('Jenis Layanan')->badge(),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('status_detail')->label('Detail Status')->badge(),
                TextEntry::make('daya_baru')->label('Daya Baru'),
                TextEntry::make('submitted_at')->label('Tgl Masuk')->dateTime(),
            ])->columns(3),

            Section::make('Data Pemohon')->schema([
                TextEntry::make('applicant.nama_lengkap')->label('Nama Lengkap'),
                TextEntry::make('applicant.nik')->label('NIK'),
                TextEntry::make('applicant.no_hp')->label('No. HP'),
                TextEntry::make('applicant.no_meter')->label('No. Meter'),
                TextEntry::make('applicant.default_alamat_detail')->label('Alamat')->columnSpanFull(),
            ])->columns(2),

            Section::make('Lokasi Instalasi')->schema([
                TextEntry::make('lokasi_kelurahan')->label('Kelurahan'),
                TextEntry::make('lokasi_kecamatan')->label('Kecamatan'),
                TextEntry::make('lokasi_kab_kota')->label('Kab/Kota'),
                TextEntry::make('koordinat_lat')->label('Lat'),
                TextEntry::make('koordinat_lng')->label('Lng'),
            ])->columns(3),

            Section::make('Riwayat Status')->schema([
                TextEntry::make('events')
                    ->label('')
                    ->getStateUsing(fn ($record) => $record->events->map(fn ($e) =>
                        '[' . optional($e->occurred_at)->format('d/m H:i') . '] '
                        . ($e->status?->getLabel() ?? $e->status)
                        . ($e->status_detail ? ' — ' . ($e->status_detail?->getLabel() ?? $e->status_detail) : '')
                        . ($e->note ? ' (' . $e->note . ')' : '')
                    )->implode("\n"))
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
