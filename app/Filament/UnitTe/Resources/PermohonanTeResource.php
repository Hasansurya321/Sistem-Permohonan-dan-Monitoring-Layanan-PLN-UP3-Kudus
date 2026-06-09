<?php

namespace App\Filament\UnitTe\Resources;

use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;
use App\Filament\UnitTe\Resources\PermohonanTeResource\Pages;
use App\Models\ServiceRequest;
use App\Support\WorkflowStatusHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PermohonanTeResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon  = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Permohonan Penyalaan';
    protected static ?string $modelLabel      = 'Permohonan Penyalaan TE';
    protected static ?int    $navigationSort  = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('submitted_at')
            ->where(function ($q) {
                $q->where('status', PermohonanStatus::UNIT_PENYALAAN)
                  // Include SELESAI with CLOSE (not yet closed) for admin closing
                  ->orWhere(function ($q2) {
                      $q2->where('status', PermohonanStatus::SELESAI)
                         ->whereIn('status_detail', [
                             PermohonanDetailStatus::PENYALAAN_BERHASIL->value,
                             PermohonanDetailStatus::PENYALAAN_SELESAI->value,
                         ]);
                  });
            });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nomor_permohonan')->disabled()->dehydrated(false),
            Forms\Components\TextInput::make('jenis_layanan')->disabled()->dehydrated(false),
            Forms\Components\Placeholder::make('status_label')
                ->label('Status')
                ->content(fn ($record) => $record?->status?->getLabel() ?? '-'),
            Forms\Components\Placeholder::make('detail_label')
                ->label('Detail Status')
                ->content(fn ($record) => $record?->status_detail?->getLabel() ?? '-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->modifyQueryUsing(fn ($query) => $query->with(['applicant']))
            ->columns([
                Tables\Columns\TextColumn::make('nomor_permohonan')
                    ->label('No. Permohonan')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('applicant.nama_lengkap')
                    ->label('Pemohon')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('jenis_layanan')
                    ->label('Layanan')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('status_detail')
                    ->label('Detail Status')
                    ->badge()
                    ->color(fn ($state) => $state instanceof PermohonanDetailStatus
                        ? WorkflowStatusHelper::filamentDetailColor($state)
                        : 'gray'),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Tgl Masuk')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->since(),
                Tables\Columns\TextColumn::make('status_changed_at')
                    ->label('Tgl Update')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('submitted_at', 'asc')
            ->emptyStateIcon('heroicon-o-bolt')
            ->emptyStateHeading('Tidak ada permohonan penyalaan')
            ->emptyStateDescription('Permohonan di tahap Unit Penyalaan (TE) akan muncul di sini.')
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),

                // ── Penyalaan Berhasil ───────────────────────────────────────
                Tables\Actions\Action::make('penyalaanBerhasil')
                    ->label('Penyalaan Berhasil')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan Penyalaan')
                            ->placeholder('Misal: Penyalaan berhasil, daya terukur normal...')
                            ->rows(3),
                    ])
                    ->action(function (ServiceRequest $record, array $data) {
                        $record->transitionTo(
                            PermohonanStatus::UNIT_PENYALAAN,
                            PermohonanDetailStatus::PENYALAAN_BERHASIL,
                            now(),
                            $data['note'] ?? null
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Penyalaan Berhasil')
                            ->body('Permohonan ' . $record->nomor_permohonan . ': penyalaan telah berhasil dilakukan.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ServiceRequest $record) =>
                        $record->status === PermohonanStatus::UNIT_PENYALAAN
                        && $record->status_detail !== PermohonanDetailStatus::PENYALAAN_BERHASIL
                        && $record->status_detail !== PermohonanDetailStatus::PENYALAAN_DIJADWALKAN
                    ),

                // ── Konfirmasi Nyala → SELESAI ───────────────────────────────
                Tables\Actions\Action::make('konfirmasiSelesai')
                    ->label('Konfirmasi Nyala — Selesaikan')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Permohonan Selesai?')
                    ->modalDescription('Permohonan akan ditandai SELESAI. Pelanggan akan mendapat notifikasi.')
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan Akhir')
                            ->placeholder('Ringkasan pekerjaan dan konfirmasi nyala...')
                            ->rows(3),
                    ])
                    ->action(function (ServiceRequest $record, array $data) {
                        $note = $data['note'] ?? null;

                        // Confirm power-on
                        $record->transitionTo(
                            PermohonanStatus::UNIT_PENYALAAN,
                            PermohonanDetailStatus::PENYALAAN_DIJADWALKAN,
                            now(),
                            $note
                        );

                        // Finalize
                        $record->transitionTo(
                            PermohonanStatus::SELESAI,
                            PermohonanDetailStatus::CLOSE,
                            now()->addSecond(),
                            'Permohonan selesai. Sambungan daya aktif.'
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Permohonan Selesai!')
                            ->body('Permohonan ' . $record->nomor_permohonan . ' telah selesai. Pelanggan telah dinotifikasi.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ServiceRequest $record) =>
                        $record->status === PermohonanStatus::UNIT_PENYALAAN
                        && $record->status_detail === PermohonanDetailStatus::PENYALAAN_BERHASIL
                    ),

                // ── Administrasi Akhir (post-SELESAI) ───────────────────────
                Tables\Actions\Action::make('administrasiAkhir')
                    ->label('Administrasi Akhir')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('gray')
                    ->tooltip('Catat proses administrasi akhir setelah permohonan selesai')
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Administrasi Akhir?')
                    ->modalDescription('Proses administrasi akhir akan dicatat. Berkas dapat di-close setelahnya.')
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan Administrasi')
                            ->placeholder('Misal: Surat penyerahan ditandatangani, berkas diarsipkan...')
                            ->rows(3),
                    ])
                    ->action(function (ServiceRequest $record, array $data) {
                        $record->transitionTo(
                            PermohonanStatus::SELESAI,
                            PermohonanDetailStatus::CLOSE,
                            now(),
                            $data['note'] ?? 'Proses administrasi akhir selesai.'
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Administrasi Akhir Dicatat')
                            ->body('Permohonan ' . $record->nomor_permohonan . ': administrasi akhir selesai.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ServiceRequest $record) =>
                        $record->status === PermohonanStatus::SELESAI
                        && $record->status_detail === PermohonanDetailStatus::CLOSE
                    ),

                // ── Close Berkas ─────────────────────────────────────────────
                Tables\Actions\Action::make('closeBerkas')
                    ->label('Close Berkas')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->tooltip('Tutup berkas permohonan secara permanen')
                    ->requiresConfirmation()
                    ->modalHeading('Close Berkas Permohonan?')
                    ->modalDescription('Berkas akan ditutup secara permanen. Tidak ada lagi perubahan status yang diizinkan.')
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan Penutupan')
                            ->placeholder('Misal: Berkas diarsipkan di lemari dokumen PLN UP3 Kudus...')
                            ->rows(3),
                    ])
                    ->action(function (ServiceRequest $record, array $data) {
                        $record->transitionTo(
                            PermohonanStatus::SELESAI,
                            PermohonanDetailStatus::CLOSE,
                            now(),
                            $data['note'] ?? 'Berkas ditutup.'
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Berkas Ditutup')
                            ->body('Permohonan ' . $record->nomor_permohonan . ' telah di-close.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ServiceRequest $record) =>
                        $record->status === PermohonanStatus::SELESAI
                        && $record->status_detail === PermohonanDetailStatus::CLOSE
                    ),

                // ── Batalkan ────────────────────────────────────────────────
                Tables\Actions\Action::make('batalkan')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (ServiceRequest $record, array $data) {
                        $record->transitionTo(
                            PermohonanStatus::SELESAI,
                            null,
                            now(),
                            $data['note']
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Permohonan Dibatalkan')
                            ->body('Permohonan ' . $record->nomor_permohonan . ' telah dibatalkan.')
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermohonanTes::route('/'),
            'view'  => Pages\ViewPermohonanTe::route('/{record}'),
        ];
    }
}