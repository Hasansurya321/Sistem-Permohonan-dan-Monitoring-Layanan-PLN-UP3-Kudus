<?php

namespace App\Filament\AdminLayanan\Pages;

use App\Models\ServiceRequest;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class Pembayaran extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Pembayaran';
    protected static ?string $title = '';
    protected static string $view = 'filament.admin-layanan.pages.pembayaran';
    protected static ?string $slug = 'pembayaran';

    protected static ?int $navigationSort = 4;

    public string $activeTab = 'menunggu';

    #[On('tab-changed')]
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getFilteredQuery())
            ->columns([
                TextColumn::make('nomor_permohonan')
                    ->label('No. Permohonan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('applicant.nama_lengkap')
                    ->label('Nama Pemohon')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('applicant.nik')
                    ->label('NIK')
                    ->searchable(),
                TextColumn::make('daya_baru')
                    ->label('Daya')
                    ->formatStateUsing(fn ($state) => $state . ' VA')
                    ->sortable(),
                TextColumn::make('status_pembayaran_display')
                    ->label('Status Pembayaran')
                    ->getStateUsing(function (ServiceRequest $record): string {
                        $detail = $record->status_detail?->value ?? '';
                        $attempt = $record->payment_attempt_count ?? 0;

                        // Pending — tampilkan (X/3)
                        if ($record->status === PermohonanStatus::PEMBAYARAN
                            && in_array($detail, ['PEMBAYARAN_PENDING', 'PEMBAYARAN_GAGAL'])
                        ) {
                            $nextAttempt = $attempt + 1;
                            return 'Pending Pembayaran (' . $nextAttempt . '/3)';
                        }

                        // Sukses
                        if (in_array($detail, ['PEMBAYARAN_SUKSES', 'PEMBAYARAN_SELESAI'])) {
                            return 'Pembayaran Sukses';
                        }

                        // Gagal final (SELESAI + PEMBAYARAN_GAGAL)
                        if ($record->status === PermohonanStatus::SELESAI
                            && $detail === 'PEMBAYARAN_GAGAL'
                        ) {
                            return 'Pembayaran Gagal';
                        }

                        return $record->status_detail?->getLabel() ?? '-';
                    }),
                TextColumn::make('payment_attempt_count')
                    ->label('Percobaan')
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->label('Tanggal Diajukan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->actions([]); // READ ONLY — admin hanya monitoring
    }

    protected function getFilteredQuery(): Builder
    {
        $query = ServiceRequest::query()
            ->submitted()
            ->with(['applicant']);

        return match ($this->activeTab) {
            // 🔒 STRICT FILTER — Hanya MENUNGGU_PEMBAYARAN
            'menunggu' => $query
                ->where('status', PermohonanStatus::PEMBAYARAN)
                ->where('status_detail', PermohonanDetailStatus::MENUNGGU_PEMBAYARAN)
                ->whereNull('cancelled_at'),

            // 🔒 STRICT FILTER — Hanya PEMBAYARAN_PENDING
            'pending'  => $query
                ->where('status', PermohonanStatus::PEMBAYARAN)
                ->where('status_detail', PermohonanDetailStatus::PEMBAYARAN_PENDING)
                ->where('payment_attempt_count', '<', 3)
                ->whereNull('cancelled_at'),

            // 🔒 STRICT FILTER — Sukses (PEMBAYARAN status) + Gagal final (SELESAI status)
            'selesai'  => $query
                ->where(function (Builder $q) {
                    $q->where(function ($q2) {
                        $q2->where('status', PermohonanStatus::PEMBAYARAN)
                            ->whereIn('status_detail', [
                                PermohonanDetailStatus::PEMBAYARAN_SUKSES,
                                PermohonanDetailStatus::PEMBAYARAN_SELESAI,
                            ]);
                    })->orWhere(function ($q2) {
                        $q2->where('status', PermohonanStatus::SELESAI)
                            ->where('status_detail', PermohonanDetailStatus::PEMBAYARAN_GAGAL);
                    })->orWhere(function ($q2) {
                        // 🔴 FIX: Hasil auto-forward dari pembayaran sukses - UNIT_KONSTRUKSI
                        $q2->where('status', PermohonanStatus::UNIT_KONSTRUKSI)
                            ->whereIn('status_detail', [
                                PermohonanDetailStatus::DITERIMA_UNIT_KONSTRUKSI,
                                PermohonanDetailStatus::KONSTRUKSI_DIJADWALKAN,
                                PermohonanDetailStatus::PEMBANGUNAN_JARINGAN,
                                PermohonanDetailStatus::KONSTRUKSI_BERHASIL,
                                PermohonanDetailStatus::KONSTRUKSI_SELESAI,
                            ]);
                    })->orWhere(function ($q2) {
                        // 🔴 FIX: Hasil auto-forward dari pembayaran sukses - UNIT_PENYALAAN
                        $q2->where('status', PermohonanStatus::UNIT_PENYALAAN)
                            ->whereIn('status_detail', [
                                PermohonanDetailStatus::DITERIMA_UNIT_PENYALAAN,
                                PermohonanDetailStatus::PENYALAAN_DIJADWALKAN,
                                PermohonanDetailStatus::PENYALAAN_BERHASIL,
                                PermohonanDetailStatus::PENYALAAN_SELESAI,
                            ]);
                    })->orWhere(function ($q2) {
                        // ✅ Status akhir setelah pembayaran sukses: SELESAI + CLOSE
                        $q2->where('status', PermohonanStatus::SELESAI)
                            ->where('status_detail', PermohonanDetailStatus::CLOSE);
                    });
                }),

            default => $query
                ->where('status', PermohonanStatus::PEMBAYARAN)
                ->where('status_detail', PermohonanDetailStatus::MENUNGGU_PEMBAYARAN)
                ->whereNull('cancelled_at'),
        };
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel, $tenant);
    }

    protected function getViewData(): array
    {
        return [
            'activeTab' => $this->activeTab,
        ];
    }
}