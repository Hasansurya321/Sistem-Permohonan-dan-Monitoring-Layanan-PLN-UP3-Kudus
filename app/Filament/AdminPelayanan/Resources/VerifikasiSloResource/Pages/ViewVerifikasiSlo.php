<?php

namespace App\Filament\AdminPelayanan\Resources\VerifikasiSloResource\Pages;

use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;
use App\Filament\AdminPelayanan\Resources\VerifikasiSloResource;
use App\Filament\AdminPelayanan\Resources\DistribusiUnitResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewVerifikasiSlo extends ViewRecord
{
    protected static string $resource = VerifikasiSloResource::class;

    // Remove getHeaderActions as requested
    /*
    protected function getHeaderActions(): array
    {
        return [ ... ];
    }
    */

    public function verifikasiSukses(): void
    {
        $record = $this->record;

        // Idempotency: Check if already verified OR number already official format
        // Checks if status is SLO_VALID OR if nomor_permohonan starts with PLN-UP3KUDUS
        $isOfficialFormat = preg_match('/^PLN-UP3KUDUS-\d+$/', (string) $record->nomor_permohonan);
        
        if ($record->status_detail === PermohonanDetailStatus::SLO_VALID || $isOfficialFormat) {
            \Filament\Notifications\Notification::make()
                ->title('Informasi')
                ->body('Data ini sudah pernah diverifikasi sukses.')
                ->info()
                ->send();
            
            // Redirect using route name to avoid 404
            $target = route('filament.admin-pelayanan.resources.distribusi-units.index');
            $this->redirect($target);
            return;
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                $draftNo = (string) $record->nomor_permohonan;
                
                // Robust digit extraction
                preg_match_all('/\d+/', $draftNo, $matches);
                $digits = !empty($matches[0]) ? end($matches[0]) : null;

                if (!$digits) {
                    throw new \Exception('Nomor draft tidak valid (tidak ada angka), tidak bisa generate nomor resmi.');
                }

                $officialNo = 'PLN-UP3KUDUS-' . $digits;

                // Uniqueness check
                $exists = \App\Models\ServiceRequest::where('nomor_permohonan', $officialNo)
                    ->where('id', '!=', $record->id)
                    ->exists();

                if ($exists) {
                    throw new \Exception('Nomor permohonan bentrok/sudah ada: ' . $officialNo);
                }

                // Update record
                $record->update([
                    'nomor_permohonan' => $officialNo,
                    'is_draft' => false,
                    // Directly setting status_detail as requested
                    'status_detail' => PermohonanDetailStatus::SLO_VALID,
                ]);

                // Maintain main status verification
                // $record->transitionTo(...) is optional if we just update detail, 
                // but usually better to keep history. Since requirement is specific on updates:
                // We'll stick to the Transaction block update above which covers the requirement.
            });

            \Filament\Notifications\Notification::make()
                ->title('Verifikasi Sukses')
                ->body('Nomor permohonan resmi: ' . $this->record->nomor_permohonan)
                ->success()
                ->send();

            // Redirect using route name to avoid 404
            $target = route('filament.admin-pelayanan.resources.distribusi-units.index');
            $this->redirect($target);

        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error Verifikasi')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function verifikasiGagal(): void
    {
        // Fitur belum aktif, do nothing
    }

    private static function stringify(mixed $v): string
    {
        if ($v === null || $v === '') return '-';

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
                            ->getStateUsing(fn ($record) => self::stringify($record->nomor_permohonan)),

                        Infolists\Components\TextEntry::make('jenis_layanan')
                            ->badge()
                            ->getStateUsing(fn ($record) => self::stringify($record->jenis_layanan)),

                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->getStateUsing(fn ($record) => self::stringify($record->status)),

                        Infolists\Components\TextEntry::make('status_detail')
                            ->badge()
                            ->getStateUsing(fn ($record) => self::stringify($record->status_detail)),

                        Infolists\Components\TextEntry::make('submitted_at')
                            ->label('Tanggal Masuk')
                            ->dateTime(),
                    ])->columns(3),

                Infolists\Components\Section::make('Data Pemohon')
                    ->schema([
                        Infolists\Components\TextEntry::make('applicant.nama_lengkap')
                            ->label('Nama Lengkap')
                            ->getStateUsing(fn ($record) => self::stringify(data_get($record, 'applicant.nama_lengkap'))),

                        Infolists\Components\TextEntry::make('applicant.nik')
                            ->label('NIK')
                            ->getStateUsing(fn ($record) => self::stringify(data_get($record, 'applicant.nik'))),

                        Infolists\Components\TextEntry::make('applicant.no_hp')
                            ->label('No HP')
                            ->getStateUsing(fn ($record) => self::stringify(data_get($record, 'applicant.no_hp'))),

                        Infolists\Components\TextEntry::make('applicant.no_meter')
                            ->label('No Meter')
                            ->getStateUsing(fn ($record) => self::stringify(data_get($record, 'applicant.no_meter'))),

                        Infolists\Components\TextEntry::make('applicant.id_pelanggan_12')
                            ->label('ID Pelanggan 12')
                            ->getStateUsing(fn ($record) => self::stringify(data_get($record, 'applicant.id_pelanggan_12'))),

                        Infolists\Components\TextEntry::make('applicant.no_kk')
                            ->label('No KK')
                            ->getStateUsing(fn ($record) => self::stringify(data_get($record, 'applicant.no_kk'))),

                        Infolists\Components\TextEntry::make('applicant.npwp')
                            ->label('NPWP')
                            ->getStateUsing(fn ($record) => self::stringify(data_get($record, 'applicant.npwp'))),

                        Infolists\Components\TextEntry::make('applicant.default_alamat_detail')
                            ->label('Alamat Detail')
                            ->getStateUsing(fn ($record) => self::stringify(data_get($record, 'applicant.default_alamat_detail')))
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('applicant.default_rt')
                            ->label('RT')
                            ->getStateUsing(fn ($record) => self::stringify(data_get($record, 'applicant.default_rt'))),

                        Infolists\Components\TextEntry::make('applicant.default_rw')
                            ->label('RW')
                            ->getStateUsing(fn ($record) => self::stringify(data_get($record, 'applicant.default_rw'))),

                        Infolists\Components\TextEntry::make('applicant.default_kelurahan')
                            ->label('Kelurahan')
                            ->getStateUsing(fn ($record) => self::stringify(data_get($record, 'applicant.default_kelurahan'))),

                        Infolists\Components\TextEntry::make('applicant.default_kecamatan')
                            ->label('Kecamatan')
                            ->getStateUsing(fn ($record) => self::stringify(data_get($record, 'applicant.default_kecamatan'))),

                        Infolists\Components\TextEntry::make('applicant.default_kab_kota')
                            ->label('Kab/Kota')
                            ->getStateUsing(fn ($record) => self::stringify(data_get($record, 'applicant.default_kab_kota'))),

                        Infolists\Components\TextEntry::make('applicant.default_provinsi')
                            ->label('Provinsi')
                            ->getStateUsing(fn ($record) => self::stringify(data_get($record, 'applicant.default_provinsi'))),
                    ])->columns(2),

                // INI YANG LO MAU TETEP ADA
                Infolists\Components\Section::make('Detail Keperluan (Ringkas)')
                    ->schema([
                        Infolists\Components\TextEntry::make('daya_baru')
                            ->label('Daya Baru')
                            ->getStateUsing(fn ($record) => self::stringify($record->daya_baru)),

                        Infolists\Components\TextEntry::make('jenis_produk')
                            ->label('Jenis Produk')
                            ->getStateUsing(fn ($record) => self::stringify($record->jenis_produk)),

                        Infolists\Components\TextEntry::make('peruntukan_koneksi')
                            ->label('Peruntukan')
                            ->getStateUsing(fn ($record) => self::stringify($record->peruntukan_koneksi)),

                        Infolists\Components\TextEntry::make('slo_no_registrasi')
                            ->label('No Registrasi SLO')
                            ->getStateUsing(fn ($record) => self::stringify($record->slo_no_registrasi)),

                        Infolists\Components\TextEntry::make('slo_no_sertifikat')
                            ->label('No Sertifikat SLO')
                            ->getStateUsing(fn ($record) => self::stringify($record->slo_no_sertifikat)),
                    ])->columns(2),

                // SECTION “Payload - JSON” DIBUANG (INI SESUAI YANG LO MINTA)
                // Infolists\Components\Section::make('Detail Keperluan (Payload - JSON)') ... (HAPUS)

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

                        Infolists\Components\ViewEntry::make('actions')
                            ->view('filament.admin-pelayanan.verifikasi-slo._actions')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
