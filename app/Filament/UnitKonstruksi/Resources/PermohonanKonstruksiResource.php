<?php

namespace App\Filament\UnitKonstruksi\Resources;

use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;
use App\Filament\UnitKonstruksi\Resources\PermohonanKonstruksiResource\Pages;
use App\Models\ServiceRequest;
use App\Support\WorkflowStatusHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PermohonanKonstruksiResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon  = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Permohonan Konstruksi';
    protected static ?string $modelLabel      = 'Permohonan Konstruksi';
    protected static ?int    $navigationSort  = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', PermohonanStatus::UNIT_KONSTRUKSI)
            ->whereNotNull('submitted_at');
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
            ->emptyStateIcon('heroicon-o-wrench-screwdriver')
            ->emptyStateHeading('Tidak ada permohonan konstruksi')
            ->emptyStateDescription('Permohonan di tahap Unit Konstruksi akan muncul di sini.')
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),

                // ── Progress Konstruksi ──────────────────────────────────────
                Tables\Actions\Action::make('progressKonstruksi')
                    ->label('Update: Konstruksi Progress')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan Progress')
                            ->placeholder('Misal: Pengerjaan jaringan tiang listrik sudah 70%...')
                            ->rows(3),
                    ])
                    ->action(function (ServiceRequest $record, array $data) {
                        $record->transitionTo(
                            PermohonanStatus::UNIT_KONSTRUKSI,
                            PermohonanDetailStatus::KONSTRUKSI_DIJADWALKAN,
                            now(),
                            $data['note'] ?? null
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Progress Diperbarui')
                            ->body('Permohonan ' . $record->nomor_permohonan . ' sedang dalam progress konstruksi.')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (ServiceRequest $record) =>
                        $record->status === PermohonanStatus::UNIT_KONSTRUKSI
                        && $record->status_detail === PermohonanDetailStatus::PEMBANGUNAN_JARINGAN
                    ),

                // ── Instalasi Selesai → Teruskan ke Unit TE ─────────────────
                Tables\Actions\Action::make('instalasiSelesai')
                    ->label('Instalasi Selesai — Teruskan ke TE')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Instalasi Selesai?')
                    ->modalDescription('Permohonan akan diteruskan ke Unit Teknik Elektrik (TE) untuk proses penyalaan.')
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan Hasil Instalasi')
                            ->placeholder('Ringkasan pekerjaan konstruksi dan instalasi...')
                            ->rows(3),
                    ])
                    ->action(function (ServiceRequest $record, array $data) {
                        $note = $data['note'] ?? null;

                        // Mark instalasi done
                        $record->transitionTo(
                            PermohonanStatus::UNIT_KONSTRUKSI,
                            PermohonanDetailStatus::KONSTRUKSI_BERHASIL,
                            now(),
                            $note
                        );

                        // Advance to penyalaan TE
                        $record->transitionTo(
                            PermohonanStatus::UNIT_PENYALAAN,
                            null,
                            now()->addSecond(),
                            'Diteruskan ke Unit TE untuk penyalaan.'
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Diteruskan ke Unit TE')
                            ->body('Permohonan ' . $record->nomor_permohonan . ' diteruskan ke Unit Teknik Elektrik.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ServiceRequest $record) =>
                        $record->status === PermohonanStatus::UNIT_KONSTRUKSI
                        && in_array($record->status_detail, [
                            PermohonanDetailStatus::PEMBANGUNAN_JARINGAN,
                            PermohonanDetailStatus::KONSTRUKSI_DIJADWALKAN,
                        ], true)
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
            'index' => Pages\ListPermohonanKonstruksis::route('/'),
            'view'  => Pages\ViewPermohonanKonstruksi::route('/{record}'),
        ];
    }
}