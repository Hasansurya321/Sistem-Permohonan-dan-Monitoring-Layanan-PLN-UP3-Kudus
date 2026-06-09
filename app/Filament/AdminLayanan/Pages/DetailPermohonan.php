<?php

namespace App\Filament\AdminLayanan\Pages;

use App\Models\ServiceRequest;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;
use Filament\Pages\Page;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DetailPermohonan extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title = 'Detail Permohonan';
    protected static string $view = 'filament.admin-layanan.pages.detail-permohonan';
    protected static ?string $slug = 'permohonan-layanan/detail';

    // Tidak muncul di sidebar
    protected static bool $shouldRegisterNavigation = false;

    public string $recordId = '';
    public string $jenisLayanan = '';
    public string $note = '';
    public ServiceRequest $requestRecord;

    public function mount(): void
    {
        // Baca parameter dari query string karena Filament Page tidak secara otomatis
        // me-map query string ke parameter mount()
        $this->recordId = request()->query('recordId', '');
        $jenisLayanan = request()->query('jenis_layanan', '');

        $this->requestRecord = ServiceRequest::with(['applicant', 'events' => function ($query) {
            $query->orderByDesc('occurred_at')->orderByDesc('id');
        }])->findOrFail($this->recordId);

        // Ambil jenis_layanan dari record jika parameter tidak sesuai atau kosong
        $this->jenisLayanan = $jenisLayanan ?: $this->requestRecord->jenis_layanan;
    }

    public static function getUrlForRecord(ServiceRequest $record, string $jenisLayanan): string
    {
        return static::getUrl([
            'recordId' => $record->id,
            'jenis_layanan' => $jenisLayanan,
        ]);
    }

    public function getDataPelanggan(): array
    {
        $data = [];
        $payload = $this->requestRecord->payload_json ?? [];
        $applicant = $this->requestRecord->applicant;

        // Data dari applicant identity
        $data['nama_lengkap'] = $applicant?->nama_lengkap ?? data_get($payload, 'data_diri.nama_lengkap');
        $data['nik'] = $applicant?->nik ?? data_get($payload, 'data_diri.nik');
        $data['no_hp'] = $applicant?->no_hp ?? data_get($payload, 'data_diri.no_hp');
        $data['email'] = $applicant?->email ?? data_get($payload, 'data_diri.email');

        return $data;
    }

    public function getDataKtp(): array
    {
        $payload = $this->requestRecord->payload_json ?? [];
        return [
            'nik' => data_get($payload, 'data_diri.nik', '-'),
            'nama' => data_get($payload, 'data_diri.nama_lengkap', '-'),
            'tempat_lahir' => data_get($payload, 'data_diri.tempat_lahir', '-'),
            'tanggal_lahir' => data_get($payload, 'data_diri.tanggal_lahir', '-'),
            'jenis_kelamin' => data_get($payload, 'data_diri.jenis_kelamin', '-'),
            'alamat_ktp' => data_get($payload, 'data_diri.alamat', '-'),
            'foto_ktp' => data_get($payload, 'data_diri.foto_ktp', '-'),
        ];
    }

    public function getDataKk(): array
    {
        $payload = $this->requestRecord->payload_json ?? [];
        return [
            'no_kk' => data_get($payload, 'data_keluarga.no_kk', '-'),
            'kepala_keluarga' => data_get($payload, 'data_keluarga.kepala_keluarga', '-'),
            'alamat_kk' => data_get($payload, 'data_keluarga.alamat_kk', '-'),
            'foto_kk' => data_get($payload, 'data_keluarga.foto_kk', '-'),
        ];
    }

    public function getDataSlo(): array
    {
        $payload = $this->requestRecord->payload_json ?? [];
        return [
            'nomor_slo' => data_get($payload, 'data_slo.nomor_slo', '-'),
            'daya_slo' => data_get($payload, 'data_slo.daya', '-'),
            'foto_slo' => data_get($payload, 'data_slo.foto_slo', '-'),
        ];
    }

    public function getDataAlamat(): array
    {
        $payload = $this->requestRecord->payload_json ?? [];
        $lokasi = data_get($payload, 'lokasi', []);
        return [
            'alamat_detail' => data_get($lokasi, 'alamat_detail', '-'),
            'rt' => data_get($lokasi, 'rt', '-'),
            'rw' => data_get($lokasi, 'rw', '-'),
            'kelurahan' => data_get($lokasi, 'kelurahan', '-'),
            'kecamatan' => data_get($lokasi, 'kecamatan', '-'),
            'kab_kota' => data_get($lokasi, 'kab_kota', '-'),
            'provinsi' => data_get($lokasi, 'provinsi', '-'),
            'koordinat' => data_get($lokasi, 'koordinat', '-'),
        ];
    }

    public function getDetailLayanan(): array
    {
        $payload = $this->requestRecord->payload_json ?? [];
        $dayaLamaTambah = data_get($payload, 'daya_lama', '-');
        $dayaBaruTambah = $this->requestRecord->daya_baru ?? data_get($payload, 'daya_baru', '-');

        $dataLayanan = [
            'jenis_layanan' => $this->jenisLayanan === 'TAMBAH_DAYA' ? 'Tambah Daya' : 'Pasang Baru',
            'id_meter' => data_get($payload, 'id_meter', '-'),
        ];

        if ($this->jenisLayanan === 'TAMBAH_DAYA') {
            $dataLayanan['daya_lama'] = $dayaLamaTambah;
            $dataLayanan['daya_baru'] = $dayaBaruTambah;
        }

        return $dataLayanan;
    }

    public function getLampiran(): array
    {
        $payload = $this->requestRecord->payload_json ?? [];
        $lampiran = data_get($payload, 'lampiran', []);
        if (!is_array($lampiran)) {
            $lampiran = [];
        }
        return $lampiran;
    }

    public function getRiwayatRevisi(): array
    {
        $events = $this->requestRecord->events;
        $riwayat = [];

        foreach ($events as $event) {
            if ($event->status_detail === PermohonanDetailStatus::DIKEMBALIKAN_DENGAN_REVISI->value ||
                $event->status_detail === PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA->value) {
                $riwayat[] = [
                    'status' => $event->status_detail,
                    'label' => PermohonanDetailStatus::tryFrom($event->status_detail)?->getLabel() ?? $event->status_detail,
                    'note' => $event->note ?? $event->description ?? '-',
                    'occurred_at' => $event->occurred_at ? \Carbon\Carbon::parse($event->occurred_at)->format('d/m/Y H:i') : '-',
                    'performed_by' => $event->title ?? '-',
                ];
            }
        }

        return $riwayat;
    }

    public function getStatusSekarang(): string
    {
        $detail = $this->requestRecord->status_detail;
        return method_exists($detail, 'getLabel') ? $detail->getLabel() : ($detail ?? '-');
    }

    public function getCatatanRevisiTerakhir(): ?string
    {
        return $this->requestRecord->last_revision_note;
    }

    public function getRevisiKeBerapa(): string
    {
        $count = $this->requestRecord->revision_count ?? 0;
        if ($count === 0) {
            return 'Baru';
        }
        return $count . '/2';
    }

    public function isStatusMenungguVerifikasi(): bool
    {
        return $this->requestRecord->status_detail === PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA;
    }

    public function isBatasRevisi(): bool
    {
        return ($this->requestRecord->revision_count ?? 0) >= 2;
    }

    public function verifikasiData(): void
    {
        try {
            $this->requestRecord->adminAccept();
            Notification::make()
                ->title('Permohonan berhasil diverifikasi. No. Resmi: ' . $this->requestRecord->nomor_permohonan)
                ->success()
                ->send();
            $this->redirect($this->getBackUrl());
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function kembalikanKePelanggan(string $note): void
    {
        try {
            $this->requestRecord->adminSendBack($note);
            Notification::make()
                ->title('Permohonan dikembalikan ke pelanggan untuk perbaikan. (Revisi ' . ($this->requestRecord->revision_count ?? 0) . '/2)')
                ->success()
                ->send();
            $this->redirect($this->getBackUrl());
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function tolakPermohonan(): void
    {
        try {
            $note = 'Ditolak setelah revisi ke-' . ($this->requestRecord->revision_count ?? 0) . '/2.';
            $this->requestRecord->adminReject($note);
            Notification::make()
                ->title($note)
                ->danger()
                ->send();
            $this->redirect($this->getBackUrl());
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getBackUrl(): string
    {
        if ($this->jenisLayanan === 'TAMBAH_DAYA') {
            return TambahDaya::getUrl();
        }
        return PasangBaru::getUrl();
    }

    protected function getViewData(): array
    {
        return [
            'record' => $this->requestRecord,
            'jenisLayanan' => $this->jenisLayanan,
            'dataPelanggan' => $this->getDataPelanggan(),
            'dataKtp' => $this->getDataKtp(),
            'dataKk' => $this->getDataKk(),
            'dataSlo' => $this->getDataSlo(),
            'dataAlamat' => $this->getDataAlamat(),
            'detailLayanan' => $this->getDetailLayanan(),
            'lampiran' => $this->getLampiran(),
            'riwayatRevisi' => $this->getRiwayatRevisi(),
            'statusSekarang' => $this->getStatusSekarang(),
            'catatanRevisiTerakhir' => $this->getCatatanRevisiTerakhir(),
            'revisiKeBerapa' => $this->getRevisiKeBerapa(),
            'isMenungguVerifikasi' => $this->isStatusMenungguVerifikasi(),
            'isBatasRevisi' => $this->isBatasRevisi(),
            'backUrl' => $this->getBackUrl(),
        ];
    }
}