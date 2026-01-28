<?php

namespace App\Filament\AdminPelayanan\Resources\PermohonanLayananResource\Pages;

use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;
use App\Filament\AdminPelayanan\Resources\PermohonanLayananResource;
use App\Filament\AdminPelayanan\Resources\VerifikasiSloResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;

class ViewPermohonanLayanan extends ViewRecord
{
    protected static string $resource = PermohonanLayananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('verifikasiSekarang')
                ->label('Verifikasi sekarang')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === PermohonanStatus::DITERIMA_PLN && $this->record->submitted_at !== null)
                ->action(function () {
                    $this->record->transitionTo(
                        PermohonanStatus::VERIFIKASI_SLO,
                        PermohonanDetailStatus::MENUNGGU_VERIFIKASI
                    );

                    return redirect(VerifikasiSloResource::getUrl('edit', ['record' => $this->record]));
                }),

            Actions\Action::make('kembali')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(PermohonanLayananResource::getUrl('index')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // SECTION 1: PEMOHON
                Infolists\Components\Section::make('Data Pemohon')
                    ->schema([
                        Infolists\Components\TextEntry::make('applicant.nama_lengkap')->label('Nama Lengkap'),
                        Infolists\Components\TextEntry::make('applicant.nik')->label('NIK'),
                        Infolists\Components\TextEntry::make('applicant.no_kk')->label('No. KK'),
                        Infolists\Components\TextEntry::make('applicant.no_hp')->label('No. Handphone'),
                        Infolists\Components\TextEntry::make('applicant.npwp')->label('NPWP'),
                        Infolists\Components\TextEntry::make('applicant.id_pelanggan_12')->label('ID Pelanggan (Target)'),
                        Infolists\Components\TextEntry::make('applicant.no_meter')->label('No. Meter (Target)'),
                    ])->columns(3),

                // SECTION 2: LAYANAN TAMBAH DAYA
                Infolists\Components\Section::make('Layanan Tambah Daya')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_permohonan')->label('Nomor Permohonan')->weight('bold'),
                        Infolists\Components\TextEntry::make('jenis_layanan')->label('Jenis Layanan')->badge(),
                        Infolists\Components\TextEntry::make('daya_baru')->label('Daya Baru (VA)'),
                        Infolists\Components\TextEntry::make('jenis_produk')->label('Jenis Produk'),
                        Infolists\Components\TextEntry::make('peruntukan_koneksi')->label('Peruntukan'),
                        
                        // Lokasi Group
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('lokasi_provinsi')->label('Provinsi'),
                            Infolists\Components\TextEntry::make('lokasi_kab_kota')->label('Kab/Kota'),
                            Infolists\Components\TextEntry::make('lokasi_kecamatan')->label('Kecamatan'),
                            Infolists\Components\TextEntry::make('lokasi_kelurahan')->label('Kelurahan'),
                            Infolists\Components\TextEntry::make('lokasi_rt')->label('RT'),
                            Infolists\Components\TextEntry::make('lokasi_rw')->label('RW'),
                        ])->columns(3)->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('lokasi_detail_tambahan')->label('Alamat Detail')->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('koordinat_lat')->label('Latitude'),
                        Infolists\Components\TextEntry::make('koordinat_lng')->label('Longitude'),
                    ])->columns(3),

                // SECTION 3: DOKUMEN & SLO
                Infolists\Components\Section::make('Dokumen & SLO')
                    ->schema([
                        Infolists\Components\TextEntry::make('slo_no_registrasi')->label('No. Registrasi SLO'),
                        Infolists\Components\TextEntry::make('slo_no_sertifikat')->label('No. Sertifikat SLO'),
                        Infolists\Components\TextEntry::make('slo_verification_status')->label('Status Verifikasi SLO')->badge(),
                        Infolists\Components\TextEntry::make('slo_verified_at')->label('Tanggal Verifikasi SLO')->dateTime(),
                        
                        // Foto Bangunan
                        Infolists\Components\TextEntry::make('applicant.foto_bangunan')
                            ->label('Foto Bangunan')
                            ->formatStateUsing(fn ($state) => $state ? 'Lihat Foto' : 'Belum Ada')
                            ->url(fn ($state) => $state ? Storage::url($state) : null, true)
                            ->icon('heroicon-o-photo')
                            ->color('primary'),

                        // Foto KTP Selfie
                        Infolists\Components\TextEntry::make('applicant.foto_ktp_selfie')
                            ->label('Foto Diri dengan KTP')
                            ->formatStateUsing(fn ($state) => $state ? 'Lihat Foto' : 'Belum Ada')
                            ->url(fn ($state) => $state ? Storage::url($state) : null, true)
                            ->icon('heroicon-o-photo')
                            ->color('primary'),
                    ])->columns(2),

                // SECTION 4: STATUS & LOG
                Infolists\Components\Section::make('Status & Riwayat')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')->badge()->label('Status Utama'),
                        Infolists\Components\TextEntry::make('status_detail')->badge()->label('Detail Status'),
                        Infolists\Components\TextEntry::make('submitted_at')->label('Tanggal Submit')->dateTime(),
                        Infolists\Components\TextEntry::make('status_changed_at')->label('Terakhir Diupdate')->dateTime(),
                        Infolists\Components\TextEntry::make('status_changed_by')->label('Diupdate Oleh'), // Adjust relationship if needed
                        
                        Infolists\Components\TextEntry::make('payload_json')
                            ->label('Snapshot Data Wizard')
                            ->columnSpanFull()
                            ->formatStateUsing(fn () => 'Lihat Raw Data')
                            // Ideally show as expandable JSON but for now simplified
                    ])->columns(3),
            ]);
    }
}
