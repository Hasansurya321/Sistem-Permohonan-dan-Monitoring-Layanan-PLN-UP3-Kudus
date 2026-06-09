<?php

namespace App\Livewire\Admin;

use App\Enums\WorkflowMap;
use App\Models\ServiceRequest;
use Livewire\Component;

class MonitoringPermohonan extends Component
{
    /** @var string Unit yang dipilih, default 'SEMUA' */
    public $selectedUnit = 'SEMUA';

    /** @var string Status detail yang dipilih, default 'SEMUA' */
    public $selectedStatus = 'SEMUA';

    /**
     * Reset selectedStatus ketika unit berubah.
     */
    public function updatedSelectedUnit(): void
    {
        $this->selectedStatus = 'SEMUA';
    }

    /**
     * Ambil daftar opsi status untuk dropdown kedua.
     * Sumber data dari WorkflowMap — bukan hardcode.
     *
     * @return array
     */
    public function getStatusOptionsProperty(): array
    {
        if ($this->selectedUnit === 'SEMUA') {
            return [];
        }

        return WorkflowMap::getStatusesForUnit($this->selectedUnit);
    }

    /**
     * Label untuk display di dropdown.
     *
     * @param string $status
     * @return string
     */
    public function getStatusLabel(string $status): string
    {
        $labels = [
            // ADMIN_LAYANAN
            'MENUNGGU_VERIFIKASI_DATA'   => 'Menunggu Verifikasi Data',
            'VERIFIKASI_DATA_SUKSES'      => 'Verifikasi Data Sukses',
            'DIKEMBALIKAN_DENGAN_REVISI'  => 'Dikembalikan Dengan Revisi',
            'DITOLAK'                     => 'Ditolak',
            'ADMINISTRASI_SELESAI'        => 'Administrasi Selesai',
            // UNIT_SURVEY
            'DITERIMA_UNIT_SURVEY'        => 'Diterima Unit Survey',
            'SURVEY_DIJADWALKAN'          => 'Survey Dijadwalkan',
            'SURVEY_LAPANGAN'             => 'Survey Lapangan',
            'SURVEY_SUKSES'               => 'Survey Sukses',
            'SURVEY_GAGAL'                => 'Survey Gagal',
            'SURVEY_SELESAI'              => 'Survey Selesai',
            // UNIT_PERENCANAAN
            'DITERIMA_UNIT_PERENCANAAN'   => 'Diterima Unit Perencanaan',
            'ANALISA_KEBUTUHAN_MATERIAL'  => 'Analisa Kebutuhan Material',
            'CEK_KETERSEDIAAN_MATERIAL'   => 'Cek Ketersediaan Material',
            'MATERIAL_TERSEDIA'           => 'Material Tersedia',
            'MATERIAL_MENUNGGU'           => 'Material Menunggu',
            'PERENCANAAN_SELESAI'         => 'Perencanaan Selesai',
            // PEMBAYARAN
            'TAGIHAN_TERBIT'              => 'Tagihan Terbit',
            'MENUNGGU_PEMBAYARAN'         => 'Menunggu Pembayaran',
            'PEMBAYARAN_PENDING'          => 'Pending Pembayaran',
            'PEMBAYARAN_SUKSES'           => 'Pembayaran Sukses',
            'PEMBAYARAN_GAGAL'            => 'Pembayaran Gagal',
            'PEMBAYARAN_SELESAI'          => 'Pembayaran Selesai',
            // UNIT_KONSTRUKSI
            'DITERIMA_UNIT_KONSTRUKSI'    => 'Diterima Unit Konstruksi',
            'KONSTRUKSI_DIJADWALKAN'      => 'Konstruksi Dijadwalkan',
            'PEMBANGUNAN_JARINGAN'        => 'Pembangunan Jaringan',
            'KONSTRUKSI_BERHASIL'         => 'Konstruksi Berhasil',
            'KONSTRUKSI_GAGAL'            => 'Konstruksi Gagal',
            'KONSTRUKSI_INSTALASI_SELESAI' => 'Konstruksi Instalasi Selesai',
            // UNIT_PENYALAAN
            'DITERIMA_UNIT_PENYALAAN'     => 'Diterima Unit Penyalaan',
            'PENYALAAN_DIJADWALKAN'       => 'Penyalaan Dijadwalkan',
            'PENYALAAN_BERHASIL'          => 'Penyalaan Berhasil',
            'PENYALAAN_GAGAL'             => 'Penyalaan Gagal',
            'PENYALAAN_SELESAI'           => 'Penyalaan Selesai',
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Render komponen dengan query dari WorkflowMap.
     */
    public function render()
    {
        $query = ServiceRequest::query()
            ->where('is_draft', false)
            ->whereNotNull('submitted_at');

        if ($this->selectedUnit !== 'SEMUA') {
            $validStatuses = WorkflowMap::getStatusesForUnit($this->selectedUnit);
            if (!empty($validStatuses)) {
                $query->whereIn('status_detail', $validStatuses);
            }
        }

        if ($this->selectedStatus !== 'SEMUA') {
            $query->where('status_detail', $this->selectedStatus);
        }

        $permohonan = $query->orderByDesc('submitted_at')->get();

        return view('livewire.admin.monitoring-permohonan', [
            'permohonan' => $permohonan,
            'units' => array_merge(['SEMUA' => 'Semua Unit'], array_combine(
                WorkflowMap::getUnitKeys(),
                [
                    'Admin Layanan',
                    'Unit Survey',
                    'Unit Perencanaan',
                    'Pembayaran',
                    'Unit Konstruksi',
                    'Unit Penyalaan (TE)',
                ]
            )),
        ]);
    }
}