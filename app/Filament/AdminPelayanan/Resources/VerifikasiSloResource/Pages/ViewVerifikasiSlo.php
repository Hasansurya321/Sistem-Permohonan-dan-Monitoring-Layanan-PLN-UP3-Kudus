<?php

namespace App\Filament\AdminPelayanan\Resources\VerifikasiSloResource\Pages;

use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;
use App\Filament\AdminPelayanan\Resources\VerifikasiSloResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewVerifikasiSlo extends ViewRecord
{
    protected static string $resource = VerifikasiSloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sloValid')
                ->label('SLO di-upload & valid')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->transitionTo(
                        PermohonanStatus::VERIFIKASI_SLO,
                        PermohonanDetailStatus::SLO_VALID
                    );

                    $this->record->transitionTo(
                        PermohonanStatus::VERIFIKASI_SLO,
                        PermohonanDetailStatus::DITERUSKAN_KE_SURVEY
                    );

                    $this->redirect(VerifikasiSloResource::getUrl('index'));
                }),

            Actions\Action::make('dokumenInvalid')
                ->label('Dokumen tidak valid')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->transitionTo(
                        PermohonanStatus::VERIFIKASI_SLO,
                        PermohonanDetailStatus::DOKUMEN_TIDAK_VALID
                    );

                    $this->redirect(VerifikasiSloResource::getUrl('index'));
                }),
        ];
    }

    private static function toSafeString(mixed $state): string
    {
        if ($state === null) return '-';

        if ($state instanceof \BackedEnum) {
            return method_exists($state, 'getLabel')
                ? (string) $state->getLabel()
                : (string) $state->value;
        }

        if (is_array($state)) {
            return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_object($state)) {
            if (method_exists($state, '__toString')) return (string) $state;
            return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $state;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Data Permohonan')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_permohonan')
                            ->formatStateUsing(fn ($state) => self::toSafeString($state)),

                        Infolists\Components\TextEntry::make('jenis_layanan')
                            ->badge()
                            ->formatStateUsing(fn ($state) => self::toSafeString($state)),

                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => self::toSafeString($state)),

                        Infolists\Components\TextEntry::make('status_detail')
                            ->badge()
                            ->formatStateUsing(fn ($state) => self::toSafeString($state)),

                        Infolists\Components\TextEntry::make('submitted_at')
                            ->label('Tanggal Masuk')
                            ->formatStateUsing(fn ($state) => self::toSafeString($state))
                            ->dateTime(),
                    ])->columns(3),

                Infolists\Components\Section::make('Data Pemohon')
                    ->schema([
                        Infolists\Components\TextEntry::make('applicant.nama_lengkap')
                            ->label('Nama Lengkap')
                            ->formatStateUsing(fn ($state) => self::toSafeString($state)),

                        Infolists\Components\TextEntry::make('applicant.nik')
                            ->label('NIK')
                            ->formatStateUsing(fn ($state) => self::toSafeString($state)),

                        Infolists\Components\TextEntry::make('applicant.no_meter')
                            ->label('No Meter')
                            ->formatStateUsing(fn ($state) => self::toSafeString($state)),

                        Infolists\Components\TextEntry::make('applicant.id_pelanggan_12')
                            ->label('ID Pelanggan 12')
                            ->formatStateUsing(fn ($state) => self::toSafeString($state)),

                        Infolists\Components\TextEntry::make('applicant.default_alamat_detail')
                            ->label('Alamat Detail')
                            ->formatStateUsing(fn ($state) => self::toSafeString($state))
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('applicant.default_rt')
                            ->label('RT')
                            ->formatStateUsing(fn ($state) => self::toSafeString($state)),

                        Infolists\Components\TextEntry::make('applicant.default_rw')
                            ->label('RW')
                            ->formatStateUsing(fn ($state) => self::toSafeString($state)),

                        Infolists\Components\TextEntry::make('applicant.default_kelurahan')
                            ->label('Kelurahan')
                            ->formatStateUsing(fn ($state) => self::toSafeString($state)),

                        Infolists\Components\TextEntry::make('applicant.default_kecamatan')
                            ->label('Kecamatan')
                            ->formatStateUsing(fn ($state) => self::toSafeString($state)),

                        Infolists\Components\TextEntry::make('applicant.default_kab_kota')
                            ->label('Kab/Kota')
                            ->formatStateUsing(fn ($state) => self::toSafeString($state)),

                        Infolists\Components\TextEntry::make('applicant.default_provinsi')
                            ->label('Provinsi')
                            ->formatStateUsing(fn ($state) => self::toSafeString($state)),
                    ])->columns(2),

                Infolists\Components\Section::make('Detail Keperluan (Payload)')
                    ->schema([
                        Infolists\Components\TextEntry::make('payload_json')
                            ->label('Data Form (JSON)')
                            ->formatStateUsing(fn ($state) => self::toSafeString($state))
                            ->extraAttributes([
                                'class' => 'whitespace-pre-wrap font-mono text-xs',
                            ])
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Dokumen Upload Pelanggan')
                    ->schema([
                        Infolists\Components\ImageEntry::make('applicant.foto_bangunan')
                            ->label('Foto Bangunan')
                            ->disk('public'),

                        Infolists\Components\ImageEntry::make('applicant.foto_ktp_selfie')
                            ->label('Foto KTP/Selfie')
                            ->disk('public'),
                    ])->columns(2),
            ]);
    }
}
