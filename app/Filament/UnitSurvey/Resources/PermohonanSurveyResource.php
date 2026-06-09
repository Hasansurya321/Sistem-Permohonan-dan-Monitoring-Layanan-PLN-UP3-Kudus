<?php

namespace App\Filament\UnitSurvey\Resources;

use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;
use App\Filament\UnitSurvey\Resources\PermohonanSurveyResource\Pages;
use App\Models\ServiceRequest;
use App\Support\WorkflowStatusHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PermohonanSurveyResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon  = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Permohonan Survey';
    protected static ?string $modelLabel      = 'Permohonan Survey';
    protected static ?int    $navigationSort  = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', PermohonanStatus::UNIT_SURVEY)
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
                    ->label('Status Detail')
                    ->badge()
                    ->color(fn ($state) => $state instanceof PermohonanDetailStatus
                        ? WorkflowStatusHelper::filamentDetailColor($state)
                        : 'gray'),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Tgl Masuk')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('submitted_at', 'asc')
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateHeading('Tidak ada permohonan survey')
            ->emptyStateDescription('Permohonan yang masuk ke tahap Unit Survey akan muncul di sini.')
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),

                // ── Jadwalkan Survey ────────────────────────────────────────
                Tables\Actions\Action::make('jadwalkanSurvey')
                    ->label('Jadwalkan Survey')
                    ->icon('heroicon-o-calendar')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan Jadwal / Keterangan')
                            ->placeholder('Misal: Jadwal survey Senin 26 Mei 2025 pukul 09.00 WIB')
                            ->rows(3),
                    ])
                    ->action(function (ServiceRequest $record, array $data) {
                        $record->transitionTo(
                            PermohonanStatus::UNIT_SURVEY,
                            PermohonanDetailStatus::SURVEY_DIJADWALKAN,
                            now(),
                            $data['note'] ?? null
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Survey Dijadwalkan')
                            ->body('Permohonan ' . $record->nomor_permohonan . ' telah dijadwalkan untuk survey lapangan.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ServiceRequest $record) =>
                        $record->status === PermohonanStatus::UNIT_SURVEY
                        && $record->status_detail === PermohonanDetailStatus::DITERIMA_UNIT_SURVEY
                    ),

                // ── Survey Selesai → Teruskan ke Perencanaan ───────────────
                Tables\Actions\Action::make('surveySelesai')
                    ->label('Survey Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Survey Selesai?')
                    ->modalDescription('Permohonan akan diteruskan ke Unit Perencanaan Material.')
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan Hasil Survey')
                            ->placeholder('Ringkasan hasil survey lapangan...')
                            ->rows(3),
                    ])
                    ->action(function (ServiceRequest $record, array $data) {
                        $note = $data['note'] ?? null;

                        // Mark survey as done
                        $record->transitionTo(
                            PermohonanStatus::UNIT_SURVEY,
                            PermohonanDetailStatus::SURVEY_SELESAI,
                            now(),
                            $note
                        );

                        // Advance to planning
                        $record->transitionTo(
                            PermohonanStatus::UNIT_PERENCANAAN,
                            PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL,
                            now()->addSecond(),
                            'Diteruskan ke Unit Perencanaan Material.'
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Survey Selesai')
                            ->body('Permohonan ' . $record->nomor_permohonan . ' diteruskan ke Unit Perencanaan Material.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ServiceRequest $record) =>
                        $record->status === PermohonanStatus::UNIT_SURVEY
                        && $record->status_detail === PermohonanDetailStatus::SURVEY_DIJADWALKAN
                    ),

                // ── Batalkan ────────────────────────────────────────────────
                Tables\Actions\Action::make('batalkan')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Permohonan?')
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
            'index' => Pages\ListPermohonanSurveys::route('/'),
            'view'  => Pages\ViewPermohonanSurvey::route('/{record}'),
        ];
    }
}