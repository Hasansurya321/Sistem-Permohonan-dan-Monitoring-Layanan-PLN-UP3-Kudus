<?php

namespace App\Filament\UnitPerencanaan\Resources;

use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;
use App\Filament\UnitPerencanaan\Resources\PermohonanPerencanaanResource\Pages;
use App\Models\ServiceRequest;
use App\Support\WorkflowStatusHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PermohonanPerencanaanResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Permohonan Perencanaan';
    protected static ?string $modelLabel      = 'Permohonan Perencanaan';
    protected static ?int    $navigationSort  = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', PermohonanStatus::UNIT_PERENCANAAN)
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
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading('Tidak ada permohonan perencanaan')
            ->emptyStateDescription('Permohonan di tahap Unit Perencanaan akan muncul di sini.')
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),

                // ── Material Menunggu ────────────────────────────────────────
                Tables\Actions\Action::make('materialMenunggu')
                    ->label('Material Menunggu')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Keterangan')
                            ->placeholder('Misal: Material masih dalam pengiriman dari gudang...')
                            ->rows(3),
                    ])
                    ->action(function (ServiceRequest $record, array $data) {
                        $record->transitionTo(
                            PermohonanStatus::UNIT_PERENCANAAN,
                            PermohonanDetailStatus::MATERIAL_MENUNGGU,
                            now(),
                            $data['note'] ?? null
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Material Menunggu')
                            ->body('Status diperbarui: material dalam antrean.')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (ServiceRequest $record) =>
                        $record->status === PermohonanStatus::UNIT_PERENCANAAN
                        && $record->status_detail === PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL
                    ),

                // ── Material Tersedia → Terbitkan Tagihan ───────────────────
                Tables\Actions\Action::make('materialTersedia')
                    ->label('Material Tersedia — Terbitkan Tagihan')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Terbitkan Tagihan Pembayaran?')
                    ->modalDescription('Permohonan akan beralih ke tahap Pembayaran dan tagihan dikirim ke pelanggan.')
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan')
                            ->placeholder('Rincian material atau tagihan...')
                            ->rows(3),
                    ])
                    ->action(function (ServiceRequest $record, array $data) {
                        $note = $data['note'] ?? null;

                        // Mark material ready
                        $record->transitionTo(
                            PermohonanStatus::UNIT_PERENCANAAN,
                            PermohonanDetailStatus::MATERIAL_TERSEDIA,
                            now(),
                            $note
                        );

                        // Advance to payment
                        $record->transitionTo(
                            PermohonanStatus::PEMBAYARAN,
                            PermohonanDetailStatus::TAGIHAN_TERBIT,
                            now()->addSecond(),
                            'Tagihan diterbitkan. Pelanggan diminta melakukan pembayaran.'
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Tagihan Diterbitkan')
                            ->body('Permohonan ' . $record->nomor_permohonan . ' menunggu pembayaran dari pelanggan.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ServiceRequest $record) =>
                        $record->status === PermohonanStatus::UNIT_PERENCANAAN
                        && in_array($record->status_detail, [
                            PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL,
                            PermohonanDetailStatus::MATERIAL_MENUNGGU,
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
            'index' => Pages\ListPermohonanPerencanaans::route('/'),
            'view'  => Pages\ViewPermohonanPerencanaan::route('/{record}'),
        ];
    }
}