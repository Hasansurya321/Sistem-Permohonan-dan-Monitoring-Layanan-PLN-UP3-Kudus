<?php

namespace App\Filament\AdminPelayanan\Resources;

use App\Filament\AdminPelayanan\Resources\PermohonanLayananResource\Pages;
use App\Models\ServiceRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;

class PermohonanLayananResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Permohonan Layanan';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Dasar')
                ->schema([
                    Forms\Components\TextInput::make('nomor_permohonan')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('jenis_layanan')->disabled()->dehydrated(false),
                    Forms\Components\Placeholder::make('status_label')
                        ->label('Status Saat Ini')
                        ->content(fn ($record) => $record?->status?->getLabel() ?? '-'),
                    Forms\Components\Placeholder::make('status_detail_label')
                        ->label('Status Detail')
                        ->content(fn ($record) => $record?->status_detail?->getLabel() ?? '-'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('nomor_permohonan')
                    ->label('No Permohonan')
                    ->searchable()
                    ->copyable()
                    ->placeholder('DRAFT'),

                Tables\Columns\TextColumn::make('applicant.nama_lengkap')
                    ->label('Pemohon')
                    ->searchable(),

                Tables\Columns\TextColumn::make('jenis_layanan')
                    ->label('Layanan')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Tgl Submit')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Tgl Selesai')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                // STRICT: hanya verifikasi sekarang (hapus View)
                Tables\Actions\Action::make('verifikasiSekarang')
                    ->label('Verifikasi sekarang')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ServiceRequest $record) {
                        $record->transitionTo(
                            PermohonanStatus::VERIFIKASI_SLO,
                            PermohonanDetailStatus::MENUNGGU_VERIFIKASI
                        );

                        return redirect(
                            VerifikasiSloResource::getUrl('view', ['record' => $record->getKey()])
                        );
                    })
                    ->visible(fn (ServiceRequest $record) =>
                        !$record->is_draft
                        && $record->status === PermohonanStatus::DITERIMA_PLN
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermohonanLayanans::route('/'),
            'view' => Pages\ViewPermohonanLayanan::route('/{record}'),
        ];
    }
}
