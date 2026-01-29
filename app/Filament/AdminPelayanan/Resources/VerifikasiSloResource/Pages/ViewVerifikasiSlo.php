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

    private static function stringify(mixed $v): string
    {
        if ($v === null) return '-';

        if ($v instanceof \BackedEnum) {
            return method_exists($v, 'getLabel') ? (string) $v->getLabel() : (string) $v->value;
        }

        if ($v instanceof \Carbon\CarbonInterface) {
            return $v->toDateTimeString();
        }

        if ($v instanceof \Illuminate\Support\Collection) {
            $v = $v->all();
        }

        if (is_array($v)) {
            return json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_object($v)) {
            return method_exists($v, '__toString')
                ? (string) $v
                : json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $v;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Data Permohonan')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_permohonan')
                            ->getStateUsing(fn($record) => self::stringify($record->nomor_permohonan)),

                        Infolists\Components\TextEntry::make('jenis_layanan')
                            ->badge()
                            ->getStateUsing(fn($record) => self::stringify($record->jenis_layanan)),

                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->getStateUsing(fn($record) => self::stringify($record->status)),

                        Infolists\Components\TextEntry::make('status_detail')
                            ->badge()
                            ->getStateUsing(fn($record) => self::stringify($record->status_detail)),

                        Infolists\Components\TextEntry::make('submitted_at')
                            ->label('Tanggal Masuk')
                            ->dateTime(),
                    ])->columns(3),

                Infolists\Components\Section::make('Data Pemohon')
                    ->schema([
                        Infolists\Components\TextEntry::make('applicant.nama_lengkap')
                            ->label('Nama Lengkap')
                            ->getStateUsing(fn($record) => self::stringify(data_get($record, 'applicant.nama_lengkap'))),

                        Infolists\Components\TextEntry::make('applicant.nik')
                            ->label('NIK')
                            ->getStateUsing(fn($record) => self::stringify(data_get($record, 'applicant.nik'))),

                        Infolists\Components\TextEntry::make('applicant.no_meter')
                            ->label('No Meter')
                            ->getStateUsing(fn($record) => self::stringify(data_get($record, 'applicant.no_meter'))),

                        Infolists\Components\TextEntry::make('applicant.id_pelanggan_12')
                            ->label('ID Pelanggan 12')
                            ->getStateUsing(fn($record) => self::stringify(data_get($record, 'applicant.id_pelanggan_12'))),

                        Infolists\Components\TextEntry::make('applicant.default_alamat_detail')
                            ->label('Alamat Detail')
                            ->getStateUsing(fn($record) => self::stringify(data_get($record, 'applicant.default_alamat_detail')))
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('applicant.default_rt')
                            ->label('RT')
                            ->getStateUsing(fn($record) => self::stringify(data_get($record, 'applicant.default_rt'))),

                        Infolists\Components\TextEntry::make('applicant.default_rw')
                            ->label('RW')
                            ->getStateUsing(fn($record) => self::stringify(data_get($record, 'applicant.default_rw'))),

                        Infolists\Components\TextEntry::make('applicant.default_kelurahan')
                            ->label('Kelurahan')
                            ->getStateUsing(fn($record) => self::stringify(data_get($record, 'applicant.default_kelurahan'))),

                        Infolists\Components\TextEntry::make('applicant.default_kecamatan')
                            ->label('Kecamatan')
                            ->getStateUsing(fn($record) => self::stringify(data_get($record, 'applicant.default_kecamatan'))),

                        Infolists\Components\TextEntry::make('applicant.default_kab_kota')
                            ->label('Kab/Kota')
                            ->getStateUsing(fn($record) => self::stringify(data_get($record, 'applicant.default_kab_kota'))),

                        Infolists\Components\TextEntry::make('applicant.default_provinsi')
                            ->label('Provinsi')
                            ->getStateUsing(fn($record) => self::stringify(data_get($record, 'applicant.default_provinsi'))),
                    ])->columns(2),

                Infolists\Components\Section::make('Detail Keperluan (Payload)')
                    ->schema([
                        Infolists\Components\TextEntry::make('payload_json')
                            ->label('Data Form (JSON)')
                            ->getStateUsing(fn($record) => self::stringify($record->payload_json))
                            ->extraAttributes([
                                'class' => 'whitespace-pre-wrap font-mono text-xs',
                            ])
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Dokumen Upload Pelanggan')
                    ->schema([
                        Infolists\Components\ImageEntry::make('applicant.foto_bangunan')
                            ->label('Foto Bangunan')
                            ->disk('public')
                            ->getStateUsing(fn ($record) => is_array(data_get($record, 'applicant.foto_bangunan'))
                                ? (data_get($record, 'applicant.foto_bangunan.0') ?? null)
                                : data_get($record, 'applicant.foto_bangunan')),

                        Infolists\Components\ImageEntry::make('applicant.foto_ktp_selfie')
                            ->label('Foto KTP/Selfie')
                            ->disk('public')
                            ->getStateUsing(fn ($record) => is_array(data_get($record, 'applicant.foto_ktp_selfie'))
                                ? (data_get($record, 'applicant.foto_ktp_selfie.0') ?? null)
                                : data_get($record, 'applicant.foto_ktp_selfie')),
                    ])->columns(2),
            ]);
    }
}
