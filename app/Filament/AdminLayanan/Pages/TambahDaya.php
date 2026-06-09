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
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class TambahDaya extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Tambah Daya';
    protected static ?string $title = '';
    protected static string $view = 'filament.admin-layanan.pages.tambah-daya';
    protected static ?string $slug = 'permohonan-layanan/tambah-daya';

    // Tidak muncul di sidebar, hanya diakses via link dari Permohonan Layanan
    protected static bool $shouldRegisterNavigation = false;

    public string $activeTab = 'menunggu';

    #[On('tab-changed')]
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        $isPendingTab = $this->activeTab === 'pending';
        $isSelesaiTab = $this->activeTab === 'selesai';

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

                // Kolom khusus tab Pending
                TextColumn::make('revision_count')
                    ->label('Revisi Ke')
                    ->formatStateUsing(fn ($state) => ($state ?? 0) . '/2')
                    ->sortable()
                    ->visible(fn () => $isPendingTab),

                TextColumn::make('last_revision_note')
                    ->label('Catatan Perbaikan')
                    ->limit(60)
                    ->visible(fn () => $isPendingTab),

                TextColumn::make('last_revision_at')
                    ->label('Tgl. Revisi')
                    ->dateTime('d/m/Y H:i')
                    ->visible(fn () => $isPendingTab),

                // Kolom tab Menunggu & Selesai
                TextColumn::make('applicant.nik')
                    ->label('NIK')
                    ->searchable()
                    ->visible(fn () => !$isPendingTab),

                TextColumn::make('daya_baru')
                    ->label('Daya Baru')
                    ->formatStateUsing(fn ($state) => $state . ' VA')
                    ->sortable()
                    ->visible(fn () => !$isPendingTab),

                TextColumn::make('status_detail')
                    ->label('Hasil')
                    ->formatStateUsing(function ($state, $record) {
                        // Di Filter Selesai: bedakan sukses vs gagal
                        if ($record->status === PermohonanStatus::SELESAI && $record->status_detail === PermohonanDetailStatus::ADMINISTRASI_SELESAI) {
                            return 'Ditolak PLN';
                        }
                        if ($record->status === PermohonanStatus::VERIFIKASI_DATA && $record->status_detail === PermohonanDetailStatus::ADMINISTRASI_SELESAI) {
                            return 'Diterima PLN';
                        }
                        // Auto-advance: status PEMBAYARAN + TAGIHAN_TERBIT = sukses
                        if ($record->status === PermohonanStatus::PEMBAYARAN && $record->status_detail === PermohonanDetailStatus::TAGIHAN_TERBIT) {
                            return 'Diterima PLN';
                        }
                        return method_exists($state, 'getLabel') ? $state->getLabel() : ($state ?? '-');
                    })
                    ->badge()
                    ->color(function ($state, $record) {
                        if ($record->status === PermohonanStatus::SELESAI && $record->status_detail === PermohonanDetailStatus::ADMINISTRASI_SELESAI) {
                            return 'danger';
                        }
                        if ($record->status === PermohonanStatus::VERIFIKASI_DATA && $record->status_detail === PermohonanDetailStatus::ADMINISTRASI_SELESAI) {
                            return 'success';
                        }
                        // Auto-advance: status PEMBAYARAN + TAGIHAN_TERBIT = sukses
                        if ($record->status === PermohonanStatus::PEMBAYARAN && $record->status_detail === PermohonanDetailStatus::TAGIHAN_TERBIT) {
                            return 'success';
                        }
                        if ($record->status_detail === PermohonanDetailStatus::DIKEMBALIKAN_DENGAN_REVISI) {
                            return 'warning';
                        }
                        if ($record->status_detail === PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA) {
                            return 'info';
                        }
                        return 'gray';
                    }),

                TextColumn::make('submitted_at')
                    ->label('Tanggal Diajukan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                // Kolom sisa revisi untuk tab Menunggu
                TextColumn::make('revision_count')
                    ->label('Revisi')
                    ->formatStateUsing(function ($state) {
                        $count = $state ?? 0;
                        if ($count === 0) return 'Baru';
                        return $count . '/2';
                    })
                    ->badge()
                    ->color(fn ($state) => ($state ?? 0) >= 2 ? 'danger' : 'warning')
                    ->visible(fn () => !$isPendingTab && !$isSelesaiTab),
            ])
            ->defaultSort('status_changed_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->actions([
                // Tombol Detail — tersedia di semua tab
                Action::make('detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ServiceRequest $record): string => DetailPermohonan::getUrlForRecord($record, 'TAMBAH_DAYA')),

                // Tombol Verifikasi Data — hanya tab Menunggu
                Action::make('verifikasi_data')
                    ->label('Verifikasi Data')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ServiceRequest $record) =>
                        $this->activeTab === 'menunggu' &&
                        $record->status_detail === PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA)
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi Data Permohonan')
                    ->modalDescription('Yakin akan memverifikasi data permohonan ini? Data akan dikonversi dari No. Draft ke No. Resmi dan status berubah menjadi "Administrasi Selesai".')
                    ->modalSubmitActionLabel('Ya, Verifikasi')
                    ->action(function (ServiceRequest $record) {
                        try {
                            $record->adminAccept();
                            Notification::make()
                                ->title('Permohonan berhasil diverifikasi. No. Resmi: ' . $record->nomor_permohonan)
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Tombol Kembalikan ke Pelanggan — hanya tab Menunggu, revisi < 2
                Action::make('kembalikan_pelanggan')
                    ->label(fn (ServiceRequest $record) => 'Kembalikan ke Pelanggan (Revisi ' . ($record->revision_count ?? 0) . '/2)')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (ServiceRequest $record) =>
                        $this->activeTab === 'menunggu' &&
                        $record->status_detail === PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA &&
                        ($record->revision_count ?? 0) < 2)
                    ->form([
                        Textarea::make('revision_note')
                            ->label('Catatan Perbaikan')
                            ->required()
                            ->helperText(fn (ServiceRequest $record) =>
                                'Sisa kesempatan revisi: ' . (2 - ($record->revision_count ?? 0)) . '/2')
                            ->placeholder('Upload KTP yang lebih jelas')
                            ->rows(4),
                    ])
                    ->modalHeading('Kembalikan ke Pelanggan')
                    ->modalDescription(fn (ServiceRequest $record) =>
                        'Berikan catatan perbaikan untuk pelanggan. Revisi ke-' . ($record->revision_count ?? 0) . '/2')
                    ->modalSubmitActionLabel('Kirim')
                    ->action(function (ServiceRequest $record, array $data) {
                        try {
                            $record->adminSendBack($data['revision_note']);
                            Notification::make()
                                ->title('Permohonan dikembalikan ke pelanggan untuk perbaikan. (Revisi ' . ($record->revision_count ?? 0) . '/2)')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Tombol Tolak — hanya tab Menunggu, revisi >= 2
                Action::make('tolak')
                    ->label('Tolak Permohonan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ServiceRequest $record) =>
                        $this->activeTab === 'menunggu' &&
                        $record->status_detail === PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA &&
                        ($record->revision_count ?? 0) >= 2)
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Permohonan')
                    ->modalDescription(fn (ServiceRequest $record) =>
                        'Yakin akan menolak permohonan ini? Permohonan telah melampaui batas revisi (' . ($record->revision_count ?? 0) . '/2).')
                    ->modalSubmitActionLabel('Ya, Tolak')
                    ->action(function (ServiceRequest $record) {
                        try {
                            $note = 'Ditolak setelah revisi ke-' . ($record->revision_count ?? 0) . '/2.';
                            $record->adminReject($note);
                            Notification::make()
                                ->title($note)
                                ->danger()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    protected function getFilteredQuery(): Builder
    {
        $query = ServiceRequest::query()
            ->where('jenis_layanan', 'TAMBAH_DAYA')
            ->submitted()
            ->with(['applicant']);

        return match ($this->activeTab) {
            'menunggu' => $query->waitingForAdmin(),
            'pending'  => $query->pendingRevision(),
            'selesai'  => $query->where(function (Builder $q) {
                // Sukses + Gagal dalam satu tab, dipisah visual via badge
                $q->adminSuccess()
                  ->orWhere(function (Builder $q2) {
                      $q2->adminFailed();
                  });
            }),
            default => $query->waitingForAdmin(),
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